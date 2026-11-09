<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Post;
use App\Services\UploadService;

final class PostController extends Controller
{
    public const STATUSES = [
        'draft'     => 'Bản nháp',
        'published' => 'Đã đăng',
    ];

    public function index(): void
    {
        $filters = [
            'q'           => trim((string) ($_GET['q'] ?? '')),
            'status'      => (string) ($_GET['status'] ?? ''),
            'category_id' => (string) ($_GET['category_id'] ?? ''),
        ];
        if (!isset(self::STATUSES[$filters['status']])) {
            $filters['status'] = '';
        }

        $result = Post::adminPaginate($filters, max(1, (int) ($_GET['page'] ?? 1)), 500);

        $this->view('admin/posts/index', [
            'result'     => $result,
            'filters'    => $filters,
            'statuses'   => self::STATUSES,
            'categories' => Post::categories(),
            'page'       => ['title' => 'Tin tức & bài viết | ' . settings('site_name'), 'nav' => 'posts'],
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $post = Post::adminFind((int) $id);
        if ($post === null) {
            Session::flash('_error', 'Bài viết không tồn tại.');
            $this->redirect(url('admin/posts'));
        }
        $this->form($post);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate($_POST, null, null);

        $upload = UploadService::storePublicImage($_FILES['image'] ?? null, 'posts');
        if (!$upload['ok']) {
            $errors['image'] = $upload['error'];
        } elseif ($upload['path'] !== '') {
            $data['image_path'] = $upload['path'];
        }

        if ($errors !== []) {
            if ($upload['ok'] && $upload['path'] !== '') {
                UploadService::deletePublicImage($upload['path']);
            }
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/posts/create'), $errors);
            }
            $this->rememberForm($data, $errors);
            $this->redirect(url('admin/posts/create'));
        }

        $id = Post::create($data, (int) Session::get('auth_id'));
        AuditLog::record('post.create', 'post', $id, ['title' => $data['title']]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã tạo bài viết "' . $data['title'] . '".', url('admin/posts'));
        }
        Session::flash('_success', 'Đã tạo bài viết "' . $data['title'] . '".');
        $this->redirect(url('admin/posts'));
    }

    public function update(string $id): void
    {
        $postId = (int) $id;
        $existing = Post::adminFind($postId);
        if ($existing === null) {
            Session::flash('_error', 'Bài viết không tồn tại.');
            $this->redirect(url('admin/posts'));
        }

        [$data, $errors] = $this->validate($_POST, $postId, (string) ($existing['image_path'] ?? ''));

        $upload = UploadService::storePublicImage($_FILES['image'] ?? null, 'posts');
        if (!$upload['ok']) {
            $errors['image'] = $upload['error'];
        } elseif ($upload['path'] !== '') {
            $data['image_path'] = $upload['path'];
        }

        if ($errors !== []) {
            if ($upload['ok'] && $upload['path'] !== '') {
                UploadService::deletePublicImage($upload['path']);
            }
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/posts/' . $postId . '/edit'), $errors);
            }
            $this->rememberForm($data, $errors);
            $this->redirect(url('admin/posts/' . $postId . '/edit'));
        }

        Post::update($postId, $data);

        // Ảnh nào bị bỏ khỏi nội dung (hoặc thay ảnh đại diện) thì xoá file tương ứng.
        $removedImages = array_diff(
            content_image_paths((string) ($existing['body'] ?? '')),
            content_image_paths((string) ($data['body'] ?? ''))
        );
        foreach ($removedImages as $removedImage) {
            UploadService::deletePublicImage($removedImage);
        }
        if (($upload['path'] ?? '') !== '' && ($existing['image_path'] ?? '') !== $upload['path']) {
            UploadService::deletePublicImage((string) ($existing['image_path'] ?? ''));
        }
        AuditLog::record('post.update', 'post', $postId, ['title' => $data['title']]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã cập nhật bài viết.', url('admin/posts'));
        }
        Session::flash('_success', 'Đã cập nhật bài viết.');
        $this->redirect(url('admin/posts'));
    }

    public function changeStatus(string $id): void
    {
        $postId = (int) $id;
        $status = (string) ($_POST['status'] ?? '');
        if (!isset(self::STATUSES[$status]) || Post::adminFind($postId) === null) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Trạng thái không hợp lệ.');
            }
            Session::flash('_error', 'Trạng thái không hợp lệ.');
            $this->redirect(url('admin/posts'));
        }
        Post::changeStatus($postId, $status);
        AuditLog::record('post.status', 'post', $postId, ['status' => $status]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã chuyển bài viết sang "' . self::STATUSES[$status] . '".', url('admin/posts'));
        }
        Session::flash('_success', 'Đã chuyển bài viết sang "' . self::STATUSES[$status] . '".');
        $this->redirect(url('admin/posts'));
    }

    public function destroy(string $id): void
    {
        $postId = (int) $id;
        $post = Post::adminFind($postId);
        if ($post === null) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Bài viết không tồn tại.');
            }
            Session::flash('_error', 'Bài viết không tồn tại.');
            $this->redirect(url('admin/posts'));
        }
        Post::softDelete($postId);

        // Xoá file ảnh gắn với bài: ảnh trong nội dung (theo link) + ảnh đại diện.
        foreach (content_image_paths((string) ($post['body'] ?? '')) as $contentImage) {
            UploadService::deletePublicImage($contentImage);
        }
        UploadService::deletePublicImage((string) ($post['image_path'] ?? ''));

        AuditLog::record('post.delete', 'post', $postId, ['title' => $post['title']]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã xoá bài viết "' . $post['title'] . '".', url('admin/posts'));
        }
        Session::flash('_success', 'Đã xoá bài viết "' . $post['title'] . '".');
        $this->redirect(url('admin/posts'));
    }

    /**
     * Upload ảnh cho CKEditor (POST /admin/posts/upload-image?token=...).
     * CKEditor 4 gọi filebrowserUploadUrl và nhận lại JS callback.
     */
    public function uploadImage(): void
    {
        // CKEditor 4 gửi CKEditorFuncNum ở QUERY STRING (không phải POST).
        $funcNum = (int) ($_GET['CKEditorFuncNum'] ?? $_POST['CKEditorFuncNum'] ?? 0);
        $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        // CKEditor 4.22 dùng filetools (XHR) => cần JSON {uploaded,fileName,url}.
        // Dialog cũ dùng iframe => cần JS callback CKEditor.tools.callFunction().
        $wantJson = (string) ($_GET['responseType'] ?? '') === 'json';

        $respondJson = static function (array $payload, int $status = 200): never {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        };

        $respondScript = static function (string $url, string $message) use ($funcNum): never {
            header('Content-Type: text/html; charset=utf-8');
            echo '<script>window.parent.CKEDITOR.tools.callFunction(' . $funcNum . ', '
                . json_encode($url, JSON_UNESCAPED_SLASHES) . ', '
                . json_encode($message, JSON_UNESCAPED_UNICODE) . ');</script>';
            exit;
        };

        $fail = static function (string $message) use ($wantJson, $respondJson, $respondScript): never {
            if ($wantJson) {
                $respondJson(['uploaded' => 0, 'error' => ['message' => $message]], 200);
            }
            $respondScript('', $message);
        };

        if (!Csrf::verify($token)) {
            http_response_code(403);
            $fail('Phiên làm việc đã hết hạn. Vui lòng tải lại trang rồi thử lại.');
        }

        $upload = UploadService::storePublicImage($_FILES['upload'] ?? null, 'posts', 'post');
        if (!$upload['ok'] || $upload['path'] === '') {
            $fail($upload['error'] !== '' ? $upload['error'] : 'Không nhận được ảnh tải lên.');
        }

        AuditLog::record('post.upload_image', 'post', null, ['path' => $upload['path']]);

        $url = asset($upload['path']);
        if ($wantJson) {
            $respondJson([
                'uploaded' => 1,
                'fileName' => basename($upload['path']),
                'url'      => $url,
            ]);
        }
        $respondScript($url, '');
    }
    /** @param array<string, mixed>|null $post */
    private function form(?array $post): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $post ?? [
            'id' => null, 'slug' => '', 'title' => '', 'category_id' => '', 'excerpt' => '',
            'body' => '', 'image_path' => '', 'image_alt' => '', 'image_caption' => '',
            'status' => 'draft', 'published_at' => '',
        ];
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        $publishedAt = (string) ($values['published_at'] ?? '');
        if ($publishedAt !== '' && !str_contains($publishedAt, 'T')) {
            $publishedAt = date('Y-m-d\TH:i', strtotime($publishedAt));
        }

        $this->view('admin/posts/form', [
            'post'        => $post,
            'values'      => $values,
            'errors'      => $errors,
            'categories'  => Post::categories(),
            'statuses'    => self::STATUSES,
            'publishedAt' => $publishedAt,
            'page'        => [
                'title' => ($post === null ? 'Thêm bài viết' : 'Sửa bài viết') . ' | ' . settings('site_name'),
                'nav'   => 'posts',
            ],
        ], 'layouts/admin');
    }

    /** @return array{0: array<string,mixed>, 1: array<string,string>} */
    private function validate(array $post, ?int $postId, ?string $existingImage): array
    {
        $data = [
            'title'         => trim((string) ($post['title'] ?? '')),
            'slug'          => trim((string) ($post['slug'] ?? '')),
            'category_id'   => (string) ($post['category_id'] ?? ''),
            'excerpt'       => trim((string) ($post['excerpt'] ?? '')),
            'body'          => trim((string) ($post['body'] ?? '')),
            'image_path'    => $existingImage ?? '',
            'remove_image'  => !empty($post['remove_image']),
            'image_alt'     => trim((string) ($post['image_alt'] ?? '')),
            'image_caption' => trim((string) ($post['image_caption'] ?? '')),
            'status'        => (string) ($post['status'] ?? 'draft'),
            'published_at'  => trim((string) ($post['published_at'] ?? '')),
        ];

        $errors = [];

        if (mb_strlen($data['title']) < 5) {
            $errors['title'] = 'Tiêu đề tối thiểu 5 ký tự.';
        }
        $data['body'] = sanitize_html($data['body']);
        $plainBody = trim((string) preg_replace('/\s+/u', ' ', strip_tags($data['body'])));
        if (mb_strlen($plainBody) < 20) {
            $errors['body'] = 'Nội dung bài viết tối thiểu 20 ký tự.';
        }
        if (mb_strlen($data['excerpt']) > 400) {
            $errors['excerpt'] = 'Mô tả ngắn tối đa 400 ký tự.';
        }
        if (!isset(self::STATUSES[$data['status']])) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }
        if ($data['category_id'] !== '') {
            $validIds = array_map(static fn(array $c): int => (int) $c['id'], Post::categories());
            if (!in_array((int) $data['category_id'], $validIds, true)) {
                $errors['category_id'] = 'Chuyên mục không hợp lệ.';
            }
        }
        if ($data['published_at'] !== '') {
            $normalized = str_replace('T', ' ', $data['published_at']);
            $timestamp = strtotime($normalized);
            if ($timestamp === false) {
                $errors['published_at'] = 'Ngày đăng không đúng định dạng.';
            } else {
                $data['published_at'] = date('Y-m-d H:i:s', $timestamp);
            }
        }
        if ($data['remove_image']) {
            $data['image_path'] = '';
        }

        if ($data['slug'] === '') {
            $data['slug'] = slugify($data['title']);
        } else {
            $data['slug'] = slugify($data['slug']);
        }
        if ($data['slug'] === '') {
            $data['slug'] = 'bai-viet';
        }
        $base = $data['slug'];
        $suffix = 2;
        while (Post::slugExists($data['slug'], $postId)) {
            $data['slug'] = $base . '-' . $suffix;
            $suffix++;
            if ($suffix > 100) {
                $errors['slug'] = 'Không tạo được slug duy nhất.';
                break;
            }
        }

        return [$data, $errors];
    }

    private function rememberForm(array $data, array $errors): void
    {
        unset($data['remove_image']);
        Session::flash('_old', $data);
        Session::flash('_errors', $errors);
        Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
    }
}
