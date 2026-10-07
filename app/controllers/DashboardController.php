<?php
/**
 * داشبورد — نمای کلی و آمار
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Charts;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\Notify;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $today = date('Y-m-d');
        $canViewAll = Auth::can('employees.view') || Auth::isSuper();

        $data = ['title' => 'داشبورد'];

        if ($canViewAll) {
            $totalEmp   = (int)DB::val("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND status = 'active'");
            $newMonth   = (int)DB::val("SELECT COUNT(*) FROM employees WHERE hire_date >= date('now','start of month') AND deleted_at IS NULL");
            $presentNow = (int)DB::val("SELECT COUNT(*) FROM attendance WHERE date = ? AND status IN ('present','late','remote')", [$today]);
            $leaveToday = (int)DB::val("SELECT COUNT(*) FROM attendance WHERE date = ? AND status = 'leave'", [$today]);
            $absentToday= (int)DB::val("SELECT COUNT(*) FROM attendance WHERE date = ? AND status = 'absent'", [$today]);
            $pendingLeave = (int)DB::val("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
            $pendingReq   = (int)DB::val("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
            $openJobs     = (int)DB::val("SELECT COUNT(*) FROM jobs WHERE status = 'open'");
            $applicants   = (int)DB::val("SELECT COUNT(*) FROM applicants WHERE stage NOT IN ('hired','rejected')");

            // نیروی کار به تفکیک واحد
            $byDept = DB::all("
                SELECT COALESCE(d.name,'بدون واحد') AS name, COUNT(*) AS c
                FROM employees e LEFT JOIN departments d ON d.id = e.dept_id
                WHERE e.deleted_at IS NULL AND e.status = 'active'
                GROUP BY e.dept_id ORDER BY c DESC");
            $deptChart = Charts::bar(array_map(fn($r) => [$r['name'], (int)$r['c']], $byDept));

            // روند حضور ۱۴ روز اخیر
            $trend = [];
            for ($i = 13; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $c = (int)DB::val("SELECT COUNT(*) FROM attendance WHERE date = ? AND status IN ('present','late','remote')", [$d]);
                $trend[] = [Jalali::format($d), $c];
            }
            $trendChart = Charts::line($trend, 560, 170, 'روند حضور');

            // ترکیب جنسیت
            $gender = DB::all("SELECT gender, COUNT(*) c FROM employees WHERE deleted_at IS NULL AND status='active' GROUP BY gender");
            $gData = [];
            foreach ($gender as $g) {
                $gData[] = [$g['gender'] === 'female' ? 'خانم' : 'آقا', (int)$g['c']];
            }
            $genderChart = Charts::donut($gData);

            // قراردادهای رو به اتمام (۳۰ روز آینده)
            $expiring = DB::all("
                SELECT id, first_name, last_name, emp_no, contract_end, position_id,
                       (SELECT title FROM positions WHERE id = position_id) pos
                FROM employees
                WHERE deleted_at IS NULL AND status='active'
                  AND contract_end IS NOT NULL AND contract_end != ''
                  AND contract_end BETWEEN date(?) AND date(?, '+' || ? || ' days')
                ORDER BY contract_end LIMIT 6", [$today, $today, (int)setting('contract_alert_days', 30)]);

            // تولدهای امروز
            $birthdays = DB::all("
                SELECT id, first_name, last_name, photo FROM employees
                WHERE deleted_at IS NULL AND status='active'
                  AND strftime('%m-%d', birth_date) = strftime('%m-%d', ?)
                LIMIT 8", [$today]);

            // مرخصی‌های در انتظار
            $pendingLeaves = DB::all("
                SELECT l.*, e.first_name, e.last_name, e.emp_no, lt.name type_name, lt.color
                FROM leave_requests l
                JOIN employees e ON e.id = l.employee_id
                JOIN leave_types lt ON lt.id = l.type_id
                WHERE l.status='pending' ORDER BY l.created_at DESC LIMIT 5");

            $data += compact(
                'totalEmp', 'newMonth', 'presentNow', 'leaveToday', 'absentToday',
                'pendingLeave', 'pendingReq', 'openJobs', 'applicants',
                'deptChart', 'trendChart', 'genderChart', 'expiring', 'birthdays', 'pendingLeaves'
            );
        }

        // فعالیت‌های اخیر
        $data['activities'] = DB::all("
            SELECT a.*, u.username FROM activity_log a
            LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.created_at DESC LIMIT 12");

        // اعلان‌های من
        $data['myNotifs'] = $user ? Notify::recent((int)$user['id'], 6) : [];

        // آمار شخصی کارمند (برای همه)
        if ($user && $user['employee_id']) {
            $eid = (int)$user['employee_id'];
            $data['myAttMonth'] = (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=date('now','start of month') AND status IN ('present','late','remote')", [$eid]);
            $data['myLateMonth'] = (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=date('now','start of month') AND status='late'", [$eid]);
            $data['myPendingLeave'] = (int)DB::val("SELECT COUNT(*) FROM leave_requests WHERE employee_id=? AND status='pending'", [$eid]);
            $year = (int)date('Y');
            $data['myLeaveLeft'] = (float)DB::val("SELECT COALESCE(SUM(days_total - days_used),0) FROM leave_balances WHERE employee_id=? AND year=? AND type_id=(SELECT id FROM leave_types WHERE slug='annual')", [$eid, $year]);
        }

        $this->render('dashboard/index', $data);
    }

    /** پرونده کاری من (خودخدمت) */
    public function self(): void
    {
        $user = Auth::user();
        if (!$user || !$user['employee_id']) {
            flash_set('warning', 'پرونده کارمندی به حساب شما متصل نیست.');
            redirect('/');
        }
        $emp = DB::row('SELECT * FROM employees WHERE id = ?', [$user['employee_id']]);
        if (!$emp) {
            flash_set('error', 'پرونده کارمندی یافت نشد.');
            redirect('/');
        }
        $eid = (int)$emp['id'];
        $today = date('Y-m-d');

        $att30 = DB::all("SELECT * FROM attendance WHERE employee_id=? AND date >= date(?, '-29 days') ORDER BY date DESC", [$eid, $today]);
        $leaves = DB::all("SELECT l.*, lt.name type_name, lt.color FROM leave_requests l
                           JOIN leave_types lt ON lt.id=l.type_id
                           WHERE l.employee_id=? ORDER BY l.created_at DESC LIMIT 10", [$eid]);
        $year = (int)date('Y');
        $balances = DB::all("SELECT b.*, lt.name, lt.color FROM leave_balances b
                             JOIN leave_types lt ON lt.id=b.type_id
                             WHERE b.employee_id=? AND b.year=? ORDER BY lt.sort", [$eid, $year]);
        $slips = DB::all("SELECT p.*, py.year, py.month FROM payslips p
                          JOIN payroll_periods py ON py.id=p.period_id
                          WHERE p.employee_id=? ORDER BY py.year DESC, py.month DESC LIMIT 6", [$eid]);
        $reqs = DB::all("SELECT * FROM requests WHERE employee_id=? ORDER BY created_at DESC LIMIT 8", [$eid]);
        $goals = DB::all("SELECT * FROM goals WHERE employee_id=? AND status='active' ORDER BY due_date", [$eid]);
        $docs = DB::all("SELECT * FROM documents WHERE employee_id=? ORDER BY created_at DESC", [$eid]);
        $history = DB::all("SELECT * FROM work_history WHERE employee_id=? ORDER BY from_date DESC", [$eid]);
        $loans = DB::all("SELECT * FROM loans WHERE employee_id=? AND status='active'", [$eid]);
        $manager = $emp['manager_id'] ? DB::row('SELECT first_name,last_name FROM employees WHERE id=?', [$emp['manager_id']]) : null;
        $dept = $emp['dept_id'] ? DB::row('SELECT name FROM departments WHERE id=?', [$emp['dept_id']]) : null;
        $pos = $emp['position_id'] ? DB::row('SELECT title FROM positions WHERE id=?', [$emp['position_id']]) : null;

        $this->render('dashboard/self', compact(
            'emp', 'att30', 'leaves', 'balances', 'slips', 'reqs', 'goals', 'docs',
            'history', 'loans', 'manager', 'dept', 'pos'
        ) + ['title' => 'پرونده کاری من']);
    }
}
