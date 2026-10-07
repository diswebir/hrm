<?php
/**
 * احراز هویت، نقش‌ها و مجوزها
 */
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;
    private static array $permissions = [];
    private static bool $permsLoaded = false;

    public static function boot(): void
    {
        // بارگذاری تنبل در اولین استفاده + کنترل نشست
        $uid = $_SESSION['user_id'] ?? null;
        if ($uid !== null) {
            $row = DB::row('SELECT u.*, r.slug AS role_slug, r.name AS role_name
                            FROM users u LEFT JOIN roles r ON r.id = u.role_id
                            WHERE u.id = ? AND u.status = ?', [(int)$uid, 'active']);
            if ($row) {
                self::$user = $row;
                self::touchSession($row);
            } else {
                self::logout(true);
            }
        }
    }

    private static function touchSession(array $user): void
    {
        $sid = session_id();
        $now = date('Y-m-d H:i:s');

        $sess = DB::row('SELECT * FROM user_sessions WHERE session_id = ?', [$sid]);
        if (!$sess) {
            // نشست جعلی/منقضی
            if (!empty($_SESSION['user_id'])) {
                self::logout(true);
            }
            return;
        }
        if ($sess['revoked_at'] !== null) {
            self::logout(true);
            return;
        }
        // انقضای بیکاری
        $ttl = (int)setting('session_ttl_minutes', 120);
        if ($ttl > 0 && strtotime($now) - strtotime($sess['last_seen_at']) > $ttl * 60) {
            DB::update('user_sessions', ['revoked_at' => $now], (int)$sess['id']);
            Flash::set('warning', 'نشست شما به دلیل عدم فعالیت منقضی شد. دوباره وارد شوید.');
            self::logout(true);
            return;
        }
        if (strtotime($now) - strtotime($sess['last_seen_at']) > 30) {
            DB::update('user_sessions', ['last_seen_at' => $now], (int)$sess['id']);
        }
    }

    /** تلاش برای ورود */
    public static function attempt(string $username, string $password): array
    {
        $username = trim($username);
        $user = DB::row(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.username = ? OR u.email = ?',
            [$username, $username]
        );

        if (!$user) {
            Log::warning('login_failed', 'کاربر یافت نشد: ' . $username);
            return [false, 'نام کاربری یا رمز عبور نادرست است.'];
        }

        if ($user['status'] !== 'active') {
            Log::warning('login_blocked', 'حساب غیرفعال: ' . $username);
            return [false, 'حساب کاربری شما غیرفعال شده است. با مدیر سامانه تماس بگیرید.'];
        }

        // قفل موقت بعد از تلاش‌های مکرر
        if ($user['locked_until'] !== null && $user['locked_until'] > date('Y-m-d H:i:s')) {
            return [false, 'حساب به دلیل تلاش‌های ناموفق مکرر، موقتاً قفل شده است. لطفاً بعداً تلاش کنید.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $fails = (int)$user['failed_attempts'] + 1;
            $lock = null;
            if ($fails >= 5) {
                $lock = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $fails = 0;
            }
            DB::run('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?', [$fails, $lock, $user['id']]);
            Log::warning('login_failed', 'رمز نادرست برای ' . $username);
            if ($lock) return [false, '۵ تلاش ناموفق؛ حساب به مدت ۱۵ دقیقه قفل شد.'];
            return [false, 'نام کاربری یا رمز عبور نادرست است.'];
        }

        // موفق
        self::login($user);
        return [true, 'خوش آمدید!'];
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['login_at'] = time();

        DB::run('UPDATE users SET last_login_at = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?',
            [date('Y-m-d H:i:s'), $user['id']]);

        DB::insert('user_sessions', [
            'user_id'       => (int)$user['id'],
            'session_id'    => session_id(),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'    => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
            'last_seen_at'  => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        Log::info('login', 'ورود موفق کاربر', ['user' => $user['username']]);

        // نشست‌های قبلی همین مرورگر را نگه داریم (فقط قدیمی‌ها را منقضی نکن)
        self::$user = $user;
        self::$loaded = true;
        self::$permsLoaded = false;
    }

    public static function logout(bool $silent = false): void
    {
        $sid = session_id();
        if ($sid) {
            DB::updateWhere('user_sessions', ['revoked_at' => date('Y-m-d H:i:s')],
                'session_id = ? AND revoked_at IS NULL', [$sid]);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
        if (!$silent) {
            // هدایت در نقطه فراخوانی
        }
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): int
    {
        return (int)(self::$user['id'] ?? 0);
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function isSuper(): bool
    {
        return (self::$user['role_slug'] ?? '') === 'super';
    }

    /** بررسی مجوز (نقش super همه‌کاره است) */
    public static function can(string $perm): bool
    {
        if (!self::check()) return false;
        if (self::isSuper()) return true;
        self::loadPerms();
        return isset(self::$permissions[$perm]);
    }

    /** بررسی یکی از چند مجوز */
    public static function canAny(array $perms): bool
    {
        foreach ($perms as $p) {
            if (self::can($p)) return true;
        }
        return false;
    }

    private static function loadPerms(): void
    {
        if (self::$permsLoaded) return;
        self::$permsLoaded = true;
        self::$permissions = [];
        if (!self::$user) return;
        $rows = DB::all('SELECT rp.permission FROM role_permissions rp
                         JOIN users u ON u.role_id = rp.role_id
                         WHERE u.id = ?', [self::$id()]);
        foreach ($rows as $r) {
            self::$permissions[$r['permission']] = true;
        }
    }

    public static function permissions(): array
    {
        self::loadPerms();
        return array_keys(self::$permissions);
    }

    /** نشست‌های فعال کاربر */
    public static function sessions(int $userId): array
    {
        return DB::all('SELECT * FROM user_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [$userId]);
    }
}
