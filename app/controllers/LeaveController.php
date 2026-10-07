<?php
/**
 * مرخصی: انواع، تعادل، درخواست، تأیید، تعطیلات، تقویم
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\Log;
use App\Core\Notify;
use App\Core\Validator;

final class LeaveController extends Controller
{
    /** تعداد روزهای کاری میان دو تاریخ (شمسی) با لحاظ مرخصی و تعطیلات */
    public static function businessDays(string $fromJ, string $toJ, array $workDays = [6, 0, 1, 2, 3, 4]): int
    {
        $g1 = Jalali::j2g($fromJ);
        $g2 = Jalali::j2g($toJ);
        if ($g2 < $g1) return 0;
        $holidays = [];
        foreach (DB::all('SELECT date FROM holidays') as $h) {
            $holidays[$h['date']] = true;
        }
        $n = 0;
        $cur = strtotime($g1);
        $end = strtotime($g2);
        while ($cur <= $end) {
            $d = date('Y-m-d', $cur);
            if ((int)date('w', $cur) === 5) {
                // جمعه
            } elseif (isset($holidays[$d])) {
                // تعطیل رسمی
            } elseif (in_array((int)date('w', $cur), $workDays, true)) {
                $n++;
            }
            $cur = strtotime('+1 day', $cur);
        }
        return $n;
    }

    private function workDaysOf(?array $emp): array
    {
        $wd = $emp['work_days'] ?? setting('work_days', '6,0,1,2,3,4');
        return array_map('intval', explode(',', (string)$wd));
    }

    public function index(): void
    {
        $user = Auth::user();
        $canSeeAll = Auth::can('leave.approve') || Auth::can('leave.manage');
        $tab = (string)($_GET['tab'] ?? ($canSeeAll ? 'mine' : 'mine'));
        $status = (string)($_GET['status'] ?? ($tab === 'approve' ? 'pending' : 'all'));

        $where = ['1=1'];
        $params = [];
        if ($tab === 'mine') {
            $where[] = 'r.employee_id = ?';
            $params[] = (int)($user['employee_id'] ?? 0);
        }
        if ($status !== 'all') {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($tab === 'approve') {
            $where[] = "r.status = 'pending'";
        }
        $whereSql = implode(' AND ', $where);

        $rows = DB::all(
            "SELECT r.*, e.first_name, e.last_name, e.emp_no, lt.name type_name, lt.color type_color, a.username approver_name
             FROM leave_requests r
             JOIN employees e ON e.id = r.employee_id
             JOIN leave_types lt ON lt.id = r.type_id
             LEFT JOIN users a ON a.id = r.approver_id
             WHERE {$whereSql}
             ORDER BY r.created_at DESC LIMIT 200",
            $params
        );

        $empId = (int)($user['employee_id'] ?? 0);
        $balances = [];
        if ($empId) {
            $balances = DB::all(
                "SELECT b.*, lt.name, lt.color FROM leave_balances b JOIN leave_types lt ON lt.id = b.type_id
                 WHERE b.employee_id = ? AND b.year = ? ORDER BY lt.name",
                [$empId, (int)setting('leave_year', date('Y'))]
            );
        }
        $pendingCount = (int)DB::val("SELECT COUNT(*) FROM leave_requests WHERE status='pending'");
        $minYears = setting('leave_min_years', '1');

        $this->render('leave/index', [
            'title' => 'مرخصی',
            'rows' => $rows, 'tab' => $tab, 'status' => $status,
            'balances' => $balances, 'canSeeAll' => $canSeeAll,
            'pendingCount' => $pendingCount, 'minYears' => $minYears,
            'types' => DB::all('SELECT * FROM leave_types WHERE active = 1 ORDER BY name'),
            'myEmpId' => $empId,
        ]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = Auth::user();
        $empId = (int)($user['employee_id'] ?? 0);
        $canAny = Auth::can('leave.manage');
        if ($canAny && (int)post('employee_id', 0)) $empId = (int)post('employee_id');

        $v = Validator::make($_POST)
            ->check('type_id', 'required|integer', 'نوع مرخصی')
            ->check('from_date', 'required', 'از تاریخ')
            ->check('to_date', 'required', 'تا تاریخ')
            ->check('reason', 'required|min:3', 'دلیل');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/leave');
        }
        if (!$empId) {
            flash_set('error', 'پرونده کارمندی شما یافت نشد.');
            redirect('/leave');
        }

        $fromJ = trim((string)post('from_date'));
        $toJ = trim((string)post('to_date'));
        if (!preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $fromJ) || !preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $toJ)) {
            flash_set('error', 'تاریخ‌ها را با قالب شمسی وارد کنید (مثال: 1404/08/05).');
            redirect('/leave');
        }
        if ($toJ < $fromJ) {
            flash_set('error', 'تاریخ پایان باید بعد از تاریخ شروع باشد.');
            redirect('/leave');
        }
        $emp = DB::row('SELECT * FROM employees WHERE id = ?', [$empId]);
        if (!$emp) {
            flash_set('error', 'کارمند یافت نشد.');
            redirect('/leave');
        }

        $type = DB::row('SELECT * FROM leave_types WHERE id = ?', [(int)post('type_id')]);
        if (!$type) {
            flash_set('error', 'نوع مرخصی نامعتبر است.');
            redirect('/leave');
        }

        $days = self::businessDays($fromJ, $toJ, $this->workDaysOf($emp));

        // شرط سابقه (فقط مرخصی استحقاقی)
        if (($type['slug'] ?? '') === 'annual') {
            $expYears = max(0, (int)floor((strtotime('today') - strtotime($emp['hire_date'])) / 31557600));
            if ($expYears < (int)$minYears) {
                flash_set('error', 'برای مرخصی استحقاقلی حداقل ' . to_fa($minYears) . ' سال سابقه لازم است؛ سابقه شما ' . to_fa((string)$expYears) . ' سال است.');
                redirect('/leave');
            }
        }

        // همپوشانی
        $overlap = (int)DB::val(
            "SELECT COUNT(*) FROM leave_requests WHERE employee_id = ? AND status IN ('pending','approved')
             AND NOT (to_date < ? OR from_date > ?)",
            [$empId, $fromJ, $toJ]
        );
        if ($overlap > 0) {
            flash_set('error', 'در این بازه درخواست مرخصی دیگری (در انتظار یا تأییدشده) دارید.');
            redirect('/leave');
        }

        DB::insert('leave_requests', [
            'employee_id' => $empId, 'type_id' => (int)$type['id'],
            'from_date' => $fromJ, 'to_date' => $toJ, 'days' => $days,
            'reason' => trim((string)post('reason')), 'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('leave_create', 'درخواست مرخصی', ['days' => $days, 'type' => $type['name']]);
        Notify::toPermission('leave.approve', '🌴 درخواست مرخصی جدید',
            trim($emp['first_name'] . ' ' . $emp['last_name']) . ' — ' . $type['name'] . ' — ' . to_fa((string)$days) . ' روز',
            '/leave?tab=approve');
        flash_set('success', 'درخواست مرخصی (' . to_fa((string)$days) . ' روز کاری) ثبت شد و در انتظار تأیید است.');
        redirect('/leave?tab=mine');
    }

    public function approve(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $rec = DB::row('SELECT * FROM leave_requests WHERE id = ?', [(int)$id]);
        if (!$rec || $rec['status'] !== 'pending') {
            flash_set('error', 'درخواست یافت نشد یا قبلاً بررسی شده است.');
            redirect('/leave?tab=approve');
        }

        $bal = DB::row('SELECT * FROM leave_balances WHERE employee_id = ? AND type_id = ? AND year = ?',
            [$rec['employee_id'], $rec['type_id'], (int)setting('leave_year', date('Y'))]);
        $days = (float)$rec['days'];
        if ($bal && ((float)$bal['days_total'] - (float)$bal['days_used']) < $days) {
            flash_set('error', 'موجودی این نوع مرخصی کافی نیست؛ ابتدا تعادل را اصلاح کنید.');
            redirect('/leave?tab=approve');
        }

        DB::begin();
        try {
            if ($bal) {
                DB::update('leave_balances', ['days_used' => (float)$bal['days_used'] + $days], (int)$bal['id']);
            }
            DB::update('leave_requests', [
                'status' => 'approved', 'approver_id' => Auth::id(), 'decided_at' => date('Y-m-d H:i:s'),
            ], (int)$id);

            // ساخت رکورد حضور برای روزهای مرخصی
            $g1 = Jalali::j2g($rec['from_date']);
            $g2 = Jalali::j2g($rec['to_date']);
            $cur = strtotime($g1);
            $end = strtotime($g2);
            while ($cur <= $end) {
                $d = date('Y-m-d', $cur);
                $exists = DB::val('SELECT id FROM attendance WHERE employee_id = ? AND date = ?', [$rec['employee_id'], $d]);
                if ($exists) {
                    DB::update('attendance', ['status' => 'leave'], (int)$exists);
                } else {
                    DB::insert('attendance', [
                        'employee_id' => (int)$rec['employee_id'], 'date' => $d, 'status' => 'leave',
                        'source' => 'auto', 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $cur = strtotime('+1 day', $cur);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('leave_approve', 'خطای تأیید مرخصی: ' . $e->getMessage());
            flash_set('error', 'تأیید ناموفق بود؛ دوباره تلاش کنید.');
            redirect('/leave?tab=approve');
        }

        $u = DB::row('SELECT id FROM users WHERE employee_id = ?', [$rec['employee_id']]);
        if ($u) {
            Notify::toUser((int)$u['id'], '✅ مرخصی تأیید شد',
                $rec['from_date'] . ' تا ' . $rec['to_date'] . ' (' . to_fa((string)$rec['days']) . ' روز)',
                '/leave?tab=mine');
        }
        Log::info('leave_approve', 'تأیید مرخصی #' . $id);
        flash_set('success', 'مرخصی تأیید و در رکورد حضور لحاظ شد.');
        redirect('/leave?tab=approve');
    }

    public function reject(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $rec = DB::row('SELECT * FROM leave_requests WHERE id = ?', [(int)$id]);
        if (!$rec || $rec['status'] !== 'pending') {
            flash_set('error', 'درخواست یافت نشد.');
            redirect('/leave?tab=approve');
        }
        DB::update('leave_requests', [
            'status' => 'rejected', 'approver_id' => Auth::id(), 'decided_at' => date('Y-m-d H:i:s'),
        ], (int)$id);
        $u = DB::row('SELECT id FROM users WHERE employee_id = ?', [$rec['employee_id']]);
        if ($u) {
            Notify::toUser((int)$u['id'], '❌ مرخصی رد شد',
                $rec['from_date'] . ' تا ' . $rec['to_date'] . ' — ' . ($rec['reason'] ?: ''),
                '/leave?tab=mine');
        }
        Log::info('leave_reject', 'رد مرخصی #' . $id);
        flash_set('success', 'درخواست رد شد.');
        redirect('/leave?tab=approve');
    }

    public function cancel(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = Auth::user();
        $rec = DB::row('SELECT * FROM leave_requests WHERE id = ?', [(int)$id]);
        if (!$rec || (int)$rec['employee_id'] !== (int)($user['employee_id'] ?? 0) || $rec['status'] !== 'pending') {
            flash_set('error', 'فقط درخواست در انتظار خودتان قابل انصراف است.');
            redirect('/leave?tab=mine');
        }
        DB::update('leave_requests', ['status' => 'cancelled'], (int)$id);
        Log::info('leave_cancel', 'انصراف مرخصی #' . $id);
        flash_set('success', 'درخواست انصراف داده شد.');
        redirect('/leave?tab=mine');
    }

    /** تعادل مرخصی همه کارمندان (سرپرستان) */
    public function balances(): void
    {
        $year = (int)($_GET['year'] ?? setting('leave_year', date('Y')));
        $rows = DB::all(
            "SELECT b.*, e.first_name, e.last_name, e.emp_no, lt.name type_name, lt.color
             FROM leave_balances b
             JOIN employees e ON e.id = b.employee_id
             JOIN leave_types lt ON lt.id = b.type_id
             WHERE b.year = ?
             ORDER BY e.first_name, lt.name",
            [$year]
        );
        $types = DB::all('SELECT * FROM leave_types ORDER BY name');
        $emps = DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");

        $this->render('leave/balances', [
            'title' => 'تعادل مرخصی',
            'rows' => $rows, 'types' => $types, 'emps' => $emps, 'year' => $year,
        ]);
    }

    public function balancesSave(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $empId = (int)post('employee_id', 0);
        $typeId = (int)post('type_id', 0);
        $year = (int)post('year', (int)setting('leave_year', date('Y')));
        if (!$empId || !$typeId) {
            flash_set('error', 'کارمند و نوع مرخصی را انتخاب کنید.');
            redirect('/leave/balances');
        }
        $total = max(0, (float)post('days_total', 0));
        $used = max(0, (float)post('days_used', 0));
        $existing = DB::row('SELECT id FROM leave_balances WHERE employee_id=? AND type_id=? AND year=?', [$empId, $typeId, $year]);
        if ($existing) {
            DB::update('leave_balances', ['days_total' => $total, 'days_used' => $used], (int)$existing['id']);
        } else {
            DB::insert('leave_balances', ['employee_id' => $empId, 'type_id' => $typeId, 'year' => $year,
                'days_total' => $total, 'days_used' => $used]);
        }
        Log::info('balance_save', 'ویرایش تعادل مرخصی', ['emp' => $empId, 'type' => $typeId]);
        flash_set('success', 'تعادل ذخیره شد.');
        redirect('/leave/balances?year=' . $year);
    }

    public function types(): void
    {
        $rows = DB::all('SELECT * FROM leave_types ORDER BY name');
        $this->render('leave/types', ['title' => 'انواع مرخصی', 'rows' => $rows]);
    }

    public function typesSave(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)->check('name', 'required|min:2', 'نام');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/leave/types');
        }
        $paid = post('paid') !== null && post('paid') !== '0' ? 1 : 0;
        DB::insert('leave_types', [
            'name' => trim((string)post('name')),
            'slug' => 't' . substr(md5((string)microtime(true) . (string)mt_rand()), 0, 8),
            'paid' => $paid,
            'deduct_pay' => $paid ? 0 : 1,
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)post('color', '')) ? (string)post('color') : '#4f46e5',
            'annual_days' => (int)post('annual_days', 0),
            'active' => 1, 'sort' => 99,
        ]);
        flash_set('success', 'نوع مرخصی افزوده شد.');
        redirect('/leave/types');
    }

    public function holidays(): void
    {
        $rows = DB::all('SELECT * FROM holidays ORDER BY date');
        $this->render('leave/holidays', ['title' => 'تعطیلات رسمی', 'rows' => $rows]);
    }

    public function holidaysSave(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $title = trim((string)post('title', ''));
        $from = trim((string)post('from_date', ''));
        $to = trim((string)post('to_date', $from));
        if ($title === '' || !preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $from) || !preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $to)) {
            flash_set('error', 'عنوان و بازه تاریخ شمسی معتبر لازم است (1404/01/01).');
            redirect('/leave/holidays');
        }
        // تبدیل بازه به روزهای میلادی
        $g1 = Jalali::j2g($from);
        $g2 = Jalali::j2g($to);
        $cur = strtotime($g1);
        $end = strtotime($g2);
        $count = 0;
        while ($cur <= $end && $count < 400) {
            $d = date('Y-m-d', $cur);
            if (!DB::val('SELECT id FROM holidays WHERE date = ?', [$d])) {
                DB::insert('national_holidays', ['date' => $d, 'title' => $title]);
                $count++;
            }
            $cur = strtotime('+1 day', $cur);
        }
        Log::info('holiday_add', 'افزودن تعطیلات', ['title' => $title, 'count' => $count]);
        flash_set('success', to_fa((string)$count) . ' روز تعطیل افزوده شد.');
        redirect('/leave/holidays');
    }

    public function holidayDelete(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        DB::delete('holidays', (int)$id);
        flash_set('success', 'تعطیلی حذف شد.');
        redirect('/leave/holidays');
    }

    /** تقویم ماهانه مرخصی‌ها */
    public function calendar(): void
    {
        $m = (int)($_GET['m'] ?? date('n'));
        $y = (int)($_GET['y'] ?? date('Y'));
        if ($m < 1) { $m = 12; $y--; }
        if ($m > 12) { $m = 1; $y++; }

        $first = sprintf('%04d-%02d-01', $y, $m);
        $lastDay = (int)date('t', strtotime($first));
        $last = sprintf('%04d-%02d-%02d', $y, $m, $lastDay);
        $startDow = (int)date('w', strtotime($first)); // 0=یکشنبه

        $leaves = DB::all(
            "SELECT r.from_date, r.to_date, r.status, e.first_name, e.last_name, lt.name type_name, lt.color
             FROM leave_requests r
             JOIN employees e ON e.id = r.employee_id
             JOIN leave_types lt ON lt.id = r.type_id
             WHERE r.status = 'approved'"
        );
        $holidays = [];
        foreach (DB::all('SELECT date, title FROM holidays') as $h) {
            $holidays[$h['date']] = $h['title'];
        }

        // نگاشت هر روز میلادی به رویدادها
        $byDay = [];
        foreach ($leaves as $l) {
            $g1 = Jalali::j2g($l['from_date']);
            $g2 = Jalali::j2g($l['to_date']);
            $cur = strtotime($g1);
            $end = strtotime($g2);
            while ($cur <= $end) {
                $d = date('Y-m-d', $cur);
                $byDay[$d][] = ['name' => trim($l['first_name'] . ' ' . $l['last_name']),
                                'type' => $l['type_name'], 'color' => $l['color']];
                $cur = strtotime('+1 day', $cur);
            }
        }

        $cells = [];
        for ($i = 0; $i < $startDow; $i++) $cells[] = null;
        for ($d = 1; $d <= $lastDay; $d++) {
            $date = sprintf('%04d-%02d-%02d', $y, $m, $d);
            $cells[] = [
                'date' => $date, 'day' => $d,
                'jalali' => Jalali::format($date),
                'isToday' => $date === date('Y-m-d'),
                'isFriday' => (int)date('w', strtotime($date)) === 5,
                'holiday' => $holidays[$date] ?? null,
                'leaves' => $byDay[$date] ?? [],
            ];
        }

        $this->render('leave/calendar', [
            'title' => 'تقویم مرخصی‌ها',
            'cells' => $cells, 'm' => $m, 'y' => $y,
            'prev' => ['m' => $m - 1, 'y' => $y], 'next' => ['m' => $m + 1, 'y' => $y],
            'jalaliHead' => Jalali::format($first) . ' — ' . Jalali::format($last),
        ]);
    }
}
