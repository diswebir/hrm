<?php
/**
 * سلامت سامانه
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Settings;

final class HealthController extends Controller
{
    public function index(): void
    {
        $checks = [];

        // ۱) PHP
        $phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
        $checks[] = ['سازگاری PHP', $phpOk, 'نسخه ' . PHP_VERSION . ($phpOk ? ' (یا بالاتر)' : ' — حداقل 7.4 لازم است')];

        // ۲) افزونه‌ها
        foreach (['pdo_sqlite' => 'pdo_sqlite', 'mbstring' => 'mbstring', 'session' => 'session'] as $ext => $label) {
            $ok = extension_loaded($ext);
            $checks[] = ['افزونه ' . $label, $ok, $ok ? 'فعال' : 'غیرفعال — با سرویس‌دهنده هاست تماس بگیرید'];
        }

        // ۳) دیتابیس
        try {
            $integrity = DB::val('PRAGMA integrity_check');
            $checks[] = ['یکپارچگی دیتابیس', $integrity === 'ok', (string)$integrity];
        } catch (\Throwable $e) {
            $checks[] = ['یکپارچگی دیتابیس', false, $e->getMessage()];
        }

        // ۴) پوشه‌ها قابل نوشتن
        foreach (['storage', 'storage/database', 'storage/sessions', 'storage/logs', 'storage/uploads', 'storage/backups', 'config'] as $dir) {
            $path = HRM_ROOT . '/' . $dir;
            $ok = is_dir($path) && is_writable($path);
            $checks[] = ['نوشتن ' . $dir, $ok, $ok ? 'قابل نوشتن' : 'NOT WRITABLE — chmod 775'];
        }

        // ۵) فایل تنظیمات
        $cfg = is_file(HRM_ROOT . '/config/config.php');
        $checks[] = ['فایل پیکربندی', $cfg, $cfg ? 'موجود (config.php)' : 'مفقود — نصب را کامل کنید'];
        $lock = is_file(HRM_ROOT . '/config/installed.lock');
        $checks[] = ['قفل نصب', $lock, $lock ? 'نصب کامل شده' : 'نصب کامل نشده'];

        // ۶) آمار جداول
        $tables = ['employees', 'users', 'attendance', 'leave_requests', 'payslips', 'activity_log', 'notifications'];
        $stats = [];
        foreach ($tables as $t) {
            try {
                $stats[$t] = (int)DB::val("SELECT COUNT(*) FROM {$t}");
            } catch (\Throwable $e) {
                $stats[$t] = -1;
            }
        }

        // ۷) خطاهای اخیر
        $recentErrors = DB::all(
            "SELECT created_at, action, message FROM activity_log WHERE level IN ('error','warning')
             ORDER BY id DESC LIMIT 10"
        );

        // ۸) حجم‌ها
        $dbSize = (int)@filesize(HRM_ROOT . '/storage/database/hrm.sqlite');
        $logSize = 0;
        foreach (glob(HRM_ROOT . '/storage/logs/*.log') ?: [] as $lf) $logSize += (int)@filesize($lf);
        $sessCount = count(glob(HRM_ROOT . '/storage/sessions/sess_*') ?: []);

        $this->render('health/index', [
            'title' => 'سلامت سامانه',
            'checks' => $checks, 'stats' => $stats,
            'recentErrors' => $recentErrors,
            'dbSize' => $dbSize, 'logSize' => $logSize, 'sessCount' => $sessCount,
            'php' => PHP_VERSION, 'sqlite' => (string)DB::val('SELECT sqlite_version()'),
            'installed' => setting('installed_version', '—'),
            'allOk' => count(array_filter($checks, fn($c) => !$c[1])) === 0,
        ]);
    }
}
