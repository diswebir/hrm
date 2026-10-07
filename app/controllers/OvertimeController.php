<?php
/**
 * اضافه‌کاری
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Notify;
use App\Core\Validator;

final class OvertimeController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $canManage = Auth::can('overtime.manage') || Auth::can('overtime.approve');
        $status = (string)($_GET['status'] ?? ($canManage ? 'all' : 'all'));
        $month = (string)($_GET['month'] ?? date('Y-m'));

        $where = ['1=1'];
        $params = [];
        if (!$canManage) {
            $where[] = 'o.employee_id = ?';
            $params[] = (int)($user['employee_id'] ?? 0);
        } else {
            $emp = (int)($_GET['emp'] ?? 0);
            if ($emp) { $where[] = 'o.employee_id = ?'; $params[] = $emp; }
        }
        if ($status !== 'all') {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }
        if ($month !== '') {
            $where[] = "strftime('%Y-%m', o.date) = ?";
            $params[] = $month;
        }
        $whereSql = implode(' AND ', $where);

        $rows = DB::all(
            "SELECT o.*, e.first_name, e.last_name, e.emp_no, a.username approver_name
             FROM overtime_requests o
             JOIN employees e ON e.id = o.employee_id
             LEFT JOIN users a ON a.id = o.approver_id
             WHERE {$whereSql}
             ORDER BY o.created_at DESC LIMIT 200",
            $params
        );

        $totalHours = array_sum(array_map(fn($r) => $r['status'] === 'approved' ? (float)$r['hours'] : 0, $rows));
        $pending = (int)DB::val("SELECT COUNT(*) FROM overtime_requests WHERE status='pending'" . ($canManage ? '' : ' AND employee_id = ' . (int)($user['employee_id'] ?? 0)));

        $this->render('overtime/index', [
            'title' => 'اضافه‌کاری',
            'rows' => $rows, 'canManage' => $canManage, 'pending' => $pending,
            'totalHours' => $totalHours,
            'employees' => DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name"),
            'filters' => compact('status', 'month'),
        ]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = Auth::user();
        $canManage = Auth::can('overtime.manage');

        $v = Validator::make($_POST)
            ->check('date', 'required|date', 'تاریخ')
            ->check('hours', 'required|numeric|min:0.5|max:12', 'ساعات');

        $empId = $canManage ? (int)post('employee_id', 0) : (int)($user['employee_id'] ?? 0);
        if (!$empId) $v->check('employee_id', 'required', 'کارمند');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/overtime');
        }

        $date = (string)post('date');
        $hours = (float)post('hours');
        $reason = trim((string)post('reason', ''));
        $rate = 1.4;
        if (($date !== '') && in_array((int)date('w', strtotime($date)), [5], true)) {
            $rate = (float)setting('overtime_holiday_rate', 2);
        } elseif ($reason !== '' && mb_strpos($reason, 'شب') !== false) {
            $rate = (float)setting('overtime_night_rate', 1.75);
        }

        DB::insert('overtime_requests', [
            'employee_id' => $empId, 'date' => $date, 'hours' => $hours,
            'reason' => $reason, 'multiplier' => $rate, 'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('ot_create', 'درخواست اضافه‌کاری', ['hours' => $hours, 'date' => $date]);
        Notify::toPermission('overtime.approve', 'درخواست اضافه‌کاری جدید',
            to_fa(number_format($hours, 1)) . ' ساعت برای ' . $date, '/overtime');
        flash_set('success', 'درخواست اضافه‌کاری ثبت شد و در انتظار تأیید است.');
        redirect('/overtime');
    }

    public function approve(string $id): void
    {
        $this->decide($id, 'approved');
    }

    public function reject(string $id): void
    {
        $this->decide($id, 'rejected');
    }

    private function decide(string $id, string $decision): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $rec = DB::row('SELECT * FROM overtime_requests WHERE id = ?', [(int)$id]);
        if (!$rec || $rec['status'] !== 'pending') {
            flash_set('error', 'درخواست یافت نشد یا قبلاً بررسی شده است.');
            redirect('/overtime');
        }
        DB::update('overtime_requests', [
            'status' => $decision,
            'approver_id' => Auth::id(),
            'decided_at' => date('Y-m-d H:i:s'),
        ], (int)$id);

        $emp = DB::row('SELECT u.id FROM users u WHERE u.employee_id = ?', [$rec['employee_id']]);
        if ($emp) {
            Notify::toUser((int)$emp['id'],
                $decision === 'approved' ? '✅ اضافه‌کاری تأیید شد' : '❌ اضافه‌کاری رد شد',
                $rec['date'] . ' — ' . to_fa(number_format((float)$rec['hours'], 1)) . ' ساعت',
                '/overtime');
        }
        Log::info('ot_' . $decision, 'تصمیم اضافه‌کاری #' . $id);
        flash_set('success', $decision === 'approved' ? 'اضافه‌کاری تأیید شد.' : 'اضافه‌کاری رد شد.');
        redirect('/overtime?' . http_build_query($_GET));
    }
}
