<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Application;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\Post;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $jobCounts = Job::countsByStatus();
        $appCounts = Application::countsByStatus();

        $this->view('admin/dashboard/index', [
            'jobCounts'    => $jobCounts,
            'appCounts'    => $appCounts,
            'jobTotal'     => array_sum($jobCounts),
            'appTotal'     => Application::total(),
            'newApps'      => Application::newCount(),
            'latestApps'   => Application::latest(6),
            'publishedNow' => Job::countPublished(),
            'postTotal'    => Post::totalPublished(),
            'contactNew'   => ContactMessage::newCount(),
            'page'         => [
                'title' => 'Bảng điều khiển | ' . settings('site_name'),
                'nav'   => 'dashboard',
            ],
        ], 'layouts/admin');
    }
}
