<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\User;

final class AuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW = 900; // 15 phút

    public function showLogin(): void
    {
        $this->view('admin/auth/login', [
            'page' => ['title' => 'Đăng nhập quản trị | ' . settings('site_name')],
        ], 'layouts/auth');
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // Chống dò mật khẩu: tối đa 5 lần sai / 15 phút cho mỗi phiên.
        $now = time();
        $attempts = array_values(array_filter(
            (array) Session::get('_login_attempts', []),
            static fn(int $t): bool => $t > $now - self::LOGIN_WINDOW
        ));
        Session::put('_login_attempts', $attempts);

        if (count($attempts) >= self::LOGIN_MAX_ATTEMPTS) {
            $wait = min($attempts) + self::LOGIN_WINDOW - $now;
            $waitMinutes = max(1, (int) ceil($wait / 60));
            $message = 'Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau khoảng ' . $waitMinutes . ' phút.';
            if ($this->isAjaxRequest()) {
                header('Retry-After: ' . $wait);
                $this->jsonResult(false, $message, status: 429);
            }
            Session::flash('_errors', ['login' => $message]);
            Session::flash('_old', ['username' => $username]);
            $this->redirect(url('admin/login'));
        }

        $errors = [];
        if ($username === '') {
            $errors['username'] = 'Vui lòng nhập tên đăng nhập.';
        }
        if ($password === '') {
            $errors['password'] = 'Vui lòng nhập mật khẩu.';
        }

        if ($errors === []) {
            $user = User::findByUsername($username);
            if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
                $errors['login'] = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
                $attempts[] = $now;
                Session::put('_login_attempts', $attempts);
                AuditLog::record('auth.login_failed', 'user', null, ['username' => $username]);
            } elseif ((int) $user['is_active'] !== 1) {
                $errors['login'] = 'Tài khoản đã bị khoá. Liên hệ quản trị viên.';
            }
        }

        if ($errors !== []) {
            if ($this->isAjaxRequest()) {
                if (isset($errors['login'])) {
                    $this->jsonResult(false, $errors['login'], status: 401);
                }
                $this->jsonResult(false, 'Vui lòng kiểm tra thông tin đăng nhập.', errors: $errors);
            }
            Session::flash('_errors', $errors);
            Session::flash('_old', ['username' => $username]);
            $this->redirect(url('admin/login'));
        }

        Session::forget('_login_attempts');

        // Đăng nhập thành công: nâng cấp hash nếu PHP đổi thuật toán mặc định.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Session::login($user);
        User::touchLogin((int) $user['id']);
        AuditLog::record('auth.login', 'user', (int) $user['id']);

        $intended = Session::get('_intended');
        Session::forget('_intended');
        $target = $intended !== null && str_starts_with((string) $intended, '/admin')
            ? url((string) $intended) : url('admin/dashboard');
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã đăng nhập.', $target);
        }
        $this->redirect($target);
    }

    public function logout(): void
    {
        AuditLog::record('auth.logout', 'user', (int) Session::get('auth_id'));
        Session::logout();
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã đăng xuất.', url('admin/login'));
        }
        $this->redirect(url('admin/login'));
    }
}
