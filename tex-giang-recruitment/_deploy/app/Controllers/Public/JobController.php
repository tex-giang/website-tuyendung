<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Seo;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Job;

final class JobController extends Controller
{
    // Bộ lọc mức lương đã bỏ khỏi trang /jobs (dùng thanh lọc ngang 4 ô).
    // Cột jobs.salary_range vẫn được giữ và ghi khi lưu tin, để sau này có thể
    // bật lại bộ lọc mà không cần migrate dữ liệu.

    public function index(): void
    {
        Job::syncExpired();
        $this->guardMenu('jobs');
        $filters = [
            'q'          => trim((string) ($_GET['q'] ?? '')),
            'location'   => (string) ($_GET['location'] ?? ''),
            'block'      => (string) ($_GET['khoi'] ?? ''),
            'department' => (string) ($_GET['department'] ?? ''),
            'sort'       => (string) ($_GET['sort'] ?? 'newest'),
        ];

        // Chỉ nhận giá trị có trong danh mục để tránh SQL/URL rác.
        if (!isset(Branch::options()[$filters['location']])) {
            $filters['location'] = '';
        }
        if (!Job::blockValid($filters['block'])) {
            $filters['block'] = '';
        }
        $allCategories = Category::options();

        // Dropdown Bộ phận: CHỈ hiện bộ phận đang có tin hiển thị công khai —
        // tránh ứng viên chọn phải bộ phận trống rồi thấy 0 kết quả.
        // KHÔNG hiện số lượng trong tên (showCount = false): chỉ hiện tên bộ phận
        // cho gọn; con số vẫn dùng để quyết định bộ phận nào được hiện.
        // Đếm theo đúng Khối đang chọn để danh sách khớp với kết quả lọc.
        $categories = Category::optionsWithJobs(
            $filters['block'] !== '' ? $filters['block'] : null,
            null,
            false
        );

        if (!isset($allCategories[$filters['department']])) {
            $filters['department'] = '';
        } elseif (!isset($categories[$filters['department']])) {
            // Bộ phận đang lọc không còn tin hiển thị (hoặc không thuộc Khối đã chọn).
            // Bỏ điều kiện VÀ dọn khỏi URL: nếu chỉ sửa biến nội bộ, URL vẫn giữ
            // ?department=… nên lần lọc sau JS đọc lại và chip/lọc bị lệch.
            $filters['department'] = '';
            $cleanQuery = array_filter([
                'q'          => $filters['q'],
                'location'   => $filters['location'],
                'khoi'       => $filters['block'],
                'sort'       => $filters['sort'] !== 'newest' ? $filters['sort'] : '',
                'page'       => ($_GET['page'] ?? '') !== '' && (int) $_GET['page'] > 1 ? (string) (int) $_GET['page'] : '',
            ], static fn(string $value): bool => $value !== '');

            if (!$this->isAjaxRequest()) {
                $this->redirect(url('jobs') . ($cleanQuery === [] ? '' : '?' . http_build_query($cleanQuery)));
            }
        }
        if (!in_array($filters['sort'], ['newest', 'urgent'], true)) {
            $filters['sort'] = 'newest';
        }

        $perPage = max(2, setting_int('jobs_per_page', 6));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Job::paginate($filters, $page, $perPage);

        // ItemList giúp Google hiểu đây là danh sách tin, không phải trang chi tiết.
        $listing = [];
        foreach ($result['items'] as $item) {
            $listing[] = [
                'url'  => url('jobs/' . $item['slug']),
                'name' => (string) preg_replace('/^\d+\s+/', '', (string) $item['title']),
            ];
        }

        // Query giữ nguyên để phân trang / sắp xếp không mất bộ lọc (kể cả tên tham số cũ).
        $query = [];
        foreach (['q', 'location', 'department'] as $key) {
            if ($filters[$key] !== '') {
                $query[$key] = $filters[$key];
            }
        }
        if ($filters['block'] !== '') {
            $query['khoi'] = $filters['block'];
        }
        if ($filters['sort'] !== 'newest') {
            $query['sort'] = $filters['sort'];
        }

        $viewData = [
            'result'      => $result,
            'filters'     => $filters,
            'query'       => $query,
            'branches'    => Branch::options(),
            'blocks'      => Job::BLOCKS,
            'categories'  => $categories,
        ];

        // Lọc/sắp xếp/phân trang bằng fetch: chỉ trả phần kết quả + form lọc,
        // KHÔNG trả cả trang → trình duyệt không nhảy về đầu trang.
        if (\App\Core\Response::wantsPublicFragment()) {
            $this->viewFragment('partials/public/jobs-fragment', $viewData);
        }

        $this->view('public/jobs/index', $viewData + [
            'page'        => [
                'title'       => 'Việc làm đang tuyển | ' . settings('site_name'),
                'description' => 'Danh sách vị trí đang tuyển tại ' . settings('legal_name')
                    . ': công nhân may, QC, kỹ sư IE, kế toán, bảo trì.',
                'body_class'  => 'tg-public page-jobs',
                'nav'         => 'jobs',
                // Drawer bộ lọc mobile + lọc tại chỗ bằng fetch (không nhảy đầu trang).
                'scripts'     => ['jobs.js'],
                'jsonld'      => [
                    Seo::itemList($listing, 'Việc làm đang tuyển tại ' . settings('legal_name')),
                    Seo::breadcrumbs([['Trang chủ', ''], ['Việc làm', 'jobs']]),
                ],
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

        $title = (string) preg_replace('/^\d+\s+/', '', (string) $job['title']);

        $this->view('public/jobs/detail', [
            'job'     => $job,
            'related' => Job::related($job, 3),
            'page'    => [
                'title'       => ($job['meta_title'] ?? '') !== '' ? $job['meta_title'] : $job['title'] . ' | ' . settings('site_name'),
                'description' => ($job['meta_description'] ?? '') !== '' ? $job['meta_description'] : excerpt((string) $job['description'], 160),
                'body_class'  => 'tg-public page-job-detail',
                'nav'         => 'jobs',
                'og_title'    => $job['title'],
                // JobPosting là điều kiện duy nhất để tin lên Google Jobs.
                'jsonld'      => [
                    Seo::jobPosting($job),
                    Seo::breadcrumbs([
                        ['Trang chủ', ''],
                        ['Việc làm', 'jobs'],
                        [$title, 'jobs/' . $job['slug']],
                    ]),
                    Seo::organization(),
                ],
            ],
        ]);
    }
}
