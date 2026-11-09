<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Banner;
use App\Services\UploadService;

final class BannerController extends Controller
{
    public const TYPES = [
        'hero'   => 'Slide đầu trang',
        'notice' => 'Dải thông báo',
        'popup'  => 'Thẻ nổi',
        'ad'     => 'Dải CTA quảng cáo',
    ];

    public function index(): void
    {
        $banners = Banner::all();
        $grouped = array_fill_keys(array_keys(self::TYPES), []);
        foreach ($banners as $banner) {
            $grouped[$banner['type']][] = $banner;
        }

        $this->view('admin/banners/index', [
            'grouped' => $grouped,
            'types'   => self::TYPES,
            'total'   => count($banners),
            'page'    => ['title' => 'Banner & thông báo | ' . settings('site_name'), 'nav' => 'banners'],
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $banner = Banner::find((int) $id);
        if ($banner === null) {
            Session::flash('_error', 'Banner không tồn tại.');
            $this->redirect(url('admin/banners'));
        }
        $this->form($banner);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate($_POST);

        $upload = UploadService::storePublicImage($_FILES['image'] ?? null, 'banners');
        if (!$upload['ok']) {
            $errors['image'] = $upload['error'];
        } elseif ($upload['path'] !== '') {
            $data['image_path'] = $upload['path'];
        }

        $uploadSmall = UploadService::storePublicImage($_FILES['image_small'] ?? null, 'banners');
        if (!$uploadSmall['ok']) {
            $errors['image_small'] = $uploadSmall['error'];
        } elseif ($uploadSmall['path'] !== '') {
            $data['image_path_small'] = $uploadSmall['path'];
        }

        if ($data['type'] === 'hero' && ($data['image_path'] ?? '') === '') {
            $errors['image'] = 'Slide đầu trang cần ảnh.';
        }

        if ($errors !== []) {
            if ($upload['ok'] && $upload['path'] !== '') {
                UploadService::deletePublicImage($upload['path']);
            }
            if ($uploadSmall['ok'] && $uploadSmall['path'] !== '') {
                UploadService::deletePublicImage($uploadSmall['path']);
            }
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/banners/create'), $errors, $data);
        }

        $id = Banner::create($data);
        AuditLog::record('banner.create', 'banner', $id, ['name' => $data['name']]);
        $this->formSuccess('Đã tạo banner "' . $data['name'] . '".', url('admin/banners'));
    }

    public function update(string $id): void
    {
        $bannerId = (int) $id;
        $existing = Banner::find($bannerId);
        if ($existing === null) {
            $this->formError('Banner không tồn tại.', url('admin/banners'), status: 404);
        }

        [$data, $errors] = $this->validate($_POST);

        $upload = UploadService::storePublicImage($_FILES['image'] ?? null, 'banners');
        if (!$upload['ok']) {
            $errors['image'] = $upload['error'];
        } elseif ($upload['path'] !== '') {
            $data['image_path'] = $upload['path'];
        }

        $uploadSmall = UploadService::storePublicImage($_FILES['image_small'] ?? null, 'banners');
        if (!$uploadSmall['ok']) {
            $errors['image_small'] = $uploadSmall['error'];
        } elseif ($uploadSmall['path'] !== '') {
            $data['image_path_small'] = $uploadSmall['path'];
        }

        if ($data['type'] === 'hero' && ($data['image_path'] ?? '') === '') {
            $errors['image'] = 'Slide đầu trang cần ảnh.';
        }

        if ($errors !== []) {
            if ($upload['ok'] && $upload['path'] !== '') {
                UploadService::deletePublicImage($upload['path']);
            }
            if ($uploadSmall['ok'] && $uploadSmall['path'] !== '') {
                UploadService::deletePublicImage($uploadSmall['path']);
            }
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/banners/' . $bannerId . '/edit'), $errors, $data);
        }

        Banner::update($bannerId, $data);
        if (($upload['path'] ?? '') !== '' && ($existing['image_path'] ?? '') !== $upload['path']) {
            UploadService::deletePublicImage((string) ($existing['image_path'] ?? ''));
        }
        if (($uploadSmall['path'] ?? '') !== '' && ($existing['image_path_small'] ?? '') !== $uploadSmall['path']) {
            UploadService::deletePublicImage((string) ($existing['image_path_small'] ?? ''));
        }
        AuditLog::record('banner.update', 'banner', $bannerId, ['name' => $data['name']]);
        $this->formSuccess('Đã cập nhật banner.', url('admin/banners'));
    }

    public function toggle(string $id): void
    {
        $banner = Banner::find((int) $id);
        if ($banner === null) {
            $this->formError('Banner không tồn tại.', url('admin/banners'), status: 404);
        }
        Banner::toggleActive((int) $id);
        AuditLog::record('banner.toggle', 'banner', (int) $id);
        $this->formSuccess(((int) $banner['is_active'] === 1 ? 'Đã tắt' : 'Đã bật') . ' banner "' . $banner['name'] . '".', url('admin/banners'));
    }

    public function destroy(string $id): void
    {
        $bannerId = (int) $id;
        $banner = Banner::find($bannerId);
        if ($banner === null) {
            $this->formError('Banner không tồn tại.', url('admin/banners'), status: 404);
        }
        Banner::delete($bannerId);
        UploadService::deletePublicImage((string) ($banner['image_path'] ?? ''));
        UploadService::deletePublicImage((string) ($banner['image_path_small'] ?? ''));
        AuditLog::record('banner.delete', 'banner', $bannerId, ['name' => $banner['name']]);
        $this->formSuccess('Đã xoá banner "' . $banner['name'] . '".', url('admin/banners'));
    }

    /** @param array<string, mixed>|null $banner */
    private function form(?array $banner): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $banner ?? [
            'id' => null, 'name' => '', 'type' => 'hero', 'title' => '', 'subtitle' => '',
            'description' => '', 'image_path' => '', 'image_alt' => '', 'link_url' => '',
            'link_label' => '', 'cta_label' => '', 'is_active' => 1, 'sort_order' => 0,
            'starts_at' => '', 'ends_at' => '',
        ];
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        $startsAt = (string) ($values['starts_at'] ?? '');
        if ($startsAt !== '' && !str_contains($startsAt, 'T')) {
            $startsAt = date('Y-m-d\TH:i', strtotime($startsAt));
        }
        $endsAt = (string) ($values['ends_at'] ?? '');
        if ($endsAt !== '' && !str_contains($endsAt, 'T')) {
            $endsAt = date('Y-m-d\TH:i', strtotime($endsAt));
        }

        $this->view('admin/banners/form', [
            'banner'   => $banner,
            'values'   => $values,
            'errors'   => $errors,
            'types'    => self::TYPES,
            'startsAt' => $startsAt,
            'endsAt'   => $endsAt,
            'page'     => [
                'title' => ($banner === null ? 'Thêm banner' : 'Sửa banner') . ' | ' . settings('site_name'),
                'nav'   => 'banners',
            ],
        ], 'layouts/admin');
    }

    /** @return array{0: array<string,mixed>, 1: array<string,string>} */
    private function validate(array $post): array
    {
        $data = [
            'name'        => trim((string) ($post['name'] ?? '')),
            'type'        => (string) ($post['type'] ?? 'hero'),
            'title'       => trim((string) ($post['title'] ?? '')),
            'subtitle'    => trim((string) ($post['subtitle'] ?? '')),
            'description' => trim((string) ($post['description'] ?? '')),
            'image_path'  => trim((string) ($post['image_path'] ?? '')),
            'image_path_small' => trim((string) ($post['image_path_small'] ?? '')),
            'image_alt'   => trim((string) ($post['image_alt'] ?? '')),
            'link_url'    => trim((string) ($post['link_url'] ?? '')),
            'link_label'  => trim((string) ($post['link_label'] ?? '')),
            'cta_label'   => trim((string) ($post['cta_label'] ?? '')),
            'is_active'   => !empty($post['is_active']) ? 1 : 0,
            'sort_order'  => (int) ($post['sort_order'] ?? 0),
            'starts_at'   => trim((string) ($post['starts_at'] ?? '')),
            'ends_at'     => trim((string) ($post['ends_at'] ?? '')),
        ];

        $errors = [];
        if (mb_strlen($data['name']) < 3) {
            $errors['name'] = 'Tên banner tối thiểu 3 ký tự.';
        }
        if (!isset(self::TYPES[$data['type']])) {
            $errors['type'] = 'Loại banner không hợp lệ.';
        }
        foreach (['starts_at', 'ends_at'] as $field) {
            if ($data[$field] === '') {
                continue;
            }
            $normalized = str_replace('T', ' ', $data[$field]);
            $timestamp = strtotime($normalized);
            if ($timestamp === false) {
                $errors[$field] = 'Thời gian không đúng định dạng.';
            } else {
                $data[$field] = date('Y-m-d H:i:s', $timestamp);
            }
        }
        if ($data['starts_at'] !== '' && $data['ends_at'] !== '' && strtotime((string) $data['ends_at']) < strtotime((string) $data['starts_at'])) {
            $errors['ends_at'] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }
        if ($data['image_path'] !== '' && !str_starts_with($data['image_path'], 'assets/uploads/')) {
            // Không cho nhập đường dẫn tuỳ ý; ảnh phải do upload tạo ra.
            $data['image_path'] = '';
        }
        if ($data['image_path_small'] !== '' && !str_starts_with($data['image_path_small'], 'assets/uploads/')) {
            $data['image_path_small'] = '';
        }

        if (mb_strlen($data['description']) > 600) {
            $errors['description'] = 'Mô tả tối đa 600 ký tự.';
        }

        return [$data, $errors];
    }
}
