<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\User;

final class AuditController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q'       => trim((string) ($_GET['q'] ?? '')),
            'user_id' => (string) ($_GET['user_id'] ?? ''),
        ];
        $result = AuditLog::paginate($filters, max(1, (int) ($_GET['page'] ?? 1)), 500);

        $this->view('admin/audit/index', [
            'result'  => $result,
            'filters' => $filters,
            'users'   => User::all(),
            'page'    => ['title' => 'Nhật ký thao tác | ' . settings('site_name'), 'nav' => 'audit'],
        ], 'layouts/admin');
    }
}
