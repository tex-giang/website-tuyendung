<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Phân quyền admin dựa trên ma trận trong app/Config/permissions.php.
 * super_admin được bypass toàn bộ. Mọi kiểm tra dùng Session::role().
 */
final class Permissions
{
    private static ?array $config = null;

    public static function config(): array
    {
        if (self::$config === null) {
            $file = BASE_PATH . '/app/Config/permissions.php';
            self::$config = is_file($file) ? (array) require $file : ['role_labels' => [], 'modules' => []];
        }
        return self::$config;
    }

    /** @return array<string, array{label:string, roles:string[]}> */
    public static function modules(): array
    {
        return self::config()['modules'] ?? [];
    }

    /** @return array<string, string> */
    public static function roleLabels(): array
    {
        return self::config()['role_labels'] ?? [];
    }

    /** @param string[] $roles */
    public static function can(?string $role, string $module, array $roles = []): bool
    {
        if ($role === null || $role === '') {
            return false;
        }
        if ($role === 'super_admin') {
            return true;
        }
        $allowed = $roles !== [] ? $roles : (self::modules()[$module]['roles'] ?? []);
        return in_array($role, $allowed, true);
    }

    public static function currentCan(string $module): bool
    {
        return self::can(Session::role(), $module);
    }

    /** Chặn truy cập module: render trang 403 trong layout admin rồi dừng. */
    public static function require(string $module): void
    {
        if (self::currentCan($module)) {
            return;
        }

        if (Response::isAjaxRequest()) {
            Response::json(['ok' => false, 'code' => 'forbidden', 'message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }

        http_response_code(403);
        $page = [
            'title' => 'Không có quyền truy cập | ' . settings('site_name'),
            'nav'   => '',
        ];
        $requiredLabel = self::modules()[$module]['label'] ?? $module;
        $userRole = Session::role();
        $roleLabel = self::roleLabels()[$userRole ?? ''] ?? ($userRole ?? 'khách');
        $content = '<div class="admin-card admin-403">'
            . '<p class="admin-breadcrumb">Lỗi 403</p>'
            . '<h1 class="admin-h1">Không có quyền truy cập</h1>'
            . '<p class="admin-sub">Vai trò hiện tại của bạn (<strong>' . e($roleLabel)
            . '</strong>) không được phép mở mục <strong>' . e($requiredLabel) . '</strong>.</p>'
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
}
