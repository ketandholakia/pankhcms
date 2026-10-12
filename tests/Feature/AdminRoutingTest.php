<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Middleware\AdminMiddleware;
use flight\net\Request;

/**
 * Deny-by-default admin routing: everything under /admin except login
 * and password reset requires an authenticated session; CSRF applies to
 * every non-safe /admin request; a public page slugged "administration"
 * is not treated as an admin path.
 */
class AdminRoutingTest extends TestCase
{
    private static bool $routesLoaded = false;

    private bool $handlersInstalled = false;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');

        // Register the production routes (and the CSRF filter) exactly as
        // Bootstrap does. loadRoutes is private; registering twice across
        // test classes is harmless — the router keeps the first match.
        if (!self::$routesLoaded) {
            $method = new \ReflectionMethod(Bootstrap::class, 'loadRoutes');
            $method->setAccessible(true);
            $method->invoke(null);
            self::$routesLoaded = true;
        }

        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        \Flight::response()->status(200)->clearBody();
    }

    protected function tearDown(): void
    {
        if ($this->handlersInstalled) {
            restore_error_handler();
            restore_exception_handler();
            $this->handlersInstalled = false;
        }
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_ACCEPT']);
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function routeFor(string $method, string $url)
    {
        $request = new Request();
        $request->url = $url;
        $request->method = $method;
        return \Flight::router()->route($request);
    }

    private function swapRequest(string $method, string $url): void
    {
        $request = new Request();
        $request->url = $url;
        $request->method = $method;

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);
    }

    private function runStartFilters(): void
    {
        $engine = \Flight::app();
        $dispatcherProp = (new \ReflectionClass($engine))->getProperty('dispatcher');
        $dispatcher = $dispatcherProp->getValue($engine);
        $filtersProp = (new \ReflectionClass($dispatcher))->getProperty('filters');
        $filters = $filtersProp->getValue($dispatcher);

        $params = [];
        $output = null;
        foreach ($filters['start']['before'] ?? [] as $filter) {
            \call_user_func_array($filter, [&$params, &$output]);
        }

        // Flight's init hook (one of the filters above) installs error and
        // exception handlers; tearDown removes them again so PHPUnit stays
        // clean.
        $this->handlersInstalled = true;
    }

    public function test_unauthenticated_request_to_admin_redirects_to_login()
    {
        unset($_SESSION['user_id']);

        $result = AdminMiddleware::handle();

        $this->assertFalse($result);
        $this->assertStringContainsString(
            '/admin/login',
            (string) (\Flight::response()->headers()['Location'] ?? ''),
            'unauthenticated /admin access must redirect to the login page'
        );
    }

    public function test_login_route_is_registered_outside_the_auth_guard()
    {
        $route = $this->routeFor('GET', '/admin/login');

        $this->assertNotFalse($route);
        $this->assertSame('/admin/login', $route->pattern);
        foreach ($route->middleware as $middleware) {
            $this->assertNotInstanceOf(
                AdminMiddleware::class,
                $middleware,
                'login must not be behind the auth guard'
            );
        }
    }

    public function test_admin_routes_carry_the_auth_middleware()
    {
        $route = $this->routeFor('GET', '/admin/users');

        $this->assertNotFalse($route);
        $found = false;
        foreach ($route->middleware as $middleware) {
            if ($middleware instanceof AdminMiddleware) {
                $found = true;
            }
        }
        $this->assertTrue($found, '/admin/users must be guarded by AdminMiddleware');
    }

    public function test_post_without_csrf_token_is_rejected_with_403()
    {
        $this->swapRequest('POST', '/admin/pages');
        unset($_POST['_csrf']);

        $this->runStartFilters();

        $this->assertSame(403, \Flight::response()->status());
        $this->assertStringContainsString('CSRF token mismatch', \Flight::response()->getBody());
    }

    public function test_administration_slug_is_not_an_admin_path()
    {
        // The CSRF filter must not fire for the public slug...
        $this->swapRequest('POST', '/administration');
        unset($_POST['_csrf']);

        $this->runStartFilters();
        $this->assertNotSame(403, \Flight::response()->status(), '"administration" must not be CSRF-gated');

        // ...and the request must route to the public page handler.
        $route = $this->routeFor('GET', '/administration');
        $this->assertNotFalse($route);
        $this->assertSame('/@slug', $route->pattern, '"administration" must resolve as a public page slug');
        foreach ($route->middleware as $middleware) {
            $this->assertNotInstanceOf(AdminMiddleware::class, $middleware);
        }
    }

    /**
     * Behavioral sweep over the live router: EVERY registered /admin route
     * except login and password reset must carry AdminMiddleware, and the
     * unguarded ones must be exactly login + password/*. Guards against a
     * new admin route being registered outside the Flight::group.
     */
    public function test_every_admin_route_is_behind_the_auth_guard()
    {
        $guarded = 0;
        $unguarded = [];

        foreach (\Flight::router()->getRoutes() as $route) {
            $pattern = (string) $route->pattern;
            if (!preg_match('#^/admin(/|$)#', $pattern)) {
                continue;
            }

            $hasGuard = false;
            foreach ($route->middleware as $middleware) {
                if ($middleware instanceof AdminMiddleware) {
                    $hasGuard = true;
                    break;
                }
            }

            $isAuthFree = preg_match('#^/admin/login#', $pattern) === 1
                || preg_match('#^/admin/password/#', $pattern) === 1;

            if ($isAuthFree) {
                $this->assertFalse(
                    $hasGuard,
                    "$pattern must stay outside the auth guard so login/reset keep working"
                );
                $unguarded[] = $pattern;
            } else {
                $this->assertTrue(
                    $hasGuard,
                    "no route may live under /admin without AdminMiddleware: {$route->methods[0]} $pattern"
                );
                $guarded++;
            }
        }

        // The sweep must actually have seen the admin area, not an empty
        // router (e.g. if routes were not loaded).
        $this->assertGreaterThanOrEqual(90, $guarded, 'the admin route map was not fully swept');
        $this->assertCount(6, $unguarded, 'exactly login (2) and password/* (4) may be unguarded');
    }

    /**
     * Every non-safe /admin request must be covered by the CSRF filter's
     * segment-exact regex — including the unauthenticated login and
     * password-reset POSTs.
     */
    public function test_every_admin_post_route_is_covered_by_the_csrf_filter()
    {
        foreach (\Flight::router()->getRoutes() as $route) {
            $pattern = (string) $route->pattern;
            if (!preg_match('#^/admin(/|$)#', $pattern)) {
                continue;
            }
            foreach ($route->methods as $method) {
                if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                    continue;
                }
                $this->assertSame(
                    1,
                    preg_match('#^/admin(/|$)#', $pattern),
                    "CSRF filter regex must cover $method $pattern"
                );
            }
        }
    }
}
