<?php
/**
 * تنظیمات سامانه (جدول settings با کش)
 */
declare(strict_types=1);

namespace App\Core;

final class Settings
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function boot(): void
    {
        self::$loaded = false;
        self::$cache = [];
    }

    private static function load(): void
    {
        if (self::$loaded) return;
        self::$loaded = true;
        try {
            foreach (DB::all('SELECT k, v FROM settings') as $row) {
                self::$cache[$row['k']] = $row['v'];
            }
        } catch (\Throwable $e) {
            // دیتابیس هنوز آماده نیست
        }
    }

    /** همه تنظیمات */
    public static function all(): array
    {
        return self::$cache ?? [];
    }

    public static function get(string $key, $default = null)
    {
        self::load();
        if (!array_key_exists($key, self::$cache)) return $default;
        $v = self::$cache[$key];
        if (is_string($v) && strlen($v) > 1 && ($v[0] === 'a' || $v[0] === 'b' || $v[0] === 'i' || $v[0] === 'd' || $v[0] === 'N')) {
            $un = @unserialize($v);
            if ($un !== false || $v === 'b:0;') return $un;
        }
        return $v;
    }

    public static function set(string $key, $value): void
    {
        self::load();
        $stored = is_array($value) || is_bool($value) || is_int($value) ? serialize($value) : (string)$value;
        $exists = DB::row('SELECT id FROM settings WHERE k = ?', [$key]);
        if ($exists) {
            DB::run('UPDATE settings SET v = ? WHERE k = ?', [$stored, $key]);
        } else {
            DB::insert('settings', ['k' => $key, 'v' => $stored]);
        }
        self::$cache[$key] = $stored;
    }

    public static function many(array $pairs): void
    {
        foreach ($pairs as $k => $v) {
            self::set($k, $v);
        }
    }
}
