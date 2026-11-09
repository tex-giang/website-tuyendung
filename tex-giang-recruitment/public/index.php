<?php
/**
 * Front controller duy nhất. DocumentRoot phải trỏ vào thư mục public/.
 */
declare(strict_types=1);

use App\Core\Router;

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();

$router->setNotFound(static function (int $code): void {
    http_response_code($code);
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
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$base = rtrim((string) env('APP_BASE', ''), '/');
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}

// Hosting trỏ DocumentRoot vào thư mục cha (truy cập qua /public/...):
// tự bỏ tiền tố /public để website vẫn chạy đúng như ở domain gốc.
if ($base === '' && ($path === '/public' || $path === '/public/index.php')) {
    $path = '/';
} elseif ($base === '' && str_starts_with($path, '/public/')) {
    $path = substr($path, strlen('/public')) ?: '/';
}

if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    header('Cache-Control: no-store');
}

// Thống kê lượt truy cập (chỉ trang public, dedupe theo thiết bị/ngày — F5 không tăng)
if ($method === 'GET' && !str_starts_with($path, '/admin')) {
    \App\Services\VisitTracker::track();
}

$router->dispatch($method, $path);
