<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/** Chặn khách chưa đăng nhập khỏi khu vực quản trị. */
final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Session::check()) {
            Session::put('_intended', current_path());
            Session::flash('_error', 'Vui lòng đăng nhập để tiếp tục.');
            redirect(url('admin/login'));
        }
    }
}
