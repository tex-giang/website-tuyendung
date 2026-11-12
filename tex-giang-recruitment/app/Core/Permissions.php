<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Phân quyền admin theo ma trận trong database (bảng role_permissions).
 *
 * Mỗi module (menu) có 4 quyền: view / create / update / delete.
 * - super_admin luôn được phép tất cả.
 * - Phạm vi nhà máy: users.all_branches = 1 → toàn bộ; ngược lại chỉ các
 *   nhà máy trong user_branches.
 *
 * Module + nhãn hiển thị vẫn khai báo ở app/Config/permissions.php; quyền
 * thực tế được quản trị viên chỉnh trong màn hình "Phân quyền".
 */
final class Permissions
{
    public const ACTIONS = [
        'view'   => 'Xem',
        'create' => 'Thêm',
        'update' => 'Sửa',
        'delete' => 'Xoá',
    ];

    private static ?array $config = null;

    /** @var array<string, array<string, bool>>|null module => action => allowed */
    private static ?array $matrix = null;

    /** @var array<string, string>|null */
    private static ?array $roleLabels = null;

    /** @var int[]|null null = toàn bộ nhà máy */
    private static ?array $branchIds = null;

    private static bool $branchScopeLoaded = false;

    public static function config(): array
    {
        if (self::$config === null) {
            $file = BASE_PATH . '/app/Config/permissions.php';
            self::$config = is_file($file) ? (array) require $file : ['role_labels' => [], 'modules' => []];
        }
        return self::$config;
    }

    /** @return array<string, array{label:string}> */
    public static function modules(): array
    {
        return self::config()['modules'] ?? [];
    }

    public static function moduleLabel(string $module): string
    {
        return self::modules()[$module]['label'] ?? $module;
    }

    /** @return array<string, string> role name => nhãn hiển thị */
    public static function roleLabels(): array
    {
        if (self::$roleLabels !== null) {
            return self::$roleLabels;
        }
        $labels = self::config()['role_labels'] ?? [];
        $stmt = Database::connection()->query('SELECT name, description FROM roles ORDER BY id');
        foreach ($stmt->fetchAll() as $row) {
            $name = (string) $row['name'];
            $description = trim((string) ($row['description'] ?? ''));
            $labels[$name] = $description !== '' ? $description : ($labels[$name] ?? $name);
        }
        return self::$roleLabels = $labels;
    }

    public static function roleLabel(?string $role): string
    {
        $labels = self::roleLabels();
        return $labels[$role ?? ''] ?? ($role ?? 'khách');
    }

    /** Xoá cache trong request hiện tại (gọi sau khi lưu ma trận quyền). */
    public static function flush(): void
    {
        self::$matrix = null;
        self::$roleLabels = null;
        self::$branchIds = null;
        self::$branchScopeLoaded = false;
    }

    /** @return array<string, array<string, bool>> */
    private static function matrix(): array
    {
        if (self::$matrix !== null) {
            return self::$matrix;
        }
        $role = Session::role();
        if ($role === null || $role === '') {
            return self::$matrix = [];
        }
        if ($role === 'super_admin') {
            $matrix = [];
            foreach (array_keys(self::modules()) as $module) {
                $matrix[$module] = array_fill_keys(array_keys(self::ACTIONS), true);
            }
            return self::$matrix = $matrix;
        }

        $stmt = Database::connection()->prepare(
            'SELECT rp.module, rp.can_view, rp.can_create, rp.can_update, rp.can_delete
             FROM role_permissions rp
             JOIN roles r ON r.id = rp.role_id
             WHERE r.name = :role'
        );
        $stmt->execute(['role' => $role]);
        $matrix = [];
        foreach ($stmt->fetchAll() as $row) {
            $matrix[(string) $row['module']] = [
                'view'   => (int) $row['can_view'] === 1,
                'create' => (int) $row['can_create'] === 1,
                'update' => (int) $row['can_update'] === 1,
                'delete' => (int) $row['can_delete'] === 1,
            ];
        }
        return self::$matrix = $matrix;
    }

    /**
     * Kiểm tra quyền. $role null = vai trò hiện tại.
     */
    public static function can(?string $role, string $module, string $action = 'view'): bool
    {
        if ($role === null || $role === '') {
            return false;
        }
        if ($role === 'super_admin') {
            return true;
        }
        if ($role === Session::role()) {
            $matrix = self::matrix();
            return $matrix[$module][$action] ?? false;
        }
        return self::roleCan($role, $module, $action);
    }

    /** Quyền của một vai trò bất kỳ (dùng cho màn hình phân quyền). */
    public static function roleCan(string $role, string $module, string $action = 'view'): bool
    {
        if ($role === 'super_admin') {
            return true;
        }
        $stmt = Database::connection()->prepare(
            'SELECT rp.can_view, rp.can_create, rp.can_update, rp.can_delete
             FROM role_permissions rp JOIN roles r ON r.id = rp.role_id
             WHERE r.name = :role AND rp.module = :module LIMIT 1'
        );
        $stmt->execute(['role' => $role, 'module' => $module]);
        $row = $stmt->fetch();
        if ($row === false) {
            return false;
        }
        return match ($action) {
            'view'   => (int) $row['can_view'] === 1,
            'create' => (int) $row['can_create'] === 1,
            'update' => (int) $row['can_update'] === 1,
            'delete' => (int) $row['can_delete'] === 1,
            default  => false,
        };
    }

    public static function currentCan(string $module, string $action = 'view'): bool
    {
        $role = Session::role();
        if ($role === null || $role === '') {
            return false;
        }
        if ($role === 'super_admin') {
            return true;
        }
        $matrix = self::matrix();
        return $matrix[$module][$action] ?? false;
    }

    /** Chặn truy cập module/hành động: render trang 403 trong layout admin rồi dừng. */
    public static function require(string $module, string $action = 'view'): void
    {
        if (self::currentCan($module, $action)) {
            return;
        }

        $actionLabel = self::ACTIONS[$action] ?? 'truy cập';
        $message = 'Bạn không có quyền ' . mb_strtolower($actionLabel, 'UTF-8') . ' mục này.';

        if (Response::isAjaxRequest()) {
            Response::json(['ok' => false, 'code' => 'forbidden', 'message' => $message], 403);
        }

        http_response_code(403);
        $page = [
            'title' => 'Không có quyền truy cập | ' . settings('site_name'),
            'nav'   => '',
        ];
        $userRole = Session::role();
        $roleLabel = self::roleLabel($userRole);
        $content = '<div class="admin-card admin-403">'
            . '<p class="admin-breadcrumb">Lỗi 403</p>'
            . '<h1 class="admin-h1">Không có quyền truy cập</h1>'
            . '<p class="admin-sub">Vai trò hiện tại của bạn (<strong>' . e($roleLabel)
            . '</strong>) không được phép <strong>' . e(mb_strtolower($actionLabel, 'UTF-8'))
            . '</strong> mục <strong>' . e(self::moduleLabel($module)) . '</strong>.</p>'
            . '<p class="admin-hint">Liên hệ quản trị viên cấp cao nếu bạn cần quyền này.</p>'
            . '<div class="admin-form__actions mt-4">'
            . '<a class="btn btn-primary" href="' . e(url('admin/dashboard')) . '">Về bảng điều khiển</a>'
            . '<a class="btn btn-ghost" href="' . e(url('admin/profile')) . '">Hồ sơ của tôi</a>'
            . '</div></div>';
        if (is_file(BASE_PATH . '/app/Views/layouts/admin.php')) {
            require BASE_PATH . '/app/Views/layouts/admin.php';
        } else {
            echo $content;
        }
        exit;
    }

    // ---------------------------------------------------------------------
    // Phạm vi nhà máy
    // ---------------------------------------------------------------------

    /** @return int[]|null null = không giới hạn nhà máy */
    public static function branchIds(): ?array
    {
        if (self::$branchScopeLoaded) {
            return self::$branchIds;
        }
        self::$branchScopeLoaded = true;

        $role = Session::role();
        if ($role === null) {
            return self::$branchIds = [];
        }
        if ($role === 'super_admin') {
            return self::$branchIds = null;
        }
        $userId = (int) Session::get('auth_id', 0);
        if ($userId <= 0) {
            return self::$branchIds = [];
        }

        $stmt = Database::connection()->prepare('SELECT all_branches FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $all = (int) $stmt->fetchColumn();
        if ($all === 1) {
            return self::$branchIds = null;
        }

        $stmt = Database::connection()->prepare('SELECT branch_id FROM user_branches WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
        $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        return self::$branchIds = array_values(array_unique($ids));
    }

    public static function allBranches(): bool
    {
        return self::branchIds() === null;
    }

    public static function canAccessBranch(int $branchId): bool
    {
        $ids = self::branchIds();
        return $ids === null || in_array($branchId, $ids, true);
    }

    /**
     * Điều kiện SQL cho cột nhà máy (không kèm tiền tố AND).
     * Trả ['', []] nếu không giới hạn; ['1 = 0', []] nếu không có nhà máy nào.
     *
     * @return array{0:string, 1:array<string,int>}
     */
    public static function branchSql(string $column): array
    {
        $ids = self::branchIds();
        if ($ids === null) {
            return ['', []];
        }
        if ($ids === []) {
            return ['1 = 0', []];
        }
        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = 'scope_b' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        return [$column . ' IN (' . implode(', ', $placeholders) . ')', $params];
    }

    /** Chặn khi bản ghi thuộc nhà máy ngoài phạm vi. */
    public static function requireBranch(?int $branchId): void
    {
        if ($branchId === null || self::canAccessBranch($branchId)) {
            return;
        }
        if (Response::isAjaxRequest()) {
            Response::json(['ok' => false, 'code' => 'forbidden', 'message' => 'Bạn không có quyền với dữ liệu của nhà máy này.'], 403);
        }
        self::render403('Bạn không có quyền xem hoặc sửa dữ liệu của nhà máy này.', 'Dữ liệu nhà máy');
    }

    /** Trang 403 trong layout admin (không AJAX) rồi dừng. */
    public static function render403(string $message, string $subject = 'Mục này'): void
    {
        http_response_code(403);
        $page = [
            'title' => 'Không có quyền truy cập | ' . settings('site_name'),
            'nav'   => '',
        ];
        $userRole = Session::role();
        $roleLabel = self::roleLabel($userRole);
        $content = '<div class="admin-card admin-403">'
            . '<p class="admin-breadcrumb">Lỗi 403</p>'
            . '<h1 class="admin-h1">Không có quyền truy cập</h1>'
            . '<p class="admin-sub">' . e($message) . '</p>'
            . '<p class="admin-hint">Vai trò hiện tại của bạn: <strong>' . e($roleLabel) . '</strong>. '
            . 'Liên hệ quản trị viên cấp cao nếu bạn cần quyền này.</p>'
            . '<div class="admin-form__actions mt-4">'
            . '<a class="btn btn-primary" href="' . e(url('admin/dashboard')) . '">Về bảng điều khiển</a>'
            . '<a class="btn btn-ghost" href="' . e(url('admin/profile')) . '">Hồ sơ của tôi</a>'
            . '</div></div>';
        if (is_file(BASE_PATH . '/app/Views/layouts/admin.php')) {
            require BASE_PATH . '/app/Views/layouts/admin.php';
        } else {
            echo $content;
        }
        exit;
    }
}
