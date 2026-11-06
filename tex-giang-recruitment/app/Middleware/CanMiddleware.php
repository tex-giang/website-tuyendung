<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Permissions;

/** Middleware phân quyền theo module: dùng trong route qua $can('jobs'). */
final class CanMiddleware
{
    public static function require(string $module): void
    {
        Permissions::require($module);
    }
}
