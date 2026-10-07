<?php
/**
 * پروفایل کاربر، رمز عبور و نشست‌ها
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Validator;

final class ProfileController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $emp = null;
        if ($user['employee_id']) {
            $emp = DB::row('SELECT * FROM employees WHERE id = ?', [$user['employee_id']]);
        }
        $sessions = Auth::sessions((int)$user['id']);
        $logs = DB::all("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 15", [$user['id']]);

        $this->render('profile/index', [
            'title' => 'پروفایل من',
            'user' => $user, 'emp' => $emp, 'sessions' => $sessions, 'logs' => $logs,
        ]);
    }

    public function update(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $user = Auth::user();

        $v = Validator::make($_POST)
            ->check('email', 'email', 'ایمیل');

        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/profile');
        }

        DB::update('users', ['email' => post('email'), 'updated_at' => date('Y-m-d H:i:s')], (int)$user['id']);

        if ($user['employee_id'] && Auth::can('employees.update')) {
            DB::update('employees', [
                'phone' => post('phone'),
                'address' => post('address'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], (int)$user['employee_id']);
        }

        Log::info('profile_update', 'به‌روزرسانی پروفایل');
        flash_set('success', 'پروفایل با موفقیت ذخیره شد.');
        redirect('/profile');
    }

    public function password(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $current = (string)post('current', '');
        $new = (string)post('new', '');
        $repeat = (string)post('repeat', '');
        $user = Auth::user();

        if (!password_verify($current, $user['password_hash'])) {
            flash_set('error', 'رمز عبور فعلی نادرست است.');
            redirect('/profile');
        }
        if (strlen($new) < 8) {
            flash_set('error', 'رمز عبور جدید باید حداقل ۸ نویسه باشد.');
            redirect('/profile');
        }
        if ($new !== $repeat) {
            flash_set('error', 'تکرار رمز عبور جدید مطابقت ندارد.');
            redirect('/profile');
        }

        DB::update('users', [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
            'must_change_pwd' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], (int)$user['id']);

        Log::info('password_change', 'تغییر رمز عبور');
        flash_set('success', 'رمز عبور تغییر کرد.');
        redirect('/profile');
    }

    public function sessions(): void
    {
        $user = Auth::user();
        $sessions = Auth::sessions((int)$user['id']);
        $this->render('profile/sessions', [
            'title' => 'نشست‌های فعال',
            'sessions' => $sessions,
            'current' => session_id(),
        ]);
    }

    public function revokeSession(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $id = (int)post('session_id', 0);
        $sess = DB::row('SELECT * FROM user_sessions WHERE id = ? AND user_id = ?', [$id, Auth::id()]);
        if ($sess) {
            DB::update('user_sessions', ['revoked_at' => date('Y-m-d H:i:s')], $id);
            Log::info('session_revoke', 'قطع نشست', ['sid' => $sess['session_id']]);
            flash_set('success', 'نشست انتخابی قطع شد.');
        }
        redirect('/profile/sessions');
    }

    public function revokeOthers(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $me = session_id();
        DB::run('UPDATE user_sessions SET revoked_at = ? WHERE user_id = ? AND session_id != ? AND revoked_at IS NULL',
            [date('Y-m-d H:i:s'), Auth::id(), $me]);
        Log::info('session_revoke_others', 'قطع همه نشست‌های دیگر');
        flash_set('success', 'همه نشست‌های دیگر قطع شدند.');
        redirect('/profile/sessions');
    }
}
