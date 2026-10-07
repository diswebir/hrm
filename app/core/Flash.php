<?php
/**
 * پیام‌های لحظه‌ای (Flash Messages)
 */
declare(strict_types=1);

namespace App\Core;

final class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $m): void { self::set('success', $m); }
    public static function error(string $m): void   { self::set('error', $m); }
    public static function warning(string $m): void { self::set('warning', $m); }
    public static function info(string $m): void    { self::set('info', $m); }

    /** دریافت و خالی کردن */
    public static function pull(): array
    {
        $all = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $all;
    }
}
