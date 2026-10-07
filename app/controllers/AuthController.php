<?php
/**
 * کنترلر ورود و خروج
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

final class AuthController extends Controller
{
    /** صفحه ورود */
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        $this->render('auth/login', [
            'title' => 'ورود به سامانه',
        ], 'auth_layout');
    }

    /** پردازش ورود */
    public function login(): void
    {
        if (Auth::check()) redirect('/');
        Csrf::verify($_POST['_token'] ?? '');

        $username = (string)post('username', '');
        $password = (string)post('password', '');

        if ($username === '' || $password === '') {
            Flash::error('نام کاربری و رمز عبور را وارد کنید.');
            redirect('/login');
        }

        [$ok, $msg] = Auth::attempt($username, $password);
        if (!$ok) {
            Flash::error($msg);
            redirect('/login');
        }

        Flash::success($msg . ' ورود شما با موفقیت انجام شد.');
        $back = $_GET['back'] ?? ($_POST['back'] ?? '/');
        if (is_string($back) && $back !== '' && $back[0] === '/' && strpos($back, '//') === false) {
            redirect($back);
        }
        redirect('/');
    }

    /** خروج */
    public function logout(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        Auth::logout();
        session_start();
        Flash::info('با موفقیت خارج شدید.');
        redirect('/login');
    }
}
