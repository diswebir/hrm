<?php
/**
 * درخواست‌ها و انتقادات عمومی
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Notify;
use App\Core\Validator;

final class RequestsController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $canSeeAll = Auth::can('requests.approve');
        $tab = (string)($_GET['tab'] ?? 'mine');

        if ($tab === 'approve' && $canSeeAll) {
            $rows = DB::all(
                "SELECT r.*, e.first_name, e.last_name, e.emp_no, u.username decider
                 FROM requests r
                 JOIN employees e ON e.id = r.employee_id
                 LEFT JOIN users u ON u.id = r.approver_id
                 WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 200"
            );
        } elseif ($tab === 'all' && $canSeeAll) {
            $rows = DB::all(
                "SELECT r.*, e.first_name, e.last_name, e.emp_no, u.username decider
                 FROM requests r
                 JOIN employees e ON e.id = r.employee_id
                 LEFT JOIN users u ON u.id = r.approver_id
                 ORDER BY r.created_at DESC LIMIT 200"
            );
        } else {
            $rows = DB::all(
                "SELECT r.*, e.first_name, e.last_name, e.emp_no, u.username decider
                 FROM requests r
                 JOIN employees e ON e.id = r.employee_id
                 LEFT JOIN users u ON u.id = r.approver_id
                 WHERE r.employee_id = ? ORDER BY r.created_at DESC LIMIT 100",
                [(int)($user['employee_id'] ?? 0)]
            );
            $tab = 'mine';
        }

        $pending = $canSeeAll ? (int)DB::val("SELECT COUNT(*) FROM requests WHERE status='pending'") : 0;

        $this->render('requests/index', [
            'title' => 'درخواست‌ها',
            'rows' => $rows, 'tab' => $tab, 'canSeeAll' => $canSeeAll, 'pending' => $pending,
            'myEmpId' => (int)($user['employee_id'] ?? 0),
            'types' => ['general' => 'عمومی', 'facilities' => 'امکانات و تجهیزات', 'complaint' => 'شکایت',
                        'suggestion' => 'پیشنهاد', 'it' => 'درخواست فناوری اطلاعات', 'other' => 'سایر'],
        ]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = Auth::user();
        $empId = (int)($user['employee_id'] ?? 0);
        if (!$empId) {
            flash_set('error', 'پرونده کارمندی ندارید.');
            redirect('/requests');
        }
        $v = Validator::make($_POST)
            ->check('title', 'required|min:3|max:140', 'عنوان')
            ->check('body', 'required|min:5', 'شرح درخواست');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/requests');
        }
        $type = (string)post('type', 'general');
        if (!isset($this->types()[$type])) $type = 'general';

        DB::insert('requests', [
            'employee_id' => $empId, 'type' => $type,
            'title' => trim((string)post('title')), 'body' => trim((string)post('body')),
            'status' => 'pending', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('request_create', 'درخواست جدید', ['type' => $type]);
        Notify::toPermission('requests.approve', '📮 درخواست جدید', (string)post('title'), '/requests?tab=approve');
        flash_set('success', 'درخواست ثبت شد و در انتظار بررسی است.');
        redirect('/requests?tab=mine');
    }

    private function types(): array
    {
        return ['general' => 1, 'facilities' => 1, 'complaint' => 1, 'suggestion' => 1, 'it' => 1, 'other' => 1];
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
        $rec = DB::row('SELECT * FROM requests WHERE id = ?', [(int)$id]);
        if (!$rec || $rec['status'] !== 'pending') {
            flash_set('error', 'درخواست یافت نشد یا قبلاً بررسی شده است.');
            redirect('/requests?tab=approve');
        }
        DB::update('requests', [
            'status' => $decision, 'approver_id' => Auth::id(),
            'decided_at' => date('Y-m-d H:i:s'),
            'decide_note' => trim((string)post('note', '')),
        ], (int)$id);
        $u = DB::row('SELECT id FROM users WHERE employee_id = ?', [$rec['employee_id']]);
        if ($u) {
            Notify::toUser((int)$u['id'],
                $decision === 'approved' ? '✅ درخواست شما تأیید شد' : '❌ درخواست شما رد شد',
                $rec['title'], '/requests?tab=mine');
        }
        Log::info('request_' . $decision, 'تصمیم درخواست #' . $id);
        flash_set('success', 'درخواست ' . ($decision === 'approved' ? 'تأیید' : 'رد') . ' شد.');
        redirect('/requests?tab=approve');
    }
}
