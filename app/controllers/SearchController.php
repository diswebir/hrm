<?php
/**
 * جستجوی سراسری (پاسخ JSON)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;

final class SearchController extends Controller
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::check()) {
            echo json_encode(['items' => []]);
            return;
        }
        $q = trim((string)($_GET['q'] ?? ''));
        $items = [];
        if (mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';
            if (Auth::can('employees.view') || Auth::isSuper()) {
                $emps = DB::all("
                    SELECT id, emp_no, first_name, last_name,
                           (SELECT title FROM positions WHERE id=position_id) pos
                    FROM employees
                    WHERE deleted_at IS NULL AND (first_name || ' ' || last_name LIKE ? OR emp_no LIKE ? OR national_code LIKE ?)
                    LIMIT 6", [$like, $like, $like]);
                foreach ($emps as $emp) {
                    $items[] = [
                        'title' => trim($emp['first_name'] . ' ' . $emp['last_name']) . ' — ' . $emp['emp_no'],
                        'desc'  => ($emp['pos'] ?: 'کارمند') . ' · مشاهده پرونده',
                        'url'   => hrm_url('/employees/' . $emp['id']),
                    ];
                }
            }
            if (Auth::can('users.manage') || Auth::isSuper()) {
                $users = DB::all("SELECT id, username FROM users WHERE username LIKE ? OR email LIKE ? LIMIT 4", [$like, $like]);
                foreach ($users as $u) {
                    $items[] = [
                        'title' => 'کاربر: ' . $u['username'],
                        'desc'  => 'مدیریت کاربران',
                        'url'   => hrm_url('/users/' . $u['id'] . '/edit'),
                    ];
                }
            }
            if (Auth::can('recruits.view') || Auth::isSuper()) {
                $apps = DB::all("SELECT id, full_name, stage FROM applicants WHERE full_name LIKE ? OR phone LIKE ? LIMIT 4", [$like, $like]);
                foreach ($apps as $a) {
                    $items[] = [
                        'title' => 'داوطلب: ' . $a['full_name'],
                        'desc'  => 'مرحله: ' . $a['stage'] . ' · استخدام',
                        'url'   => hrm_url('/recruit/applicants/' . $a['id']),
                    ];
                }
            }
            // صفحات پرکاربرد
            $pages = [
                ['/leave', 'مرخصی‌ها'], ['/attendance', 'حضور و غیاب'],
                ['/reports', 'گزارش‌ها'], ['/about', 'درباره سامانه'],
                ['/payroll', 'حقوق و دستمزد'], ['/settings', 'تنظیمات'],
            ];
            foreach ($pages as $p) {
                if (mb_strpos($p[1], $q) !== false) {
                    $items[] = ['title' => $p[1], 'desc' => 'صفحه سامانه', 'url' => hrm_url($p[0])];
                }
            }
        }
        echo json_encode(['items' => array_slice($items, 0, 10)], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
