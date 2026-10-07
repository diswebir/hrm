<?php
/**
 * مدیریت کارمندان — فهرست، افزودن، ویرایش، پرونده، ورود/خروج CSV
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\Log;
use App\Core\Validator;

final class EmployeesController extends Controller
{
    /** شماره پرسنلی بعدی */
    private function nextEmpNo(): string
    {
        $prefix = (string)setting('emp_no_prefix', 'EMP-');
        $digits = max(3, (int)setting('emp_no_digits', 4));
        $max = (int)DB::val('SELECT emp_no FROM employees ORDER BY id DESC LIMIT 1');
        $last = 0;
        $row = DB::val('SELECT MAX(CAST(SUBSTR(emp_no, ' . (strlen($prefix) + 1) . ') AS INTEGER)) FROM employees WHERE emp_no LIKE ?', [$prefix . '%']);
        if ($row) $last = (int)$row;
        return $prefix . str_pad((string)($last + 1), $digits, '0', STR_PAD_LEFT);
    }

    /** لیست با فیلتر */
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $dept = (int)($_GET['dept'] ?? 0);
        $status = (string)($_GET['status'] ?? 'active');
        $gender = (string)($_GET['gender'] ?? '');
        $page = $this->page();
        $perPage = $this->perPage();

        $where = ['1=1'];
        $params = [];

        if ($status === 'archived') {
            $where[] = 'deleted_at IS NOT NULL';
        } else {
            $where[] = 'deleted_at IS NULL';
            if ($status !== 'all') {
                $where[] = 'status = ?';
                $params[] = $status;
            }
        }
        if ($q !== '') {
            $where[] = '(first_name || " " || last_name LIKE ? OR emp_no LIKE ? OR national_code LIKE ? OR phone LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($dept > 0) {
            $where[] = 'dept_id = ?';
            $params[] = $dept;
        }
        if ($gender === 'male' || $gender === 'female') {
            $where[] = 'gender = ?';
            $params[] = $gender;
        }

        $whereSql = implode(' AND ', $where);
        $total = (int)DB::val("SELECT COUNT(*) FROM employees WHERE {$whereSql}", $params);
        $rows = DB::all(
            "SELECT e.*, d.name AS dept_name, p.title AS pos_title
             FROM employees e
             LEFT JOIN departments d ON d.id = e.dept_id
             LEFT JOIN positions p ON p.id = e.position_id
             WHERE {$whereSql}
             ORDER BY e.created_at DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $params
        );

        $this->render('employees/index', [
            'title' => 'فهرست کارمندان',
            'rows' => $rows, 'total' => $total, 'page' => $page,
            'pager' => $this->pager($total, $perPage, $page),
            'depts' => DB::all('SELECT id, name FROM departments ORDER BY name'),
            'filters' => compact('q', 'dept', 'status', 'gender'),
            'statusCounts' => [
                'active' => (int)DB::val("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND status='active'"),
                'inactive' => (int)DB::val("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND status='inactive'"),
                'archived' => (int)DB::val('SELECT COUNT(*) FROM employees WHERE deleted_at IS NOT NULL'),
                'all' => (int)DB::val('SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL'),
            ],
        ]);
    }

    public function create(): void
    {
        $this->render('employees/form', [
            'title' => 'افزودن کارمند جدید',
            'emp' => null,
            'depts' => DB::all('SELECT id, name FROM departments ORDER BY name'),
            'positions' => DB::all('SELECT id, title, department_id FROM positions ORDER BY level DESC, title'),
            'peers' => DB::all('SELECT id, first_name, last_name FROM employees WHERE deleted_at IS NULL ORDER BY first_name'),
            'shifts' => DB::all('SELECT * FROM shifts WHERE active = 1'),
            'nextNo' => $this->nextEmpNo(),
        ]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $data = $this->collect($_POST);
        $v = $this->validate($data, 0);
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/employees/create');
        }

        $data['emp_no'] = $this->nextEmpNo();
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('employees', $data);

        // سقف مرخصی سال جاری
        $year = (int)date('Y');
        foreach (DB::all('SELECT id, annual_days FROM leave_types WHERE active = 1') as $lt) {
            DB::insert('leave_balances', [
                'employee_id' => $id, 'type_id' => $lt['id'], 'year' => $year,
                'days_total' => (float)$lt['annual_days'], 'days_used' => 0,
            ]);
        }

        Log::info('emp_create', 'افزودن کارمند: ' . $data['first_name'] . ' ' . $data['last_name'], ['no' => $data['emp_no']]);
        flash_set('success', 'کارمند «' . $data['first_name'] . ' ' . $data['last_name'] . '» با کد ' . $data['emp_no'] . ' افزوده شد.');
        redirect('/employees/' . $id);
    }

    public function show(string $id): void
    {
        $emp = $this->find((int)$id);
        $eid = (int)$emp['id'];

        $dept = $emp['dept_id'] ? DB::row('SELECT name FROM departments WHERE id = ?', [$emp['dept_id']]) : null;
        $pos = $emp['position_id'] ? DB::row('SELECT title FROM positions WHERE id = ?', [$emp['position_id']]) : null;
        $manager = $emp['manager_id'] ? DB::row('SELECT id, first_name, last_name FROM employees WHERE id = ?', [$emp['manager_id']]) : null;
        $user = DB::row('SELECT id, username, status FROM users WHERE employee_id = ?', [$eid]);

        $att = DB::all('SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 30', [$eid]);
        $leaves = DB::all('SELECT l.*, lt.name type_name, lt.color FROM leave_requests l
                           JOIN leave_types lt ON lt.id = l.type_id
                           WHERE l.employee_id = ? ORDER BY l.created_at DESC LIMIT 15', [$eid]);
        $docs = DB::all('SELECT * FROM documents WHERE employee_id = ? ORDER BY created_at DESC', [$eid]);
        $notes = DB::all('SELECT n.*, u.username FROM employee_notes n
                          LEFT JOIN users u ON u.id = n.user_id
                          WHERE n.employee_id = ? ORDER BY n.created_at DESC', [$eid]);
        $history = DB::all('SELECT * FROM work_history WHERE employee_id = ? ORDER BY from_date DESC', [$eid]);
        $goals = DB::all('SELECT * FROM goals WHERE employee_id = ? ORDER BY created_at DESC LIMIT 8', [$eid]);
        $slips = DB::all('SELECT p.*, py.year, py.month FROM payslips p
                          JOIN payroll_periods py ON py.id = p.period_id
                          WHERE p.employee_id = ? ORDER BY py.year DESC, py.month DESC LIMIT 6', [$eid]);
        $balYear = (int)date('Y');
        $balances = DB::all('SELECT b.*, lt.name, lt.color FROM leave_balances b
                             JOIN leave_types lt ON lt.id = b.type_id
                             WHERE b.employee_id = ? AND b.year = ?', [$eid, $balYear]);
        $summary = [
            'att_days' => (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND date >= date('now','start of month') AND status IN ('present','late','remote')", [$eid]),
            'late' => (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND date >= date('now','start of month') AND status = 'late'", [$eid]),
            'absent' => (int)DB::val("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND date >= date('now','start of month') AND status = 'absent'", [$eid]),
        ];

        $this->render('employees/show', [
            'title' => trim($emp['first_name'] . ' ' . $emp['last_name']),
            'emp' => $emp, 'dept' => $dept, 'pos' => $pos, 'manager' => $manager,
            'user' => $user, 'att' => $att, 'leaves' => $leaves, 'docs' => $docs,
            'notes' => $notes, 'history' => $history, 'goals' => $goals, 'slips' => $slips,
            'balances' => $balances, 'summary' => $summary,
            'depts' => DB::all('SELECT id, name FROM departments ORDER BY name'),
            'positions' => DB::all('SELECT id, title, department_id FROM positions ORDER BY level DESC, title'),
            'peers' => DB::all('SELECT id, first_name, last_name FROM employees WHERE deleted_at IS NULL AND id != ? ORDER BY first_name', [$eid]),
            'shifts' => DB::all('SELECT * FROM shifts WHERE active = 1'),
        ]);
    }

    public function edit(string $id): void
    {
        $emp = $this->find((int)$id);
        $this->render('employees/form', [
            'title' => 'ویرایش: ' . trim($emp['first_name'] . ' ' . $emp['last_name']),
            'emp' => $emp,
            'depts' => DB::all('SELECT id, name FROM departments ORDER BY name'),
            'positions' => DB::all('SELECT id, title, department_id FROM positions ORDER BY level DESC, title'),
            'peers' => DB::all('SELECT id, first_name, last_name FROM employees WHERE deleted_at IS NULL AND id != ? ORDER BY first_name', [(int)$id]),
            'shifts' => DB::all('SELECT * FROM shifts WHERE active = 1'),
            'nextNo' => $emp['emp_no'],
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $emp = $this->find((int)$id);
        $data = $this->collect($_POST);
        $v = $this->validate($data, (int)$id);
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/employees/' . $id . '/edit');
        }
        unset($data['emp_no']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::update('employees', $data, (int)$id);

        // آپلود عکس از فرم ویرایش (اختیاری)
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && $_FILES['photo']['size'] > 0) {
            $this->savePhoto((int)$id, $_FILES['photo']);
        }
        Log::info('emp_update', 'ویرایش کارمند #' . $emp['emp_no']);
        flash_set('success', 'اطلاعات کارمند به‌روزرسانی شد.');
        redirect('/employees/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $emp = $this->find((int)$id);
        DB::update('employees', ['deleted_at' => date('Y-m-d H:i:s'), 'status' => 'inactive'], (int)$id);
        DB::run('UPDATE users SET status = ? WHERE employee_id = ?', ['inactive', (int)$id]);
        Log::warning('emp_delete', 'حذف نرم کارمند #' . $emp['emp_no']);
        flash_set('success', 'کارمند حذف (بایگانی) شد. از فیلتر «آرشیو» قابل بازیابی است.');
        redirect('/employees?status=archived');
    }

    public function archive(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $emp = $this->find((int)$id);
        DB::update('employees', ['status' => 'inactive'], (int)$id);
        DB::run('UPDATE users SET status = ? WHERE employee_id = ?', ['inactive', (int)$id]);
        Log::info('emp_archive', 'بایگانی کارمند #' . $emp['emp_no']);
        flash_set('success', 'کارمند غیرفعال شد.');
        redirect('/employees/' . $id);
    }

    public function restore(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $emp = DB::row('SELECT * FROM employees WHERE id = ?', [(int)$id]);
        if (!$emp) {
            flash_set('error', 'کارمند یافت نشد.');
            redirect('/employees');
        }
        DB::update('employees', ['deleted_at' => null, 'status' => 'active'], (int)$id);
        DB::run('UPDATE users SET status = ? WHERE employee_id = ?', ['active', (int)$id]);
        Log::info('emp_restore', 'بازگردانی کارمند #' . $emp['emp_no']);
        flash_set('success', 'کارمند بازگردانی شد.');
        redirect('/employees/' . $id);
    }

    public function photo(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $emp = $this->find((int)$id);
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'فایلی انتخاب نشده یا آپلود ناموفق بود.');
            redirect('/employees/' . $id);
        }
        try {
            $this->savePhoto((int)$id, $_FILES['photo']);
            Log::info('emp_photo', 'تغییر عکس #' . $emp['emp_no']);
            flash_set('success', 'عکس پرونده به‌روزرسانی شد.');
        } catch (\RuntimeException $e) {
            flash_set('error', $e->getMessage());
        }
        redirect('/employees/' . $id);
    }

    /** ذخیره امن عکس (mime واقعی، حجم محدود) */
    private function savePhoto(int $id, array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('آپلود عکس ناموفق بود.');
        }
        if ($file['size'] > 3 * 1024 * 1024) {
            throw new \RuntimeException('حجم عکس نباید بیشتر از ۳ مگابایت باشد.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('فقط فایل تصویری JPG/PNG/WebP مجاز است.');
        }
        $name = 'emp_' . $id . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
        $dest = HRM_ROOT . '/storage/uploads/' . $name;
        if (!is_dir(HRM_ROOT . '/storage/uploads')) @mkdir(HRM_ROOT . '/storage/uploads', 0775, true);
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('ذخیره فایل ممکن نشد؛ دسترسی پوشه uploads را بررسی کنید.');
        }
        $old = DB::val('SELECT photo FROM employees WHERE id = ?', [$id]);
        if ($old) @unlink(HRM_ROOT . '/storage/uploads/' . $old);
        DB::update('employees', ['photo' => $name], $id);
    }

    public function addNote(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $emp = $this->find((int)$id);
        $note = trim((string)post('note', ''));
        if ($note === '') {
            flash_set('error', 'متن یادداشت خالی است.');
            redirect('/employees/' . $id);
        }
        DB::insert('employee_notes', [
            'employee_id' => (int)$id, 'user_id' => Auth::id(),
            'note' => $note, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('emp_note', 'یادداشت برای #' . $emp['emp_no']);
        flash_set('success', 'یادداشت ثبت شد.');
        redirect('/employees/' . $id . '#notes');
    }

    public function addHistory(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $emp = $this->find((int)$id);
        $v = Validator::make($_POST)->check('title', 'required|max:120', 'عنوان');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/employees/' . $id);
        }
        $from = $this->j2gPost('from_date');
        $to = $this->j2gPost('to_date');
        DB::insert('work_history', [
            'employee_id' => (int)$id, 'title' => post('title'),
            'department' => post('department'), 'from_date' => $from, 'to_date' => $to,
            'note' => post('note'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('emp_history', 'افزودن سوابق #' . $emp['emp_no']);
        flash_set('success', 'سابقه شغلی ثبت شد.');
        redirect('/employees/' . $id . '#history');
    }

    public function addDocument(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $empId = (int)post('employee_id', 0);
        $emp = $this->find($empId);
        $title = trim((string)post('title', ''));
        if ($title === '') {
            flash_set('error', 'عنوان مدرک الزامی است.');
            redirect('/employees/' . $empId);
        }
        $filePath = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK && $_FILES['file']['size'] > 0) {
            $file = $_FILES['file'];
            if ($file['size'] > 8 * 1024 * 1024) {
                flash_set('error', 'حجم فایل نباید بیشتر از ۸ مگابایت باشد.');
                redirect('/employees/' . $empId);
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = [
                'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png',
                'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            ];
            if (!isset($allowed[$mime])) {
                flash_set('error', 'نوع فایل مجاز نیست (PDF، تصویر، Word).');
                redirect('/employees/' . $empId);
            }
            $name = 'doc_' . $empId . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
            $dest = HRM_ROOT . '/storage/uploads/' . $name;
            if (!is_dir(HRM_ROOT . '/storage/uploads')) @mkdir(HRM_ROOT . '/storage/uploads', 0775, true);
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $filePath = $name;
            }
        }
        DB::insert('documents', [
            'employee_id' => $empId, 'title' => $title,
            'category' => post('category', 'other'),
            'file_path' => $filePath,
            'expiry_date' => $this->j2gPost('expiry_date'),
            'note' => post('note'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('emp_doc', 'افزودن مدرک: ' . $title, ['emp' => $emp['emp_no']]);
        flash_set('success', 'مدرک ثبت شد.');
        redirect('/employees/' . $empId . '#docs');
    }

    public function deleteDocument(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $doc = DB::row('SELECT * FROM documents WHERE id = ?', [(int)$id]);
        if ($doc) {
            if ($doc['file_path']) @unlink(HRM_ROOT . '/storage/uploads/' . $doc['file_path']);
            DB::delete('documents', (int)$id);
            Log::info('emp_doc_delete', 'حذف مدرک: ' . $doc['title']);
            flash_set('success', 'مدرک حذف شد.');
            $back = '/employees/' . $doc['employee_id'] . '#docs';
        } else {
            $back = '/employees';
        }
        redirect($back);
    }

    /** دفترچه تلفن */
    public function directory(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $dept = (int)($_GET['dept'] ?? 0);
        $where = ["e.deleted_at IS NULL", "e.status = 'active'"];
        $params = [];
        if ($q !== '') {
            $where[] = '(e.first_name || " " || e.last_name LIKE ? OR e.phone LIKE ? OR e.email LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if ($dept) {
            $where[] = 'e.dept_id = ?';
            $params[] = $dept;
        }
        $rows = DB::all(
            'SELECT e.id, e.first_name, e.last_name, e.phone, e.email, e.emp_no,
                    d.name dept_name, p.title pos_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.dept_id
             LEFT JOIN positions p ON p.id = e.position_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY e.first_name, e.last_name LIMIT 300',
            $params
        );
        $this->render('employees/directory', [
            'title' => 'دفترچه تلفن',
            'rows' => $rows,
            'depts' => DB::all('SELECT id, name FROM departments ORDER BY name'),
            'filters' => compact('q', 'dept'),
        ]);
    }

    /** خروجی CSV */
    public function export(): void
    {
        if (!Auth::can('employees.export') && !Auth::isSuper()) {
            flash_set('error', 'مجوز خروجی ندارید.');
            redirect('/employees');
        }
        $rows = DB::all("
            SELECT e.emp_no, e.first_name, e.last_name, e.national_code, e.gender,
                   d.name dept, p.title pos, e.hire_date, e.phone, e.email,
                   e.contract_type, e.salary_base, e.status
            FROM employees e
            LEFT JOIN departments d ON d.id = e.dept_id
            LEFT JOIN positions p ON p.id = e.position_id
            WHERE e.deleted_at IS NULL
            ORDER BY e.emp_no");
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['emp_no'], $r['first_name'], $r['last_name'], $r['national_code'],
                $r['gender'] === 'female' ? 'خانم' : 'آقا',
                $r['dept'], $r['pos'],
                Jalali::format($r['hire_date']),
                $r['phone'], $r['email'],
                ['permanent' => 'دائم', 'temporary' => 'موقت', 'probation' => 'آزمایشی', 'project' => 'پروژه‌ای'][$r['contract_type']] ?? $r['contract_type'],
                money($r['salary_base']),
                ['active' => 'فعال', 'inactive' => 'غیرفعال'][$r['status']] ?? $r['status'],
            ];
        }
        Log::info('emp_export', 'خروجی CSV کارمندان', ['count' => count($out)]);
        csv_download('employees-' . date('Y-m-d'), [
            'کد پرسنلی', 'نام', 'نام خانوادگی', 'کد ملی', 'جنسیت',
            'واحد', 'پست', 'تاریخ استخدام', 'تلفن', 'ایمیل', 'نوع قرارداد', 'حقوق پایه', 'وضعیت',
        ], $out);
    }

    /** قالب CSV */
    public function template(): void
    {
        csv_download('employees-template', [
            'first_name', 'last_name', 'national_code', 'gender', 'birth_date',
            'hire_date', 'department', 'position', 'email', 'phone', 'salary_base',
        ], [
            ['علی', 'رضایی', '0012345678', 'male', '1402/05/12', '1403/07/01', 'فناوری اطلاعات', 'توسعه‌دهنده نرم‌افزار', 'ali@example.com', '09120000000', '25000000'],
        ]);
    }

    public function importForm(): void
    {
        $this->render('employees/import', [
            'title' => 'ورود توده‌ای کارمندان',
            'depts' => DB::all('SELECT id, name FROM departments ORDER BY name'),
            'positions' => DB::all('SELECT id, title FROM positions ORDER BY title'),
        ]);
    }

    public function importRun(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'فایل CSV انتخاب نشده است.');
            redirect('/employees/import');
        }
        $ext = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            flash_set('error', 'فقط فایل CSV مجاز است.');
            redirect('/employees/import');
        }
        $rows = \App\Core\Csv::read($_FILES['csv']['tmp_name']);
        if (!$rows) {
            flash_set('error', 'فایل خالی یا بدون سرستون معتبر است.');
            redirect('/employees/import');
        }

        $created = 0; $skipped = 0; $errors = [];
        $year = (int)date('Y');
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $first = trim($row['first_name'] ?? '');
            $last = trim($row['last_name'] ?? '');
            if ($first === '' || $last === '') {
                $errors[] = "ردیف {$line}: نام یا نام خانوادگی خالی است.";
                continue;
            }
            $nc = trim($row['national_code'] ?? '');
            if ($nc !== '' && (int)DB::val('SELECT COUNT(*) FROM employees WHERE national_code = ?', [$nc]) > 0) {
                $skipped++;
                continue;
            }
            $deptId = null;
            if (!empty($row['department'])) {
                $deptId = DB::val('SELECT id FROM departments WHERE name = ?', [trim($row['department'])]);
            }
            $posId = null;
            if (!empty($row['position'])) {
                $posId = DB::val('SELECT id FROM positions WHERE title = ?', [trim($row['position'])]);
            }
            $birth = $this->parseJalaliDate($row['birth_date'] ?? '');
            $hire = $this->parseJalaliDate($row['hire_date'] ?? '');
            $gender = strtolower(trim($row['gender'] ?? 'male'));
            $gender = in_array($gender, ['female', 'f', 'زن', 'خانم'], true) ? 'female' : 'male';

            $empId = DB::insert('employees', [
                'emp_no' => $this->nextEmpNo(),
                'first_name' => $first, 'last_name' => $last,
                'national_code' => $nc !== '' ? $nc : null,
                'gender' => $gender,
                'birth_date' => $birth, 'hire_date' => $hire ?: date('Y-m-d'),
                'dept_id' => $deptId ? (int)$deptId : null,
                'position_id' => $posId ? (int)$posId : null,
                'email' => trim($row['email'] ?? '') ?: null,
                'phone' => trim($row['phone'] ?? '') ?: null,
                'salary_base' => (float)preg_replace('/[^\d.]/', '', (string)($row['salary_base'] ?? '0')),
                'contract_type' => 'permanent',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            foreach (DB::all('SELECT id, annual_days FROM leave_types WHERE active = 1') as $lt) {
                DB::insert('leave_balances', [
                    'employee_id' => $empId, 'type_id' => $lt['id'], 'year' => $year,
                    'days_total' => (float)$lt['annual_days'], 'days_used' => 0,
                ]);
            }
            $created++;
        }

        Log::info('emp_import', 'ورود توده‌ای', ['created' => $created, 'skipped' => $skipped]);
        $msg = to_fa((string)$created) . ' کارمند افزوده شد';
        if ($skipped) $msg .= '، ' . to_fa((string)$skipped) . ' تکراری رد شد';
        if ($errors) $msg .= '، ' . to_fa((string)count($errors)) . ' خطا';
        flash_set($created ? 'success' : 'warning', $msg);
        if ($errors) {
            flash_set('info', mb_substr(implode(' ', array_slice($errors, 0, 3)), 0, 300));
        }
        redirect('/employees');
    }

    // ---------- کمکی‌ها ----------

    private function find(int $id): array
    {
        $emp = DB::row('SELECT * FROM employees WHERE id = ?', [$id]);
        if (!$emp) {
            http_response_code(404);
            \App\Core\View::render('errors/404', ['title' => 'کارمند یافت نشد'], 'auth_layout');
            exit;
        }
        return $emp;
    }

    /** تبدیل ورودی‌های تاریخ شمسی فرم به میلادی */
    private function collect(array $in): array
    {
        return [
            'first_name' => trim((string)($in['first_name'] ?? '')),
            'last_name' => trim((string)($in['last_name'] ?? '')),
            'national_code' => trim((string)($in['national_code'] ?? '')) ?: null,
            'gender' => in_array($in['gender'] ?? 'male', ['male', 'female'], true) ? $in['gender'] : 'male',
            'birth_date' => $this->parseJalaliDate($in['birth_date'] ?? ''),
            'hire_date' => $this->parseJalaliDate($in['hire_date'] ?? '') ?: date('Y-m-d'),
            'dept_id' => ((int)($in['dept_id'] ?? 0)) ?: null,
            'position_id' => ((int)($in['position_id'] ?? 0)) ?: null,
            'manager_id' => ((int)($in['manager_id'] ?? 0)) ?: null,
            'shift_id' => ((int)($in['shift_id'] ?? 0)) ?: null,
            'email' => trim((string)($in['email'] ?? '')) ?: null,
            'phone' => trim((string)($in['phone'] ?? '')) ?: null,
            'address' => trim((string)($in['address'] ?? '')) ?: null,
            'contract_type' => in_array($in['contract_type'] ?? 'permanent', ['permanent', 'temporary', 'probation', 'project'], true) ? $in['contract_type'] : 'permanent',
            'contract_start' => $this->parseJalaliDate($in['contract_start'] ?? ''),
            'contract_end' => $this->parseJalaliDate($in['contract_end'] ?? ''),
            'probation_end' => $this->parseJalaliDate($in['probation_end'] ?? ''),
            'salary_base' => (float)preg_replace('/[^\d.]/', '', (string)($in['salary_base'] ?? '0')),
            'bank_account' => trim((string)($in['bank_account'] ?? '')) ?: null,
            'insurance_no' => trim((string)($in['insurance_no'] ?? '')) ?: null,
            'education' => trim((string)($in['education'] ?? '')) ?: null,
            'marital_status' => in_array($in['marital_status'] ?? 'single', ['single', 'married'], true) ? $in['marital_status'] : 'single',
            'dependents' => max(0, (int)($in['dependents'] ?? 0)),
            'work_days' => $this->parseWorkDays(is_array($in['work_days'] ?? null) ? implode(',', $in['work_days']) : (string)($in['work_days'] ?? '')),
            'status' => in_array($in['status'] ?? 'active', ['active', 'inactive'], true) ? $in['status'] : 'active',
            'notes' => trim((string)($in['notes'] ?? '')) ?: null,
        ];
    }

    private function validate(array $data, int $id): \App\Core\Validator
    {
        $v = Validator::make($data)
            ->check('first_name', 'required|max:60', 'نام')
            ->check('last_name', 'required|max:60', 'نام خانوادگی');
        if ($data['national_code'] !== null && !preg_match('/^\d{10}$/', $data['national_code'])) {
            $v->check('national_code', 'required', 'کد ملی'); // پیام عمومی
        }
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $v->check('email', 'email', 'ایمیل');
        }
        if ($data['salary_base'] < 0) {
            $v->check('salary_base', 'numeric', 'حقوق پایه');
        }
        return $v;
    }

    private function parseJalaliDate($value): ?string
    {
        if ($value === null || trim((string)$value) === '') return null;
        $value = trim((string)$value);
        $value = str_replace(['/', '.'], '-', $value);
        if (!Jalali::validJalali($value)) return null;
        $g = Jalali::j2g($value);
        return $g !== '' ? $g : null;
    }

    private function parseWorkDays(string $raw): string
    {
        if ($raw === '') return (string)setting('work_days', '6,0,1,2,3,4');
        $parts = explode(',', $raw);
        $clean = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '' && ctype_digit($p) && (int)$p >= 0 && (int)$p <= 6) {
                $clean[] = (int)$p;
            }
        }
        $clean = array_values(array_unique($clean));
        return $clean ? implode(',', $clean) : (string)setting('work_days', '6,0,1,2,3,4');
    }

    private function j2gPost(string $key): ?string
    {
        return $this->parseJalaliDate($_POST[$key] ?? '');
    }
}
