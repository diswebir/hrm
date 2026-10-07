<?php
/**
 * Bootstrap — بارگذاری هسته سامانه
 */
declare(strict_types=1);

define('HRM_ROOT', dirname(__DIR__));
define('HRM_START', microtime(true));

// ---------- تشخیص نصب ----------
define('HRM_INSTALLED', file_exists(HRM_ROOT . '/config/installed.lock'));

// ---------- پیکربندی ----------
if (HRM_INSTALLED && file_exists(HRM_ROOT . '/config/config.php')) {
    $config = require HRM_ROOT . '/config/config.php';
} else {
    $config = [
        'app_name'  => 'سامانه مدیریت منابع انسانی',
        'locale'    => 'fa',
        'timezone'  => 'Asia/Tehran',
        'debug'     => false,
        'base_url'  => '',
        'db_path'   => HRM_ROOT . '/storage/database/hrm.sqlite',
    ];
}
$GLOBALS['hrm_config'] = $config;

date_default_timezone_set($config['timezone'] ?? 'Asia/Tehran');
mb_internal_encoding('UTF-8');

// ---------- نمایش خطاها ----------
$debug = !empty($config['debug']);
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    $logFile = HRM_ROOT . '/storage/logs/php-error.log';
    if (is_dir(dirname($logFile))) {
        ini_set('error_log', $logFile);
    }
}

// ---------- اتوماتیک‌لودر ----------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $rel = substr($class, strlen($prefix));          // Core\DB  | Controllers\Foo
    // پوشه‌ها با حروف کوچک‌اند (سازگاری لینوکس و ویندوز)
    if (strncmp($rel, 'Controllers\\', 12) === 0) {
        $rel = 'controllers/' . substr($rel, 12);
        $path = HRM_ROOT . '/app/' . str_replace('\\', '/', $rel) . '.php';
    } elseif (strncmp($rel, 'Core\\', 5) === 0) {
        $path = HRM_ROOT . '/app/core/' . str_replace('\\', '/', substr($rel, 5)) . '.php';
    } else {
        $path = HRM_ROOT . '/app/' . str_replace('\\', '/', $rel) . '.php';
    }
    if (is_file($path)) require $path;
});

// ---------- کتابخانه‌های عمومی ----------
require_once HRM_ROOT . '/app/core/Helpers.php';
require_once HRM_ROOT . '/app/core/Jalali.php';

if (!HRM_INSTALLED) {
    // هنوز نصب نشده → فقط نصب‌کننده مجاز است
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($script, ['install.php'], true)) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        header('Location: ' . $base . '/install.php');
        exit;
    }
    return;
}

require_once HRM_ROOT . '/app/core/DB.php';
require_once HRM_ROOT . '/app/core/Settings.php';
require_once HRM_ROOT . '/app/core/Csrf.php';
require_once HRM_ROOT . '/app/core/Flash.php';
require_once HRM_ROOT . '/app/core/Auth.php';
require_once HRM_ROOT . '/app/core/Log.php';
require_once HRM_ROOT . '/app/core/Validator.php';
require_once HRM_ROOT . '/app/core/Router.php';
require_once HRM_ROOT . '/app/core/View.php';
require_once HRM_ROOT . '/app/core/Csv.php';
require_once HRM_ROOT . '/app/core/Charts.php';
require_once HRM_ROOT . '/app/core/Notify.php';

// ---------- نشست ----------
if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessPath = HRM_ROOT . '/storage/sessions';
    if (is_dir($sessPath) && is_writable($sessPath)) {
        session_save_path($sessPath);
    }
    session_name('HRMSESS');
    // طول عمر فایل نشست باید از مهلت بیکاری بزرگ‌تر باشد
    $ttlMin = 120;
    if (is_file(HRM_ROOT . '/config/config.php')) {
        $ttlMin = max(10, (int)($_SESSION_TTL_HINT ?? 120));
    }
    ini_set('session.gc_maxlifetime', (string)($ttlMin * 60));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (function () {
            $p = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
            return $p === '' || $p === '/' ? '/' : $p;
        })(),
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

// ---------- پارامترهای پایه ----------
App\Core\Settings::boot();
App\Core\Csrf::boot();
App\Core\Auth::boot();

// ---------- مدیریت خطاهای سراسری ----------
set_exception_handler(function (Throwable $e): void {
    App\Core\Log::error('exception', $e->getMessage(), [
        'file' => $e->getFile() . ':' . $e->getLine(),
    ]);
    $debug = !empty($GLOBALS['hrm_config']['debug']);
    http_response_code(500);
    if ($debug) {
        echo '<h1>خطای برنامه‌نویسی</h1><pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        $base = hrm_base();
        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8">'
            . '<title>خطا</title>'
            . '<body style="font-family:Tahoma;text-align:center;padding:60px;background:#f8fafc">'
            . '<div style="background:#fff;border-radius:16px;max-width:520px;margin:auto;padding:40px;box-shadow:0 10px 40px rgba(0,0,0,.08)">'
            . '<div style="font-size:48px">⚠️</div>'
            . '<h2>خطای غیرمنتظره‌ای رخ داد</h2>'
            . '<p style="color:#64748b">درخواست شما پردازش نشد. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.</p>'
            . '<a href="' . hrm_url('/') . '" style="display:inline-block;margin-top:12px;background:#2563eb;color:#fff;padding:10px 24px;border-radius:10px;text-decoration:none">بازگشت به صفحه اصلی</a>'
            . '</div></body></html>';
    }
    exit;
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        App\Core\Log::error('fatal', $err['message'], ['file' => $err['file'] . ':' . $err['line']]);
    }
});
