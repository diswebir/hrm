<?php
/**
 * پشتیبان‌گیری و بازیابی (SQLite)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Log;

final class BackupController extends Controller
{
    private function dir(): string
    {
        $dir = HRM_ROOT . '/storage/backups';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir;
    }

    public function index(): void
    {
        $files = [];
        foreach (glob($this->dir() . '/*.sqlite') ?: [] as $f) {
            $files[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        usort($files, fn($a, $b) => $b['time'] <=> $a['time']);
        $this->render('backup/index', [
            'title' => 'پشتیبان‌گیری',
            'files' => $files,
            'dbSize' => (int)@filesize(HRM_ROOT . '/storage/database/hrm.sqlite'),
            'free' => @disk_free_space(HRM_ROOT) ?: 0,
        ]);
    }

    public function create(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $name = 'backup-' . date('Ymd-His') . '.sqlite';
        $dest = $this->dir() . '/' . $name;
        try {
            // VACUUM INTO یک کپی سازگار و فشرده می‌سازد
            \App\Core\DB::execScript("VACUUM INTO '" . str_replace("'", "''", $dest) . "'");
        } catch (\Throwable $e) {
            // پشتیبان خطی: کپی فایل خام
            if (!@copy(HRM_ROOT . '/storage/database/hrm.sqlite', $dest)) {
                flash_set('error', 'پشتیبان‌گیری ناموفق بود: ' . $e->getMessage());
                redirect('/backup');
            }
        }
        // نگه‌داشتن حداکثر ۱۰ نسخه
        $files = glob($this->dir() . '/*.sqlite') ?: [];
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($files, 10) as $old) {
            @unlink($old);
        }
        Log::info('backup_create', 'پشتیبان‌گیری', ['file' => $name]);
        flash_set('success', 'پشتیبان «' . $name . '» ساخته شد.');
        redirect('/backup');
    }

    public function download(string $file): void
    {
        $file = basename($file); // جلوگیری از path traversal
        $path = $this->dir() . '/' . $file;
        if (!preg_match('/^backup-[\d-]+\.sqlite$/', $file) || !is_file($path)) {
            http_response_code(404);
            $this->render('errors/404', ['title' => '404']);
            return;
        }
        Log::info('backup_download', 'دانلود پشتیبان', ['file' => $file]);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    public function restore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $file = basename((string)post('file', ''));
        $path = $this->dir() . '/' . $file;
        if (!preg_match('/^backup-[\d-]+\.sqlite$/', $file) || !is_file($path)) {
            flash_set('error', 'فایل پشتیبان معتبر نیست.');
            redirect('/backup');
        }
        // ابتدا پشتیبان از وضعیت فعلی
        $safety = $this->dir() . '/backup-before-restore-' . date('Ymd-His') . '.sqlite';
        @copy(HRM_ROOT . '/storage/database/hrm.sqlite', $safety);

        // بستن اتصال و جایگزینی فایل
        \App\Core\DB::close();
        if (!@copy($path, HRM_ROOT . '/storage/database/hrm.sqlite')) {
            flash_set('error', 'بازیابی ممکن نشد؛ دسترسی فایل دیتابیس را بررسی کنید.');
            redirect('/backup');
        }
        @chmod(HRM_ROOT . '/storage/database/hrm.sqlite', 0664);
        Log::warn('backup_restore', 'بازیابی از پشتیبان', ['file' => $file]);
        flash_set('success', 'دیتابیس از پشتیبان بازیابی شد. لطفاً دوباره وارد شوید.');
        redirect('/login');
    }

    public function delete(string $file): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $file = basename($file);
        if (preg_match('/^backup-[\d-]+\.sqlite$/', $file)) {
            @unlink($this->dir() . '/' . $file);
            Log::info('backup_delete', 'حذف پشتیبان', ['file' => $file]);
            flash_set('success', 'پشتیبان حذف شد.');
        }
        redirect('/backup');
    }
}
