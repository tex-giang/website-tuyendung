<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/** Phân quyền theo vai trò. Gọi trong route: fn() => RoleMiddleware::only(['super_admin']). */
final class RoleMiddleware
{
    /** @param string[] $allowed */
    public static function only(array $allowed): void
    {
        $role = Session::role();
        if ($role === null || !in_array($role, $allowed, true)) {
            http_response_code(403);
            $layout = BASE_PATH . '/app/Views/layouts/admin.php';
            $title = 'Không có quyền truy cập';
            $content = '<div class="admin-card"><h1>403 — Không có quyền</h1>'
                . '<p class="mt-2">Tài khoản của bạn không được phép thực hiện thao tác này.</p>'
                . '<a class="btn btn-primary mt-4" href="' . e(url('admin/dashboard')) . '">Về bảng điều khiển</a></div>';
            if (is_file($layout)) {
                require $layout;
            } else {
                echo $content;
            }
            exit;
        }
    }
}
