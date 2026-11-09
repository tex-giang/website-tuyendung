<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\User;

final class UserController extends Controller
{
    public function index(): void
    {
        $this->guardSuperAdmin();

        $this->view('admin/users/index', [
            'users' => User::all(),
            'roles' => User::roles(),
            'page'  => ['title' => 'Người dùng quản trị | ' . settings('site_name'), 'nav' => 'users'],
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->guardSuperAdmin();
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $this->guardSuperAdmin();
        $user = User::find((int) $id);
        if ($user === null) {
            Session::flash('_error', 'Tài khoản không tồn tại.');
            $this->redirect(url('admin/users'));
        }
        $this->form($user);
    }

    public function store(): void
    {
        $this->guardSuperAdmin();

        $data = $this->collect($_POST);
        $errors = $this->validate($data, null);

        if ($errors !== []) {
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/users/create'), $errors, $data);
        }

        $data['password_hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
        $id = User::create($data);
        AuditLog::record('user.create', 'user', $id, ['username' => $data['username']]);
        $this->formSuccess('Đã tạo tài khoản "' . $data['username'] . '".', url('admin/users'));
    }

    public function update(string $id): void
    {
        $this->guardSuperAdmin();
        $userId = (int) $id;
        $user = User::find($userId);
        if ($user === null) {
            $this->formError('Tài khoản không tồn tại.', url('admin/users'), status: 404);
        }

        $data = $this->collect($_POST);
        $errors = $this->validate($data, $userId);

        // Không cho tự khoá tài khoản đang đăng nhập.
        if ($userId === (int) Session::get('auth_id') && (int) $data['is_active'] !== 1) {
            $errors['is_active'] = 'Không thể tự khoá tài khoản đang đăng nhập.';
        }

        // Luôn phải còn ít nhất một super_admin đang hoạt động.
        if (!isset($errors['role_id'])) {
            $newRoleName = null;
            foreach (User::roles() as $roleName => $_) {
                if (User::roleIdByName($roleName) === $data['role_id']) {
                    $newRoleName = $roleName;
                    break;
                }
            }
            $willRemainSuper = $newRoleName === 'super_admin' && (int) $data['is_active'] === 1;
            if (!$willRemainSuper) {
                $otherActiveSupers = 0;
                foreach (User::all() as $row) {
                    if ((int) $row['id'] !== $userId && $row['role_name'] === 'super_admin' && (int) $row['is_active'] === 1) {
                        $otherActiveSupers++;
                    }
                }
                if ($otherActiveSupers === 0) {
                    $errors['role_id'] = 'Phải còn ít nhất một quản trị viên cấp cao đang hoạt động.';
                }
            }
        }

        if ($errors !== []) {
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/users/' . $userId . '/edit'), $errors, $data);
        }

        User::update($userId, $data);
        AuditLog::record('user.update', 'user', $userId, ['username' => $user['username']]);
        $this->formSuccess('Đã cập nhật tài khoản.', url('admin/users'));
    }

    public function resetPassword(string $id): void
    {
        $this->guardSuperAdmin();
        $userId = (int) $id;
        $user = User::find($userId);
        if ($user === null) {
            $this->formError('Tài khoản không tồn tại.', url('admin/users'), status: 404);
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        if (mb_strlen($password) < 8) {
            $this->formError('Mật khẩu mới tối thiểu 8 ký tự.', url('admin/users/' . $userId . '/edit'), ['password' => 'Mật khẩu mới tối thiểu 8 ký tự.']);
        }
        if ($password !== $confirm) {
            $this->formError('Mật khẩu nhập lại không khớp.', url('admin/users/' . $userId . '/edit'), ['password_confirm' => 'Mật khẩu nhập lại không khớp.']);
        }

        User::updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        AuditLog::record('user.password_reset', 'user', $userId, ['username' => $user['username']]);
        $this->formSuccess('Đã đặt lại mật khẩu cho "' . $user['username'] . '".', url('admin/users/' . $userId . '/edit'));
    }

    private function guardSuperAdmin(): void
    {
        if (Session::role() !== 'super_admin') {
            Session::flash('_error', 'Chỉ quản trị viên cấp cao được quản lý tài khoản.');
            $this->redirect(url('admin/dashboard'));
        }
    }

    private function collect(array $post): array
    {
        return [
            'username'  => trim((string) ($post['username'] ?? '')),
            'full_name' => trim((string) ($post['full_name'] ?? '')),
            'email'     => trim((string) ($post['email'] ?? '')),
            'phone'     => trim((string) ($post['phone'] ?? '')),
            'role_id'   => (int) ($post['role_id'] ?? 0),
            'is_active' => !empty($post['is_active']) ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $exceptId): array
    {
        $errors = [];
        if (!preg_match('/^[a-z0-9_.-]{3,50}$/', $data['username'])) {
            $errors['username'] = 'Tên đăng nhập 3–50 ký tự, chỉ chữ thường, số, dấu . _ -';
        } elseif (User::usernameExists($data['username'], $exceptId)) {
            $errors['username'] = 'Tên đăng nhập đã tồn tại.';
        }
        if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
            $errors['full_name'] = 'Họ tên 2–150 ký tự.';
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email chưa hợp lệ.';
        }
        $validRoleIds = [];
        foreach (User::roles() as $roleName => $_) {
            $roleId = User::roleIdByName($roleName);
            if ($roleId !== null) {
                $validRoleIds[] = $roleId;
            }
        }
        if (!in_array($data['role_id'], $validRoleIds, true)) {
            $errors['role_id'] = 'Vai trò không hợp lệ.';
        }
        if ($exceptId === null) {
            $password = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['password_confirm'] ?? '');
            if (mb_strlen($password) < 8) {
                $errors['password'] = 'Mật khẩu tối thiểu 8 ký tự.';
            } elseif ($password !== $confirm) {
                $errors['password_confirm'] = 'Mật khẩu nhập lại không khớp.';
            }
        }
        return $errors;
    }

    /** @param array<string, mixed>|null $user */
    private function form(?array $user): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $user ?? [
            'id' => null, 'username' => '', 'full_name' => '', 'email' => '', 'phone' => '',
            'role_id' => User::roleIdByName('recruiter') ?? 0, 'is_active' => 1,
        ];
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        $this->view('admin/users/form', [
            'user'   => $user,
            'values' => $values,
            'errors' => $errors,
            'roles'  => User::roles(),
            'page'   => [
                'title' => ($user === null ? 'Thêm tài khoản' : 'Sửa tài khoản') . ' | ' . settings('site_name'),
                'nav'   => 'users',
            ],
        ], 'layouts/admin');
    }
}
