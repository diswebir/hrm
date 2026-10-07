<?php
/**
 * محافظ CSRF — توکن‌های یک‌بارمصرف فرم
 */
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function boot(): void
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        // نوسازی دوره‌ای توکن
        if (empty($_SESSION['_csrf_at']) || time() - (int)$_SESSION['_csrf_at'] > 3600) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
            $_SESSION['_csrf_at'] = time();
        }
    }

    public static function token(): string
    {
        return (string)($_SESSION['_csrf'] ?? '');
    }

    public static function verify($token): void
    {
        if (!is_string($token) || $token === '' || !hash_equals(self::token(), $token)) {
            http_response_code(419);
            echo '<meta charset="utf-8"><p style="font-family:tahoma;text-align:center;padding:40px">'
                . 'جلسه شما منقضی شده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش نمایید.</p>';
            exit;
        }
    }
}
