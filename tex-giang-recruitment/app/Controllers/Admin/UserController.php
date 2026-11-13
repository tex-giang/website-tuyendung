<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permissions;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;

final class UserController extends Controller
{
    private const REQUIRE_ACTIVE_ADMIN = 'Phải còn ít nhất một quản trị viên cấp cao đang hoạt động.';

    public function index(): void
    {
        $this->guardSuperAdmin();

        $this->view('admin/users/index', [
            'users'   => User::all(),
            'roles'   => User::roles(),
            'branches' => Branch::idOptions(),
            'page'    => ['title' => 'Người dùng quản trị | ' . settings('site_name'), 'nav' => 'users'],
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

        $editUrl = url('admin/users/' . $userId . '/edit');
        if ($errors !== []) {
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', $editUrl, $errors, $data);
        }

        $willRemainSuper = $data['role_id'] === User::roleIdByName('super_admin') && $data['is_active'] === 1;
        $saved = Database::transaction(static function () use ($userId, $data, $willRemainSuper): bool {
            if (!$willRemainSuper && !User::hasOtherActiveSuperAdmin($userId)) {
                return false;
            }
            User::update($userId, $data);
            return true;
        });
        if (!$saved) {
            $this->formError(self::REQUIRE_ACTIVE_ADMIN, $editUrl, ['role_id' => self::REQUIRE_ACTIVE_ADMIN], $data);
        }
        AuditLog::record('user.update', 'user', $userId, ['username' => $user['username']]);
        $destination = $userId === (int) Session::get('auth_id') && !$willRemainSuper ? 'admin/profile' : 'admin/users';
        $this->formSuccess('Đã cập nhật tài khoản.', url($destination));
    }

    public function changeStatus(string $id): void
    {
        $this->guardSuperAdmin();
        $userId = (int) $id;
        $status = $_POST['is_active'] ?? '';
        $listUrl = url('admin/users');
        if (!in_array($status, ['0', '1'], true)) {
            $this->formError('Trạng thái tài khoản không hợp lệ.', $listUrl);
        }
        if (User::find($userId) === null) {
            $this->formError('Tài khoản không tồn tại.', $listUrl, status: 404);
        }
        $active = $status === '1';
        if (!$active && $userId === (int) Session::get('auth_id')) {
            $this->formError('Không thể tự khóa tài khoản đang đăng nhập.', $listUrl, status: 409);
        }

        $changed = Database::transaction(static function () use ($userId, $active): ?bool {
            if (!$active && !User::hasOtherActiveSuperAdmin($userId)) {
                return null;
            }
            return User::setActive($userId, $active);
        });
        if ($changed === null) {
            $this->formError(self::REQUIRE_ACTIVE_ADMIN, $listUrl, status: 409);
        }
        if ($changed) {
            AuditLog::record('user.status', 'user', $userId, ['is_active' => $active]);
        }
        $this->formSuccess($active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.', $listUrl);
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
        $branchIds = $post['branch_ids'] ?? [];
        $branchIds = is_array($branchIds) ? array_map('intval', $branchIds) : [];

        return [
            'username'     => trim((string) ($post['username'] ?? '')),
            'full_name'    => trim((string) ($post['full_name'] ?? '')),
            'email'        => trim((string) ($post['email'] ?? '')),
            'phone'        => trim((string) ($post['phone'] ?? '')),
            'role_id'      => (int) ($post['role_id'] ?? 0),
            'all_branches' => !empty($post['all_branches']) ? 1 : 0,
            'branch_ids'   => $branchIds,
            'is_active'    => !empty($post['is_active']) ? 1 : 0,
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
        if ($data['email'] !== '' && (mb_strlen($data['email']) > 190 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
            $errors['email'] = 'Email chưa hợp lệ.';
        }
        if (mb_strlen($data['phone']) > 30) {
            $errors['phone'] = 'Số điện thoại tối đa 30 ký tự.';
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
        $validBranchIds = array_map('intval', array_keys(Branch::idOptions()));
        if (!empty($data['all_branches'])) {
            $data['branch_ids'] = [];
        } else {
            $data['branch_ids'] = array_values(array_intersect($data['branch_ids'], $validBranchIds));
            if ($data['branch_ids'] === []) {
                $errors['branch_ids'] = 'Chọn ít nhất một nhà máy hoặc bật "Tất cả nhà máy".';
            }
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
            'all_branches' => 1, 'branch_ids' => [],
        ];
        if ($old !== []) {
            $values = array_merge($values, $old);
        }
        $selectedBranches = $user !== null ? User::branchIdsForUser((int) $user['id']) : [];
        if (isset($values['branch_ids']) && is_array($values['branch_ids'])) {
            $selectedBranches = array_map('intval', $values['branch_ids']);
        }
        $values['branch_ids'] = $selectedBranches;
        $values['all_branches'] = (int) ($values['all_branches'] ?? 1);

        // Phạm vi nhà máy của vai trò đang chọn (để hiển thị gợi ý trong form).
        $roleNameById = [];
        foreach (User::roles() as $name => $label) {
            $roleId = User::roleIdByName($name);
            if ($roleId !== null) {
                $roleNameById[$roleId] = $name;
            }
        }
        $selectedRoleId = (int) $values['role_id'];
        $selectedRoleName = $roleNameById[$selectedRoleId] ?? null;
        $roleScope = ['label' => '', 'branch_ids' => null];
        if ($selectedRoleName !== null) {
            $roleScope = [
                'label'      => User::roles()[$selectedRoleName] ?? $selectedRoleName,
                'branch_ids' => Permissions::roleBranchIds($selectedRoleName),
            ];
        }

        $this->view('admin/users/form', [
            'user'      => $user,
            'values'    => $values,
            'errors'    => $errors,
            'roles'     => User::roles(),
            'branches'  => Branch::idOptions(),
            'roleScope' => $roleScope,
            'page'   => [
                'title' => ($user === null ? 'Thêm tài khoản' : 'Sửa tài khoản') . ' | ' . settings('site_name'),
                'nav'   => 'users',
            ],
        ], 'layouts/admin');
    }
}
