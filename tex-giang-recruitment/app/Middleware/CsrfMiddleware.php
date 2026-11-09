<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;

/** Kiểm tra CSRF cho mọi POST. Áp dụng trước khi controller chạy. */
final class CsrfMiddleware
{
    public static function handle(bool $admin = false): void
    {
        if (!is_post()) {
            return;
        }
        if (Csrf::checkRequest()) {
            return;
        }

        if ($admin && Response::isAjaxRequest()) {
            Response::json([
                'ok' => false,
                'code' => 'csrf',
                'message' => 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng tải lại trang.',
            ], 403);
        }
        Session::flash('_error', 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.');
        Response::back(url('admin/login'));
    }
}
