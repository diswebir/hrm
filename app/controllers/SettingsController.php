<?php
/**
 * تنظیمات سامانه
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Settings;

final class SettingsController extends Controller
{
    private const KEYS = [
        'app_name', 'org_name', 'logo_text', 'theme', 'timezone',
        'work_days', 'work_start', 'work_end', 'late_grace_min',
        'session_ttl_minutes', 'emp_no_prefix', 'emp_no_digits',
        'currency', 'overtime_rate', 'overtime_night_rate', 'overtime_holiday_rate',
        'contract_alert_days', 'probation_alert_days', 'tax_exempt_personal',
    ];

    public function index(): void
    {
        $this->render('settings/index', [
            'title' => 'تنظیمات',
            'settings' => Settings::all(),
            'counts' => [
                'employees' => (int)DB::val('SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL'),
                'users' => (int)DB::val('SELECT COUNT(*) FROM users'),
                'logs' => (int)DB::val('SELECT COUNT(*) FROM activity_log'),
                'attendance' => (int)DB::val('SELECT COUNT(*) FROM attendance'),
            ],
            'php' => PHP_VERSION,
            'version' => setting('installed_version', '1.0.0'),
        ]);
    }

    public function save(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $saved = 0;
        foreach (self::KEYS as $key) {
            if (!array_key_exists($key, $_POST)) continue;
            $raw = $_POST[$key];
            $val = is_array($raw) ? implode(',', $raw) : trim((string)$raw);
            switch ($key) {
                case 'session_ttl_minutes':
                    $val = (string)min(10080, max(5, (int)$val));
                    break;
                case 'late_grace_min':
                    $val = (string)min(120, max(0, (int)$val));
                    break;
                case 'emp_no_digits':
                    $val = (string)min(9, max(1, (int)$val));
                    break;
                case 'work_start':
                case 'work_end':
                    if (!preg_match('/^\d{2}:\d{2}$/', $val)) $val = $key === 'work_start' ? '08:00' : '17:00';
                    break;
                case 'emp_no_prefix':
                    if (!preg_match('/^[A-Za-z]{0,5}$/', $val)) $val = 'EMP';
                    break;
                case 'app_name':
                    if ($val === '' || mb_strlen($val) > 80) continue 2;
                    break;
                case 'org_name':
                    if (mb_strlen($val) > 120) continue 2;
                    break;
                case 'timezone':
                    if (!in_array($val, ['Asia/Tehran', 'UTC'], true)) $val = 'Asia/Tehran';
                    break;
                case 'work_days':
                    $parts = array_filter(array_map('intval', explode(',', $val)), fn($x) => $x >= 0 && $x <= 6);
                    $val = implode(',', array_unique($parts)) ?: '6,0,1,2,3,4';
                    break;
                case 'overtime_rate':
                case 'overtime_night_rate':
                case 'overtime_holiday_rate':
                    $val = (string)min(5, max(1, (float)$val));
                    break;
                default:
                    if (mb_strlen($val) > 200) $val = mb_substr($val, 0, 200);
            }
            Settings::set($key, $val);
            $saved++;
        }
        Log::info('settings_save', 'به‌روزرسانی تنظیمات', ['count' => $saved]);
        flash_set('success', to_fa((string)$saved) . ' تنظیم ذخیره شد.');
        redirect('/settings');
    }
}
