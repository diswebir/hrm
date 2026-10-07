<?php
/**
 * خروجی CSV سازگار با اکسل (پشتیبانی از فارسی)
 */
declare(strict_types=1);

namespace App\Core;

final class Csv
{
    public static function download(string $filename, array $header, array $rows): void
    {
        if (!str_ends_with(strtolower($filename), '.csv')) {
            $filename .= '.csv';
        }
        $filename = preg_replace('/[^\w\-.]+/u', '_', $filename) ?: 'export.csv';

        // پیشوند BOM برای نمایش درست فارسی در اکسل
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM

        // BOM + پیشوند = برای محاسبه فرمول اکسل جلوی کاملاً عددی‌ها را می‌گیریم
        fputcsv($out, $header, ',', '"', '\\');

        foreach ($rows as $row) {
            $clean = [];
            foreach ($row as $v) {
                $v = (string)($v ?? '');
                // جلوگیری از حمله CSV Injection
                if (preg_match('/^[=+\-@\t\r]/', $v)) {
                    $v = "'" . $v;
                }
                $clean[] = $v;
            }
            fputcsv($out, $clean, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /** خواندن فایل CSV آپلودی (برای ورود توده‌ای) */
    public static function read(string $path): array
    {
        $rows = [];
        $fh = fopen($path, 'r');
        if (!$fh) return $rows;
        // حذف BOM احتمالی
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fh);
        }
        $header = null;
        while (($line = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            if ($header === null) {
                $header = array_map(fn($h) => mb_strtolower(trim((string)$h)), $line);
                continue;
            }
            if ($line === [null] || $line === []) continue;
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = trim((string)($line[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }
}
