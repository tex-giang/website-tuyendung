<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;

/** Chặn khách chưa đăng nhập khỏi khu vực quản trị. */
final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Session::check()) {
            if (Response::isAjaxRequest()) {
                if (!is_post()) {
                    Session::put('_intended', current_path());
                }
                Response::json([
                    'ok' => false,
                    'code' => 'auth_required',
                    'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.',
                    'redirect' => url('admin/login'),
                ], 401);
            }
            Session::put('_intended', current_path());
            Session::flash('_error', 'Vui lòng đăng nhập để tiếp tục.');
            redirect(url('admin/login'));
        }
    }
}
