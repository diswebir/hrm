<?php
/**
 * توابع کمکی سراسری
 */
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Jalali;
use App\Core\Settings;

/** خروجی امن HTML (جلوگیری از XSS) */
function e($value): string
{
    if ($value === null) return '';
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** آدرس پایه برنامه (برای نصب در زیرپوشه) */
function hrm_base(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = dirname($script);
    if ($dir === '/' || $dir === '\\') $dir = '';
    // اگر از مسیر روت باشد و فایل‌ها عقب‌تر نباشند
    $base = rtrim($dir, '/');
    // وقتی درخواست از index.php داخل پوشه می‌آید اما index.php ریشه نیست
    if (basename($script) !== 'index.php' && basename($script) !== 'install.php') {
        // مثلاً /app/index.php — نادیده بگیر
    }
    return $base;
}

/** ساخت URL کامل */
function hrm_url(string $path = '/'): string
{
    $base = hrm_base();
    if ($path === '') $path = '/';
    if ($path[0] !== '/') $path = '/' . $path;
    return $base . $path;
}

/** هدایت به آدرس */
function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : hrm_url($path)));
    exit;
}

/** خواندن ورودی (امن، trim شده) */
function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? null;
    if (is_string($v)) {
        $v = trim($v);
        if ($v === '') return $default;
    }
    return $v ?? $default;
}

/** فقط POST */
function post(string $key, $default = null)
{
    $v = $_POST[$key] ?? null;
    if (is_string($v)) {
        $v = trim($v);
        if ($v === '') return $default;
    }
    return $v ?? $default;
}

/** فیلد مخفی CSRF */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function csrf_verify(): void
{
    Csrf::verify($_POST['_token'] ?? '');
}

/** پیام‌های لحظه‌ای (flash) */
function flash_set(string $type, string $message): void
{
    \App\Core\Flash::set($type, $message);
}

/** برچسب‌های رابط کاربری */
function __(string $key, array $replace = []): string
{
    static $dict = null;
    if ($dict === null) {
        $dict = is_file(HRM_ROOT . '/lang/fa.php') ? require HRM_ROOT . '/lang/fa.php' : [];
    }
    $text = $dict[$key] ?? $key;
    foreach ($replace as $k => $v) {
        $text = str_replace(':' . $k, (string)$v, $text);
    }
    return $text;
}

/** تاریخ میلادی → شمسی نمایشی */
function jdate(?string $g, string $sep = '/'): string
{
    return Jalali::format($g, $sep);
}

/** امروز شمسی */
function today_j(): string
{
    return Jalali::g2j(date('Y-m-d'));
}

/** مبلغ با جداکننده هزارگان */
function money($n): string
{
    if ($n === null || $n === '') return '۰';
    return number_format((float)$n, 0, '.', ',');
}

/** عدد فارسی‌شده (برای شماره‌ها) */
function to_fa(string $s): string
{
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace($en, $fa, $s);
}

/** درصد */
function pct(float $n, int $dec = 1): string
{
    return to_fa(number_format($n, $dec)) . '٪';
}

/** برچسب وضعیت‌های استاندارد */
function status_badge(string $status): string
{
    $map = [
        'active'   => ['فعال', 'ok'],
        'inactive' => ['غیرفعال', 'muted'],
        'archived' => ['بایگانی', 'muted'],
        'pending'  => ['در انتظار', 'warn'],
        'approved' => ['تأیید شده', 'ok'],
        'rejected' => ['رد شده', 'danger'],
        'canceled' => ['لغو شده', 'muted'],
        'paid'     => ['پرداخت شده', 'ok'],
        'open'     => ['باز', 'ok'],
        'closed'   => ['بسته', 'muted'],
        'hired'    => ['استخدام شده', 'ok'],
        'new'      => ['جدید', 'info'],
        'screening' => ['غربالگری', 'info'],
        'interview' => ['مصاحبه', 'warn'],
        'offer'    => ['پیشنهاد', 'info'],
        'draft'    => ['پیش‌نویس', 'muted'],
        'final'    => ['نهایی', 'ok'],
        'cancelled'=> ['انصرافی', 'muted'],
        'archived' => ['حذف‌شده', 'muted'],
        'late'     => ['تأخیر', 'warn'],
        'remote'   => ['دورکاری', 'info'],
        'trip'     => ['مأموریت', 'info'],
        'holiday'  => ['تعطیل', 'muted'],
        'locked'   => ['قفل شده', 'info'],
        'completed'=> ['تکمیل شده', 'ok'],
        'running'  => ['در حال اجرا', 'info'],
        'failed'   => ['ناموفق', 'danger'],
        'late'     => ['تأخیر', 'warn'],
        'present'  => ['حاضر', 'ok'],
        'absent'   => ['غایب', 'danger'],
        'leave'    => ['مرخصی', 'info'],
        'on_leave' => ['مرخصی', 'info'],
    ];
    [$label, $kind] = $map[$status] ?? [ucfirst($status), 'muted'];
    return '<span class="badge badge-' . $kind . '">' . e($label) . '</span>';
}

/** خروجی سریع JSON */
function json_out($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** امن‌سازی نام فایل آپلودی */
function safe_filename(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^\p{L}\p{N}_\-]+/u', '_', $base) ?: 'file';
    $base = substr($base, 0, 60);
    return $base . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
}

/** تنظیمات */
function setting(string $key, $default = null)
{
    return \App\Core\Settings::get($key, $default);
}

/** کاربر جاری */
function auth_user(): ?array
{
    return Auth::user();
}

function can(string $perm): bool
{
    return Auth::can($perm);
}

/** برچسب نام کامل کارمند */
function emp_name(?array $emp): string
{
    if (!$emp) return '—';
    return trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
}

/** خروجی فایل CSV استاندارد اکسل (با BOM) */
function csv_download(string $filename, array $header, array $rows): never
{
    \App\Core\Csv::download($filename, $header, $rows);
    exit;
}
