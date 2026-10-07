<?php
/**
 * حقوق و دستمزد — مطابق اسکیمای payslips/payroll_periods/payroll_lines
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Validator;

final class PayrollController extends Controller
{
    /** یافتن یا ساخت دوره */
    private function period(int $year, int $month): int
    {
        $p = DB::row('SELECT id FROM payroll_periods WHERE year = ? AND month = ?', [$year, $month]);
        if ($p) return (int)$p['id'];
        return (int)DB::insert('payroll_periods', [
            'year' => $year, 'month' => $month, 'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** محاسبه فیش یک کارمند در دوره */
    private function runCalc(int $periodId, int $empId): float
    {
        $emp = DB::row('SELECT * FROM employees WHERE id = ?', [$empId]);
        if (!$emp) throw new \RuntimeException('کارمند یافت نشد.');

        $base = (float)$emp['salary_base'];
        $assigned = DB::all(
            "SELECT ec.amount assign_amount, sc.id comp_id, sc.code, sc.name, sc.type, sc.calc_type,
                    sc.default_amount, sc.formula, sc.insurance_rate
             FROM employee_components ec
             JOIN salary_components sc ON sc.id = ec.component_id
             WHERE ec.employee_id = ? AND sc.active = 1",
            [$empId]
        );
        $byCode = [];
        foreach ($assigned as $a) $byCode[$a['code']] = $a;

        $earn = ['حقوق پایه' => round($base)];
        $ded = [];
        $tax = 0.0;

        // تسهیلات فعال: قسط ماهانه
        $loanAmt = (float)DB::val(
            "SELECT COALESCE(SUM(installment),0) FROM loans WHERE employee_id = ? AND status IN ('active','approved')",
            [$empId]
        );
        // اضافه‌کاری تأییدشده ماه جاری دوره
        $per = DB::row('SELECT year, month FROM payroll_periods WHERE id = ?', [$periodId]);
        $otSum = (float)DB::val(
            "SELECT COALESCE(SUM(hours * multiplier * 1.0),0) FROM overtime_requests
             WHERE employee_id = ? AND status = 'approved' AND strftime('%Y-%m', date) = ?",
            [$empId, sprintf('%04d-%02d', (int)$per['year'], (int)$per['month'])]
        );
        $baseHour = $base > 0 ? ($base / 30 / 8) : 0;

        foreach ($assigned as $a) {
            $amount = max(0.0, (float)$a['assign_amount']);
            if ($amount <= 0.0) $amount = max(0.0, (float)$a['default_amount']);
            $code = $a['code'];

            if ($a['type'] === 'earning') {
                if ($code === 'BASE' || $a['calc_type'] === 'base') continue; // پایه جدا
                if ($code === 'OT' || $a['calc_type'] === 'overtime') {
                    $amount = $otSum * $baseHour;
                }
                $earn[$a['name']] = round($amount);
            } else {
                if ($code === 'BIME' || $a['calc_type'] === 'percent_base') {
                    $rate = (float)$a['insurance_rate'] > 0 ? (float)$a['insurance_rate'] : 0.0967;
                    $amount = $base * $rate;
                } elseif ($code === 'LOAN' || $a['calc_type'] === 'loans') {
                    $amount = $loanAmt;
                } elseif ($code === 'TAX' || $a['calc_type'] === 'tax') {
                    continue; // مالیات جداگانه
                }
                if ($amount > 0) $ded[$a['name']] = round($amount);
            }
        }
        if (!isset($byCode['LOAN']) && $loanAmt > 0) $ded['اقساط وام'] = round($loanAmt);
        if ($otSum > 0 && !isset($byCode['OT'])) $earn['اضافه‌کاری'] = round($otSum * $baseHour);

        // مالیات بر درآمد (نرخ مصوب ساده‌شده)
        $gross = array_sum($earn);
        $pension = array_sum($ded);
        $taxable = $gross - $pension - 14000000; // معافیت ماهانه (تومان-معادل ریال)
        if ($taxable > 0) {
            $tax = min($taxable * 0.10, max(0, $base * 0.10));
        }
        $ded['مالیات'] = round($tax);
        $totalDed = array_sum($ded);
        $net = $gross - $totalDed;

        // کارکرد/غیبت ماه
        $ym = sprintf('%04d-%02d', (int)$per['year'], (int)$per['month']);
        $worked = (int)DB::val(
            "SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND strftime('%Y-%m', date) = ? AND status IN ('present','late','remote')",
            [$empId, $ym]
        );
        $absent = (int)DB::val(
            "SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND strftime('%Y-%m', date) = ? AND status = 'absent'",
            [$empId, $ym]
        );

        $details = json_encode(['earnings' => $earn, 'deductions' => $ded], JSON_UNESCAPED_UNICODE);
        $existing = DB::row('SELECT id FROM payslips WHERE period_id = ? AND employee_id = ?',
            [$periodId, $empId]);
        $data = [
            'base_salary' => round($base), 'gross_earn' => round($gross),
            'total_deduct' => round($totalDed), 'tax' => round($tax),
            'net_pay' => round($net), 'worked_days' => $worked, 'absent_days' => $absent,
            'details_json' => $details,
        ];
        if ($existing) {
            DB::update('payslips', $data, (int)$existing['id']);
            $slipId = (int)$existing['id'];
        } else {
            $slipId = (int)DB::insert('payslips', [
                'period_id' => $periodId, 'employee_id' => $empId,
                'created_at' => date('Y-m-d H:i:s'),
            ] + $data);
        }

        // خطوط: اجزای تخصیص‌یافته + پایه
        DB::run('DELETE FROM payroll_lines WHERE period_id = ? AND employee_id = ?', [$periodId, $empId]);
        foreach ($assigned as $a) {
            $code = $a['code'];
            if ($code === 'BASE') continue;
            $amount = $earn[$a['name']] ?? ($ded[$a['name']] ?? null);
            if ($amount === null) continue;
            DB::insert('payroll_lines', [
                'period_id' => $periodId, 'employee_id' => $empId,
                'component_id' => (int)$a['comp_id'], 'amount' => (float)$amount,
            ]);
        }
        $baseComp = DB::val("SELECT id FROM salary_components WHERE code = 'BASE'");
        if ($baseComp) {
            DB::insert('payroll_lines', [
                'period_id' => $periodId, 'employee_id' => $empId,
                'component_id' => (int)$baseComp, 'amount' => round($base),
            ]);
        }
        return (float)$net;
    }

    public function index(): void
    {
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $periodRec = DB::row('SELECT * FROM payroll_periods WHERE year = ? AND month = ?', [$year, $month]);
        $slips = [];
        if ($periodRec) {
            $slips = DB::all(
                "SELECT p.*, e.first_name, e.last_name, e.emp_no, pp.status AS period_status, pp.year, pp.month
                 FROM payslips p
                 JOIN employees e ON e.id = p.employee_id
                 JOIN payroll_periods pp ON pp.id = p.period_id
                 WHERE p.period_id = ? ORDER BY e.emp_no",
                [(int)$periodRec['id']]
            );
        }
        $sumGross = array_sum(array_map(fn($s) => (float)$s['gross_earn'], $slips));
        $sumDed = array_sum(array_map(fn($s) => (float)$s['total_deduct'], $slips));
        $sumNet = array_sum(array_map(fn($s) => (float)$s['net_pay'], $slips));

        $components = DB::all('SELECT * FROM salary_components ORDER BY type, name');
        $emps = DB::all("SELECT id, first_name, last_name, emp_no, salary_base FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");
        $assignments = DB::all(
            "SELECT ec.id, ec.amount, sc.name, e.first_name, e.last_name, e.emp_no
             FROM employee_components ec
             JOIN salary_components sc ON sc.id = ec.component_id
             JOIN employees e ON e.id = ec.employee_id
             ORDER BY e.first_name, sc.name LIMIT 80"
        );

        $this->render('payroll/index', [
            'title' => 'حقوق و دستمزد',
            'year' => $year, 'month' => $month, 'periodRec' => $periodRec,
            'slips' => $slips, 'components' => $components, 'emps' => $emps,
            'assignments' => $assignments,
            'sumGross' => $sumGross, 'sumDed' => $sumDed, 'sumNet' => $sumNet,
        ]);
    }

    public function run(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $year = (int)post('year', date('Y'));
        $month = (int)post('month', date('n'));
        $empId = (int)post('employee_id', 0);
        if ($empId) {
            $ids = [$empId];
        } else {
            $ids = array_map('intval', array_column(
                DB::all("SELECT id FROM employees WHERE deleted_at IS NULL AND status='active'"),
                'id'
            ));
        }
        $periodId = $this->period($year, $month);
        $count = 0;
        try {
            foreach ($ids as $id) {
                $this->runCalc($periodId, $id);
                $count++;
            }
        } catch (\RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/payroll');
        }
        Log::info('payroll_run', 'اجرای محاسبه حقوق', ['period' => $year . '-' . $month, 'count' => $count]);
        flash_set('success', 'محاسبه ' . to_fa((string)$count) . ' فیش انجام شد.');
        redirect('/payroll?year=' . $year . '&month=' . $month);
    }

    public function finalize(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $p = DB::row('SELECT * FROM payroll_periods WHERE id = ?', [(int)$id]);
        if (!$p) {
            flash_set('error', 'دوره یافت نشد.');
            redirect('/payroll');
        }
        $n = (int)DB::val('SELECT COUNT(*) FROM payslips WHERE period_id = ?', [(int)$id]);
        DB::update('payroll_periods', [
            'status' => 'final', 'locked_at' => date('Y-m-d H:i:s'),
        ], (int)$id);
        Log::info('payroll_finalize', 'نهایی‌سازی دوره', ['period_id' => $id, 'count' => $n]);
        flash_set('success', 'دوره نهایی و قفل شد (' . to_fa((string)$n) . ' فیش).');
        redirect('/payroll?year=' . (int)$p['year'] . '&month=' . (int)$p['month']);
    }

    public function copy(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $fromY = (int)post('from_year', 0);
        $fromM = (int)post('from_month', 0);
        $toY = (int)post('to_year', (int)date('Y'));
        $toM = (int)post('to_month', (int)date('n'));
        $src = DB::row('SELECT id FROM payroll_periods WHERE year = ? AND month = ?', [$fromY, $fromM]);
        if (!$src) {
            flash_set('error', 'دوره مبدأ فیشی ندارد.');
            redirect('/payroll');
        }
        $dst = $this->period($toY, $toM);
        $srcSlips = DB::all('SELECT * FROM payslips WHERE period_id = ?', [(int)$src['id']]);
        $count = 0;
        foreach ($srcSlips as $s) {
            if (DB::val('SELECT id FROM payslips WHERE period_id = ? AND employee_id = ?', [$dst, $s['employee_id']])) {
                continue;
            }
            DB::insert('payslips', [
                'period_id' => $dst, 'employee_id' => $s['employee_id'],
                'base_salary' => $s['base_salary'], 'gross_earn' => $s['gross_earn'],
                'total_deduct' => $s['total_deduct'], 'tax' => $s['tax'], 'net_pay' => $s['net_pay'],
                'worked_days' => $s['worked_days'], 'absent_days' => $s['absent_days'],
                'details_json' => $s['details_json'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }
        Log::info('payroll_copy', 'کپی دوره', ['from' => $fromY . '/' . $fromM, 'to' => $toY . '/' . $toM, 'n' => $count]);
        flash_set('success', to_fa((string)$count) . ' فیش کپی شد.');
        redirect('/payroll?year=' . $toY . '&month=' . $toM);
    }

    // ---------- اجزای حقوق ----------

    public function componentSave(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)->check('title', 'required|min:2', 'عنوان');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/payroll?tab=components');
        }
        $kind = in_array(post('kind'), ['earning', 'deduction'], true) ? post('kind') : 'earning';
        DB::insert('salary_components', [
            'code' => 'C' . strtoupper(substr(md5((string)microtime(true)), 0, 5)),
            'name' => trim((string)post('title')),
            'type' => $kind,
            'calc_type' => in_array(post('calc_type'), ['fixed', 'percent_base'], true) ? (string)post('calc_type') : 'fixed',
            'formula' => trim((string)post('formula', '')),
            'default_amount' => (float)post('rate', 0),
            'active' => 1, 'is_system' => 0, 'sort' => 50,
        ]);
        flash_set('success', 'جزو حقوق افزوده شد.');
        redirect('/payroll?tab=components');
    }

    public function componentDelete(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $comp = DB::row('SELECT * FROM salary_components WHERE id = ?', [(int)$id]);
        if ($comp && (int)$comp['is_system']) {
            flash_set('error', 'جزو سیستمی قابل حذف نیست.');
            redirect('/payroll?tab=components');
        }
        $used = (int)DB::val('SELECT COUNT(*) FROM employee_components WHERE component_id = ?', [(int)$id]);
        if ($used) {
            flash_set('error', 'این جزو به ' . to_fa((string)$used) . ' کارمند تخصیص دارد؛ ابتدا تخصیص‌ها را حذف کنید.');
            redirect('/payroll?tab=components');
        }
        DB::delete('salary_components', (int)$id);
        flash_set('success', 'حذف شد.');
        redirect('/payroll?tab=components');
    }

    public function componentAssign(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $empId = (int)post('employee_id', 0);
        $compId = (int)post('component_id', 0);
        $amount = (float)post('value', 0);
        if (!$empId || !$compId) {
            flash_set('error', 'کارمند و جزو را انتخاب کنید.');
            redirect('/payroll?tab=components');
        }
        $exists = DB::val('SELECT id FROM employee_components WHERE employee_id = ? AND component_id = ?',
            [$empId, $compId]);
        if ($exists) {
            DB::update('employee_components', ['amount' => $amount], (int)$exists);
        } else {
            DB::insert('employee_components', ['employee_id' => $empId, 'component_id' => $compId, 'amount' => $amount]);
        }
        flash_set('success', 'تخصیص ذخیره شد.');
        redirect('/payroll?tab=components');
    }

    public function componentUnassign(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        DB::delete('employee_components', (int)$id);
        flash_set('success', 'تخصیص حذف شد.');
        redirect('/payroll?tab=components');
    }

    // ---------- وام کارمندان ----------

    public function loans(): void
    {
        $rows = DB::all(
            "SELECT l.*, e.first_name, e.last_name, e.emp_no,
                    (SELECT COALESCE(SUM(amount),0) FROM loan_payments WHERE loan_id = l.id) paid
             FROM loans l JOIN employees e ON e.id = l.employee_id
             ORDER BY l.status, l.created_at DESC"
        );
        $emps = DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");
        $this->render('payroll/loans', ['title' => 'وام‌ها', 'rows' => $rows, 'emps' => $emps]);
    }

    public function loanSave(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $empId = (int)post('employee_id', 0);
        $amount = (float)post('amount', 0);
        $months = (int)post('months', 1);
        if (!$empId || $amount < 10000 || $months < 1 || $months > 60) {
            flash_set('error', 'کارمند، مبلغ (حداقل ۱۰٬۰۰۰) و اقساط ۱ تا ۶۰ ماه را درست وارد کنید.');
            redirect('/payroll/loans');
        }
        $start = trim((string)post('start_date', ''));
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $start)) {
            $start = \App\Core\Jalali::j2g($start);
        }
        DB::insert('loans', [
            'employee_id' => $empId, 'amount' => $amount,
            'installment' => round($amount / $months, -3),
            'months' => $months, 'paid_installments' => 0,
            'start_date' => $start ?: date('Y-m-d'),
            'reason' => trim((string)post('reason', '')),
            'status' => 'active', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('loan_create', 'ثبت وام', ['emp' => $empId, 'amount' => $amount]);
        flash_set('success', 'وام ثبت شد و به کسورات حقوق افزوده خواهد شد.');
        redirect('/payroll/loans');
    }

    public function loanPay(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $loan = DB::row('SELECT * FROM loans WHERE id = ?', [(int)$id]);
        if (!$loan) {
            flash_set('error', 'وام یافت نشد.');
            redirect('/payroll/loans');
        }
        if ((int)$loan['paid_installments'] >= (int)$loan['months']) {
            flash_set('info', 'همه اقساط این وام پرداخت شده است.');
            redirect('/payroll/loans');
        }
        DB::insert('loan_payments', [
            'loan_id' => (int)$id, 'amount' => (float)$loan['installment'],
            'paid_date' => date('Y-m-d'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $paid = (int)$loan['paid_installments'] + 1;
        DB::update('loans', [
            'paid_installments' => $paid,
            'status' => $paid >= (int)$loan['months'] ? 'closed' : 'active',
        ], (int)$id);
        Log::info('loan_pay', 'پرداخت قسط وام', ['loan' => $id, 'n' => $paid]);
        flash_set('success', 'قسط ' . to_fa((string)$paid) . ' ثبت شد' .
            ($paid >= (int)$loan['months'] ? ' — وام تسویه شد 🎉' : '.'));
        redirect('/payroll/loans');
    }

    public function loanDelete(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        DB::run('DELETE FROM loan_payments WHERE loan_id = ?', [(int)$id]);
        DB::delete('loans', (int)$id);
        Log::info('loan_delete', 'حذف وام', ['id' => $id]);
        flash_set('success', 'وام و اقساطش حذف شد.');
        redirect('/payroll/loans');
    }

    public function slip(string $id): void
    {
        $slip = DB::row(
            "SELECT p.*, pp.year, pp.month, pp.status AS period_status,
                    e.first_name, e.last_name, e.emp_no, e.national_code, e.bank_account
             FROM payslips p
             JOIN payroll_periods pp ON pp.id = p.period_id
             JOIN employees e ON e.id = p.employee_id
             WHERE p.id = ?",
            [(int)$id]
        );
        if (!$slip) {
            http_response_code(404);
            $this->render('errors/404', ['title' => '404']);
            return;
        }
        $details = json_decode((string)$slip['details_json'], true) ?: [];
        $this->render('payroll/slip', [
            'title' => 'فیش حقوق ' . $slip['emp_no'],
            'slip' => $slip,
            'earnings' => $details['earnings'] ?? [],
            'deductions' => $details['deductions'] ?? [],
        ]);
    }

    public function export(): void
    {
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        $slips = DB::all(
            "SELECT p.*, pp.status period_status, e.first_name, e.last_name, e.emp_no
             FROM payslips p
             JOIN payroll_periods pp ON pp.id = p.period_id
             JOIN employees e ON e.id = p.employee_id
             WHERE pp.year = ? AND pp.month = ? ORDER BY e.emp_no",
            [$year, $month]
        );
        $out = [];
        foreach ($slips as $s) {
            $out[] = [$s['emp_no'], $s['first_name'] . ' ' . $s['last_name'],
                      (string)$s['gross_earn'], (string)$s['total_deduct'], (string)$s['net_pay'],
                      ['draft' => 'پیش‌نویس', 'final' => 'نهایی', 'paid' => 'پرداخت‌شده'][$s['period_status']] ?? $s['period_status']];
        }
        Log::info('payroll_export', 'خروجی حقوق', ['count' => count($out)]);
        csv_download(sprintf('payroll-%04d-%02d', $year, $month),
            ['کد پرسنلی', 'نام', 'درآمد', 'کسورات', 'خالص', 'وضعیت'], $out);
    }
}
