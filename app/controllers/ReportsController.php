<?php
/**
 * گزارش‌های مدیریتی
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Log;

final class ReportsController extends Controller
{
    /** فهرست گزارش‌ها */
    private function catalog(): array
    {
        return [
            'headcount' => ['نیروی انسانی', '👥', 'سرشماری به تفکیک واحد، جنسیت و وضعیت'],
            'attendance' => ['حضور و غیاب', '🕐', 'خلاصه حضور/تأخیر/غیبت در بازه انتخابی'],
            'leave' => ['مصرف مرخصی', '🌴', 'مصرف مرخصی به تفکیک نوع و کارمند'],
            'payroll' => ['هزینه حقوق', '💰', 'روند ماهانه هزینه حقوق سازمان'],
            'training' => ['آموزش', '🎓', 'دوره‌ها و نرخ قبولی'],
            'recruit' => ['جذب و استخدام', '🎯', 'قیف متقاضیان'],
        ];
    }

    public function index(): void
    {
        $this->render('reports/index', ['title' => 'گزارش‌ها', 'catalog' => $this->catalog()]);
    }

    private function build(string $slug): array
    {
        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');

        switch ($slug) {
            case 'headcount':
                $rows = DB::all(
                    "SELECT d.name dept, e.gender, e.status, COUNT(*) c
                     FROM employees e LEFT JOIN departments d ON d.id = e.dept_id
                     WHERE e.deleted_at IS NULL
                     GROUP BY d.name, e.gender, e.status ORDER BY d.name"
                );
                $total = (int)DB::val('SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL');
                $male = (int)DB::val("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND gender='male'");
                $female = (int)DB::val("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND gender='female'");
                $active = (int)DB::val("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND status='active'");
                return ['rows' => $rows, 'kpis' => ['کل' => $total, 'مرد' => $male, 'زن' => $female, 'فعال' => $active],
                        'headers' => ['واحد', 'جنسیت', 'وضعیت', 'تعداد']];

            case 'attendance':
                $rows = DB::all(
                    "SELECT e.emp_no, e.first_name || ' ' || e.last_name name, d.name dept,
                            COUNT(*) days,
                            SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) present,
                            SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) late,
                            SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) absent,
                            SUM(CASE WHEN a.status='leave' THEN 1 ELSE 0 END) leaves,
                            COALESCE(SUM(a.work_min),0) work_min,
                            COALESCE(SUM(a.late_min),0) late_min
                     FROM attendance a
                     JOIN employees e ON e.id = a.employee_id
                     LEFT JOIN departments d ON d.id = e.dept_id
                     WHERE a.date BETWEEN ? AND ? AND e.deleted_at IS NULL
                     GROUP BY a.employee_id
                     ORDER BY late DESC, absent DESC",
                    [$from, $to]
                );
                $out = [];
                foreach ($rows as $r) {
                    $out[] = [$r['emp_no'], $r['name'], $r['dept'] ?? '—', (string)$r['present'],
                              (string)$r['late'], (string)$r['absent'], (string)$r['leaves'],
                              intdiv((int)$r['work_min'], 60) . ':' . str_pad((string)(((int)$r['work_min']) % 60), 2, '0', STR_PAD_LEFT),
                              (string)$r['late_min']];
                }
                return ['rows' => $out, 'kpis' => ['پرسنل دارای رکورد' => count($out)],
                        'headers' => ['کد', 'نام', 'واحد', 'حاضر', 'تأخیر', 'غایب', 'مرخصی', 'کارکرد', 'دقیقه تأخیر']];

            case 'leave':
                $rows = DB::all(
                    "SELECT lt.name type, lt.paid, COUNT(*) cnt, SUM(r.days) days
                     FROM leave_requests r JOIN leave_types lt ON lt.id = r.type_id
                     WHERE r.status = 'approved' AND r.from_date <= ? AND r.to_date >= ?
                     GROUP BY r.type_id ORDER BY days DESC",
                    [$to, $from]
                );
                $out = [];
                foreach ($rows as $r) {
                    $out[] = [$r['type'], $r['paid'] ? 'پرداختی' : 'بدون حقوق', (string)$r['cnt'], (string)$r['days']];
                }
                $byEmp = DB::all(
                    "SELECT e.emp_no, e.first_name||' '||e.last_name name, SUM(r.days) days, COUNT(*) cnt
                     FROM leave_requests r JOIN employees e ON e.id = r.employee_id
                     WHERE r.status='approved' AND r.from_date <= ? AND r.to_date >= ?
                     GROUP BY r.employee_id ORDER BY days DESC LIMIT 10",
                    [$to, $from]
                );
                return ['rows' => $out, 'kpis' => ['کل روزهای مرخصی' => array_sum(array_map(fn($r) => (int)$r['days'], $out))],
                        'headers' => ['نوع', 'پرداخت', 'تعداد درخواست', 'مجموع روز'],
                        'secondary' => ['title' => 'پرمصرف‌ترین افراد',
                                        'headers' => ['کد', 'نام', 'روز', 'تعداد'],
                                        'rows' => array_map(fn($r) => [$r['emp_no'], $r['name'], (string)$r['days'], (string)$r['cnt']], $byEmp)]];

            case 'payroll':
                $rows = DB::all(
                    "SELECT pp.year, pp.month, COUNT(p.id) cnt,
                            SUM(p.gross_earn) gross, SUM(p.total_deduct) ded, SUM(p.net_pay) net
                     FROM payroll_periods pp JOIN payslips p ON p.period_id = pp.id
                     GROUP BY pp.id ORDER BY pp.year, pp.month"
                );
                $out = [];
                foreach ($rows as $r) {
                    $out[] = [to_fa((string)$r['year']) . '/' . to_fa((string)$r['month']), (string)$r['cnt'],
                              number_format((float)$r['gross']), number_format((float)$r['ded']),
                              number_format((float)$r['net'])];
                }
                return ['rows' => $out, 'kpis' => ['دوره‌های محاسبه‌شده' => count($out)],
                        'headers' => ['دوره', 'فیش', 'درآمد (ریال)', 'کسورات (ریال)', 'پرداختی (ریال)']];

            case 'training':
                $rows = DB::all(
                    "SELECT c.title, c.start_date, c.seats,
                            COUNT(en.id) enrolled,
                            SUM(CASE WHEN en.status='passed' THEN 1 ELSE 0 END) passed,
                            SUM(CASE WHEN en.status='failed' THEN 1 ELSE 0 END) failed
                     FROM courses c LEFT JOIN enrollments en ON en.course_id = c.id
                     GROUP BY c.id ORDER BY c.start_date DESC"
                );
                $out = [];
                foreach ($rows as $r) {
                    $pct = (int)$r['enrolled'] > 0 ? round(100 * (int)$r['passed'] / (int)$r['enrolled']) : 0;
                    $out[] = [$r['title'], jdate($r['start_date']), (string)$r['enrolled'], (string)$r['passed'], (string)$r['failed'], $pct . '٪'];
                }
                return ['rows' => $out, 'kpis' => ['کل دوره‌ها' => count($rows)],
                        'headers' => ['دوره', 'شروع', 'ثبت‌نام', 'قبول', 'مردود', 'نرخ قبولی']];

            case 'recruit':
                $rows = DB::all('SELECT stage, COUNT(*) c FROM applicants GROUP BY stage');
                $order = ['new' => 'جدید', 'screening' => 'غربالگری', 'interview' => 'مصاحبه', 'offer' => 'پیشنهاد', 'hired' => 'استخدام شده', 'rejected' => 'رد شده'];
                $map = array_column($rows, 'c', 'stage');
                $out = [];
                foreach ($order as $k => $lbl) {
                    $out[] = [$lbl, (string)($map[$k] ?? 0)];
                }
                $jobs = DB::all('SELECT title, status, (SELECT COUNT(*) FROM applicants WHERE job_id = jobs.id) c FROM jobs ORDER BY created_at DESC');
                return ['rows' => $out, 'kpis' => ['کل متقاضیان' => (int)DB::val('SELECT COUNT(*) FROM applicants')],
                        'headers' => ['مرحله', 'تعداد'],
                        'secondary' => ['title' => 'آگهی‌ها', 'headers' => ['عنوان', 'وضعیت', 'متقاضی'],
                                        'rows' => array_map(fn($j) => [$j['title'], $j['status'], (string)$j['c']], $jobs)]];
        }
        return [];
    }

    public function run(string $slug): void
    {
        $catalog = $this->catalog();
        if (!isset($catalog[$slug])) {
            http_response_code(404);
            $this->render('errors/404', ['title' => '404']);
            return;
        }
        $data = $this->build($slug);
        $this->render('reports/show', [
            'title' => 'گزارش: ' . $catalog[$slug][0],
            'slug' => $slug, 'meta' => $catalog[$slug], 'data' => $data,
        ]);
    }

    public function export(string $slug): void
    {
        $catalog = $this->catalog();
        if (!isset($catalog[$slug])) {
            redirect('/reports');
        }
        $data = $this->build($slug);
        $rows = $data['rows'] ?? [];
        $headers = $data['headers'] ?? ['ستون'];
        // هدرهای فارسی CSV
        $out = [];
        foreach ($rows as $r) {
            $out[] = array_map(fn($x) => (string)$x, array_values($r));
        }
        Log::info('report_export', 'خروجی گزارش', ['slug' => $slug, 'count' => count($out)]);
        csv_download('report-' . $slug . '-' . date('Ymd'), $headers, $out);
    }
}
