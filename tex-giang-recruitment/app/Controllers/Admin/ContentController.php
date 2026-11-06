<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Setting;

final class ContentController extends Controller
{
    /** Nội dung soạn thảo trên các trang công khai. */
    public const FIELDS = [
        'content_hero_title_1'  => ['Tiêu đề hero — dòng 1', 'text', 120],
        'content_hero_title_2'  => ['Tiêu đề hero — dòng 2 (màu nhấn)', 'text', 120],
        'content_hero_subtitle' => ['Mô tả dưới tiêu đề hero', 'textarea', 400],
        'content_about_1'       => ['Trang chủ — đoạn giới thiệu 1', 'textarea', 800],
        'content_about_2'       => ['Trang chủ — đoạn giới thiệu 2', 'textarea', 800],
        'content_philosophy'    => ['Triết lý / khẩu hiệu', 'text', 200],
        'content_footer_intro'  => ['Giới thiệu ngắn ở footer', 'textarea', 300],
        'stat_1_value'          => ['Số liệu 1 — giá trị', 'text', 20],
        'stat_1_label'          => ['Số liệu 1 — nhãn', 'text', 120],
        'stat_2_value'          => ['Số liệu 2 — giá trị', 'text', 20],
        'stat_2_label'          => ['Số liệu 2 — nhãn', 'text', 120],
        'stat_3_value'          => ['Số liệu 3 — giá trị', 'text', 20],
        'stat_3_label'          => ['Số liệu 3 — nhãn', 'text', 120],
        'stat_4_value'          => ['Số liệu 4 — giá trị', 'text', 20],
        'stat_4_label'          => ['Số liệu 4 — nhãn', 'text', 120],
    ];

    public function index(): void
    {
        $this->view('admin/content/index', [
            'settings' => settings_all(),
            'fields'   => self::FIELDS,
            'page'     => ['title' => 'Nội dung website | ' . settings('site_name'), 'nav' => 'content'],
        ], 'layouts/admin');
    }

    public function update(): void
    {
        $values = [];
        $errors = [];

        foreach (self::FIELDS as $key => [$label, $type, $max]) {
            if (!array_key_exists($key, $_POST)) {
                continue;
            }
            $value = trim((string) $_POST[$key]);
            if (mb_strlen($value) > $max) {
                $errors[$key] = $label . ' tối đa ' . $max . ' ký tự.';
                continue;
            }
            if (in_array($key, ['content_hero_title_1', 'content_hero_title_2', 'content_about_1'], true) && $value === '') {
                $errors[$key] = $label . ' không được để trống.';
                continue;
            }
            $values[$key] = $value;
        }

        if ($errors !== []) {
            Session::flash('_errors', $errors);
            Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
            $this->redirect(url('admin/content'));
        }

        Setting::setMany($values);
        AuditLog::record('content.update', 'settings', null, ['keys' => array_keys($values)]);
        Session::flash('_success', 'Đã cập nhật nội dung website.');
        $this->redirect(url('admin/content'));
    }
}
