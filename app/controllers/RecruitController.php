<?php
/**
 * جذب و استخدام (ATS)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Validator;

final class RecruitController extends Controller
{
    private const STAGES = ['new' => 'جدید', 'screening' => 'غربالگری', 'interview' => 'مصاحبه',
                            'offer' => 'پیشنهاد', 'hired' => 'استخدام شده', 'rejected' => 'رد شده'];

    public function index(): void
    {
        $stats = [
            'open' => (int)DB::val("SELECT COUNT(*) FROM jobs WHERE status = 'open'"),
            'all' => (int)DB::val('SELECT COUNT(*) FROM jobs'),
            'applicants' => (int)DB::val('SELECT COUNT(*) FROM applicants'),
            'interviews' => (int)DB::val("SELECT COUNT(*) FROM interviews WHERE result IS NULL OR result = ''"),
            'hired' => (int)DB::val("SELECT COUNT(*) FROM applicants WHERE stage = 'hired'"),
        ];
        $jobs = DB::all(
            "SELECT j.*, d.name dept_name, (SELECT COUNT(*) FROM applicants WHERE job_id = j.id) app_count
             FROM jobs j LEFT JOIN departments d ON d.id = j.dept_id
             ORDER BY j.created_at DESC"
        );
        $applicants = DB::all(
            "SELECT a.*, j.title job_title FROM applicants a
             LEFT JOIN jobs j ON j.id = a.job_id
             ORDER BY a.updated_at DESC, a.id DESC LIMIT 200"
        );
        $interviews = DB::all(
            "SELECT i.*, a.full_name, u.username interviewer
             FROM interviews i
             JOIN applicants a ON a.id = i.applicant_id
             LEFT JOIN users u ON u.id = i.interviewer_id
             ORDER BY i.interview_at DESC LIMIT 50"
        );
        $this->render('recruit/index', [
            'title' => 'جذب و استخدام',
            'stats' => $stats, 'jobs' => $jobs, 'applicants' => $applicants,
            'interviews' => $interviews, 'stages' => self::STAGES,
        ]);
    }

    public function jobStore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)->check('title', 'required|min:3', 'عنوان شغل');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/recruit');
        }
        DB::insert('jobs', [
            'title' => trim((string)post('title')),
            'dept_id' => (int)post('dept_id', 0) ?: null,
            'description' => trim((string)post('description', '')),
            'requirements' => trim((string)post('requirements', '')),
            'salary_min' => (float)post('salary_min', 0),
            'salary_max' => (float)post('salary_max', 0),
            'openings' => max(1, (int)post('openings', 1)),
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('job_create', 'ایجاد آگهی', ['title' => post('title')]);
        flash_set('success', 'آگهی ایجاد شد (پیش‌نویس).');
        redirect('/recruit');
    }

    public function jobPublish(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $job = DB::row('SELECT * FROM jobs WHERE id = ?', [(int)$id]);
        if (!$job) {
            flash_set('error', 'آگهی یافت نشد.');
            redirect('/recruit');
        }
        $new = $job['status'] === 'open' ? 'closed' : 'open';
        DB::update('jobs', [
            'status' => $new,
            'published_at' => $new === 'open' ? date('Y-m-d H:i:s') : $job['published_at'],
        ], (int)$id);
        Log::info('job_toggle', 'تغییر وضعیت آگهی', ['id' => $id, 'status' => $new]);
        flash_set('success', $new === 'open' ? 'آگهی منتشر شد.' : 'آگهی بسته شد.');
        redirect('/recruit');
    }

    /** ثبت متقاضی (توسط HR یا فرم عمومی) */
    public function applicantStore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)
            ->check('full_name', 'required|min:3', 'نام کامل')
            ->check('phone', 'required', 'تلفن');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/recruit');
        }
        $phone = trim((string)post('phone'));
        if (!preg_match('/^0?9\d{9}$/', $phone)) {
            flash_set('error', 'شماره تلفن همراه معتبر وارد کنید (مثال: 09121234567).');
            redirect('/recruit');
        }
        $jobId = (int)post('job_id', 0);
        $email = trim((string)post('email', ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            flash_set('error', 'ایمیل معتبر نیست.');
            redirect('/recruit');
        }
        $resume = null;
        if (isset($_FILES['resume']) && ($_FILES['resume']['error'] ?? 4) === UPLOAD_ERR_OK) {
            $resume = $this->saveResume($_FILES['resume']);
        }
        DB::insert('applicants', [
            'job_id' => $jobId ?: null,
            'full_name' => trim((string)post('full_name')),
            'email' => $email ?: null, 'phone' => $phone,
            'stage' => 'new',
            'expected_pay' => (float)post('expected_pay', 0),
            'resume_path' => $resume,
            'source' => (string)post('source', 'دستی'),
            'notes' => trim((string)post('notes', '')),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('applicant_add', 'ثبت متقاضی', ['name' => post('full_name')]);
        flash_set('success', 'متقاضی ثبت شد.');
        redirect('/recruit?tab=applicants');
    }

    private function saveResume(array $file): ?string
    {
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'png'], true)) return null;
        if ($file['size'] > 5 * 1024 * 1024) return null;
        $name = 'cv_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $dest = HRM_ROOT . '/storage/uploads/' . $name;
        if (!is_dir(HRM_ROOT . '/storage/uploads')) @mkdir(HRM_ROOT . '/storage/uploads', 0775, true);
        return move_uploaded_file($file['tmp_name'], $dest) ? $name : null;
    }

    public function stage(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $a = DB::row('SELECT * FROM applicants WHERE id = ?', [(int)$id]);
        if (!$a) {
            flash_set('error', 'متقاضی یافت نشد.');
            redirect('/recruit?tab=applicants');
        }
        $stage = (string)post('stage', '');
        if (!isset(self::STAGES[$stage])) {
            flash_set('error', 'مرحله نامعتبر است.');
            redirect('/recruit?tab=applicants');
        }
        DB::update('applicants', ['stage' => $stage, 'updated_at' => date('Y-m-d H:i:s')], (int)$id);
        Log::info('applicant_stage', 'تغییر مرحله متقاضی', ['id' => $id, 'stage' => $stage]);
        if ($stage === 'hired') {
            flash_set('success', 'تبریک! متقاضی به «استخدام شده» منتقل شد — اکنون از بخش کارمندان پرونده بسازید.');
        } else {
            flash_set('success', 'مرحله به‌روزرسانی شد.');
        }
        redirect('/recruit?tab=applicants');
    }

    public function interviewStore(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $aId = (int)post('applicant_id', 0);
        if (!$aId || !DB::val('SELECT id FROM applicants WHERE id = ?', [$aId])) {
            flash_set('error', 'متقاضی را انتخاب کنید.');
            redirect('/recruit?tab=interviews');
        }
        $v = Validator::make($_POST)->check('interview_at', 'required', 'زمان مصاحبه');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/recruit?tab=interviews');
        }
        $at = trim((string)post('interview_at'));
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}/', $at)) {
            $at = \App\Core\Jalali::j2g(substr($at, 0, 10)) . ' ' . (substr($at, 11, 5) ?: '09:00');
        }
        DB::insert('interviews', [
            'applicant_id' => $aId,
            'interviewer_id' => Auth::id(),
            'interview_at' => $at,
            'mode' => in_array(post('mode'), ['in_person', 'online', 'phone'], true) ? post('mode') : 'in_person',
            'score' => min(5, max(1, (int)post('score', 3))),
            'result' => (string)post('result', ''),
            'notes' => trim((string)post('notes', '')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        DB::update('applicants', ['stage' => 'interview', 'updated_at' => date('Y-m-d H:i:s')], $aId);
        Log::info('interview_add', 'ثبت مصاحبه', ['applicant' => $aId]);
        flash_set('success', 'مصاحبه ثبت شد.');
        redirect('/recruit?tab=interviews');
    }
}
