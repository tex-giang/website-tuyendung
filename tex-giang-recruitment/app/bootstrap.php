<?php
/**
 * Bootstrap dùng chung cho web và CLI.
 * Không dùng Composer: autoloader PSR-4 tối giản để chạy được trên shared hosting
 * (cPanel) mà không cần cài đặt gì thêm.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Env.php';

\App\Core\Env::load(BASE_PATH . '/.env');

// PSR-4: App\ => app/
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/helpers.php';

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'));

$debug = env_bool('APP_DEBUG', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED);

// Lỗi không lường trước: ghi log, không để lộ chi tiết ra ngoài khi production.
set_exception_handler(static function (Throwable $e): void {
    error_log('[TEX-GIANG] ' . $e::class . ': ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (str_starts_with(current_path(), '/admin') && \App\Core\Response::isAjaxRequest()) {
        \App\Core\Response::json(['ok' => false, 'message' => 'Máy chủ chưa xử lý xong yêu cầu. Vui lòng kiểm tra dữ liệu trước khi thử lại.'], 500);
    }
    if (env_bool('APP_DEBUG', false)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $e::class . ': ' . $e->getMessage() . "\n\n" . $e->getTraceAsString();
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    $title = 'Đã xảy ra lỗi hệ thống';
    $content = '<div class="tg-container py-24 text-center"><h1 class="text-[28px] font-medium text-ink">'
        . 'Đã xảy ra lỗi hệ thống</h1><p class="mt-3 text-[16px] text-muted">Vui lòng thử lại sau hoặc liên hệ phòng nhân sự.</p>'
        . '<a class="tg-btn tg-btn--primary mt-6" href="' . e(url('/')) . '">Về trang chủ</a></div>';
    $layout = BASE_PATH . '/app/Views/layouts/public.php';
    if (is_file($layout)) {
        require $layout;
    } else {
        echo "<!doctype html><meta charset=\"utf-8\"><title>{$title}</title>{$content}";
    }
});

\App\Core\Session::start();
