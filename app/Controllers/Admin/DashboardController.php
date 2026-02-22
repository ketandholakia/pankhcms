<?php

namespace App\Controllers\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class DashboardController
{
    public static function index()
    {
        $stats = self::buildStats();
        echo \Flight::get("blade")->render("admin.dashboard", compact('stats'));
    }

    private static function buildStats(): array
    {
        $now = new \DateTimeImmutable('now');
        $weekStart = $now->sub(new \DateInterval('P7D'))->format('Y-m-d H:i:s');
        $monthStart = $now->sub(new \DateInterval('P30D'))->format('Y-m-d H:i:s');

        $hasPages = self::hasTable('pages');
        $hasUsers = self::hasTable('users');
        $hasLogs = self::hasTable('logs');
        $hasRoles = self::hasTable('roles') && self::hasTable('user_roles');

        $contentTotal = $hasPages ? (int) Page::count() : 0;
        $publishedCount = self::countPagesByStatus('published');
        $draftCount = self::countPagesByStatus('draft');
        $pendingCount = self::countPagesByStatus('pending');
        $scheduledCount = self::countPagesByStatus('scheduled');

        $views = self::queryFirstAvailableInt([
            "SELECT COUNT(*) AS value FROM page_views",
            "SELECT COALESCE(SUM(view_count), 0) AS value FROM pages",
            "SELECT COALESCE(SUM(views), 0) AS value FROM pages",
            "SELECT COALESCE(SUM(total_views), 0) AS value FROM pages",
        ]);

        $comments = self::queryFirstAvailableInt([
            "SELECT COUNT(*) AS value FROM comments",
        ]);

        $newContentWeek = $hasPages && self::hasColumn('pages', 'created_at')
            ? (int) DB::table('pages')->where('created_at', '>=', $weekStart)->count()
            : null;

        $mostViewed = self::queryFirstAvailableRow([
            "SELECT p.title, p.slug, COUNT(v.id) AS metric FROM page_views v INNER JOIN pages p ON p.id = v.page_id GROUP BY p.id, p.title, p.slug ORDER BY metric DESC LIMIT 1",
            "SELECT title, slug, COALESCE(view_count, 0) AS metric FROM pages ORDER BY view_count DESC LIMIT 1",
            "SELECT title, slug, COALESCE(views, 0) AS metric FROM pages ORDER BY views DESC LIMIT 1",
            "SELECT title, slug, COALESCE(total_views, 0) AS metric FROM pages ORDER BY total_views DESC LIMIT 1",
        ]);

        $recentlyUpdated = $hasPages && self::hasColumn('pages', 'updated_at')
            ? DB::table('pages')->select(['id', 'title', 'slug', 'updated_at'])->orderByDesc('updated_at')->limit(5)->get()->toArray()
            : [];

        $expiringContent = self::queryFirstAvailableInt([
            "SELECT COUNT(*) AS value FROM pages WHERE expires_at IS NOT NULL AND expires_at <= datetime('now', '+7 day')",
            "SELECT COUNT(*) AS value FROM pages WHERE expiry_date IS NOT NULL AND expiry_date <= datetime('now', '+7 day')",
        ]);

        $newUsersMonth = $hasUsers && self::hasColumn('users', 'created_at')
            ? (int) DB::table('users')->where('created_at', '>=', $monthStart)->count()
            : null;

        $activeUsers = self::activeUsersCount($monthStart);
        $lastLoginAt = self::lastLoginAt();
        $failedLogins = self::failedLoginAttempts();

        $adminCount = null;
        $editorCount = null;
        if ($hasRoles) {
            $adminCount = self::countUsersByRole('admin');
            $editorCount = self::countUsersByRole('editor');
        }

        $todayVisits = self::queryFirstAvailableInt([
            "SELECT COUNT(*) AS value FROM visits WHERE date(created_at) = date('now')",
            "SELECT COUNT(*) AS value FROM page_views WHERE date(created_at) = date('now')",
            "SELECT COUNT(*) AS value FROM analytics_visits WHERE date(created_at) = date('now')",
        ]);

        $weeklyVisits = self::queryWeeklyVisits();

        $topPages = self::queryFirstAvailableRows([
            "SELECT p.title, p.slug, COUNT(v.id) AS metric FROM page_views v INNER JOIN pages p ON p.id = v.page_id GROUP BY p.id, p.title, p.slug ORDER BY metric DESC LIMIT 5",
            "SELECT title, slug, COALESCE(view_count, 0) AS metric FROM pages ORDER BY view_count DESC LIMIT 5",
            "SELECT title, slug, COALESCE(views, 0) AS metric FROM pages ORDER BY views DESC LIMIT 5",
            "SELECT title, slug, COALESCE(total_views, 0) AS metric FROM pages ORDER BY total_views DESC LIMIT 5",
        ]);

        $trafficSources = self::queryFirstAvailableRows([
            "SELECT source, COUNT(*) AS metric FROM page_views GROUP BY source ORDER BY metric DESC LIMIT 5",
            "SELECT source, COUNT(*) AS metric FROM visits GROUP BY source ORDER BY metric DESC LIMIT 5",
            "SELECT source, COUNT(*) AS metric FROM analytics_visits GROUP BY source ORDER BY metric DESC LIMIT 5",
        ]);

        $bounceRate = self::queryFirstAvailableFloat([
            "SELECT AVG(CASE WHEN is_bounce = 1 THEN 1.0 ELSE 0.0 END) * 100 AS value FROM visits",
            "SELECT AVG(bounce_rate) AS value FROM analytics_daily",
        ]);

        $queueStatus = self::queueStatus();

        return [
            'summary' => [
                'total_content' => $contentTotal,
                'published_posts' => $publishedCount,
                'draft_pending' => $draftCount + $pendingCount,
                'total_views' => $views,
                'total_users' => $hasUsers ? (int) User::count() : 0,
                'comments_count' => $comments,
            ],
            'content_management' => [
                'new_content_week' => $newContentWeek,
                'most_viewed' => $mostViewed,
                'recently_updated' => $recentlyUpdated,
                'scheduled_posts' => $scheduledCount,
                'expiring_content' => $expiringContent,
            ],
            'user_access' => [
                'new_users_month' => $newUsersMonth,
                'active_users' => $activeUsers,
                'admin_count' => $adminCount,
                'editor_count' => $editorCount,
                'last_login_activity' => $lastLoginAt,
                'failed_login_attempts' => $failedLogins,
            ],
            'traffic' => [
                'today_visits' => $todayVisits,
                'weekly_visits' => $weeklyVisits,
                'top_pages' => $topPages,
                'traffic_sources' => $trafficSources,
                'bounce_rate' => $bounceRate,
            ],
            'system_health' => [
                'php_version' => PHP_VERSION,
                'database_size' => self::databaseSizeBytes(),
                'disk_usage' => self::cmsDiskUsageBytes(),
                'cache_status' => self::cacheStatus(),
                'queue_status' => $queueStatus,
                'backup_status' => self::backupStatus(),
                'cron_status' => self::cronStatus(),
            ],
        ];
    }

    private static function hasTable(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function hasColumn(string $table, string $column): bool
    {
        try {
            return DB::schema()->hasTable($table) && DB::schema()->hasColumn($table, $column);
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function countPagesByStatus(string $status): int
    {
        try {
            if (!self::hasColumn('pages', 'status')) {
                return 0;
            }

            return (int) DB::table('pages')->where('status', $status)->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function countUsersByRole(string $roleName): ?int
    {
        try {
            return (int) DB::table('user_roles')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->whereRaw('LOWER(roles.name) = ?', [strtolower($roleName)])
                ->distinct('user_roles.user_id')
                ->count('user_roles.user_id');
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function activeUsersCount(string $monthStart): ?int
    {
        try {
            if (self::hasColumn('users', 'last_login_at')) {
                return (int) DB::table('users')->where('last_login_at', '>=', $monthStart)->count();
            }

            if (self::hasTable('logs') && self::hasColumn('logs', 'action') && self::hasColumn('logs', 'created_at')) {
                return (int) DB::table('logs')
                    ->where('created_at', '>=', $monthStart)
                    ->where('action', 'like', '%login%')
                    ->distinct('user_id')
                    ->count('user_id');
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    private static function lastLoginAt(): ?string
    {
        try {
            if (self::hasColumn('users', 'last_login_at')) {
                $value = DB::table('users')->max('last_login_at');
                return $value ? (string) $value : null;
            }

            if (self::hasTable('logs') && self::hasColumn('logs', 'action') && self::hasColumn('logs', 'created_at')) {
                $value = DB::table('logs')->where('action', 'like', '%login%')->max('created_at');
                return $value ? (string) $value : null;
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    private static function failedLoginAttempts(): ?int
    {
        $dir = dirname(__DIR__, 3) . '/storage/cache/login_throttle';
        if (!is_dir($dir)) {
            return 0;
        }

        $files = glob($dir . '/*.json');
        if ($files === false) {
            return null;
        }

        $total = 0;
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            $total += (int) ($decoded['count'] ?? 0);
        }

        return $total;
    }

    private static function queryWeeklyVisits(): array
    {
        $queries = [
            "SELECT date(created_at) AS day, COUNT(*) AS visits FROM visits WHERE created_at >= datetime('now', '-6 day') GROUP BY day ORDER BY day ASC",
            "SELECT date(created_at) AS day, COUNT(*) AS visits FROM page_views WHERE created_at >= datetime('now', '-6 day') GROUP BY day ORDER BY day ASC",
            "SELECT date(created_at) AS day, COUNT(*) AS visits FROM analytics_visits WHERE created_at >= datetime('now', '-6 day') GROUP BY day ORDER BY day ASC",
        ];

        $rows = self::queryFirstAvailableRows($queries);
        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'day' => (string) ($row->day ?? ''),
                'visits' => (int) ($row->visits ?? 0),
            ];
        }

        return $result;
    }

    private static function queryFirstAvailableInt(array $queries): ?int
    {
        foreach ($queries as $sql) {
            try {
                $row = DB::selectOne($sql);
                if ($row !== null && isset($row->value)) {
                    return (int) $row->value;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private static function queryFirstAvailableFloat(array $queries): ?float
    {
        foreach ($queries as $sql) {
            try {
                $row = DB::selectOne($sql);
                if ($row !== null && isset($row->value)) {
                    return is_numeric($row->value) ? (float) $row->value : null;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private static function queryFirstAvailableRow(array $queries): ?object
    {
        foreach ($queries as $sql) {
            try {
                $row = DB::selectOne($sql);
                if ($row !== null) {
                    return $row;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private static function queryFirstAvailableRows(array $queries): array
    {
        foreach ($queries as $sql) {
            try {
                $rows = DB::select($sql);
                if (is_array($rows)) {
                    return $rows;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return [];
    }

    private static function queueStatus(): array
    {
        if (!self::hasTable('jobs')) {
            return ['enabled' => false, 'pending' => null];
        }

        try {
            return ['enabled' => true, 'pending' => (int) DB::table('jobs')->count()];
        } catch (Throwable $e) {
            return ['enabled' => true, 'pending' => null];
        }
    }

    private static function cacheStatus(): array
    {
        $cacheDir = dirname(__DIR__, 3) . '/storage/cache';
        if (!is_dir($cacheDir)) {
            return ['enabled' => false, 'writable' => false, 'files' => 0];
        }

        $files = glob($cacheDir . '/*');
        return [
            'enabled' => true,
            'writable' => is_writable($cacheDir),
            'files' => is_array($files) ? count($files) : 0,
        ];
    }

    private static function backupStatus(): array
    {
        $backupDir = dirname(__DIR__, 3) . '/storage/backups';
        if (!is_dir($backupDir)) {
            return ['enabled' => false, 'count' => 0, 'latest' => null];
        }

        $files = glob($backupDir . '/*.zip');
        if (!is_array($files)) {
            return ['enabled' => true, 'count' => 0, 'latest' => null];
        }

        $latest = null;
        $latestTs = 0;
        foreach ($files as $file) {
            $mtime = @filemtime($file);
            if ($mtime !== false && $mtime > $latestTs) {
                $latestTs = $mtime;
                $latest = date('Y-m-d H:i:s', $mtime);
            }
        }

        return ['enabled' => true, 'count' => count($files), 'latest' => $latest];
    }

    private static function cronStatus(): array
    {
        $lastRun = setting('cron_last_run', null);
        return [
            'configured' => $lastRun !== null,
            'last_run' => $lastRun,
        ];
    }

    private static function cmsDiskUsageBytes(): ?int
    {
        $paths = [
            dirname(__DIR__, 3) . '/storage',
            dirname(__DIR__, 3) . '/public/uploads',
            dirname(__DIR__, 3) . '/themes',
        ];

        $size = 0;
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return $size;
    }

    private static function databaseSizeBytes(): ?int
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if ($driver === 'sqlite') {
                $dbPath = (string) $connection->getConfig('database');
                if ($dbPath !== '' && is_file($dbPath)) {
                    $size = @filesize($dbPath);
                    return $size === false ? null : (int) $size;
                }

                return null;
            }

            if ($driver === 'mysql') {
                $dbName = $connection->getDatabaseName();
                $row = DB::selectOne(
                    'SELECT COALESCE(SUM(data_length + index_length), 0) AS value FROM information_schema.tables WHERE table_schema = ?',
                    [$dbName]
                );

                return $row !== null && isset($row->value) ? (int) $row->value : null;
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }
}
