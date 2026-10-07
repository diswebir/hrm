<?php
/**
 * ثبت رویدادها (Activity Log / Audit Trail)
 */
declare(strict_types=1);

namespace App\Core;

final class Log
{
    public static function add(string $level, string $action, string $message = '', array $context = []): void
    {
        try {
            DB::insert('activity_log', [
                'user_id'    => Auth::id() ?: null,
                'level'      => $level,
                'action'     => substr($action, 0, 64),
                'message'    => mb_substr($message, 0, 1000),
                'context'    => $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // لاگ نباید مانع اجرای برنامه شود
            @error_log('[HRM log fail] ' . $e->getMessage());
        }
    }

    public static function info(string $action, string $message = '', array $ctx = []): void
    {
        self::add('info', $action, $message, $ctx);
    }

    public static function warning(string $action, string $message = '', array $ctx = []): void
    {
        self::add('warning', $action, $message, $ctx);
    }

    public static function error(string $action, string $message = '', array $ctx = []): void
    {
        self::add('error', $action, $message, $ctx);
    }
}
