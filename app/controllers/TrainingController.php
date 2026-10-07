<?php
/**
 * آموزش: دوره‌ها و ثبت‌نام‌ها
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Validator;

final class TrainingController extends Controller
{
    public function index(): void
    {
        $courses = DB::all(
            "SELECT c.*, (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) enrolled,
                    (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'passed') passed
             FROM courses c ORDER BY c.created_at DESC"
        );
        $enrollments = DB::all(
            "SELECT en.*, c.title course_title, e.first_name, e.last_name, e.emp_no
             FROM enrollments en
             JOIN courses c ON c.id = en.course_id
             JOIN employees e ON e.id = en.employee_id
             ORDER BY c.title, e.first_name LIMIT 300"
        );
        $emps = DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");

        $this->render('training/index', [
            'title' => 'آموزش',
            'courses' => $courses, 'enrollments' => $enrollments, 'emps' => $emps,
            'stats' => [
                'courses' => count($courses),
                'active' => (int)DB::val("SELECT COUNT(*) FROM courses WHERE status = 'running'"),
                'enrolled' => (int)DB::val('SELECT COUNT(*) FROM enrollments'),
                'passed' => (int)DB::val("SELECT COUNT(*) FROM enrollments WHERE status = 'passed'"),
            ],
        ]);
    }

    public function courseStore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)
            ->check('title', 'required|min:3', 'عنوان دوره')
            ->check('start_date', 'required', 'تاریخ شروع');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/training');
        }
        $start = trim((string)post('start_date'));
        $end = trim((string)post('end_date', ''));
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $start)) $start = \App\Core\Jalali::j2g($start);
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $end)) $end = \App\Core\Jalali::j2g($end);

        DB::insert('courses', [
            'title' => trim((string)post('title')),
            'provider' => trim((string)post('provider', '')),
            'start_date' => $start,
            'end_date' => $end ?: null,
            'seats' => max(1, (int)post('seats', 20)),
            'cost' => (float)post('cost', 0),
            'status' => in_array(post('status'), ['planned', 'running', 'done'], true) ? post('status') : 'planned',
            'note' => trim((string)post('note', '')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('course_create', 'ایجاد دوره آموزشی', ['title' => post('title')]);
        flash_set('success', 'دوره ایجاد شد.');
        redirect('/training');
    }

    public function enroll(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $cId = (int)post('course_id', 0);
        $eId = (int)post('employee_id', 0);
        if (!$cId || !$eId) {
            flash_set('error', 'دوره و کارمند را انتخاب کنید.');
            redirect('/training');
        }
        $course = DB::row('SELECT * FROM courses WHERE id = ?', [$cId]);
        if (!$course) {
            flash_set('error', 'دوره یافت نشد.');
            redirect('/training');
        }
        $count = (int)DB::val('SELECT COUNT(*) FROM enrollments WHERE course_id = ?', [$cId]);
        if ($count >= (int)$course['seats']) {
            flash_set('error', 'ظرفیت دوره تکمیل است (' . to_fa((string)$course['seats']) . ' نفر).');
            redirect('/training');
        }
        if (DB::val('SELECT id FROM enrollments WHERE course_id = ? AND employee_id = ?', [$cId, $eId])) {
            flash_set('error', 'این کارمند قبلاً ثبت‌نام شده است.');
            redirect('/training');
        }
        DB::insert('enrollments', ['course_id' => $cId, 'employee_id' => $eId, 'status' => 'enrolled', 'score' => 0]);
        Log::info('enroll', 'ثبت‌نام دوره', ['course' => $cId, 'emp' => $eId]);
        flash_set('success', 'ثبت‌نام انجام شد.');
        redirect('/training');
    }

    public function complete(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $en = DB::row('SELECT * FROM enrollments WHERE id = ?', [(int)$id]);
        if (!$en) {
            flash_set('error', 'ثبت‌نام یافت نشد.');
            redirect('/training');
        }
        $score = (float)post('score', 0);
        $status = post('result') === 'failed' ? 'failed' : 'passed';
        DB::update('enrollments', [
            'status' => $status,
            'score' => min(100, max(0, $score)),
            'cert_no' => $status === 'passed' ? ('CERT-' . str_pad((string)$en['id'], 5, '0', STR_PAD_LEFT) . '-' . date('y')) : null,
        ], (int)$id);
        Log::info('course_complete', 'پایان دوره', ['enrollment' => $id, 'status' => $status]);
        flash_set('success', $status === 'passed'
            ? 'قبولی ثبت شد؛ گواهینامه ' . to_fa(number_format($score, 0)) . ' درصد صادر شد.'
            : 'مردودی ثبت شد.');
        redirect('/training');
    }

    public function unenroll(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        DB::delete('enrollments', (int)$id);
        flash_set('success', 'ثبت‌نام حذف شد.');
        redirect('/training');
    }
}
