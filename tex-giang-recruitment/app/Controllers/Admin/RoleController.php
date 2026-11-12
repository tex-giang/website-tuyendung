<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permissions;
use App\Core\Session;
use App\Models\AuditLog;

/** Phân quyền theo vai trò: ma trận menu × (xem / thêm / sửa / xoá). */
final class RoleController extends Controller
{
    public function index(): void
    {
        $roles = Database::connection()->query(
            'SELECT r.id, r.name, r.description,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS users_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_view = 1) AS view_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_create = 1) AS create_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_update = 1) AS update_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.can_delete = 1) AS delete_count
             FROM roles r ORDER BY r.id'
        )->fetchAll();

        $this->view('admin/roles/index', [
            'roles' => $roles,
            'totalModules' => count(Permissions::modules()),
            'page'  => ['title' => 'Phân quyền | ' . settings('site_name'), 'nav' => 'roles'],
        ], 'layouts/admin');
    }

    public function edit(string $id): void
    {
        $role = $this->findRole((int) $id);
        $matrix = $this->matrixFor((int) $role['id']);

        $this->view('admin/roles/edit', [
            'role'    => $role,
            'matrix'  => $matrix,
            'modules' => Permissions::modules(),
            'actions' => Permissions::ACTIONS,
            'page'    => ['title' => 'Phân quyền: ' . $this->roleName($role) . ' | ' . settings('site_name'), 'nav' => 'roles'],
        ], 'layouts/admin');
    }

    public function update(string $id): void
    {
        $role = $this->findRole((int) $id);
        $editUrl = url('admin/roles/' . (int) $role['id'] . '/edit');

        if ($role['name'] === 'super_admin') {
            $this->formError('Quản trị hệ thống luôn có toàn quyền, không cần chỉnh.', url('admin/roles'));
        }

        $modules = array_keys(Permissions::modules());
        $actions = array_keys(Permissions::ACTIONS);
        $perms = $_POST['perm'] ?? [];
        $perms = is_array($perms) ? $perms : [];

        $rows = [];
        foreach ($modules as $module) {
            $row = ['view' => 0, 'create' => 0, 'update' => 0, 'delete' => 0];
            foreach ($actions as $action) {
                $row[$action] = isset($perms[$module][$action]) && (string) $perms[$module][$action] === '1' ? 1 : 0;
            }
            // Có quyền thêm/sửa/xoá mà không có quyền xem là vô nghĩa -> tự bật xem.
            if (($row['create'] === 1 || $row['update'] === 1 || $row['delete'] === 1)) {
                $row['view'] = 1;
            }
            $rows[$module] = $row;
        }

        $roleId = (int) $role['id'];
        Database::transaction(static function () use ($roleId, $rows): void {
            $pdo = Database::connection();
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role')->execute(['role' => $roleId]);
            $stmt = $pdo->prepare(
                'INSERT INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
                 VALUES (:role_id, :module, :can_view, :can_create, :can_update, :can_delete)'
            );
            foreach ($rows as $module => $row) {
                $stmt->execute([
                    'role_id'    => $roleId,
                    'module'     => $module,
                    'can_view'   => $row['view'],
                    'can_create' => $row['create'],
                    'can_update' => $row['update'],
                    'can_delete' => $row['delete'],
                ]);
            }
        });

        Permissions::flush();
        AuditLog::record('role.update', 'role', $roleId, ['role' => $role['name']]);
        $this->formSuccess('Đã lưu phân quyền cho vai trò "' . $this->roleName($role) . '".', url('admin/roles'));
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

    /** @return array<string, array<string, int>> module => action => 0|1 */
    private function matrixFor(int $roleId): array
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

    private function roleName(array $role): string
    {
        $label = trim((string) ($role['description'] ?? ''));
        return $label !== '' ? $label : (string) $role['name'];
    }
}
