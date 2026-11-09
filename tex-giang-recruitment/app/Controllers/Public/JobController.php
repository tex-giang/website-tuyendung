<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Job;

final class JobController extends Controller
{
    public const SALARY_RANGES = [
        'under-8' => 'Dưới 8 triệu (học việc)',
        '8-12'    => '8 – 12 triệu',
        '12-18'   => '12 – 18 triệu',
        'over-18' => 'Trên 18 triệu',
    ];

    public function index(): void
    {
        Job::syncExpired();
        $this->guardMenu('jobs');
        $filters = [
            'q'          => trim((string) ($_GET['q'] ?? '')),
            'location'   => (string) ($_GET['location'] ?? ''),
            'department' => (string) ($_GET['department'] ?? ''),
            'salary'     => (string) ($_GET['salary'] ?? ''),
            'sort'       => (string) ($_GET['sort'] ?? 'newest'),
        ];

        // Chỉ nhận giá trị có trong danh mục để tránh SQL/URL rác.
        if (!isset(Branch::options()[$filters['location']])) {
            $filters['location'] = '';
        }
        if (!isset(Category::options()[$filters['department']])) {
            $filters['department'] = '';
        }
        if (!isset(self::SALARY_RANGES[$filters['salary']])) {
            $filters['salary'] = '';
        }
        if (!in_array($filters['sort'], ['newest', 'urgent'], true)) {
            $filters['sort'] = 'newest';
        }

        $perPage = max(2, setting_int('jobs_per_page', 6));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Job::paginate($filters, $page, $perPage);

        $this->view('public/jobs/index', [
            'result'      => $result,
            'filters'     => $filters,
            'branches'    => Branch::options(),
            'categories'  => Category::options(),
            'salaryRanges' => self::SALARY_RANGES,
            'page'        => [
                'title'       => 'Việc làm đang tuyển | ' . settings('site_name'),
                'description' => 'Danh sách vị trí đang tuyển tại ' . settings('legal_name')
                    . ': công nhân may, QC, kỹ sư IE, kế toán, bảo trì.',
                'body_class'  => 'tg-public page-jobs',
                'nav'         => 'jobs',
                // Drawer bộ lọc mobile + tự submit khi đổi select/sort.
                'scripts'     => ['jobs.js'],
            ],
        ]);
    }

    public function show(string $slug): void
    {
        Job::syncExpired();
        $this->guardMenu('jobs');
        $job = Job::findPublishedBySlug($slug);
        if ($job === null) {
            $this->notFound('Tin tuyển dụng không tồn tại hoặc đã hết hạn.');
        }

        Job::incrementViews((int) $job['id']);

        $this->view('public/jobs/detail', [
            'job'     => $job,
            'related' => Job::related($job, 3),
            'page'    => [
                'title'       => ($job['meta_title'] ?? '') !== '' ? $job['meta_title'] : $job['title'] . ' | ' . settings('site_name'),
                'description' => ($job['meta_description'] ?? '') !== '' ? $job['meta_description'] : excerpt((string) $job['description'], 160),
                'body_class'  => 'tg-public page-job-detail',
                'nav'         => 'jobs',
                'og_title'    => $job['title'],
            ],
        ]);
    }
}
