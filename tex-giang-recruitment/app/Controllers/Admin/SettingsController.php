<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Setting;

final class SettingsController extends Controller
{
    /** Các khoá cấu hình được phép sửa, theo thứ tự hiển thị trong form. */
    public const GROUPS = [
        'general' => [
            'label' => 'Thông tin chung',
            'hint' => 'Tên hiển thị và pháp nhân của doanh nghiệp.',
            'fields' => [
                'site_name'     => ['Tên website', 'text', 150],
                'legal_name'    => ['Tên pháp nhân', 'text', 200],
                'website'       => ['Website công ty', 'text', 500],
            ],
        ],
        'recruitment' => [
            'label' => 'Tuyển dụng',
            'hint' => 'Giá trị mặc định cho tin tuyển dụng và địa chỉ nhận hồ sơ.',
            'fields' => [
                'jobs_per_page'      => ['Số tin mỗi trang', 'number', 3],
                'email'              => ['Email tuyển dụng', 'email', 190],
            ],
        ],
        'contact' => [
            'label' => 'Kênh liên hệ',
            'hint' => 'Các kênh hiển thị ở chân trang và trang Liên hệ.',
            'fields' => [
                'zalo'     => ['Zalo', 'text', 500],
                'facebook'  => ['Facebook', 'text', 500],
                'messenger' => ['Messenger (m.me/…)', 'text', 500],
                'youtube'  => ['YouTube', 'text', 500],
            ],
        ],
        'seo' => [
            'label' => 'SEO trang chủ',
            'hint' => 'Nội dung có thể hiển thị trong kết quả tìm kiếm.',
            'fields' => [
                'seo_title'       => ['Tiêu đề SEO', 'text', 220],
                'seo_description' => ['Mô tả SEO', 'textarea', 320],
            ],
        ],
    ];

    public function index(): void
    {
        $this->view('admin/settings/index', [
            'settings' => settings_all(),
            'groups'   => self::GROUPS,
            'page'     => ['title' => 'Cài đặt hệ thống | ' . settings('site_name'), 'nav' => 'settings'],
        ], 'layouts/admin');
    }

    public function update(): void
    {
        $values = [];
        $old = [];
        $errors = [];

        foreach (self::GROUPS as $group) {
            foreach ($group['fields'] as $key => [$label, $type, $max]) {
                if (!array_key_exists($key, $_POST)) {
                    continue;
                }
                $value = trim((string) $_POST[$key]);
                $old[$key] = $value;

                if (mb_strlen($value) > $max) {
                    $errors[$key] = $label . ' tối đa ' . $max . ' ký tự.';
                    continue;
                }
                if ($key === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$key] = 'Email chưa hợp lệ.';
                    continue;
                }

                if ($key === 'jobs_per_page') {
                    $int = (int) $value;
                    if ($int < 2 || $int > 50) {
                        $errors[$key] = 'Số tin mỗi trang phải từ 2 đến 50.';
                        continue;
                    }
                    $value = (string) $int;
                }
                if (in_array($key, ['site_name', 'legal_name'], true) && $value === '') {
                    $errors[$key] = $label . ' không được để trống.';
                    continue;
                }
                $values[$key] = $value;
            }
        }

        if ($errors !== []) {
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/settings'), $errors, $old);
        }

        Setting::setMany($values);
        AuditLog::record('settings.update', 'settings', null, ['keys' => array_keys($values)]);
        $this->formSuccess('Đã lưu cài đặt hệ thống.', url('admin/settings'));
    }
}
