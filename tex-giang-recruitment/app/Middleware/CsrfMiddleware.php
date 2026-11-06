<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Session;

/** Kiểm tra CSRF cho mọi POST. Áp dụng trước khi controller chạy. */
final class CsrfMiddleware
{
    public static function handle(): void
    {
        if (!is_post()) {
            return;
        }
        if (Csrf::checkRequest()) {
            return;
        }

        Session::flash('_error', 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.');
        \App\Core\Response::back(url('admin/login'));
    }
}
