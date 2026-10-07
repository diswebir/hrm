<?php
/**
 * عملکرد: اهداف، ارزیابی، تشویق/تنبیه
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Notify;
use App\Core\Validator;

final class PerformanceController extends Controller
{
    public function goals(): void
    {
        $user = Auth::user();
        $scope = (string)($_GET['scope'] ?? 'mine');
        if ($scope === 'all' && (Auth::can('performance.manage') || Auth::isSuper())) {
            $rows = DB::all(
                "SELECT g.*, e.first_name, e.last_name, e.emp_no
                 FROM goals g JOIN employees e ON e.id = g.employee_id
                 ORDER BY g.due_date, e.first_name LIMIT 300"
            );
        } else {
            $scope = 'mine';
            $rows = DB::all(
                "SELECT g.*, e.first_name, e.last_name, e.emp_no
                 FROM goals g JOIN employees e ON e.id = g.employee_id
                 WHERE g.employee_id = ? ORDER BY g.due_date",
                [(int)($user['employee_id'] ?? 0)]
            );
        }
        $emps = DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");
        $avg = $rows ? array_sum(array_map(fn($g) => (float)$g['score'], $rows)) / count($rows) : 0;

        $this->render('performance/goals', [
            'title' => 'اهداف عملکردی',
            'rows' => $rows, 'scope' => $scope, 'emps' => $emps, 'avg' => $avg,
            'canAll' => Auth::can('performance.manage') || Auth::isSuper(),
            'myEmpId' => (int)($user['employee_id'] ?? 0),
        ]);
    }

    public function goalStore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = Auth::user();
        $canAny = Auth::can('performance.manage');
        $empId = $canAny ? (int)post('employee_id', 0) : (int)($user['employee_id'] ?? 0);
        if (!$empId) $empId = (int)($user['employee_id'] ?? 0);

        $v = Validator::make($_POST)
            ->check('title', 'required|min:3', 'عنوان هدف')
            ->check('weight', 'required|integer|min:1|max:100', 'وزن');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/performance/goals');
        }
        $due = trim((string)post('due_date', ''));
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $due)) {
            $due = \App\Core\Jalali::j2g($due);
        }
        DB::insert('goals', [
            'employee_id' => $empId,
            'title' => trim((string)post('title')),
            'description' => trim((string)post('description', '')),
            'weight' => (int)post('weight'),
            'period' => trim((string)post('period', date('Y'))),
            'target_value' => (float)post('target_value', 0),
            'actual_value' => 0, 'score' => 0,
            'status' => 'active',
            'due_date' => $due ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('goal_create', 'هدف جدید', ['emp' => $empId]);
        flash_set('success', 'هدف ثبت شد.');
        redirect('/performance/goals?scope=' . ($canAny ? 'all' : 'mine'));
    }

    public function goalScore(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $g = DB::row('SELECT * FROM goals WHERE id = ?', [(int)$id]);
        if (!$g) {
            flash_set('error', 'هدف یافت نشد.');
            redirect('/performance/goals');
        }
        $score = min(100, max(0, (float)post('score', 0)));
        $actual = (float)post('actual_value', 0);
        DB::update('goals', [
            'score' => $score, 'actual_value' => $actual,
            'status' => $score >= 100 ? 'done' : ($score > 0 ? 'active' : 'active'),
        ], (int)$id);
        Log::info('goal_score', 'نمره هدف', ['id' => $id, 'score' => $score]);
        flash_set('success', 'نمره هدف ذخیره شد.');
        redirect('/performance/goals?scope=' . (string)($_GET['scope'] ?? 'mine'));
    }

    public function reviews(): void
    {
        $user = Auth::user();
        $rows = DB::all(
            "SELECT rv.*, e.first_name, e.last_name, e.emp_no, ru.username reviewer
             FROM reviews rv
             JOIN employees e ON e.id = rv.employee_id
             LEFT JOIN users ru ON ru.id = rv.reviewer_id
             ORDER BY rv.created_at DESC LIMIT 200"
        );
        $emps = DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");

        $this->render('performance/reviews', [
            'title' => 'ارزیابی عملکرد',
            'rows' => $rows, 'emps' => $emps,
            'period' => (int)date('Y'),
            'avgAll' => $rows ? array_sum(array_map(fn($r) => (float)$r['overall_score'], $rows)) / count($rows) : 0,
        ]);
    }

    public function reviewStore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)
            ->check('employee_id', 'required|integer', 'کارمند')
            ->check('period', 'required', 'دوره');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/performance/reviews');
        }
        // میانگین چهار شاخص ۱ تا ۵
        $q = min(5, max(1, (int)post('q1', 3)));
        $d = min(5, max(1, (int)post('q2', 3)));
        $c = min(5, max(1, (int)post('q3', 3)));
        $t = min(5, max(1, (int)post('q4', 3)));
        $overall = round((($q + $d + $c + $t) / 4) * 20, 1); // از ۱۰۰

        DB::insert('reviews', [
            'employee_id' => (int)post('employee_id'),
            'reviewer_id' => Auth::id(),
            'period' => trim((string)post('period')),
            'overall_score' => $overall,
            'strengths' => trim((string)post('strengths', '')),
            'improvements' => trim((string)post('improvements', '')),
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('review_submit', 'ارزیابی عملکرد', ['emp' => post('employee_id'), 'score' => $overall]);
        $u = DB::row('SELECT id FROM users WHERE employee_id = ?', [(int)post('employee_id')]);
        if ($u) {
            Notify::toUser((int)$u['id'], '📊 ارزیابی عملکرد ثبت شد',
                'نمره دوره ' . e((string)post('period')) . ': ' . to_fa(number_format($overall, 1)) . ' از ۱۰۰',
                '/performance/reviews');
        }
        flash_set('success', 'ارزیابی با نمره ' . to_fa(number_format($overall, 1)) . ' ثبت شد.');
        redirect('/performance/reviews');
    }

    public function penalty(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)
            ->check('employee_id', 'required|integer', 'کارمند')
            ->check('amount', 'required|numeric|min:0', 'مبلغ')
            ->check('reason', 'required|min:3', 'دلیل');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/performance/penalties');
        }
        $kind = post('kind') === 'reward' ? 'reward' : 'penalty';
        $date = trim((string)post('date', ''));
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date)) $date = \App\Core\Jalali::j2g($date);
        DB::insert('rewards_penalties', [
            'employee_id' => (int)post('employee_id'),
            'kind' => $kind,
            'amount' => (float)post('amount'),
            'date' => $date ?: date('Y-m-d'),
            'reason' => trim((string)post('reason')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('rp_add', $kind === 'reward' ? 'پاداش' : 'جریمه', ['emp' => post('employee_id')]);
        flash_set('success', $kind === 'reward' ? 'پاداش ثبت شد.' : 'جریمه ثبت شد.');
        redirect('/performance/penalties');
    }

    public function penalties(): void
    {
        $rows = DB::all(
            "SELECT rp.*, e.first_name, e.last_name, e.emp_no
             FROM rewards_penalties rp JOIN employees e ON e.id = rp.employee_id
             ORDER BY rp.date DESC LIMIT 200"
        );
        $disc = DB::all(
            "SELECT d.*, e.first_name, e.last_name, e.emp_no
             FROM discipline_records d JOIN employees e ON e.id = d.employee_id
             ORDER BY d.date DESC LIMIT 200"
        );
        $sumR = (float)DB::val("SELECT COALESCE(SUM(amount),0) FROM rewards_penalties WHERE kind='reward'");
        $sumP = (float)DB::val("SELECT COALESCE(SUM(amount),0) FROM rewards_penalties WHERE kind='penalty'");
        $emps = DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");

        $this->render('performance/penalties', [
            'title' => 'تشویق و تنبیه',
            'rows' => $rows, 'disc' => $disc, 'emps' => $emps,
            'sumR' => $sumR, 'sumP' => $sumP,
        ]);
    }

    public function disciplineStore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $empId = (int)post('employee_id', 0);
        $title = trim((string)post('title', ''));
        if (!$empId || $title === '') {
            flash_set('error', 'کارمند و عنوان ضروری است.');
            redirect('/performance/penalties');
        }
        $date = trim((string)post('date', ''));
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date)) $date = \App\Core\Jalali::j2g($date);
        DB::insert('discipline_records', [
            'employee_id' => $empId,
            'date' => $date ?: date('Y-m-d'),
            'kind' => in_array(post('kind'), ['warning', 'reprimand', 'suspension', 'dismissal'], true) ? post('kind') : 'warning',
            'title' => $title,
            'description' => trim((string)post('description', '')),
            'action' => trim((string)post('action', '')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('discipline_add', 'ثبت سوءسابقه', ['emp' => $empId]);
        flash_set('success', 'سوابق انضباطی ثبت شد.');
        redirect('/performance/penalties');
    }
}
