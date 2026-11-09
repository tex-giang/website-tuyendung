<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;

/** Trang đăng nhập: nếu đã đăng nhập thì vào thẳng dashboard. */
final class GuestMiddleware
{
    public static function handle(): void
    {
        if (Session::check()) {
            if (is_post() && Response::isAjaxRequest()) {
                Response::json(['ok' => true, 'redirect' => url('admin/dashboard')]);
            }
            redirect(url('admin/dashboard'));
        }
    }
}
