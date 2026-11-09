<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\ContentItem;

/**
 * Quản trị nội dung 2 trang tĩnh có bảng dữ liệu:
 *  - Về chúng tôi: mốc phát triển / tầm nhìn - sứ mệnh - giá trị / chứng nhận
 *  - Phúc lợi: nhóm phúc lợi + gạch đầu dòng / bảng so sánh
 *
 * Dùng chung một controller + 2 view tổng quát; cấu hình cột ở TYPES.
 */
final class PageContentController extends Controller
{
    /** Nhóm trang hiển thị thành tab trên đầu trang quản trị. */
    private const GROUPS = [
        'about' => [
            'label' => 'Trang Về chúng tôi',
            'nav'   => 'pages-about',
            'types' => ['milestone', 'value', 'certification'],
        ],
        'benefits' => [
            'label' => 'Trang Phúc lợi',
            'nav'   => 'pages-benefits',
            'types' => ['benefit-group', 'benefit-item', 'comparison'],
        ],
    ];

    /** Cấu hình từng loại nội dung: bảng, cột hiển thị, field form. */
    private const TYPES = [
        'milestone' => [
            'group'   => 'about',
            'label'   => 'Mốc phát triển',
            'singular'=> 'mốc phát triển',
            'table'   => 'about_milestones',
            'columns' => ['year_label' => 'Mốc', 'text' => 'Nội dung', 'is_highlight' => 'Nhãn "Đang vận hành"'],
            'quick_fields' => ['year_label' => 'Mốc (VD: 2025)', 'text' => 'Nội dung'],
            'fields'  => [
                'year_label'   => ['Mốc thời gian', 'text', 40, true],
                'text'         => ['Nội dung', 'textarea', 500, true],
                'is_highlight' => ['Hiện nhãn "Đang vận hành"', 'checkbox', 0, false],
                'is_active'    => ['Hiển thị ngoài website', 'checkbox', 0, false],
                'sort_order'   => ['Thứ tự', 'number', 0, false],
            ],
        ],
        'value' => [
            'group'   => 'about',
            'label'   => 'Tầm nhìn — Sứ mệnh — Giá trị',
            'singular'=> 'giá trị',
            'table'   => 'about_values',
            'columns' => ['title' => 'Tiêu đề', 'body' => 'Nội dung'],
            'quick_fields' => ['title' => 'Tiêu đề', 'body' => 'Nội dung'],
            'fields'  => [
                'title'      => ['Tiêu đề', 'text', 120, true],
                'body'       => ['Nội dung', 'textarea', 800, true],
                'is_active'  => ['Hiển thị ngoài website', 'checkbox', 0, false],
                'sort_order' => ['Thứ tự', 'number', 0, false],
            ],
        ],
        'certification' => [
            'group'   => 'about',
            'label'   => 'Chứng nhận đang áp dụng',
            'singular'=> 'chứng nhận',
            'table'   => 'about_certifications',
            'columns' => ['label' => 'Chứng nhận'],
            'quick_fields' => ['label' => 'Chứng nhận'],
            'fields'  => [
                'label'      => ['Chứng nhận', 'text', 200, true],
                'is_active'  => ['Hiển thị ngoài website', 'checkbox', 0, false],
                'sort_order' => ['Thứ tự', 'number', 0, false],
            ],
        ],
        'benefit-group' => [
            'group'   => 'benefits',
            'label'   => 'Nhóm phúc lợi',
            'singular'=> 'nhóm phúc lợi',
            'table'   => 'benefit_groups',
            'columns' => ['title' => 'Tiêu đề', 'intro' => 'Mô tả', 'icon' => 'Icon'],
            'quick_fields' => ['title' => 'Tiêu đề nhóm', 'icon' => 'Icon', 'intro' => 'Mô tả'],
            'fields'  => [
                'title'      => ['Tiêu đề nhóm', 'text', 200, true],
                'icon'       => ['Icon', 'select', 80, true],
                'intro'      => ['Mô tả ngắn', 'textarea', 500, false],
                'is_active'  => ['Hiển thị ngoài website', 'checkbox', 0, false],
                'sort_order' => ['Thứ tự', 'number', 0, false],
            ],
        ],
        'benefit-item' => [
            'group'   => 'benefits',
            'label'   => 'Gạch đầu dòng phúc lợi',
            'singular'=> 'gạch đầu dòng',
            'table'   => 'benefit_items',
            'columns' => ['group_id' => 'Thuộc nhóm', 'text' => 'Nội dung'],
            'fields'  => [
                'group_id'   => ['Thuộc nhóm phúc lợi', 'select-group', 0, true],
                'text'       => ['Nội dung', 'textarea', 500, true],
                'is_active'  => ['Hiển thị ngoài website', 'checkbox', 0, false],
                'sort_order' => ['Thứ tự', 'number', 0, false],
            ],
        ],
        'comparison' => [
            'group'   => 'benefits',
            'label'   => 'Bảng so sánh',
            'singular'=> 'dòng so sánh',
            'table'   => 'benefit_comparisons',
            'columns' => ['label' => 'Hạng mục', 'tex_giang' => 'Tại TEX-GIANG', 'other_value' => 'Nơi khác'],
            'quick_fields' => ['label' => 'Hạng mục', 'tex_giang' => 'Tại TEX-GIANG', 'other_value' => 'Nơi khác'],
            'fields'  => [
                'label'       => ['Hạng mục', 'text', 120, true],
                'tex_giang'   => ['Tại TEX-GIANG', 'text', 300, true],
                'other_value' => ['Nơi khác thường thấy', 'text', 300, true],
                'is_active'   => ['Hiển thị ngoài website', 'checkbox', 0, false],
                'sort_order'  => ['Thứ tự', 'number', 0, false],
            ],
        ],
    ];

    /** Icon có sẵn trong public/assets/images/icons. */
    private const ICONS = [
        'shield-check', 'check2', 'briefcase', 'bus-front', 'buildings', 'geo-alt',
        'telephone', 'envelope', 'file-earmark-text', 'send', 'list', 'sliders',
    ];

    public function index(string $type): void
    {
        $config = $this->config($type);
        $rows = ContentItem::all($config['table']);

        $this->view('admin/pages/index', [
            'type'        => $type,
            'config'      => $config,
            'groupConfig' => self::GROUPS[$config['group']],
            'tabs'        => $this->tabs($config['group']),
            'rows'        => $rows,
            'quickFields' => $config['quick_fields'] ?? [],
            'icons'       => self::ICONS,
            'groupNames'  => $this->groupNames(),
            'page'        => ['title' => $config['label'] . ' | ' . settings('site_name'), 'nav' => self::GROUPS[$config['group']]['nav']],
        ], 'layouts/admin');
    }

    public function create(string $type): void
    {
        $this->form($type, null);
    }

    public function edit(string $type, string $id): void
    {
        $config = $this->config($type);
        $row = ContentItem::find($config['table'], (int) $id);
        if ($row === null) {
            Session::flash('_error', 'Không tìm thấy ' . $config['singular'] . '.');
            $this->redirect(url('admin/pages/' . $type));
        }
        $this->form($type, $row);
    }

    public function store(string $type): void
    {
        $config = $this->config($type);
        [$data, $errors] = $this->validate($config, $_POST, null);
        if ($errors !== []) {
            $this->respondFail($errors, url('admin/pages/' . $type . '/create'));
        }

        if ((int) ($data['sort_order'] ?? 0) === 0) {
            $data['sort_order'] = ContentItem::maxSort($config['table']) + 1;
        }

        $id = ContentItem::create($config['table'], $data);
        if ($type === 'benefit-group') {
            $this->syncGroupItems($id, $_POST['items'] ?? []);
        }

        AuditLog::record('pagecontent.create', $type, $id, ['label' => $this->rowLabel($config, $data)]);
        $this->respondSuccess('Đã thêm ' . $config['singular'] . '.', url('admin/pages/' . $type));
    }

    public function update(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        if (ContentItem::find($config['table'], $recordId) === null) {
            $this->respondFail(['record' => 'Không tìm thấy ' . $config['singular'] . '.'], url('admin/pages/' . $type));
        }

        [$data, $errors] = $this->validate($config, $_POST, $recordId);
        if ($errors !== []) {
            $this->respondFail($errors, url('admin/pages/' . $type . '/' . $recordId . '/edit'));
        }

        ContentItem::update($config['table'], $recordId, $data);
        if ($type === 'benefit-group') {
            $this->syncGroupItems($recordId, $_POST['items'] ?? [], true);
        }

        AuditLog::record('pagecontent.update', $type, $recordId, ['label' => $this->rowLabel($config, $data)]);
        $this->respondSuccess('Đã cập nhật ' . $config['singular'] . '.', url('admin/pages/' . $type));
    }

    public function destroy(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        $row = ContentItem::find($config['table'], $recordId);
        if ($row === null) {
            Session::flash('_error', 'Không tìm thấy ' . $config['singular'] . '.');
            $this->redirect(url('admin/pages/' . $type));
        }
        ContentItem::delete($config['table'], $recordId);
        AuditLog::record('pagecontent.delete', $type, $recordId, ['label' => $this->rowLabel($config, $row)]);
        Session::flash('_success', 'Đã xoá ' . $config['singular'] . '.');
        $this->redirect(url('admin/pages/' . $type));
    }

    public function toggle(string $type, string $id): void
    {
        $config = $this->config($type);
        if (ContentItem::find($config['table'], (int) $id) === null) {
            Session::flash('_error', 'Không tìm thấy ' . $config['singular'] . '.');
            $this->redirect(url('admin/pages/' . $type));
        }
        ContentItem::toggleActive($config['table'], (int) $id);
        Session::flash('_success', 'Đã đổi trạng thái hiển thị.');
        $this->redirect(url('admin/pages/' . $type));
    }

    // ===== Trang Về chúng tôi: sửa tất cả trong MỘT form repeater (kiểu Người liên hệ) =====

    public function about(): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $this->view('admin/pages/about', [
            'milestones'     => $old['milestones'] ?? ContentItem::all('about_milestones'),
            'values'         => $old['values'] ?? ContentItem::all('about_values'),
            'certifications' => $old['certifications'] ?? ContentItem::all('about_certifications'),
            'errors'         => $errors,
            'page'           => ['title' => 'Trang Về chúng tôi | ' . settings('site_name'), 'nav' => 'pages-about'],
        ], 'layouts/admin');
    }

    public function aboutSave(): void
    {
        [$milestones, $mErrors] = $this->collectRows($_POST['milestones'] ?? [], [
            'year_label'   => ['Mốc thời gian', 40, true],
            'text'         => ['Nội dung', 500, true],
            'is_highlight' => ['flag', 0, false],
        ], 'mốc phát triển');
        [$values, $vErrors] = $this->collectRows($_POST['values'] ?? [], [
            'title' => ['Tiêu đề', 120, true],
            'body'  => ['Nội dung', 800, true],
        ], 'tầm nhìn/sứ mệnh/giá trị');
        [$certs, $cErrors] = $this->collectRows($_POST['certifications'] ?? [], [
            'label' => ['Chứng nhận', 200, true],
        ], 'chứng nhận');

        $errors = array_merge($mErrors, $vErrors, $cErrors);
        if (count($milestones) > 30) { $errors[] = 'Tối đa 30 mốc phát triển.'; }
        if (count($values) > 10) { $errors[] = 'Tối đa 10 mục tầm nhìn/sứ mệnh/giá trị.'; }
        if (count($certs) > 20) { $errors[] = 'Tối đa 20 chứng nhận.'; }

        if ($errors !== []) {
            Session::flash('_old', $_POST);
            Session::flash('_errors', ['general' => $errors[0]]);
            Session::flash('_error', $errors[0]);
            $this->redirect(url('admin/pages/about'));
        }

        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM about_milestones');
            foreach ($milestones as $index => $row) {
                $row['sort_order'] = $index + 1;
                $row['is_active'] = 1;
                ContentItem::create('about_milestones', $row);
            }
            $pdo->exec('DELETE FROM about_values');
            foreach ($values as $index => $row) {
                $row['sort_order'] = $index + 1;
                $row['is_active'] = 1;
                ContentItem::create('about_values', $row);
            }
            $pdo->exec('DELETE FROM about_certifications');
            foreach ($certs as $index => $row) {
                $row['sort_order'] = $index + 1;
                $row['is_active'] = 1;
                ContentItem::create('about_certifications', $row);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[TEX-GIANG] Lỗi lưu nội dung Về chúng tôi: ' . $e->getMessage());
            Session::flash('_error', 'Không lưu được nội dung. Vui lòng thử lại.');
            $this->redirect(url('admin/pages/about'));
        }

        AuditLog::record('pagecontent.about_save', 'page', null, ['milestones' => count($milestones)]);
        Session::flash('_success', 'Đã lưu nội dung trang Về chúng tôi.');
        $this->redirect(url('admin/pages/about'));
    }

    // ===== Trang Phúc lợi: nhóm + dòng con + bảng so sánh trong MỘT form =====

    public function benefits(): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        if ($old !== []) {
            $groups = $old['groups'] ?? [];
        } else {
            $groups = ContentItem::all('benefit_groups');
            foreach ($groups as &$group) {
                $group['items'] = ContentItem::byGroup('benefit_items', (int) $group['id']);
            }
            unset($group);
        }

        $this->view('admin/pages/benefits', [
            'groups'      => $groups,
            'comparisons' => $old['comparisons'] ?? ContentItem::all('benefit_comparisons'),
            'icons'       => self::ICONS,
            'errors'      => $errors,
            'page'        => ['title' => 'Trang Phúc lợi | ' . settings('site_name'), 'nav' => 'pages-benefits'],
        ], 'layouts/admin');
    }

    public function benefitsSave(): void
    {
        $rawGroups = $_POST['groups'] ?? [];
        $groups = [];
        $errors = [];

        if (!is_array($rawGroups)) {
            $rawGroups = [];
        }

        foreach ($rawGroups as $groupIndex => $rawGroup) {
            if (!is_array($rawGroup)) {
                continue;
            }
            $title = trim((string) ($rawGroup['title'] ?? ''));
            $intro = trim((string) ($rawGroup['intro'] ?? ''));
            $icon  = (string) ($rawGroup['icon'] ?? 'check2');
            $itemsRaw = $rawGroup['items'] ?? [];

            $items = [];
            if (is_array($itemsRaw)) {
                foreach ($itemsRaw as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $text = trim((string) ($item['text'] ?? ''));
                    if ($text === '') {
                        continue;
                    }
                    if (mb_strlen($text) > 500) {
                        $errors[] = 'Nhóm ' . ($groupIndex + 1) . ': dòng phúc lợi tối đa 500 ký tự.';
                        continue;
                    }
                    $items[] = $text;
                }
            }

            if ($title === '' && $intro === '' && $items === []) {
                continue;
            }
            if ($title === '' || mb_strlen($title) > 200) {
                $errors[] = 'Nhóm ' . ($groupIndex + 1) . ': tiêu đề 1–200 ký tự.';
            }
            if (mb_strlen($intro) > 500) {
                $errors[] = 'Nhóm ' . ($groupIndex + 1) . ': mô tả tối đa 500 ký tự.';
            }
            if (!in_array($icon, self::ICONS, true)) {
                $icon = 'check2';
            }
            if (count($items) > 30) {
                $errors[] = 'Nhóm ' . ($groupIndex + 1) . ': tối đa 30 dòng.';
            }
            $groups[] = ['title' => $title, 'icon' => $icon, 'intro' => $intro, 'items' => $items];
        }

        [$comparisons, $cmpErrors] = $this->collectRows($_POST['comparisons'] ?? [], [
            'label'       => ['Hạng mục', 120, true],
            'tex_giang'   => ['Tại TEX-GIANG', 300, true],
            'other_value' => ['Nơi khác', 300, true],
        ], 'so sánh');
        $errors = array_merge($errors, $cmpErrors);

        if (count($groups) === 0) {
            $errors[] = 'Cần ít nhất một nhóm phúc lợi.';
        }
        if (count($groups) > 20) {
            $errors[] = 'Tối đa 20 nhóm phúc lợi.';
        }
        if (count($comparisons) > 50) {
            $errors[] = 'Tối đa 50 dòng so sánh.';
        }

        if ($errors !== []) {
            Session::flash('_old', $_POST);
            Session::flash('_errors', ['general' => $errors[0]]);
            Session::flash('_error', $errors[0]);
            $this->redirect(url('admin/pages/benefits'));
        }

        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM benefit_items');
            $pdo->exec('DELETE FROM benefit_groups');
            $pdo->exec('DELETE FROM benefit_comparisons');

            foreach ($groups as $index => $group) {
                $groupId = ContentItem::create('benefit_groups', [
                    'title'      => $group['title'],
                    'icon'       => $group['icon'],
                    'intro'      => $group['intro'],
                    'sort_order' => $index + 1,
                    'is_active'  => 1,
                ]);
                foreach ($group['items'] as $itemIndex => $text) {
                    ContentItem::create('benefit_items', [
                        'group_id'   => $groupId,
                        'text'       => $text,
                        'sort_order' => $itemIndex + 1,
                        'is_active'  => 1,
                    ]);
                }
            }

            foreach ($comparisons as $index => $row) {
                $row['sort_order'] = $index + 1;
                $row['is_active'] = 1;
                ContentItem::create('benefit_comparisons', $row);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[TEX-GIANG] Lỗi lưu nội dung Phúc lợi: ' . $e->getMessage());
            Session::flash('_error', 'Không lưu được nội dung. Vui lòng thử lại.');
            $this->redirect(url('admin/pages/benefits'));
        }

        AuditLog::record('pagecontent.benefits_save', 'page', null, ['groups' => count($groups)]);
        Session::flash('_success', 'Đã lưu nội dung trang Phúc lợi.');
        $this->redirect(url('admin/pages/benefits'));
    }

    /**
     * Gom các dòng repeater từ form (kiểu Người liên hệ tuyển dụng).
     *
     * @param mixed $raw
     * @param array<string, array{0:string,1:int,2:bool}> $fields name => [label, max, required]
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function collectRows($raw, array $fields, string $singular): array
    {
        $rows = [];
        $errors = [];
        if (!is_array($raw)) {
            return [$rows, $errors];
        }

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = [];
            $empty = true;
            foreach ($fields as $name => [$label, $max, $required]) {
                if ($name === 'is_highlight') {
                    $row[$name] = !empty($item[$name]) ? 1 : 0;
                    continue;
                }
                $value = trim((string) ($item[$name] ?? ''));
                if ($value !== '') {
                    $empty = false;
                }
                if ($required && $value === '') {
                    $errors[] = 'Dòng ' . (count($rows) + 1) . ' (' . $singular . '): ' . $label . ' không được để trống.';
                } elseif ($max > 0 && mb_strlen($value) > $max) {
                    $errors[] = 'Dòng ' . (count($rows) + 1) . ' (' . $singular . '): ' . $label . ' tối đa ' . $max . ' ký tự.';
                }
                $row[$name] = $value;
            }
            if ($empty) {
                continue;
            }
            $rows[] = $row;
        }

        return [$rows, $errors];
    }

    /** @return array<string, array<string, mixed>> */
    private function config(string $type): array
    {
        if (!isset(self::TYPES[$type])) {
            Session::flash('_error', 'Loại nội dung không hợp lệ.');
            $this->redirect(url('admin/content'));
        }
        return self::TYPES[$type];
    }

    /** @return array<int, array{type:string,label:string,url:string,active:bool}> */
    private function tabs(string $group): array
    {
        $tabs = [];
        foreach (self::GROUPS[$group]['types'] as $type) {
            $tabs[] = [
                'type'   => $type,
                'label'  => self::TYPES[$type]['label'],
                'url'    => url('admin/pages/' . $type),
                'active' => false,
            ];
        }
        return $tabs;
    }

    /** @return array<int, array<string, mixed>> */
    private function groupNames(): array
    {
        return ContentItem::all('benefit_groups');
    }

    /** @param array<string, mixed>|null $row */
    private function form(string $type, ?array $row): void
    {
        $config = $this->config($type);
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $row ?? ['id' => null, 'is_active' => 1, 'sort_order' => 0];
        $items = [];
        if ($type === 'benefit-group' && $row !== null) {
            $items = ContentItem::byGroup('benefit_items', (int) $row['id']);
        }
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        $this->view('admin/pages/form', [
            'type'        => $type,
            'config'      => $config,
            'groupConfig' => self::GROUPS[$config['group']],
            'tabs'        => $this->tabs($config['group']),
            'values'      => $values,
            'errors'      => $errors,
            'icons'       => self::ICONS,
            'groupNames'  => $this->groupNames(),
            'items'       => $items,
            'page'        => ['title' => ($row === null ? 'Thêm ' : 'Sửa ') . $config['singular'] . ' | ' . settings('site_name'), 'nav' => self::GROUPS[$config['group']]['nav']],
        ], 'layouts/admin');
    }

    /** @param array<string, string> $errors */
    private function respondFail(array $errors, string $redirect): never
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResult(false, 'Vui lòng kiểm tra lại các trường được đánh dấu.', $redirect, $errors);
        }
        Session::flash('_old', $errors === [] ? [] : ($_POST ?? []));
        Session::flash('_errors', $errors);
        Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
        $this->redirect($redirect);
    }

    private function respondSuccess(string $message, string $redirect): never
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, $message, $redirect);
        }
        Session::flash('_success', $message);
        $this->redirect($redirect);
    }

    /**
     * Đồng bộ các dòng con (gạch đầu dòng) của một nhóm phúc lợi.
     * Dòng có id => cập nhật; dòng mới => thêm; dòng cũ không còn => xoá.
     *
     * @param array<int, mixed> $rows
     */
    private function syncGroupItems(int $groupId, array $rows, bool $removeMissing = false): void
    {
        $existing = ContentItem::byGroup('benefit_items', $groupId);
        $existingIds = array_map(static fn(array $row): int => (int) $row['id'], $existing);
        $keptIds = [];

        $order = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $text = mb_substr($text, 0, 500);
            $order++;
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId > 0 && in_array($rowId, $existingIds, true)) {
                ContentItem::update('benefit_items', $rowId, [
                    'text'       => $text,
                    'sort_order' => $order,
                    'is_active'  => 1,
                ]);
                $keptIds[] = $rowId;
            } else {
                ContentItem::create('benefit_items', [
                    'group_id'   => $groupId,
                    'text'       => $text,
                    'sort_order' => $order,
                    'is_active'  => 1,
                ]);
            }
        }

        if ($removeMissing) {
            $toDelete = array_diff($existingIds, $keptIds);
            ContentItem::deleteByIds('benefit_items', $toDelete);
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validate(array $config, array $post, ?int $exceptId): array
    {
        $data = [];
        $errors = [];

        foreach ($config['fields'] as $name => [$label, $kind, $max, $required]) {
            if ($kind === 'checkbox') {
                $data[$name] = !empty($post[$name]) ? 1 : 0;
                continue;
            }
            $value = trim((string) ($post[$name] ?? ''));
            if ($required && $value === '') {
                $errors[$name] = $label . ' không được để trống.';
                continue;
            }
            if ($kind !== 'number' && $max > 0 && mb_strlen($value) > $max) {
                $errors[$name] = $label . ' tối đa ' . $max . ' ký tự.';
                continue;
            }
            if ($kind === 'number') {
                $value = (string) (int) $value;
            }
            if ($kind === 'select-group') {
                $groupId = (int) $value;
                $exists = false;
                foreach ($this->groupNames() as $group) {
                    if ((int) $group['id'] === $groupId) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $errors[$name] = 'Chọn nhóm phúc lợi hợp lệ.';
                    continue;
                }
                $data[$name] = $groupId;
                continue;
            }
            $data[$name] = $value;
        }

        return [$data, $errors];
    }

    /** @param array<string, mixed> $row */
    private function rowLabel(array $config, array $row): string
    {
        foreach (['title', 'label', 'year_label', 'text'] as $key) {
            if (!empty($row[$key])) {
                return mb_substr((string) $row[$key], 0, 80);
            }
        }
        return '';
    }
}
