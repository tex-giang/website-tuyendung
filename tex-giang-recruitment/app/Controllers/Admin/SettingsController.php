<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Setting;

final class SettingsController extends Controller
{
    /** Các khoá cấu hình được phép sửa ở trang này (nhóm general/contact/social/seo). */
    public const GROUPS = [
        'general' => [
            'label' => 'Thông tin chung',
            'fields' => [
                'site_name'     => ['Tên website', 'text', 150],
                'legal_name'    => ['Tên pháp nhân', 'text', 200],
                'jobs_per_page' => ['Số tin mỗi trang (2–50)', 'number', 3],
                'jobs_deadline_days' => ['Số ngày nhận hồ sơ mặc định', 'number', 3],
            ],
        ],
        'contact' => [
            'label' => 'Liên hệ tuyển dụng',
            'fields' => [
                'email'           => ['Email nhận hồ sơ', 'email', 190],
                'zalo'            => ['Link Zalo OA (dán link https://zalo.me/... hoặc số Zalo)', 'text', 500],
            ],
        ],
        'social' => [
            'label' => 'Mạng xã hội',
            'fields' => [
                'facebook' => ['Facebook', 'text', 500],
                'youtube'  => ['YouTube', 'text', 500],
                'website'  => ['Website công ty', 'text', 500],
            ],
        ],
        'seo' => [
            'label' => 'SEO',
            'fields' => [
                'seo_title'       => ['Tiêu đề SEO trang chủ', 'text', 220],
                'seo_description' => ['Mô tả SEO trang chủ', 'textarea', 320],
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
        $allowed = [];
        foreach (self::GROUPS as $group) {
            $allowed = array_merge($allowed, array_keys($group['fields']));
        }

        $values = [];
        $errors = [];

        foreach (self::GROUPS as $group) {
            foreach ($group['fields'] as $key => [$label, $type, $max]) {
                if (!array_key_exists($key, $_POST)) {
                    continue;
                }
                $value = trim((string) $_POST[$key]);

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
            Session::flash('_errors', $errors);
            Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
            $this->redirect(url('admin/settings'));
        }

        Setting::setMany($values);
        AuditLog::record('settings.update', 'settings', null, ['keys' => array_keys($values)]);
        Session::flash('_success', 'Đã lưu cài đặt hệ thống.');
        $this->redirect(url('admin/settings'));
    }
}
