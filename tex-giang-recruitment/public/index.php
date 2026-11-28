<?php
/**
 * Front controller duy nhất. DocumentRoot phải trỏ vào thư mục public/.
 */
declare(strict_types=1);

use App\Core\Response;
use App\Core\Router;

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();

$router->setNotFound(static function (int $code): void {
    http_response_code($code);
    if (Response::isAdminApiRequest()) {
        Response::json(['ok' => false, 'code' => 'not_found', 'message' => 'Đường dẫn API không tồn tại.'], $code);
    }
    $page = [
        'title'       => 'Không tìm thấy trang | ' . settings('site_name'),
        'description' => '',
        'body_class'  => 'tg-public',
        'nav'         => '',
    ];
    if (str_starts_with(current_path(), '/admin')) {
        if (\App\Core\Response::isAjaxRequest()) {
            \App\Core\Response::json(['ok' => false, 'message' => 'Đường dẫn quản trị không tồn tại.'], $code);
        }
        $title = 'Không tìm thấy trang';
        $content = '<div class="admin-card"><h1 class="admin-h1">404 — Không tìm thấy trang</h1>'
            . '<p class="mt-2 text-muted">Đường dẫn không tồn tại trong hệ thống quản trị.</p>'
            . '<a class="btn btn-primary mt-4" href="' . e(url('admin/dashboard')) . '">Về bảng điều khiển</a></div>';
        require BASE_PATH . '/app/Views/layouts/admin.php';
        return;
    }
    $message = 'Trang bạn tìm không tồn tại hoặc đã được di chuyển.';
    ob_start();
    require BASE_PATH . '/app/Views/public/404.php';
    $content = (string) ob_get_clean();
    require BASE_PATH . '/app/Views/layouts/public.php';
});

require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/admin.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = current_path();

header('Content-Language: vi');

if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    header('Cache-Control: no-store');
}

// Thống kê lượt truy cập (chỉ trang public, dedupe theo thiết bị/ngày — F5 không tăng)
if ($method === 'GET' && !str_starts_with($path, '/admin')) {
    \App\Services\VisitTracker::track();
}

$router->dispatch($method, $path);
