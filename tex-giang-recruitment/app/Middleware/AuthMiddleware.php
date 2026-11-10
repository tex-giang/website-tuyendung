<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;
use App\Models\User;

/** Chặn khách chưa đăng nhập khỏi khu vực quản trị. */
final class AuthMiddleware
{
    public static function handle(): void
    {
        if (Session::check()) {
            $user = User::find((int) Session::get('auth_id'));
            if ($user !== null && (int) $user['is_active'] === 1) {
                Session::syncUser($user);
                return;
            }
            Session::forget('auth_id');
            Session::forget('auth_user');
            Session::regenerate();
        }

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
