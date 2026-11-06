<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Job;

final class JobController extends Controller
{
    public const STATUSES = [
        'draft'     => 'Bản nháp',
        'published' => 'Đang đăng',
        'paused'    => 'Tạm dừng',
        'closed'    => 'Đã đóng',
    ];

    public const SALARY_RANGES = [
        ''        => '— Không chọn —',
        'under-8' => 'Dưới 8 triệu (học việc)',
        '8-12'    => '8 – 12 triệu',
        '12-18'   => '12 – 18 triệu',
        'over-18' => 'Trên 18 triệu',
    ];

    public function index(): void
    {
        $filters = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
        ];
        if (!isset(self::STATUSES[$filters['status']])) {
            $filters['status'] = '';
        }

        $result = Job::adminPaginate($filters, max(1, (int) ($_GET['page'] ?? 1)), 500);

        $this->view('admin/jobs/index', [
            'result'   => $result,
            'filters'  => $filters,
            'statuses' => self::STATUSES,
            'counts'   => Job::countsByStatus(),
            'page'     => ['title' => 'Tin tuyển dụng | ' . settings('site_name'), 'nav' => 'jobs'],
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $job = Job::adminFind((int) $id);
        if ($job === null) {
            Session::flash('_error', 'Tin tuyển dụng không tồn tại.');
            $this->redirect(url('admin/jobs'));
        }
        $this->form($job);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate($_POST, null);
        if ($errors !== []) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/jobs/create'), $errors);
            }
            $this->rememberForm($data, $errors);
            $this->redirect(url('admin/jobs/create'));
        }

        $id = Job::create($data, (int) Session::get('auth_id'));
        AuditLog::record('job.create', 'job', $id, ['title' => $data['title']]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã tạo tin tuyển dụng "' . $data['title'] . '".', url('admin/jobs/' . $id . '/edit'));
        }
        Session::flash('_success', 'Đã tạo tin tuyển dụng "' . $data['title'] . '".');
        $this->redirect(url('admin/jobs/' . $id . '/edit'));
    }

    public function update(string $id): void
    {
        $jobId = (int) $id;
        $existing = Job::adminFind($jobId);
        if ($existing === null) {
            Session::flash('_error', 'Tin tuyển dụng không tồn tại.');
            $this->redirect(url('admin/jobs'));
        }

        [$data, $errors] = $this->validate($_POST, $jobId);
        if ($errors !== []) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/jobs/' . $jobId . '/edit'), $errors);
            }
            $this->rememberForm($data, $errors);
            $this->redirect(url('admin/jobs/' . $jobId . '/edit'));
        }

        Job::update($jobId, $data);
        AuditLog::record('job.update', 'job', $jobId, ['title' => $data['title']]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã cập nhật tin tuyển dụng.', url('admin/jobs/' . $jobId . '/edit'));
        }
        Session::flash('_success', 'Đã cập nhật tin tuyển dụng.');
        $this->redirect(url('admin/jobs/' . $jobId . '/edit'));
    }

    public function changeStatus(string $id): void
    {
        $jobId = (int) $id;
        $status = (string) ($_POST['status'] ?? '');
        if (!isset(self::STATUSES[$status]) || Job::adminFind($jobId) === null) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Trạng thái không hợp lệ.');
            }
            Session::flash('_error', 'Trạng thái không hợp lệ.');
            $this->redirect(url('admin/jobs'));
        }
        Job::changeStatus($jobId, $status);
        AuditLog::record('job.status', 'job', $jobId, ['status' => $status]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã chuyển trạng thái tin sang "' . self::STATUSES[$status] . '".', url('admin/jobs'));
        }
        Session::flash('_success', 'Đã chuyển trạng thái tin sang "' . self::STATUSES[$status] . '".');
        $this->redirect(url('admin/jobs'));
    }

    public function duplicate(string $id): void
    {
        $newId = Job::duplicate((int) $id, (int) Session::get('auth_id'));
        if ($newId === null) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Không tìm thấy tin để nhân bản.');
            }
            Session::flash('_error', 'Không tìm thấy tin để nhân bản.');
            $this->redirect(url('admin/jobs'));
        }
        AuditLog::record('job.duplicate', 'job', $newId, ['from' => (int) $id]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã nhân bản tin thành bản nháp mới.', url('admin/jobs/' . $newId . '/edit'));
        }
        Session::flash('_success', 'Đã nhân bản tin thành bản nháp mới.');
        $this->redirect(url('admin/jobs/' . $newId . '/edit'));
    }

    public function destroy(string $id): void
    {
        $jobId = (int) $id;
        $job = Job::adminFind($jobId);
        if ($job === null) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Tin tuyển dụng không tồn tại.');
            }
            Session::flash('_error', 'Tin tuyển dụng không tồn tại.');
            $this->redirect(url('admin/jobs'));
        }
        Job::softDelete($jobId);
        AuditLog::record('job.delete', 'job', $jobId, ['title' => $job['title']]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã xoá tin "' . $job['title'] . '". Hồ sơ ứng tuyển liên quan vẫn được giữ.', url('admin/jobs'));
        }
        Session::flash('_success', 'Đã xoá tin "' . $job['title'] . '". Hồ sơ ứng tuyển liên quan vẫn được giữ.');
        $this->redirect(url('admin/jobs'));
    }

    /** @param array<string, mixed>|null $job */
    private function form(?array $job): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $job ?? [
            'id' => null, 'slug' => '', 'code' => '', 'branch_id' => '', 'category_id' => '',
            'title' => '', 'quantity' => 1, 'location' => '', 'employment_type' => 'Toàn thời gian, chính thức',
            'experience' => '', 'salary_text' => '', 'salary_min' => '', 'salary_max' => '', 'salary_range' => '',
            'description' => '', 'requirements' => '', 'benefits' => '', 'status' => 'draft', 'is_urgent' => 0,
            'posted_at' => '', 'deadline' => '', 'meta_title' => '', 'meta_description' => '', 'sort_order' => 0,
        ];
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        $this->view('admin/jobs/form', [
            'job'        => $job,
            'values'     => $values,
            'errors'     => $errors,
            'branches'   => Branch::all(),
            'categories' => Category::all(),
            'statuses'   => self::STATUSES,
            'salaryRanges' => self::SALARY_RANGES,
            'page'       => [
                'title' => ($job === null ? 'Thêm tin tuyển dụng' : 'Sửa tin tuyển dụng') . ' | ' . settings('site_name'),
                'nav'   => 'jobs',
            ],
        ], 'layouts/admin');
    }

    /**
     * Validate + chuẩn hoá dữ liệu form.
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validate(array $post, ?int $jobId): array
    {
        $data = [
            'title'            => trim((string) ($post['title'] ?? '')),
            'slug'             => trim((string) ($post['slug'] ?? '')),
            'code'             => trim((string) ($post['code'] ?? '')),
            'branch_id'        => (string) ($post['branch_id'] ?? ''),
            'category_id'      => (string) ($post['category_id'] ?? ''),
            'quantity'         => trim((string) ($post['quantity'] ?? '1')),
            'location'         => trim((string) ($post['location'] ?? '')),
            'employment_type'  => trim((string) ($post['employment_type'] ?? '')),
            'experience'       => trim((string) ($post['experience'] ?? '')),
            'salary_text'      => trim((string) ($post['salary_text'] ?? '')),
            'salary_min'       => trim((string) ($post['salary_min'] ?? '')),
            'salary_max'       => trim((string) ($post['salary_max'] ?? '')),
            'salary_range'     => (string) ($post['salary_range'] ?? ''),
            'description'      => trim((string) ($post['description'] ?? '')),
            'requirements'     => trim((string) ($post['requirements'] ?? '')),
            'benefits'         => trim((string) ($post['benefits'] ?? '')),
            'status'           => (string) ($post['status'] ?? 'draft'),
            'is_urgent'        => !empty($post['is_urgent']) ? 1 : 0,
            'posted_at'        => trim((string) ($post['posted_at'] ?? '')),
            'deadline'         => trim((string) ($post['deadline'] ?? '')),
            'meta_title'       => trim((string) ($post['meta_title'] ?? '')),
            'meta_description' => trim((string) ($post['meta_description'] ?? '')),
            'sort_order'       => trim((string) ($post['sort_order'] ?? '0')),
        ];

        $errors = [];

        if (mb_strlen($data['title']) < 5) {
            $errors['title'] = 'Tiêu đề tối thiểu 5 ký tự.';
        }
        if ($data['branch_id'] === '' || !array_key_exists((int) $data['branch_id'], array_column(Branch::all(), 'name', 'id'))) {
            $errors['branch_id'] = 'Chọn khu sản xuất.';
        }
        if ((int) $data['quantity'] < 1) {
            $errors['quantity'] = 'Số lượng phải lớn hơn 0.';
        }
        if (mb_strlen($data['description']) < 20) {
            $errors['description'] = 'Mô tả công việc tối thiểu 20 ký tự.';
        }
        if (!isset(self::STATUSES[$data['status']])) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }
        if ($data['salary_range'] !== '' && !isset(self::SALARY_RANGES[$data['salary_range']])) {
            $errors['salary_range'] = 'Khoảng lương không hợp lệ.';
        }
        if ($data['salary_min'] !== '' && (!ctype_digit($data['salary_min']) || (int) $data['salary_min'] < 0)) {
            $errors['salary_min'] = 'Lương tối thiểu phải là số nguyên dương.';
        }
        if ($data['salary_max'] !== '' && (!ctype_digit($data['salary_max']) || (int) $data['salary_max'] < 0)) {
            $errors['salary_max'] = 'Lương tối đa phải là số nguyên dương.';
        }
        if ($data['salary_min'] !== '' && $data['salary_max'] !== '' && (int) $data['salary_max'] < (int) $data['salary_min']) {
            $errors['salary_max'] = 'Lương tối đa phải lớn hơn hoặc bằng lương tối thiểu.';
        }
        if ($data['deadline'] !== '' && !$this->isDate($data['deadline'])) {
            $errors['deadline'] = 'Hạn nộp không đúng định dạng ngày.';
        }
        if ($data['posted_at'] !== '' && !$this->isDateTime($data['posted_at'])) {
            $errors['posted_at'] = 'Ngày đăng không đúng định dạng.';
        }

        // Slug: tự sinh nếu để trống, luôn đảm bảo duy nhất.
        if ($data['slug'] === '') {
            $data['slug'] = slugify($data['title']);
        } else {
            $data['slug'] = slugify($data['slug']);
        }
        if ($data['slug'] === '') {
            $data['slug'] = 'tin-tuyen-dung';
        }
        $base = $data['slug'];
        $suffix = 2;
        while (Job::slugExists($data['slug'], $jobId)) {
            $data['slug'] = $base . '-' . $suffix;
            $suffix++;
            if ($suffix > 100) {
                $errors['slug'] = 'Không tạo được slug duy nhất, hãy nhập slug khác.';
                break;
            }
        }

        if ($data['code'] !== '' && Job::codeExists($data['code'], $jobId)) {
            $errors['code'] = 'Mã tin đã tồn tại.';
        }

        // Chuẩn hoá ngày để lưu DB.
        $data['posted_at'] = $data['posted_at'] !== '' ? date('Y-m-d H:i:s', strtotime($data['posted_at'])) : '';
        $data['deadline'] = $data['deadline'] !== '' ? date('Y-m-d', strtotime($data['deadline'])) : '';
        $data['sort_order'] = (int) $data['sort_order'];

        return [$data, $errors];
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function isDateTime(string $value): bool
    {
        $value = str_replace('T', ' ', $value);
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return true;
            }
        }
        return false;
    }

    private function rememberForm(array $data, array $errors): void
    {
        Session::flash('_old', $data);
        Session::flash('_errors', $errors);
        Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
    }
}
