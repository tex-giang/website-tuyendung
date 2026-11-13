<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permissions;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Branch;

/**
 * Phân quyền theo vai trò:
 *  - Ma trận menu × (xem / thêm / sửa / xoá).
 *  - Phạm vi nhà máy của vai trò (Tất cả hoặc danh sách nhà máy).
 *  - Tạo / sửa / xoá vai trò tự do (trừ super_admin).
 */
final class RoleController extends Controller
{
    public function index(): void
    {
        $this->guardSuperAdmin();

        $roles = Database::connection()->query(
            'SELECT r.id, r.name, r.description, r.all_branches,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS users_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_view = 1) AS view_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_create = 1) AS create_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_update = 1) AS update_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_delete = 1) AS delete_count,
                    (SELECT GROUP_CONCAT(b.name ORDER BY b.sort_order SEPARATOR ", ")
                       FROM role_branches rb JOIN branches b ON b.id = rb.branch_id
                      WHERE rb.role_id = r.id) AS branch_names
             FROM roles r ORDER BY r.id'
        )->fetchAll();

        $this->view('admin/roles/index', [
            'roles' => $roles,
            'totalModules' => count(Permissions::modules()),
            'branchLabels' => Branch::idOptions(),
            'page'  => ['title' => 'Phân quyền | ' . settings('site_name'), 'nav' => 'roles'],
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->guardSuperAdmin();
        $this->form(null);
    }

    public function store(): void
    {
        $this->guardSuperAdmin();

        $data = $this->collect($_POST);
        $errors = $this->validate($data, null);
        if ($errors !== []) {
            $this->formError('Vui lòng kiểm tra lại các thông tin của vai trò.', url('admin/roles/create'), $errors, $data);
        }

        $roleId = 0;
        Database::transaction(static function () use ($data, &$roleId): void {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('INSERT INTO roles (name, description, all_branches) VALUES (:name, :description, :all_branches)');
            $stmt->execute([
                'name'         => $data['name'],
                'description'  => $data['description'],
                'all_branches' => $data['all_branches'],
            ]);
            $roleId = (int) $pdo->lastInsertId();
            self::savePermissions($roleId, $data['perms']);
            self::saveBranches($roleId, $data['all_branches'], $data['branch_ids']);
            self::saveUsers($roleId, $data['user_ids'], []);
        });

        Permissions::flush();
        AuditLog::record('role.create', 'role', $roleId, ['role' => $data['name']]);
        $this->formSuccess('Đã tạo vai trò "' . $data['description'] . '".', url('admin/roles'));
    }

    public function edit(string $id): void
    {
        $this->guardSuperAdmin();
        $role = $this->findRole((int) $id);
        if ($role['name'] === 'super_admin') {
            $this->formError('Quản trị hệ thống luôn có toàn quyền, không cần chỉnh.', url('admin/roles'));
        }
        $this->form($role);
    }

    public function update(string $id): void
    {
        $this->guardSuperAdmin();
        $role = $this->findRole((int) $id);
        if ($role['name'] === 'super_admin') {
            $this->formError('Quản trị hệ thống luôn có toàn quyền, không cần chỉnh.', url('admin/roles'));
        }

        $editUrl = url('admin/roles/' . (int) $role['id'] . '/edit');
        $data = $this->collect($_POST);
        $data['name'] = (string) $role['name']; // không đổi tên định danh
        $errors = $this->validate($data, (int) $role['id']);
        if ($errors !== []) {
            $this->formError('Vui lòng kiểm tra lại các thông tin của vai trò.', $editUrl, $errors, $data);
        }

        $roleId = (int) $role['id'];
        Database::transaction(static function () use ($roleId, $data): void {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('UPDATE roles SET description = :description, all_branches = :all_branches WHERE id = :id');
            $stmt->execute([
                'description'  => $data['description'],
                'all_branches' => $data['all_branches'],
                'id'           => $roleId,
            ]);
            self::savePermissions($roleId, $data['perms']);
            self::saveBranches($roleId, $data['all_branches'], $data['branch_ids']);
            $previousMembers = array_map('intval', $pdo->query('SELECT id FROM users WHERE role_id = ' . $roleId)->fetchAll(\PDO::FETCH_COLUMN));
            self::saveUsers($roleId, $data['user_ids'], $previousMembers);
        });

        Permissions::flush();
        AuditLog::record('role.update', 'role', $roleId, ['role' => $role['name']]);
        $this->formSuccess('Đã lưu vai trò "' . $data['description'] . '".', url('admin/roles'));
    }

    public function destroy(string $id): void
    {
        $this->guardSuperAdmin();
        $role = $this->findRole((int) $id);
        $listUrl = url('admin/roles');

        if ($role['name'] === 'super_admin') {
            $this->formError('Không thể xoá vai trò Quản trị hệ thống.', $listUrl);
        }
        // Không cho xoá các vai trò mặc định của hệ thống.
        $protectedRoles = array_keys(Permissions::config()['role_labels'] ?? []);
        if (in_array((string) $role['name'], $protectedRoles, true)) {
            $this->formError('Đây là vai trò mặc định của hệ thống, không thể xoá.', $listUrl);
        }
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE role_id = :id');
        $stmt->execute(['id' => (int) $role['id']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->formError('Vai trò đang được gán cho tài khoản, hãy chuyển tài khoản sang vai trò khác trước.', $listUrl);
        }

        Database::connection()->prepare('DELETE FROM roles WHERE id = :id')->execute(['id' => (int) $role['id']]);
        Permissions::flush();
        AuditLog::record('role.delete', 'role', (int) $role['id'], ['role' => $role['name']]);
        $this->formSuccess('Đã xoá vai trò "' . $this->roleLabel($role) . '".', $listUrl);
    }

    // ------------------------------------------------------------------ form

    /** @param array<string, mixed>|null $role */
    private function form(?array $role): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);
        $isEdit = $role !== null;

        if ($isEdit) {
            $permissions = $this->permissionsFor((int) $role['id']);
            $branchIds = array_map('intval', Database::connection()
                ->query('SELECT branch_id FROM role_branches WHERE role_id = ' . (int) $role['id'])
                ->fetchAll(\PDO::FETCH_COLUMN));
            $values = [
                'name'         => (string) $role['name'],
                'description'  => (string) ($role['description'] ?? ''),
                'all_branches' => (int) ($role['all_branches'] ?? 1),
                'branch_ids'   => $branchIds,
                'perms'        => $permissions,
            ];
        } else {
            $values = [
                'name'         => '',
                'description'  => '',
                'all_branches' => 1,
                'branch_ids'   => [],
                'perms'        => [],
            ];
        }
        if ($old !== []) {
            $values = array_merge($values, $old);
        }
        $values['perms'] = is_array($values['perms'] ?? null) ? $values['perms'] : [];
        $values['branch_ids'] = is_array($values['branch_ids'] ?? null) ? $values['branch_ids'] : [];
        $values['all_branches'] = (int) ($values['all_branches'] ?? 1);

        $this->view('admin/roles/form', [
            'role'      => $role,
            'isEdit'    => $isEdit,
            'values'    => $values,
            'errors'    => $errors,
            'modules'   => Permissions::modules(),
            'actions'   => Permissions::ACTIONS,
            'branches'  => Branch::idOptions(),
            'assignableUsers' => $this->assignableUsers(),
            'selectedUsers'   => $isEdit
                ? array_map('intval', Database::connection()
                    ->query('SELECT id FROM users WHERE role_id = ' . (int) $role['id'])
                    ->fetchAll(\PDO::FETCH_COLUMN))
                : (is_array($values['user_ids'] ?? null) ? array_map('intval', $values['user_ids']) : []),
            'page'      => [
                'title' => ($isEdit ? 'Sửa vai trò: ' : 'Tạo vai trò: ') . ($isEdit ? $this->roleLabel($role) : 'mới') . ' | ' . settings('site_name'),
                'nav'   => 'roles',
            ],
        ], 'layouts/admin');
    }

    // ------------------------------------------------------------------ data

    /** @return array<string, mixed> */
    private function collect(array $post): array
    {
        $branchIds = $post['branch_ids'] ?? [];
        $branchIds = is_array($branchIds) ? array_map('intval', $branchIds) : [];
        $perms = $post['perm'] ?? [];
        $perms = is_array($perms) ? $perms : [];
        $userIds = $post['user_ids'] ?? [];
        $userIds = is_array($userIds) ? array_map('intval', $userIds) : [];

        return [
            'name'         => trim((string) ($post['name'] ?? '')),
            'description'  => trim((string) ($post['description'] ?? '')),
            'all_branches' => !empty($post['all_branches']) ? 1 : 0,
            'branch_ids'   => $branchIds,
            'perms'        => $perms,
            'user_ids'     => $userIds,
        ];
    }

    private function validate(array &$data, ?int $exceptId): array
    {
        $errors = [];
        if ($data['description'] === '' || mb_strlen($data['description']) < 3 || mb_strlen($data['description']) > 255) {
            $errors['description'] = 'Tên vai trò 3–255 ký tự.';
        }

        // Tên định danh: tự sinh từ mô tả nếu tạo mới, đảm bảo duy nhất.
        if ($exceptId === null) {
            $base = preg_replace('/[^a-z0-9]+/', '_', strtolower($this->ascii($data['description']))) ?? '';
            $base = trim($base, '_');
            if ($base === '') {
                $base = 'role';
            }
            $name = $base;
            $i = 2;
            while ($this->nameExists($name, null)) {
                $name = $base . '_' . $i;
                $i++;
            }
            $data['name'] = $name;
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
        return $errors;
    }

    private function ascii(string $value): string
    {
        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậäå', 'e' => 'éèẻẽẹêếềểễệë', 'i' => 'íìỉĩịîï',
            'o' => 'óòỏõọôốồổỗộơớờởỡợö', 'u' => 'úùủũụưứừửữựûü', 'y' => 'ýỳỷỹỵÿ',
            'd' => 'đ', 'c' => 'ç', 'n' => 'ñ',
        ];
        $value = mb_strtolower($value, 'UTF-8');
        foreach ($map as $ascii => $chars) {
            $value = str_replace(mb_str_split($chars), $ascii, $value);
        }
        return $value;
    }

    private function nameExists(string $name, ?int $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM roles WHERE name = :name' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['name' => $name];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** @param array<string, mixed> $perms POST perm[module][action] */
    private static function savePermissions(int $roleId, array $perms): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role')->execute(['role' => $roleId]);
        $stmt = $pdo->prepare(
            'INSERT INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
             VALUES (:role_id, :module, :can_view, :can_create, :can_update, :can_delete)'
        );
        foreach (array_keys(Permissions::modules()) as $module) {
            $row = ['view' => 0, 'create' => 0, 'update' => 0, 'delete' => 0];
            foreach (array_keys(Permissions::ACTIONS) as $action) {
                $row[$action] = isset($perms[$module][$action]) && (string) $perms[$module][$action] === '1' ? 1 : 0;
            }
            if ($row['create'] === 1 || $row['update'] === 1 || $row['delete'] === 1) {
                $row['view'] = 1;
            }
            $stmt->execute([
                'role_id'    => $roleId,
                'module'     => $module,
                'can_view'   => $row['view'],
                'can_create' => $row['create'],
                'can_update' => $row['update'],
                'can_delete' => $row['delete'],
            ]);
        }
    }

    /**
     * Gán tài khoản vào vai trò.
     *
     * @param int[] $userIds         Tài khoản được chọn (gán vào vai trò này)
     * @param int[] $previousMembers Thành viên cũ bị bỏ chọn (chuyển về vai trò mặc định)
     */
    private static function saveUsers(int $roleId, array $userIds, array $previousMembers): void
    {
        $pdo = Database::connection();
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        // Bảo vệ: không bao giờ đổi vai trò của tài khoản Quản trị hệ thống.
        if ($userIds !== []) {
            $placeholders = [];
            $params = ['role_id' => $roleId];
            foreach ($userIds as $i => $id) {
                $key = 'u' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $sql = 'UPDATE users u JOIN roles r ON r.id = u.role_id SET u.role_id = :role_id'
                 . ' WHERE u.id IN (' . implode(', ', $placeholders) . ') AND r.name <> \'super_admin\'';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        // Thành viên cũ bị bỏ chọn: chuyển về vai trò mặc định "Nhân viên tuyển dụng" nếu có.
        $fallback = $previousMembers === [] ? [] : array_values(array_diff(array_map('intval', $previousMembers), $userIds));
        $fallbackRoleId = self::fallbackRoleId();
        if ($fallback !== [] && $fallbackRoleId !== null && $fallbackRoleId !== $roleId) {
            $placeholders = [];
            $params = ['role_id' => $fallbackRoleId];
            foreach ($fallback as $i => $id) {
                $key = 'f' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $stmt = $pdo->prepare('UPDATE users SET role_id = :role_id WHERE id IN (' . implode(', ', $placeholders) . ')');
            $stmt->execute($params);
        }
    }

    private static function fallbackRoleId(): ?int
    {
        $stmt = Database::connection()->prepare("SELECT id FROM roles WHERE name = 'recruiter' LIMIT 1");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** @return array<int, array<string, mixed>> tất cả tài khoản đã tạo (super_admin chỉ để hiển thị) */
    private function assignableUsers(): array
    {
        return Database::connection()->query(
            "SELECT u.id, u.username, u.full_name, u.is_active, r.name AS role_name, r.description AS role_label
             FROM users u JOIN roles r ON r.id = u.role_id
             ORDER BY (r.name = 'super_admin'), u.full_name, u.username"
        )->fetchAll();
    }

    /** @param int[] $branchIds */
    private static function saveBranches(int $roleId, int $allBranches, array $branchIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM role_branches WHERE role_id = :role')->execute(['role' => $roleId]);
        if ($allBranches === 1) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO role_branches (role_id, branch_id) VALUES (:role_id, :branch_id)');
        foreach (array_unique(array_map('intval', $branchIds)) as $branchId) {
            $stmt->execute(['role_id' => $roleId, 'branch_id' => $branchId]);
        }
    }

    /** @return array<string, array<string, int>> module => action => 0|1 */
    private function permissionsFor(int $roleId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT module, can_view, can_create, can_update, can_delete FROM role_permissions WHERE role_id = :role'
        );
        $stmt->execute(['role' => $roleId]);
        $matrix = [];
        foreach ($stmt->fetchAll() as $row) {
            $matrix[(string) $row['module']] = [
                'view'   => (int) $row['can_view'],
                'create' => (int) $row['can_create'],
                'update' => (int) $row['can_update'],
                'delete' => (int) $row['can_delete'],
            ];
        }
        return $matrix;
    }

    /** @return array<string, mixed> */
    private function findRole(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch();
        if ($role === false) {
            Session::flash('_error', 'Vai trò không tồn tại.');
            $this->redirect(url('admin/roles'));
        }
        return $role;
    }

    /** Nhãn ngắn: ưu tiên config, rồi description, cuối cùng là name. */
    private function roleLabel(array $role): string
    {
        $config = Permissions::config()['role_labels'] ?? [];
        $label = $config[(string) $role['name']] ?? trim((string) ($role['description'] ?? ''));
        return $label !== '' ? $label : (string) $role['name'];
    }

    private function guardSuperAdmin(): void
    {
        if (Session::role() !== 'super_admin') {
            Session::flash('_error', 'Chỉ quản trị viên cấp cao được chỉnh phân quyền.');
            $this->redirect(url('admin/dashboard'));
        }
    }
}
