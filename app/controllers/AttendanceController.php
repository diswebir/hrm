<?php
/**
 * حضور و غیاب
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\Log;
use App\Core\Validator;

final class AttendanceController extends Controller
{
    /** محاسبه شیفت مؤثر کارمند */
    private function effectiveShift(?array $emp): array
    {
        if ($emp && !empty($emp['shift_id'])) {
            $sh = DB::row('SELECT * FROM shifts WHERE id = ?', [$emp['shift_id']]);
            if ($sh) return $sh;
        }
        $sh = DB::row('SELECT * FROM shifts WHERE active = 1 ORDER BY id LIMIT 1');
        if ($sh) return $sh;
        return ['name' => 'پیش‌فرض', 'start_time' => setting('work_start', '08:00'),
                'end_time' => setting('work_end', '17:00'), 'grace_min' => (int)setting('late_grace_min', 10),
                'overnight' => 0];
    }

    /** محاسبه وضعیت یک رکورد حضور */
    private function computeStatus(string $date, ?string $in, ?string $out, array $shift): array
    {
        $late = 0;
        $early = 0;
        $work = 0;
        $grace = (int)$shift['grace_min'];

        if ($in) {
            [$ih, $im] = array_map('intval', explode(':', $in . ':00'));
            [$sh, $sm] = array_map('intval', explode(':', $shift['start_time'] . ':00'));
            $inMin = $ih * 60 + $im;
            $startMin = $sh * 60 + $sm;
            if ($inMin > $startMin + $grace) {
                $late = $inMin - ($startMin + $grace);
            }
        }
        if ($in && $out) {
            [$ih, $im] = array_map('intval', explode(':', $in . ':00'));
            [$oh, $om] = array_map('intval', explode(':', $out . ':00'));
            $inMin = $ih * 60 + $im;
            $outMin = $oh * 60 + $om;
            if ($outMin < $inMin) $outMin += 1440; // شیفت شبانه
            $work = max(0, $outMin - $inMin - 60); // یک ساعت استراحت

            [$eh, $em] = array_map('intval', explode(':', $shift['end_time'] . ':00'));
            $endMin = $eh * 60 + $em;
            if ($endMin < $inMin) $endMin += 1440;
            if ($outMin < $endMin) $early = $endMin - $outMin;
        }
        return ['late' => $late, 'early' => $early, 'work' => $work];
    }

    public function index(): void
    {
        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
        $dept = (int)($_GET['dept'] ?? 0);
        $status = (string)($_GET['status'] ?? '');
        $q = trim((string)($_GET['q'] ?? ''));
        $page = $this->page();
        $perPage = $this->perPage();

        $where = ['a.date BETWEEN ? AND ?'];
        $params = [$from, $to];
        if ($dept) { $where[] = 'e.dept_id = ?'; $params[] = $dept; }
        if ($status !== '') { $where[] = 'a.status = ?'; $params[] = $status; }
        if ($q !== '') {
            $where[] = '(e.first_name || " " || e.last_name LIKE ? OR e.emp_no LIKE ?)';
            array_push($params, '%' . $q . '%', '%' . $q . '%');
        }
        $whereSql = implode(' AND ', $where);

        $total = (int)DB::val("SELECT COUNT(*) FROM attendance a JOIN employees e ON e.id = a.employee_id WHERE {$whereSql}", $params);
        $rows = DB::all(
            "SELECT a.*, e.first_name, e.last_name, e.emp_no, e.dept_id, d.name dept_name
             FROM attendance a
             JOIN employees e ON e.id = a.employee_id
             LEFT JOIN departments d ON d.id = e.dept_id
             WHERE {$whereSql}
             ORDER BY a.date DESC, e.emp_no
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $params
        );

        $stats = DB::all(
            "SELECT a.status, COUNT(*) c FROM attendance a JOIN employees e ON e.id = a.employee_id
             WHERE {$whereSql} GROUP BY a.status",
            $params
        );
        $statMap = ['present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0, 'remote' => 0, 'trip' => 0, 'holiday' => 0];
        foreach ($stats as $s) $statMap[$s['status']] = (int)$s['c'];

        $this->render('attendance/index', [
            'title' => 'حضور و غیاب',
            'rows' => $rows, 'total' => $total, 'page' => $page,
            'pager' => $this->pager($total, $perPage, $page),
            'depts' => DB::all('SELECT id, name FROM departments ORDER BY name'),
            'employees' => DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name"),
            'shifts' => DB::all('SELECT * FROM shifts WHERE active=1'),
            'filters' => compact('from', 'to', 'dept', 'status', 'q'),
            'statMap' => $statMap,
        ]);
    }

    /** حضور من */
    public function my(): void
    {
        $user = Auth::user();
        if (!$user['employee_id']) {
            flash_set('warning', 'پرونده کارمندی به حساب شما متصل نیست.');
            redirect('/');
        }
        $eid = (int)$user['employee_id'];
        $today = date('Y-m-d');
        $rows = DB::all('SELECT * FROM attendance WHERE employee_id = ? AND date >= date(?, "-29 days") ORDER BY date DESC', [$eid, $today]);
        $todayRec = DB::row('SELECT * FROM attendance WHERE employee_id = ? AND date = ?', [$eid, $today]);

        $month = [
            'present' => (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=date('now','start of month') AND status IN ('present','late','remote')", [$eid]),
            'late' => (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=date('now','start of month') AND status='late'", [$eid]),
            'absent' => (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=date('now','start of month') AND status='absent'", [$eid]),
            'leave' => (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=date('now','start of month') AND status='leave'", [$eid]),
            'work_min' => (int)DB::val("SELECT COALESCE(SUM(work_min),0) FROM attendance WHERE employee_id=? AND date>=date('now','start of month')", [$eid]),
        ];

        $this->render('attendance/my', [
            'title' => 'حضور من',
            'rows' => $rows, 'todayRec' => $todayRec, 'month' => $month,
        ]);
    }

    /** ثبت ورود/خروج (خودخدمت) */
    public function check(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = Auth::user();
        if (!$user['employee_id']) {
            flash_set('error', 'پرونده کارمندی ندارید.');
            redirect('/attendance/my');
        }
        $eid = (int)$user['employee_id'];
        $emp = DB::row('SELECT * FROM employees WHERE id = ?', [$eid]);
        $today = date('Y-m-d');
        $now = date('H:i');
        $shift = $this->effectiveShift($emp);

        $rec = DB::row('SELECT * FROM attendance WHERE employee_id = ? AND date = ?', [$eid, $today]);

        if (!$rec) {
            $c = $this->computeStatus($today, $now, null, $shift);
            $status = $c['late'] > 15 ? 'late' : 'present';
            DB::insert('attendance', [
                'employee_id' => $eid, 'date' => $today,
                'check_in' => $now, 'check_out' => null,
                'status' => $status, 'late_min' => $c['late'], 'work_min' => 0,
                'source' => 'manual', 'created_at' => date('Y-m-d H:i:s'),
            ]);
            Log::info('check_in', 'ثبت ورود', ['time' => $now, 'late' => $c['late']]);
            $msg = 'ورود شما در ساعت ' . to_fa($now) . ' ثبت شد';
            if ($c['late'] > 0) $msg .= ' (تأخیر: ' . to_fa((string)$c['late']) . ' دقیقه)';
            flash_set('success', $msg . '.');
        } elseif (!$rec['check_out']) {
            $c = $this->computeStatus($today, $rec['check_in'], $now, $shift);
            $status = $rec['status'] === 'leave' ? 'leave' : ($c['late'] > 15 ? 'late' : 'present');
            DB::update('attendance', [
                'check_out' => $now, 'work_min' => $c['work'],
                'early_min' => $c['early'], 'status' => $status, 'late_min' => $c['late'],
            ], (int)$rec['id']);
            Log::info('check_out', 'ثبت خروج', ['time' => $now]);
            $hours = intdiv($c['work'], 60);
            $mins = $c['work'] % 60;
            flash_set('success', 'خروج شما در ساعت ' . to_fa($now) . ' ثبت شد (کارکرد امروز: ' . to_fa((string)$hours) . ' ساعت و ' . to_fa((string)$mins) . ' دقیقه).');
        } else {
            flash_set('info', 'ورود و خروج امروز شما قبلاً ثبت شده است (ورود ' . to_fa($rec['check_in']) . ' — خروج ' . to_fa($rec['check_out']) . ').');
        }
        redirect('/attendance/my');
    }

    /** ثبت رکورد توسط مسئول */
    public function save(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)
            ->check('employee_id', 'required|integer', 'کارمند')
            ->check('date', 'required|date', 'تاریخ');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/attendance');
        }
        $empId = (int)post('employee_id');
        $date = (string)post('date');
        $emp = DB::row('SELECT * FROM employees WHERE id = ?', [$empId]);
        if (!$emp) {
            flash_set('error', 'کارمند یافت نشد.');
            redirect('/attendance');
        }
        $in = trim((string)post('check_in', ''));
        $out = trim((string)post('check_out', ''));
        $in = preg_match('/^\d{2}:\d{2}$/', $in) ? $in : null;
        $out = preg_match('/^\d{2}:\d{2}$/', $out) ? $out : null;
        $note = post('note');
        $manualStatus = (string)post('status', '');
        $shift = $this->effectiveShift($emp);
        $c = $this->computeStatus($date, $in, $out, $shift);

        if ($manualStatus !== '' && in_array($manualStatus, ['absent', 'leave', 'trip', 'remote', 'holiday'], true)) {
            $status = $manualStatus;
        } elseif (!$in) {
            $status = 'absent';
        } else {
            $status = $c['late'] > 15 ? 'late' : 'present';
        }

        $data = [
            'check_in' => $in, 'check_out' => $out, 'status' => $status,
            'late_min' => $c['late'], 'early_min' => $c['early'], 'work_min' => $c['work'],
            'source' => 'manual', 'note' => $note,
        ];
        $existing = DB::row('SELECT id FROM attendance WHERE employee_id = ? AND date = ?', [$empId, $date]);
        if ($existing) {
            DB::update('attendance', $data, (int)$existing['id']);
        } else {
            $data['employee_id'] = $empId;
            $data['date'] = $date;
            $data['created_at'] = date('Y-m-d H:i:s');
            DB::insert('attendance', $data);
        }
        Log::info('att_save', 'ثبت حضور', ['emp' => $emp['emp_no'], 'date' => $date, 'status' => $status]);
        flash_set('success', 'رکورد حضور ذخیره شد.');
        redirect('/attendance?' . http_build_query($_GET));
    }

    public function delete(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $rec = DB::row('SELECT * FROM attendance WHERE id = ?', [(int)$id]);
        if ($rec) {
            DB::delete('attendance', (int)$id);
            Log::info('att_delete', 'حذف رکورد حضور', ['id' => $id]);
            flash_set('success', 'رکورد حذف شد.');
        }
        redirect('/attendance?' . http_build_query($_GET));
    }

    public function export(): void
    {
        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-d'));
        $rows = DB::all(
            "SELECT a.*, e.first_name, e.last_name, e.emp_no
             FROM attendance a JOIN employees e ON e.id = a.employee_id
             WHERE a.date BETWEEN ? AND ? ORDER BY a.date, e.emp_no",
            [$from, $to]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                Jalali::format($r['date']), $r['emp_no'], $r['first_name'] . ' ' . $r['last_name'],
                $r['check_in'], $r['check_out'],
                $r['work_min'] ? intdiv((int)$r['work_min'], 60) . ':' . str_pad((string)(((int)$r['work_min']) % 60), 2, '0', STR_PAD_LEFT) : '',
                (string)$r['late_min'],
                ['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'leave' => 'مرخصی', 'remote' => 'دورکاری', 'trip' => 'مأموریت', 'holiday' => 'تعطیل'][$r['status']] ?? $r['status'],
                $r['note'],
            ];
        }
        Log::info('att_export', 'خروجی حضور و غیاب', ['count' => count($out)]);
        csv_download('attendance-' . $from . '_' . $to, [
            'تاریخ', 'کد پرسنلی', 'نام', 'ورود', 'خروج', 'کارکرد', 'تأخیر(دقیقه)', 'وضعیت', 'توضیح',
        ], $out);
    }
}
