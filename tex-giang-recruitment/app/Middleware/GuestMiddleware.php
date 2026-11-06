<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/** Trang đăng nhập: nếu đã đăng nhập thì vào thẳng dashboard. */
final class GuestMiddleware
{
    public static function handle(): void
    {
        if (Session::check()) {
            redirect(url('admin/dashboard'));
        }
    }
}
