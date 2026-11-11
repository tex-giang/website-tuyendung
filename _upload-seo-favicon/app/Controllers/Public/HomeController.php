<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Seo;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\Job;

final class HomeController extends Controller
{
    public function index(): void
    {
        \App\Models\Job::syncExpired();

        // Trụ sở lấy từ khu đang hoạt động đầu tiên — nguồn cho Organization.address.
        $branches = Branch::active();
        $office = $branches[0] ?? null;

        $this->view('public/home', [
            'heroBanners' => Banner::activeByType('hero'),
            'notice'      => Banner::firstActive('notice'),
            'popup'       => Banner::firstActive('popup'),
            'ad'          => Banner::firstActive('ad'),
            'jobs'        => Job::latestPublished(6),
            'jobCount'    => Job::countPublished(),
            'urgentJobs'  => Job::forUrgentDialog(6),
            'page'        => [
                'title'       => settings('seo_title', 'Tuyển dụng TEX-GIANG'),
                'description' => settings('seo_description'),
                'body_class'  => 'tg-public page-index',
                'nav'         => 'home',
                // Slider ảnh hero + carousel việc làm + dialog "Tuyển gấp".
                'scripts'     => ['home-slider.js', 'home-jobs.js', 'home-urgent-dialog.js'],
                // Trang chủ khai báo tổ chức + website: gốc để Google nối các trang con.
                'jsonld'      => [Seo::organization($office), Seo::website()],
            ],
        ]);
    }
}
