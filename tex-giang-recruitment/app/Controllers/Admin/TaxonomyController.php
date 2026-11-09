<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
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
        ],
        'category' => [
            'label'    => 'Khối phòng ban',
            'singular' => 'khối phòng ban',
            'model'    => Category::class,
        ],
        'post-category' => [
            'label'    => 'Chuyên mục tin tức',
            'singular' => 'chuyên mục',
            'model'    => PostCategory::class,
        ],
    ];

    public function index(): void
    {
        $this->view('admin/categories/index', [
            'branches'       => Branch::all(),
            'categories'     => Category::all(),
            'postCategories' => PostCategory::all(),
            'branchContacts' => BranchContact::allGrouped(),
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
            $this->redirect(url('admin/categories'));
        }
        $this->form($type, $record);
    }

    public function store(string $type): void
    {
        $config = $this->config($type);
        [$data, $errors] = $this->validate($type, $_POST, null);
        [$contacts, $contactErrors] = $this->validateContacts($_POST);
        $errors = array_merge($errors, $contactErrors);

        if ($errors !== []) {
            $data['contacts'] = $contacts;
            $this->remember($data, $errors, url('admin/categories/' . $type . '/create'));
        }

        $model = $config['model'];
        $id = $model::create($data);
        if ($type === 'branch') {
            BranchContact::replaceForBranch($id, $contacts);
        }
        AuditLog::record('taxonomy.create', $type, $id, ['name' => $data['name']]);
        Session::flash('_success', 'Đã thêm ' . $config['singular'] . ' "' . $data['name'] . '".');
        $this->redirect(url('admin/categories'));
    }

    public function update(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        $model = $config['model'];
        if ($model::find($recordId) === null) {
            Session::flash('_error', 'Không tìm thấy ' . $config['singular'] . '.');
            $this->redirect(url('admin/categories'));
        }

        [$data, $errors] = $this->validate($type, $_POST, $recordId);
        [$contacts, $contactErrors] = $this->validateContacts($_POST);
        $errors = array_merge($errors, $contactErrors);
        if ($errors !== []) {
            $data['contacts'] = $contacts;
            $this->remember($data, $errors, url('admin/categories/' . $type . '/' . $recordId . '/edit'));
        }

        $model::update($recordId, $data);
        if ($type === 'branch') {
            BranchContact::replaceForBranch($recordId, $contacts);
        }
        AuditLog::record('taxonomy.update', $type, $recordId, ['name' => $data['name']]);
        Session::flash('_success', 'Đã cập nhật ' . $config['singular'] . '.');
        $this->redirect(url('admin/categories'));
    }

    public function destroy(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        $model = $config['model'];
        $record = $model::find($recordId);
        if ($record === null) {
            Session::flash('_error', 'Không tìm thấy ' . $config['singular'] . '.');
            $this->redirect(url('admin/categories'));
        }

        if ($type === 'branch' && Branch::jobCount($recordId) > 0) {
            Session::flash('_error', 'Không xoá được: còn ' . Branch::jobCount($recordId) . ' tin tuyển dụng thuộc khu này. Hãy chuyển tin sang khu khác trước.');
            $this->redirect(url('admin/categories'));
        }
        if ($type === 'post-category' && PostCategory::postCount($recordId) > 0) {
            Session::flash('_error', 'Không xoá được: còn ' . PostCategory::postCount($recordId) . ' bài viết thuộc chuyên mục này.');
            $this->redirect(url('admin/categories'));
        }

        $model::delete($recordId);
        AuditLog::record('taxonomy.delete', $type, $recordId, ['name' => $record['name']]);
        Session::flash('_success', 'Đã xoá ' . $config['singular'] . ' "' . $record['name'] . '".');
        $this->redirect(url('admin/categories'));
    }

    public function toggle(string $type, string $id): void
    {
        $config = $this->config($type);
        $recordId = (int) $id;
        $model = $config['model'];
        $record = $model::find($recordId);
        if ($record === null) {
            Session::flash('_error', 'Không tìm thấy ' . $config['singular'] . '.');
            $this->redirect(url('admin/categories'));
        }
        $model::toggleActive($recordId);
        AuditLog::record('taxonomy.toggle', $type, $recordId, ['name' => $record['name']]);
        Session::flash('_success', ((int) $record['is_active'] === 1 ? 'Đã tắt' : 'Đã bật') . ' ' . $config['singular'] . ' "' . $record['name'] . '".');
        $this->redirect(url('admin/categories'));
    }

    /** @return array{label:string, singular:string, model:class-string} */
    private function config(string $type): array
    {
        if (!isset(self::TYPES[$type])) {
            Session::flash('_error', 'Loại danh mục không hợp lệ.');
            $this->redirect(url('admin/categories'));
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
            $phone    = trim((string) ($item['phone'] ?? ''));
            if ($fullName === '' && $phone === '') {
                continue;
            }
            $row++;

            if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
                $errors['contacts'] = 'Dòng liên hệ ' . $row . ': họ tên phải từ 2 đến 120 ký tự.';
                continue;
            }

            if ($phone === '' || !preg_match('/^[0-9+\s.\-()]{6,30}$/', $phone)) {
                $errors['contacts'] = 'Dòng liên hệ ' . $row . ': số điện thoại không hợp lệ (chỉ số, khoảng trắng, +, -, ., ngoặc).';
                continue;
            }
            if (mb_strlen($phone) > 30) {
                $errors['contacts'] = 'Dòng liên hệ ' . $row . ': số điện thoại tối đa 30 ký tự.';
                continue;
            }
            if (count($contacts) >= 20) {
                $errors['contacts'] = 'Mỗi khu sản xuất tối đa 20 người liên hệ.';
                break;
            }

            $contacts[] = [
                'full_name' => $fullName,
                'phone'     => $phone,
                'is_active' => 1,
            ];
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
        $base = $data['slug'];
        $suffix = 2;
        while ($model::slugExists($data['slug'], $exceptId)) {
            $data['slug'] = $base . '-' . $suffix;
            $suffix++;
            if ($suffix > 100) {
                $errors['slug'] = 'Không tạo được slug duy nhất.';
                break;
            }
        }

        return [$data, $errors];
    }

    private function remember(array $data, array $errors, string $redirect): never
    {
        Session::flash('_old', $data);
        Session::flash('_errors', $errors);
        Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
        $this->redirect($redirect);
    }
}
