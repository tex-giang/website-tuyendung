<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\User;

final class ProfileController extends Controller
{
    public function index(): void
    {
        $user = User::find((int) Session::get('auth_id'));
        if ($user === null) {
            Session::flash('_error', 'Không tìm thấy tài khoản.');
            $this->redirect(url('admin/dashboard'));
        }

        $this->view('admin/profile/index', [
            'user'   => $user,
            'errors' => Session::pull('_errors', []),
            'roles'  => User::roles(),
            'page'   => ['title' => 'Hồ sơ cá nhân | ' . settings('site_name'), 'nav' => ''],
        ], 'layouts/admin');
    }

    public function updatePassword(): void
    {
        $userId = (int) Session::get('auth_id');
        $user = User::find($userId);
        if ($user === null) {
            $this->formError('Không tìm thấy tài khoản.', url('admin/login'), status: 404);
        }

        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $errors = [];
        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';
        }
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Mật khẩu mới tối thiểu 8 ký tự.';
        } elseif ($password === $current) {
            $errors['password'] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        }
        if ($password !== $confirm) {
            $errors['password_confirm'] = 'Mật khẩu nhập lại không khớp.';
        }

        if ($errors !== []) {
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/profile'), $errors);
        }

        User::updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        AuditLog::record('user.password_change', 'user', $userId);
        $this->formSuccess('Đã đổi mật khẩu. Hãy dùng mật khẩu mới cho lần đăng nhập sau.', url('admin/profile'));
    }
}
