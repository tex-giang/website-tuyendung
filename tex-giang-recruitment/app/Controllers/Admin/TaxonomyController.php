<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchContact;
use App\Models\Category;
use App\Models\PostCategory;

/**
 * Danh mục dùng chung: khu sản xuất, khối phòng ban, chuyên mục tin tức.
 * Ba loại dùng chung một controller để giữ cấu trúc form/validate thống nhất.
 */
final class TaxonomyController extends Controller
{
    private const TYPES = [
        'branch' => [
            'label'    => 'Khu sản xuất',
            'singular' => 'khu sản xuất',
            'model'    => Branch::class,
            'hint'     => 'Các khu sản xuất đang tuyển dụng, kèm địa chỉ, bản đồ và đầu mối liên hệ tuyển dụng.',
        ],
        'category' => [
            'label'    => 'Khối phòng ban',
            'singular' => 'khối phòng ban',
            'model'    => Category::class,
            'hint'     => 'Khối phòng ban dùng để phân loại tin tuyển dụng.',
        ],
        'post-category' => [
            'label'    => 'Chuyên mục tin tức',
            'singular' => 'chuyên mục tin tức',
            'model'    => PostCategory::class,
            'hint'     => 'Chuyên mục dùng để phân loại bài viết, tin tức trên website.',
        ],
    ];

    public function index(): void
    {
        $type = (string) ($_GET['type'] ?? 'branch');
        if (!isset(self::TYPES[$type])) {
            $type = 'branch';
        }
        $model = self::TYPES[$type]['model'];
        $rows = $model::all();
        $usage = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $usage[$id] = $type === 'post-category' ? PostCategory::postCount($id) : $model::jobCount($id);
        }
        $this->view('admin/categories/index', [
            'type'           => $type,
            'types'          => self::TYPES,
            'rows'           => $rows,
            'usage'          => $usage,
            'branchContacts' => $type === 'branch' ? BranchContact::allGrouped() : [],
            'page'           => ['title' => 'Danh mục dùng chung | ' . settings('site_name'), 'nav' => 'categories'],
        ], 'layouts/admin');
    }

    public function create(string $type): void
    {
        $this->form($type, null);
    }

    public function edit(string $type, string $id): void
    {
        $config = $this->config($type);
        $model = $config['model'];
        $record = $model::find((int) $id);
        if ($record === null) {
            Session::flash('_error', 'Không tìm thấy ' . $config['singular'] . '.');
            $this->redirect($this->listUrl($type));
        }
        $this->form($type, $record);
    }

    public function store(string $type): void
    {
        $config = $this->config($type);
        [$data, $errors] = $this->validate($type, $_POST, null);
        [$contacts, $contactErrors] = $type === 'branch' ? $this->validateContacts($_POST) : [[], []];
        $errors = array_merge($errors, $contactErrors);

        if ($errors !== []) {
            $data['contacts'] = $contacts;
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/categories/' . $type . '/create'), $errors, $data);
        }

        $model = $config['model'];
        $id = Database::transaction(static function () use ($model, $type, $data, $contacts): int {
            $id = $model::create($data);
            if ($type === 'branch') {
                BranchContact::replaceForBranch($id, $contacts);
            }
            return $id;
        });
        AuditLog::record('taxonomy.create', $type, $id, ['name' => $data['name']]);
        $this->formSuccess('Đã thêm ' . $config['singular'] . ' "' . $data['name'] . '".', $this->listUrl($type));
    }

    public function update(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        $model = $config['model'];
        if ($model::find($recordId) === null) {
            $this->formError('Không tìm thấy ' . $config['singular'] . '.', $this->listUrl($type), status: 404);
        }

        [$data, $errors] = $this->validate($type, $_POST, $recordId);
        [$contacts, $contactErrors] = $type === 'branch' ? $this->validateContacts($_POST) : [[], []];
        $errors = array_merge($errors, $contactErrors);
        if ($errors !== []) {
            $data['contacts'] = $contacts;
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/categories/' . $type . '/' . $recordId . '/edit'), $errors, $data);
        }

        Database::transaction(static function () use ($model, $type, $recordId, $data, $contacts): void {
            $model::update($recordId, $data);
            if ($type === 'branch') {
                BranchContact::replaceForBranch($recordId, $contacts);
            }
        });
        AuditLog::record('taxonomy.update', $type, $recordId, ['name' => $data['name']]);
        $this->formSuccess('Đã cập nhật ' . $config['singular'] . '.', $this->listUrl($type));
    }

    public function destroy(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        $model = $config['model'];
        $record = $model::find($recordId);
        if ($record === null) {
            $this->formError('Không tìm thấy ' . $config['singular'] . '.', $this->listUrl($type), status: 404);
        }

        if ($type === 'branch' && Branch::jobCount($recordId) > 0) {
            $this->formError('Không xoá được: còn ' . Branch::jobCount($recordId) . ' tin tuyển dụng thuộc khu này. Hãy chuyển tin sang khu khác trước.', $this->listUrl($type), status: 409);
        }
        if ($type === 'post-category' && PostCategory::postCount($recordId) > 0) {
            $this->formError('Không xoá được: còn ' . PostCategory::postCount($recordId) . ' bài viết thuộc chuyên mục này.', $this->listUrl($type), status: 409);
        }

        $model::delete($recordId);
        AuditLog::record('taxonomy.delete', $type, $recordId, ['name' => $record['name']]);
        $this->formSuccess('Đã xoá ' . $config['singular'] . ' "' . $record['name'] . '".', $this->listUrl($type));
    }

    public function toggle(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        $model = $config['model'];
        $record = $model::find($recordId);
        if ($record === null) {
            $this->formError('Không tìm thấy ' . $config['singular'] . '.', $this->listUrl($type), status: 404);
        }
        $model::toggleActive($recordId);
        AuditLog::record('taxonomy.toggle', $type, $recordId, ['name' => $record['name']]);
        $this->formSuccess(((int) $record['is_active'] === 1 ? 'Đã tắt' : 'Đã bật') . ' ' . $config['singular'] . ' "' . $record['name'] . '".', $this->listUrl($type));
    }

    private function listUrl(string $type): string
    {
        return url('admin/categories') . '?' . http_build_query(['type' => $type]);
    }

    /** @return array{label:string, singular:string, model:class-string, hint:string} */
    private function config(string $type): array
    {
        if (!isset(self::TYPES[$type])) {
            $this->formError('Loại danh mục không hợp lệ.', url('admin/categories'), status: 404);
        }
        return self::TYPES[$type];
    }

    /** @param array<string, mixed>|null $record */
    private function form(string $type, ?array $record): void
    {
        $config = $this->config($type);
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $record ?? [
            'id' => null, 'slug' => '', 'name' => '', 'address' => '', 'phone' => '',
            'map_url' => '', 'map_link' => '', 'description' => '', 'sort_order' => 0, 'is_active' => 1,
        ];
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        $contacts = [];
        if (isset($values['contacts']) && is_array($values['contacts'])) {
            $contacts = $values['contacts'];
        } elseif ($record !== null && $type === 'branch') {
            $contacts = BranchContact::byBranch((int) $record['id'], false);
        }
        unset($values['contacts']);

        $this->view('admin/categories/form', [
            'type'     => $type,
            'config'   => $config,
            'record'   => $record,
            'values'   => $values,
            'errors'   => $errors,
            'contacts' => $contacts,
            'page'     => [
                'title' => ($record === null ? 'Thêm ' : 'Sửa ') . $config['singular'] . ' | ' . settings('site_name'),
                'nav'   => 'categories',
            ],
        ], 'layouts/admin');
    }

    /**
     * Danh sách người liên hệ (chỉ áp dụng cho khu sản xuất): mỗi dòng họ tên + vai trò + SĐT.
     * @return array{0: array<int, array{full_name:string,title:string,phone:string,is_active:int}>, 1: array<string,string>}
     */
    private function validateContacts(array $post): array
    {
        $raw = $post['contacts'] ?? [];
        if (!is_array($raw)) {
            return [[], []];
        }

        $contacts = [];
        $errors = [];
        $row = 0;
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $fullName = trim((string) ($item['full_name'] ?? ''));
            $title    = trim((string) ($item['title'] ?? ''));
            $phone    = trim((string) ($item['phone'] ?? ''));
            if ($fullName === '' && $title === '' && $phone === '') {
                continue;
            }
            $row++;
            $contacts[] = [
                'full_name' => $fullName,
                'title'     => $title,
                'phone'     => $phone,
                'is_active' => (int) ($item['is_active'] ?? 1) === 1 ? 1 : 0,
            ];

            if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
                $errors['contacts'] = 'Dòng liên hệ ' . $row . ': họ tên phải từ 2 đến 120 ký tự.';
                continue;
            }

            if (mb_strlen($title) > 80) {
                $errors['contacts'] = 'Dòng liên hệ ' . $row . ': vai trò tối đa 80 ký tự.';
                continue;
            }
            if (!preg_match('/^[0-9+\s.\-()]{6,30}$/', $phone)) {
                $errors['contacts'] = 'Dòng liên hệ ' . $row . ': số điện thoại không hợp lệ (chỉ số, khoảng trắng, +, -, ., ngoặc).';
            }
        }
        if (count($contacts) > 20) {
            $errors['contacts'] = 'Mỗi khu sản xuất tối đa 20 người liên hệ.';
        }

        return [$contacts, $errors];
    }

    /** @return array{0: array<string,mixed>, 1: array<string,string>} */
    private function validate(string $type, array $post, ?int $exceptId): array
    {
        $model = self::TYPES[$type]['model'];
        $data = [
            'name'        => trim((string) ($post['name'] ?? '')),
            'slug'        => trim((string) ($post['slug'] ?? '')),
            'address'     => trim((string) ($post['address'] ?? '')),
            'phone'       => trim((string) ($post['phone'] ?? '')),
            'map_url'     => trim((string) ($post['map_url'] ?? '')),
            'map_link'    => trim((string) ($post['map_link'] ?? '')),
            'description' => trim((string) ($post['description'] ?? '')),
            'sort_order'  => (int) ($post['sort_order'] ?? 0),
            'is_active'   => !empty($post['is_active']) ? 1 : 0,
        ];

        $errors = [];
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 120) {
            $errors['name'] = 'Tên danh mục 2–120 ký tự.';
        }
        if (mb_strlen($data['address']) > 255) {
            $errors['address'] = 'Địa chỉ tối đa 255 ký tự.';
        }
        if (mb_strlen($data['phone']) > 30) {
            $errors['phone'] = 'Điện thoại tối đa 30 ký tự.';
        }
        if (mb_strlen($data['map_url']) > 500) {
            $errors['map_url'] = 'Link bản đồ tối đa 500 ký tự.';
        } elseif ($data['map_url'] !== '' && map_embed_src($data['map_url']) === '') {
            $errors['map_url'] = 'Nhập liên kết nhúng bản đồ hợp lệ.';
        }
        if (mb_strlen($data['map_link']) > 500 || ($data['map_link'] !== '' && !preg_match('#^https?://[^\s]+$#i', $data['map_link']))) {
            $errors['map_link'] = 'Nhập liên kết HTTP hoặc HTTPS, tối đa 500 ký tự.';
        }
        if ($data['sort_order'] < -32768 || $data['sort_order'] > 32767) {
            $errors['sort_order'] = 'Thứ tự nằm trong khoảng -32768 đến 32767.';
        }
        if (mb_strlen($data['description']) > 255) {
            $errors['description'] = 'Mô tả tối đa 255 ký tự.';
        }

        if ($data['slug'] === '') {
            $data['slug'] = slugify($data['name']);
        } else {
            $data['slug'] = slugify($data['slug']);
        }
        if ($data['slug'] === '') {
            $data['slug'] = 'danh-muc';
        }
        $base = substr($data['slug'], 0, 80);
        $data['slug'] = $base;
        $suffix = 2;
        while ($model::slugExists($data['slug'], $exceptId)) {
            $ending = '-' . $suffix;
            $data['slug'] = substr($base, 0, 80 - strlen($ending)) . $ending;
            $suffix++;
            if ($suffix > 100) {
                $errors['slug'] = 'Không tạo được slug duy nhất.';
                break;
            }
        }

        return [$data, $errors];
    }
}
