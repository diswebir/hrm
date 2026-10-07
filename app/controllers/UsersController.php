<?php
/**
 * کاربران سامانه
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Validator;

final class UsersController extends Controller
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.username LIKE ? OR u.email LIKE ? OR e.first_name || " " || e.last_name LIKE ?)';
            array_push($params, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%');
        }
        $whereSql = implode(' AND ', $where);
        $rows = DB::all(
            "SELECT u.*, r.name role_name, r.slug role_slug, e.first_name, e.last_name, e.emp_no
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE {$whereSql}
             ORDER BY u.id LIMIT 500",
            $params
        );
        $emps = DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name");
        $roles = DB::all('SELECT * FROM roles ORDER BY id');

        $this->render('users/index', [
            'title' => 'کاربران',
            'rows' => $rows, 'q' => $q, 'emps' => $emps, 'roles' => $roles,
        ]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $v = Validator::make($_POST)
            ->check('username', 'required|min:3|max:32', 'نام کاربری')
            ->check('password', 'required|min:8', 'رمز عبور')
            ->check('role_id', 'required|integer', 'نقش');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/users');
        }
        $username = strtolower(trim((string)post('username')));
        if (!preg_match('/^[a-z0-9._-]{3,32}$/', $username)) {
            flash_set('error', 'نام کاربری فقط حروف انگلیسی، عدد، نقطه و خط تیره باشد.');
            redirect('/users');
        }
        if (DB::val('SELECT id FROM users WHERE username = ?', [$username])) {
            flash_set('error', 'این نام کاربری قبلاً ثبت شده است.');
            redirect('/users');
        }
        $email = trim((string)post('email', ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            flash_set('error', 'ایمیل معتبر نیست.');
            redirect('/users');
        }
        $empId = (int)post('employee_id', 0);
        if ($empId && DB::val('SELECT id FROM users WHERE employee_id = ?', [$empId])) {
            flash_set('error', 'این کارمند قبلاً کاربر سامانه شده است.');
            redirect('/users');
        }
        DB::insert('users', [
            'employee_id' => $empId ?: null,
            'username' => $username,
            'email' => $email ?: null,
            'password_hash' => password_hash((string)post('password'), PASSWORD_DEFAULT),
            'role_id' => (int)post('role_id'),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('user_create', 'ایجاد کاربر', ['username' => $username]);
        flash_set('success', 'کاربر ایجاد شد.');
        redirect('/users');
    }

    public function edit(string $id): void
    {
        $user = DB::row(
            "SELECT u.*, r.name role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?",
            [(int)$id]
        );
        if (!$user) {
            http_response_code(404);
            $this->render('errors/404', ['title' => '404']);
            return;
        }
        $this->render('users/edit', [
            'title' => 'ویرایش کاربر',
            'user' => $user,
            'roles' => DB::all('SELECT * FROM roles ORDER BY id'),
            'emps' => DB::all("SELECT id, first_name, last_name, emp_no FROM employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name"),
            'sessions' => DB::all(
                'SELECT * FROM user_sessions WHERE user_id = ? ORDER BY last_seen_at DESC LIMIT 10',
                [(int)$id]
            ),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = DB::row('SELECT * FROM users WHERE id = ?', [(int)$id]);
        if (!$user) {
            flash_set('error', 'کاربر یافت نشد.');
            redirect('/users');
        }
        $role = (int)post('role_id', (int)$user['role_id']);
        $isSuper = Auth::isSuper();
        if (!$isSuper) {
            $myRole = DB::val('SELECT slug FROM roles WHERE id = ?', [(int)Auth::user()['role_id']]);
            $targetRole = DB::val('SELECT slug FROM roles WHERE id = ?', [$role]);
            if ($targetRole === 'super-admin' || ($user['role_id'] !== $role && (int)$user['id'] === Auth::id())) {
                flash_set('error', 'شما مجاز به این تغییر نیستید.');
                redirect('/users');
            }
        }
        $data = [
            'role_id' => $role,
            'status' => in_array(post('status'), ['active', 'inactive'], true) ? post('status') : $user['status'],
            'email' => trim((string)post('email', '')) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        DB::update('users', $data, (int)$id);

        $pwd = (string)post('new_password', '');
        if ($pwd !== '') {
            if (strlen($pwd) < 8) {
                flash_set('error', 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.');
                redirect('/users/' . $id . '/edit');
            }
            DB::update('users', ['password_hash' => password_hash($pwd, PASSWORD_DEFAULT)], (int)$id);
            // خروج همه نشست‌ها
            DB::run('UPDATE user_sessions SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL',
                [date('Y-m-d H:i:s'), (int)$id]);
            Log::warn('user_pwd_reset', 'بازنشانی رمز کاربر', ['id' => $id]);
        }
        Log::info('user_update', 'ویرایش کاربر', ['id' => $id]);
        flash_set('success', 'کاربر به‌روزرسانی شد.');
        redirect('/users');
    }

    public function delete(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        if ((int)$id === Auth::id()) {
            flash_set('error', 'امکان حذف حساب خودتان وجود ندارد.');
            redirect('/users');
        }
        $user = DB::row('SELECT u.*, r.slug role_slug FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [(int)$id]);
        if (!$user) {
            flash_set('error', 'کاربر یافت نشد.');
            redirect('/users');
        }
        if ($user['role_slug'] === 'super-admin' && !Auth::isSuper()) {
            flash_set('error', 'فقط مدیر کل می‌تواند مدیر کل دیگری را حذف کند.');
            redirect('/users');
        }
        // غیرفعال‌سازی به‌جای حذف فیزیکی (ملاحظه ردیابی)
        DB::update('users', ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')], (int)$id);
        DB::run('UPDATE user_sessions SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL',
            [date('Y-m-d H:i:s'), (int)$id]);
        Log::info('user_disable', 'غیرفعال‌سازی کاربر', ['id' => $id]);
        flash_set('success', 'کاربر غیرفعال شد (داده‌ها حفظ شد).');
        redirect('/users');
    }
}
