<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class VisitTracker
{
    private static bool $tableChecked = false;

    public static function track(?int $pageId, string $path): void
    {
        if (!self::ensureTable()) {
            return;
        }

        try {
            $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            $source = self::detectSource($referrer);
            $ip = client_ip();
            $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

            session_init();
            $sessionId = session_id();

            DB::table('page_views')->insert([
                'page_id' => $pageId,
                'path' => $path,
                'source' => $source,
                'referrer' => $referrer !== '' ? $referrer : null,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'session_id' => $sessionId !== '' ? $sessionId : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Keep public page rendering resilient.
        }
    }

    private static function ensureTable(): bool
    {
        if (self::$tableChecked) {
            return true;
        }

        try {
            $schema = DB::schema();

            if (!$schema->hasTable('page_views')) {
                $schema->create('page_views', function ($table) {
                    $table->bigIncrements('id');
                    $table->integer('page_id')->nullable();
                    $table->string('path')->nullable();
                    $table->string('source')->nullable();
                    $table->text('referrer')->nullable();
                    $table->string('ip', 45)->nullable();
                    $table->text('user_agent')->nullable();
                    $table->string('session_id')->nullable();
                    $table->dateTime('created_at')->nullable();
                });

                if ($schema->hasTable('pages')) {
                    try {
                        $schema->table('page_views', function ($table) {
                            $table->foreign('page_id')->references('id')->on('pages')->onDelete('set null');
                        });
                    } catch (Throwable $e) {
                        // FK is optional; keep tracker working across DB engines.
                    }
                }

                try {
                    DB::connection()->statement('CREATE INDEX IF NOT EXISTS idx_page_views_created_at ON page_views(created_at)');
                    DB::connection()->statement('CREATE INDEX IF NOT EXISTS idx_page_views_page_id ON page_views(page_id)');
                    DB::connection()->statement('CREATE INDEX IF NOT EXISTS idx_page_views_source ON page_views(source)');
                } catch (Throwable $e) {
                    // Index creation best effort.
                }
            }

            self::$tableChecked = true;
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function detectSource(string $referrer): string
    {
        if ($referrer === '') {
            return 'direct';
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (!$host) {
            return 'referral';
        }

        $host = strtolower((string) $host);

        if (str_contains($host, 'google.')) {
            return 'google';
        }
        if (str_contains($host, 'bing.')) {
            return 'bing';
        }
        if (str_contains($host, 'yahoo.')) {
            return 'yahoo';
        }
        if (str_contains($host, 'duckduckgo.')) {
            return 'duckduckgo';
        }
        if (str_contains($host, 'facebook.')) {
            return 'facebook';
        }
        if (str_contains($host, 'instagram.')) {
            return 'instagram';
        }
        if (str_contains($host, 'x.com') || str_contains($host, 'twitter.')) {
            return 'x/twitter';
        }
        if (str_contains($host, 'linkedin.')) {
            return 'linkedin';
        }

        return 'referral';
    }
}
