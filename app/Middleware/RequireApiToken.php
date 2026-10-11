<?php

namespace App\Middleware;

use App\Models\ApiToken;
use App\Core\Auth;

class RequireApiToken
{
    public function before()
    {
        $authHeader = \Flight::request()->getHeader('Authorization') ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? null);

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            self::reject('Unauthorized: Missing or invalid token');
            return;
        }

        $token = $matches[1];
        $hashedToken = hash('sha256', $token);

        $apiToken = ApiToken::with('user')->where('token', $hashedToken)->first();

        if (!$apiToken || !$apiToken->user) {
            self::reject('Unauthorized: Invalid token');
            return;
        }

        if ($apiToken->isExpired()) {
            self::reject('Unauthorized: Token expired');
            return;
        }

        // Throttle last_used_at writes to at most once per minute so API
        // traffic does not hammer the database.
        if ($apiToken->last_used_at === null || $apiToken->last_used_at->diffInSeconds(\Illuminate\Support\Carbon::now()) >= 60) {
            $apiToken->last_used_at = \Illuminate\Support\Carbon::now();
            $apiToken->save();
        }

        Auth::setStatelessUser($apiToken->user);
    }

    /**
     * Send the JSON error and stop. Under PHPUnit the response is written
     * without exiting so tests can assert on it.
     */
    private static function reject(string $message): void
    {
        \Flight::response()->status(401);
        \Flight::json(['error' => $message], 401);
        if (empty(getenv('PHPUNIT_TEST'))) {
            exit;
        }
    }
}
