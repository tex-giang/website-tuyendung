<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\ContactMessage;
use App\Models\ContentItem;
use App\Models\Job;
use App\Models\Post;

final class PageController extends Controller
{
    public function about(): void
    {
        $this->guardMenu('about');
        $this->view('public/about', [
            'jobCount'       => Job::countPublished(),
            'milestones'     => ContentItem::active('about_milestones'),
            'values'         => ContentItem::active('about_values'),
            'certifications' => ContentItem::active('about_certifications'),
            'page'     => [
                'title'       => 'Về TEX-GIANG | ' . settings('site_name'),
                'description' => 'Công ty Cổ phần TEX-GIANG (TGI, thành lập 2007) — 3 khu sản xuất tại Tân Hương, '
                    . 'Cai Lậy, Chợ Gạo (Đồng Tháp), khoảng 3.500 lao động, chứng nhận SA8000, ISO 9001, OEKO-TEX.',
                'body_class'  => 'tg-public page-about',
                'nav'         => 'about',
            ],
        ]);
    }

    public function why(): void
    {
        $this->guardMenu('why-tex-giang');
        $benefitGroups = ContentItem::active('benefit_groups');
        $benefitItems = ContentItem::active('benefit_items');
        $itemsByGroup = [];
        foreach ($benefitItems as $item) {
            $itemsByGroup[(int) $item['group_id']][] = $item;
        }
        foreach ($benefitGroups as &$group) {
            $group['items'] = $itemsByGroup[(int) $group['id']] ?? [];
        }
        unset($group);

        $this->view('public/why', [
            'benefitGroups' => $benefitGroups,
            'comparisons'   => ContentItem::active('benefit_comparisons'),
            'page' => [
                'title'       => 'Phúc lợi & vì sao chọn TEX-GIANG | ' . settings('site_name'),
                'description' => 'Môi trường làm việc đạt SA8000, ISO 9001; thưởng tháng 13 và các dịp lễ, '
                    . 'phụ cấp nuôi con nhỏ, xe đưa rước và cơm ca tại TEX-GIANG.',
                'body_class'  => 'tg-public page-why',
                'nav'         => 'why-tex-giang',
            ],
        ]);
    }

    public function news(): void
    {
        $this->guardMenu('news');
        $categories = Post::categoriesWithCounts();
        $validSlugs = array_map(static fn(array $c): string => (string) $c['slug'], $categories);

        $categorySlug = trim((string) ($_GET['chuyen-muc'] ?? ''));
        if ($categorySlug !== '' && !in_array($categorySlug, $validSlugs, true)) {
            $categorySlug = '';
        }

        $result = Post::paginate(max(1, (int) ($_GET['page'] ?? 1)), 6, $categorySlug !== '' ? $categorySlug : null);

        $this->view('public/news/index', [
            'result'       => $result,
            'categories'   => $categories,
            'featured'     => Post::latest(3),
            'categorySlug' => $categorySlug,
            'totalAll'     => Post::totalPublished(),
            'page'         => [
                'title'       => 'Tin tức & tuyển dụng | ' . settings('site_name'),
                'description' => 'Thông báo tuyển dụng, hoạt động nội bộ và cẩm nang phỏng vấn tại '
                    . settings('legal_name') . ', KCN Tân Hương, Đồng Tháp.',
                'body_class'  => 'tg-public page-news',
                'nav'         => 'news',
            ],
        ]);
    }

    public function newsDetail(string $slug): void
    {
        $this->guardMenu('news');
        $post = Post::findPublishedBySlug($slug);
        if ($post === null) {
            $this->notFound('Bài viết không tồn tại hoặc đã được gỡ.');
        }
        Post::incrementViews((int) $post['id']);

        $this->view('public/news/detail', [
            'post'    => $post,
            'related' => Post::related($post, 3),
            'page'    => [
                'title'       => $post['title'] . ' | ' . settings('site_name'),
                'description' => excerpt((string) ($post['excerpt'] ?? $post['body']), 160),
                'body_class'  => 'tg-public page-news-detail',
                'nav'         => 'news',
                'og_image'    => $post['image_path'] ?? null,
            ],
        ]);
    }

    public function subscribe(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('_subscribe_message', 'Email chưa hợp lệ. Vui lòng kiểm tra lại.');
            Session::flash('_subscribed_email', $email);
            $this->redirect(url('news'));
        }

        ContactMessage::create([
            'full_name' => 'Khách đăng ký nhận tin',
            'phone'     => '',
            'email'     => $email,
            'subject'   => 'Đăng ký nhận tin tuyển dụng',
            'message'   => 'Đăng ký nhận thông báo đợt tuyển mới qua email: ' . $email,
        ]);

        Session::flash('_subscribe_message', 'Đã ghi nhận email ' . $email . '. Chúng tôi sẽ gửi thông báo khi có đợt tuyển mới.');
        $this->redirect(url('news'));
    }

    public function contact(): void
    {
        $this->guardMenu('contact');
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $this->view('public/contact', [
            'old'    => $old,
            'errors' => $errors,
            'page'   => [
                'title'       => 'Liên hệ tuyển dụng | ' . settings('site_name'),
                'description' => 'Thông tin liên hệ phòng nhân sự ' . settings('legal_name')
                    . ': địa chỉ KCN Tân Hương, số điện thoại người phụ trách, email và lịch tiếp nhận hồ sơ.',
                'body_class'  => 'tg-public page-contact',
                'nav'         => 'contact',
            ],
        ]);
    }

    public function contactSubmit(): void
    {
        $data = [
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'phone'     => trim((string) ($_POST['phone'] ?? '')),
            'email'     => trim((string) ($_POST['email'] ?? '')),
            'area'      => trim((string) ($_POST['area'] ?? '')),
            'message'   => trim((string) ($_POST['message'] ?? '')),
        ];

        $areas = [
            'tan-huong' => 'Khu vực Tân Hương',
            'cho-gao'   => 'Khu vực Chợ Gạo',
            'cai-lay'   => 'Khu vực Cai Lậy',
            'khac'      => 'Cần tư vấn thêm',
        ];

        $errors = [];
        if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
            $errors['full_name'] = 'Nhập họ và tên (2–150 ký tự).';
        }
        if (!isset($areas[$data['area']])) {
            $errors['area'] = 'Chọn khu vực muốn liên hệ.';
        }
        if (!preg_match('/^(?:0|\+?84)[35789]\d{8}$/', preg_replace('/[\s().-]/', '', $data['phone']) ?? '')) {
            $errors['phone'] = 'Nhập số di động Việt Nam hợp lệ, ví dụ 0918 123 456.';
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Kiểm tra lại địa chỉ email.';
        }
        if (mb_strlen($data['message']) < 10 || mb_strlen($data['message']) > 3000) {
            $errors['message'] = 'Nội dung cần từ 10 đến 3000 ký tự.';
        }

        if ($errors !== []) {
            Session::flash('_errors', $errors);
            Session::flash('_old', $data);
            Session::flash('_error', 'Vui lòng kiểm tra lại các mục được đánh dấu.');
            $this->redirect(url('contact') . '#lien-he');
        }

        ContactMessage::create([
            'full_name' => $data['full_name'],
            'phone'     => $data['phone'],
            'email'     => $data['email'],
            'subject'   => 'Hỏi đáp từ trang liên hệ — ' . $areas[$data['area']],
            'message'   => 'Khu vực: ' . $areas[$data['area']] . "\n\n" . $data['message'],
        ]);

        Session::flash('_success', 'Đã gửi câu hỏi. Phòng nhân sự sẽ phản hồi trong giờ làm việc.');
        $this->redirect(url('contact') . '#lien-he');
    }
}
