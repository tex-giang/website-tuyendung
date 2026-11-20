<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Permissions;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Job;
use App\Services\JobImport;

final class JobController extends Controller
{
    public const STATUSES = [
        'draft'     => 'Bản nháp',
        'published' => 'Đang đăng',
        'paused'    => 'Tạm dừng',
        'closed'    => 'Đã đóng',
        'expired'   => 'Hết hạn',
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
        Job::syncExpired();
        $filters = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'block'  => (string) ($_GET['block'] ?? ''),
        ];
        if (!isset(self::STATUSES[$filters['status']])) {
            $filters['status'] = '';
        }
        if (!Job::blockValid($filters['block'])) {
            $filters['block'] = '';
        }

        $result = Job::adminPaginate($filters, max(1, (int) ($_GET['page'] ?? 1)));

        $this->view('admin/jobs/index', [
            'result'   => $result,
            'filters'  => $filters,
            'statuses' => self::STATUSES,
            'blocks'   => Job::BLOCKS,
            'counts'   => Job::countsByStatus(),
            'blockMismatch' => Job::countBlockMismatch(),
            'page'     => ['title' => 'Tin tuyển dụng | ' . settings('site_name'), 'nav' => 'jobs'],
        ], 'layouts/admin');
    }

    public function create(): void
    {
        // Nhân bản: chỉ NẠP dữ liệu tin gốc vào form, chưa lưu DB.
        $sourceId = (int) ($_GET['from'] ?? 0);
        $source = $sourceId > 0 ? Job::adminFind($sourceId) : null;
        $this->guardJobBranch($source);
        $this->form(null, $source);
    }

    public function edit(string $id): void
    {
        $job = Job::adminFind((int) $id);
        if ($job === null) {
            Session::flash('_error', 'Tin tuyển dụng không tồn tại.');
            $this->redirect(url('admin/jobs'));
        }
        $this->guardJobBranch($job);
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
        $message = 'Đã tạo tin tuyển dụng "' . $data['title'] . '".' . $this->blockSyncNote($data);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, $message, url('admin/jobs'));
        }
        Session::flash('_success', $message);
        $this->redirect(url('admin/jobs'));
    }

    public function update(string $id): void
    {
        $jobId = (int) $id;
        $existing = Job::adminFind($jobId);
        $this->guardJobBranch($existing);
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
        $message = 'Đã cập nhật tin tuyển dụng.' . $this->blockSyncNote($data);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, $message, url('admin/jobs'));
        }
        Session::flash('_success', $message);
        $this->redirect(url('admin/jobs'));
    }

    public function changeStatus(string $id): void
    {
        $jobId = (int) $id;
        $status = (string) ($_POST['status'] ?? '');
        $jobRow = Job::adminFind($jobId);
        $this->guardJobBranch($jobRow);
        if (!isset(self::STATUSES[$status]) || $jobRow === null) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Trạng thái không hợp lệ.');
            }
            Session::flash('_error', 'Trạng thái không hợp lệ.');
            $this->redirect(url('admin/jobs'));
        }
        if ($status === 'published' && ($jobRow['deadline'] ?? '') !== '' && (string) $jobRow['deadline'] < date('Y-m-d')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Tin đã quá hạn nộp — cập nhật hạn nộp mới trước khi đăng lại.');
            }
            Session::flash('_error', 'Tin đã quá hạn nộp — cập nhật hạn nộp mới trước khi đăng lại.');
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
        // Chỉ chuyển sang form tạo mới với dữ liệu đã nạp sẵn — KHÔNG ghi DB.
        // Chỉ khi bấm "Lưu" ở form thì tin mới thực sự được tạo.
        $job = Job::adminFind((int) $id);
        if ($job === null) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Không tìm thấy tin để nhân bản.');
            }
            Session::flash('_error', 'Không tìm thấy tin để nhân bản.');
            $this->redirect(url('admin/jobs'));
        }

        $this->guardJobBranch($job);
        $message = 'Đã nạp dữ liệu tin "' . $job['title'] . '" vào form tạo mới. Bấm Lưu để lưu bản sao vào DB.';
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, $message, url('admin/jobs/create') . '?from=' . (int) $id);
        }
        Session::flash('_success', $message);
        $this->redirect(url('admin/jobs/create') . '?from=' . (int) $id);
    }

    /** Tải file Excel mẫu (.xlsx) — hỗ trợ ?format=csv để lấy bản CSV. */
    public function template(): void
    {
        $format = strtolower(trim((string) ($_GET['format'] ?? 'xlsx')));
        if ($format === 'csv' || !class_exists(\ZipArchive::class)) {
            $csv = JobImport::templateCsv();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="mau-tin-tuyen-dung-' . date('Ymd') . '.csv"');
            header('Cache-Control: no-store, max-age=0');
            echo "\xEF\xBB\xBF" . $csv;
            exit;
        }

        try {
            $xlsx = JobImport::templateXlsx();
        } catch (\Throwable $exception) {
            error_log('[JOB-TEMPLATE] ' . $exception->getMessage());
            $csv = JobImport::templateCsv();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="mau-tin-tuyen-dung-' . date('Ymd') . '.csv"');
            header('Cache-Control: no-store, max-age=0');
            echo "\xEF\xBB\xBF" . $csv;
            exit;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="mau-tin-tuyen-dung-' . date('Ymd') . '.xlsx"');
        header('Content-Length: ' . strlen($xlsx));
        header('Cache-Control: no-store, max-age=0');
        echo $xlsx;
        exit;
    }

    /**
     * Xuất tin hiện có (trong phạm vi nhà máy của tài khoản) ra Excel/CSV,
     * điền sẵn Mã tin + nội dung để sửa và import lại theo hướng cập nhật.
     * Hỗ trợ ?format=csv cho máy không có extension zip.
     */
    public function export(): void
    {
        // adminPaginate đã áp phạm vi nhà máy + lọc tin chưa xoá.
        $jobs = Job::adminPaginate([], 1, 5000)['items'];
        AuditLog::record('job.export', 'job', null, ['count' => count($jobs)]);

        $stamp = date('Ymd');
        $format = strtolower(trim((string) ($_GET['format'] ?? 'xlsx')));

        $sendCsv = static function () use ($jobs, $stamp): never {
            $csv = JobImport::exportCsv($jobs);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="tin-tuyen-dung-hien-co-' . $stamp . '.csv"');
            header('Cache-Control: no-store, max-age=0');
            echo "\xEF\xBB\xBF" . $csv;
            exit;
        };

        if ($format === 'csv' || !class_exists(\ZipArchive::class)) {
            $sendCsv();
        }

        try {
            $xlsx = JobImport::exportXlsx($jobs);
        } catch (\Throwable $exception) {
            error_log('[JOB-EXPORT] ' . $exception->getMessage());
            $sendCsv();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="tin-tuyen-dung-hien-co-' . $stamp . '.xlsx"');
        header('Content-Length: ' . strlen($xlsx));
        header('Cache-Control: no-store, max-age=0');
        echo $xlsx;
        exit;
    }

    /** Import danh sách tin từ file Excel (.xlsx) hoặc CSV (theo template). */
    public function import(): void
    {
        $redirect = url('admin/jobs');
        $file = $_FILES['template'] ?? null;

        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('_error', 'Chưa chọn file template hoặc file tải lên bị lỗi.');
            $this->redirect($redirect);
        }
        if ((int) $file['size'] <= 0 || (int) $file['size'] > JobImport::MAX_BYTES) {
            Session::flash('_error', 'File rỗng hoặc vượt quá 3 MB.');
            $this->redirect($redirect);
        }
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'csv', 'txt'], true)) {
            Session::flash('_error', 'Chỉ nhận file Excel (.xlsx) hoặc CSV (.csv) đúng theo template.');
            $this->redirect($redirect);
        }

        try {
            $parsed = $extension === 'xlsx'
                ? JobImport::parseXlsx((string) $file['tmp_name'])
                : JobImport::parse((string) file_get_contents((string) $file['tmp_name']));
        } catch (\Throwable $exception) {
            error_log('[JOB-IMPORT] ' . $exception->getMessage());
            Session::flash('_error', 'Không đọc được file tải lên. Hãy dùng đúng file template (.xlsx hoặc .csv) và thử lại.');
            $this->redirect($redirect);
        }
        if ($parsed['missing'] !== []) {
            $labels = [];
            foreach ($parsed['missing'] as $key) {
                $labels[] = JobImport::COLUMNS[$key][0] ?? $key;
            }
            Session::flash('_error', 'File thiếu cột bắt buộc: ' . implode(', ', $labels) . '. Hãy tải lại template mới nhất.');
            $this->redirect($redirect);
        }

        $rows = $parsed['rows'];
        if ($rows === []) {
            Session::flash('_error', 'File không có dòng dữ liệu nào để import (dòng [MẪU] và dòng ghi chú được bỏ qua).');
            $this->redirect($redirect);
        }
        if (count($rows) > JobImport::MAX_ROWS) {
            Session::flash('_error', 'Tối đa ' . JobImport::MAX_ROWS . ' dòng mỗi lần import. File đang có ' . count($rows) . ' dòng.');
            $this->redirect($redirect);
        }

        // Bảng tra tên/slug -> id cho khu sản xuất và bộ phận.
        $branchLookup = [];
        foreach (Branch::all() as $branch) {
            $branchId = (int) $branch['id'];
            $branchLookup[(string) $branchId] = $branchId;
            $branchLookup[JobImport::norm((string) $branch['name'])] = $branchId;
            $branchLookup[JobImport::branchKey((string) $branch['name'])] = $branchId;
            $branchLookup[JobImport::norm((string) $branch['slug'])] = $branchId;
        }
        $categoryLookup = [];
        $categoryBlocks = [];
        foreach (Category::all() as $category) {
            $categoryId = (int) $category['id'];
            $categoryLookup[JobImport::norm((string) $category['name'])] = $categoryId;
            $categoryLookup[JobImport::norm((string) $category['slug'])] = $categoryId;
            $categoryBlocks[$categoryId] = (string) ($category['block'] ?? '');
        }

        $userId = (int) Session::get('auth_id');
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (string) ($row['_line'] ?? '?');
            $branchValue = trim((string) ($row['branch'] ?? ''));
            $branchId = $branchLookup[JobImport::branchKey($branchValue)]
                ?? $branchLookup[JobImport::norm($branchValue)]
                ?? null;

            if ($branchId === null) {
                $errors[] = 'Dòng ' . $line . ': không nhận diện được khu sản xuất "' . $branchValue . '".';
                continue;
            }
            if (!Permissions::canAccessBranch($branchId)) {
                $errors[] = 'Dòng ' . $line . ': bạn không có quyền với khu sản xuất "' . $branchValue . '".';
                continue;
            }

            $categoryId = '';
            $categoryValue = trim((string) ($row['category'] ?? ''));
            if ($categoryValue !== '') {
                $categoryId = (string) ($categoryLookup[JobImport::norm($categoryValue)] ?? '');
                if ($categoryId === '') {
                    $errors[] = 'Dòng ' . $line . ': không nhận diện được bộ phận "' . $categoryValue . '".';
                    continue;
                }
            }

            // Khối: cột mới. Để trống → mặc định Xí nghiệp.
            $blockValue = JobImport::block((string) ($row['block'] ?? ''));
            $block = $blockValue !== '' ? $blockValue : Job::DEFAULT_BLOCK;
            if (trim((string) ($row['block'] ?? '')) !== '' && $blockValue === '') {
                $errors[] = 'Dòng ' . $line . ': Khối "' . trim((string) $row['block'])
                    . '" không hợp lệ (chỉ nhận Văn phòng hoặc Xí nghiệp).';
                continue;
            }

            // Bộ phận phải thuộc đúng Khối (bộ phận dùng chung block rỗng luôn hợp lệ).
            if ($categoryId !== '') {
                $ownBlock = $categoryBlocks[(int) $categoryId] ?? '';
                if ($ownBlock !== '' && $ownBlock !== $block) {
                    $errors[] = 'Dòng ' . $line . ': bộ phận "' . $categoryValue . '" thuộc Khối '
                        . Job::blockLabel($ownBlock) . ', không khớp Khối ' . Job::blockLabel($block) . '.';
                    continue;
                }
            }

            // Mã tin: có mã trùng tin đang tồn tại => CẬP NHẬT; trống => tạo mới.
            // Mã sai định dạng / không tồn tại => báo lỗi, không âm thầm tạo tin mới.
            $code = trim((string) ($row['code'] ?? ''));
            $existing = null;
            if ($code !== '') {
                if (preg_match('/^TD\d{2}-\d{4}$/', $code) !== 1) {
                    $errors[] = 'Dòng ' . $line . ': mã tin "' . $code . '" không đúng định dạng TD26-0001.';
                    continue;
                }
                $existing = Job::findByCode($code);
                if ($existing === null) {
                    $errors[] = 'Dòng ' . $line . ': mã tin "' . $code . '" không tồn tại hoặc tin đã xoá. Kiểm tra lại mã, hoặc để trống Mã tin để tạo tin mới.';
                    continue;
                }
                if (!Permissions::canAccessBranch((int) $existing['branch_id'])) {
                    $errors[] = 'Dòng ' . $line . ': bạn không có quyền sửa tin "' . $code . '" (ngoài phạm vi nhà máy).';
                    continue;
                }
                if (!Permissions::currentCan('jobs', 'update')) {
                    $errors[] = 'Dòng ' . $line . ': bạn chỉ có quyền tạo mới, không có quyền sửa tin "' . $code . '".';
                    continue;
                }
            }

            $quantity = trim((string) ($row['quantity'] ?? ''));
            $post = [
                'title'            => trim((string) ($row['title'] ?? '')),
                'slug'             => '',
                'code'             => $code,
                'branch_id'        => (string) $branchId,
                'block'            => $block,
                'category_id'      => $categoryId,
                'quantity'         => $quantity !== '' ? $quantity : '1',
                'location'         => trim((string) ($row['location'] ?? '')),
                'employment_type'  => trim((string) ($row['employment_type'] ?? '')),
                'experience'       => trim((string) ($row['experience'] ?? '')),
                'salary_text'      => trim((string) ($row['salary_text'] ?? '')),
                'salary_min'       => trim((string) ($row['salary_min'] ?? '')),
                'salary_max'       => trim((string) ($row['salary_max'] ?? '')),
                'salary_range'     => JobImport::salaryRange((string) ($row['salary_range'] ?? '')),
                'description'      => trim((string) ($row['description'] ?? '')),
                'requirements'     => trim((string) ($row['requirements'] ?? '')),
                'benefits'         => trim((string) ($row['benefits'] ?? '')),
                'status'           => JobImport::status((string) ($row['status'] ?? '')),
                'is_urgent'        => JobImport::flag((string) ($row['is_urgent'] ?? '')),
                'posted_at'        => JobImport::dateTime((string) ($row['posted_at'] ?? '')),
                'deadline'         => JobImport::date((string) ($row['deadline'] ?? '')),
                'meta_title'       => trim((string) ($row['meta_title'] ?? '')),
                'meta_description' => trim((string) ($row['meta_description'] ?? '')),
                'sort_order'       => '0',
            ];

            $existingId = $existing !== null ? (int) $existing['id'] : null;
            [$data, $rowErrors] = $this->validate($post, $existingId);
            if ($rowErrors !== []) {
                $messages = array_slice(array_values($rowErrors), 0, 2);
                $errors[] = 'Dòng ' . $line . ': ' . implode(' ', $messages);
                continue;
            }

            if ($existing !== null) {
                // Cập nhật: giữ slug/URL công khai (import không có cột slug,
                // validate tự sinh slug theo tiêu đề mới — phải ghi đè lại slug cũ).
                $data['slug'] = (string) $existing['slug'];
                // Ngày đăng để trống trong file = giữ ngày đăng cũ, không đặt về hôm nay.
                if (trim((string) ($row['posted_at'] ?? '')) === '') {
                    $data['posted_at'] = (string) ($existing['posted_at'] ?? '') !== ''
                        ? (string) $existing['posted_at']
                        : date('Y-m-d H:i:s');
                }
                // Trạng thái để trống = giữ trạng thái hiện tại, không hạ tin đang đăng về nháp.
                if (trim((string) ($row['status'] ?? '')) === '') {
                    $data['status'] = (string) $existing['status'];
                }
                // Import không có cột sort_order — giữ thứ tự hiện tại của tin.
                $data['sort_order'] = (int) ($existing['sort_order'] ?? 0);
            }

            try {
                if ($existing !== null) {
                    Job::update((int) $existing['id'], $data);
                    AuditLog::record('job.import_update', 'job', (int) $existing['id'], ['code' => $code, 'title' => $data['title']]);
                    $updated++;
                } else {
                    $jobId = Job::create($data, $userId);
                    AuditLog::record('job.import', 'job', $jobId, ['title' => $data['title']]);
                    $created++;
                }
            } catch (\Throwable $exception) {
                error_log('[JOB-IMPORT] ' . $exception->getMessage());
                $errors[] = 'Dòng ' . $line . ': lỗi lưu database.';
            }
        }

        AuditLog::record('job.import.batch', 'job', null, ['created' => $created, 'updated' => $updated, 'failed' => count($errors)]);

        $summary = [];
        if ($created > 0) {
            $summary[] = 'tạo mới ' . $created . ' tin';
        }
        if ($updated > 0) {
            $summary[] = 'cập nhật ' . $updated . ' tin';
        }
        if ($summary !== []) {
            Session::flash('_success', 'Đã import: ' . implode(', ', $summary) . '.');
        }
        if ($errors !== []) {
            Session::flash(
                '_error',
                'Có ' . count($errors) . ' dòng chưa import được: '
                . implode(' | ', array_slice($errors, 0, 5))
                . (count($errors) > 5 ? ' …' : '')
            );
        }

        $this->redirect($redirect);
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
        $this->guardJobBranch($job);
        Job::softDelete($jobId);
        AuditLog::record('job.delete', 'job', $jobId, ['title' => $job['title']]);
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã xoá tin "' . $job['title'] . '". Hồ sơ ứng tuyển liên quan vẫn được giữ.', url('admin/jobs'));
        }
        Session::flash('_success', 'Đã xoá tin "' . $job['title'] . '". Hồ sơ ứng tuyển liên quan vẫn được giữ.');
        $this->redirect(url('admin/jobs'));
    }

    /** @param array<string, mixed>|null $job */
    /** @param array<string, mixed>|null $job @param array<string, mixed>|null $source */
    private function form(?array $job, ?array $source = null): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $job ?? [
            'id' => null, 'slug' => '', 'code' => '', 'branch_id' => '', 'block' => Job::DEFAULT_BLOCK, 'category_id' => '',
            'title' => '', 'quantity' => 1, 'location' => '', 'employment_type' => 'Toàn thời gian, chính thức',
            'experience' => '', 'salary_text' => '', 'salary_min' => '', 'salary_max' => '', 'salary_range' => '',
            'description' => '', 'requirements' => '', 'benefits' => '', 'status' => 'draft', 'is_urgent' => 0,
            'posted_at' => '', 'deadline' => '', 'meta_title' => '', 'meta_description' => '', 'sort_order' => 0,
        ];
        if ($source !== null) {
            // Nạp dữ liệu tin gốc để hiển thị; chưa ghi DB.
            $values = array_merge($values, $source);
            $values['id'] = null;
            $values['slug'] = '';
            $values['code'] = '';
            $values['status'] = 'draft';
            $values['posted_at'] = '';
            if (($values['deadline'] ?? '') !== '' && (string) $values['deadline'] < date('Y-m-d')) {
                $values['deadline'] = '';
            }
            $values['title'] = (string) $source['title'] . ' (bản sao)';
        }
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        // Dropdown Bộ phận: chỉ hiện bộ phận ĐANG CÓ TIN (kể cả tin nháp/chưa đăng),
        // vì đây là form quản trị — bộ phận không có tin nào là danh mục chưa dùng.
        // LUÔN giữ bộ phận đang gán cho tin này (kể cả khi nó chưa có tin khác),
        // nếu không khi sửa tin cũ ô chọn sẽ rỗng và vô tình đổi bộ phận.
        $categories = Category::withJobCounts();
        $keepCategoryId = (int) ($values['category_id'] ?? 0);
        if ($keepCategoryId > 0) {
            $kept = Category::find($keepCategoryId);
            if ($kept !== null && !in_array($keepCategoryId, array_map(
                static fn(array $c): int => (int) $c['id'],
                $categories
            ), true)) {
                array_unshift($categories, $kept + ['job_count' => 0]);
            }
        }

        $this->view('admin/jobs/form', [
            'job'        => $job,
            'values'     => $values,
            'errors'     => $errors,
            'branches'   => $this->branchOptionsForUser(),
            'blocks'     => Job::BLOCKS,
            'categories' => $categories,
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
            'block'            => (string) ($post['block'] ?? ''),
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

        // Tự sinh phần lương hiển thị + khoảng lương từ "Lương từ/đến" nếu để trống.
        // (Template import chỉ có 2 cột lương từ/đến; form admin cũng có thể bỏ trống.)
        if ($data['salary_text'] === '') {
            $data['salary_text'] = JobImport::salaryText($data['salary_min'], $data['salary_max']);
        }
        if ($data['salary_range'] === '') {
            $data['salary_range'] = JobImport::salaryRangeFromAmounts($data['salary_min'], $data['salary_max']);
        }

        $errors = [];

        if (mb_strlen($data['title']) < 5) {
            $errors['title'] = 'Tiêu đề tối thiểu 5 ký tự.';
        }
        if ($data['branch_id'] === '' || !array_key_exists((int) $data['branch_id'], array_column(Branch::all(), 'name', 'id'))) {
            $errors['branch_id'] = 'Chọn khu sản xuất.';
        } elseif (!Permissions::canAccessBranch((int) $data['branch_id'])) {
            $errors['branch_id'] = 'Bạn không có quyền chọn khu sản xuất này.';
        }

        // Khối là cố định: chỉ nhận 'office' hoặc 'enterprise'.
        if (!Job::blockValid($data['block'])) {
            $errors['block'] = 'Chọn Khối tuyển dụng (Văn phòng hoặc Xí nghiệp).';
        }

        // Bộ phận phải thuộc đúng Khối đã chọn (bộ phận dùng chung block = NULL luôn hợp lệ).
        if ($data['category_id'] !== '') {
            $category = Category::find((int) $data['category_id']);
            if ($category === null) {
                $errors['category_id'] = 'Bộ phận không tồn tại.';
            } elseif (($category['block'] ?? null) !== null && (string) $category['block'] !== '') {
                // Bộ phận đã gán Khối → Khối của BỘ PHẬN là chuẩn.
                // Nếu người dùng chọn Khối khác, tự đồng bộ thay vì báo lỗi:
                // tránh sinh ra tin lệch Khối (dropdown đếm theo bộ phận, lọc theo
                // jobs.block → lệch sẽ ra kết quả 0 khó hiểu).
                if (Job::blockValid($data['block']) && (string) $category['block'] !== $data['block']) {
                    // Ghi nhớ để báo cho người dùng biết Khối đã được tự chỉnh.
                    $data['_block_synced_from'] = (string) $data['block'];
                    $data['_block_synced_to'] = (string) $category['block'];
                    $data['block'] = (string) $category['block'];
                }
            }
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
        // Ngày nhập tay dạng dd/mm/yyyy (hoặc dd/mm/yyyy HH:MM, yyyy-mm-dd…) → chuẩn hoá ISO.
        $deadlineRaw = $data['deadline'];
        $postedRaw = $data['posted_at'];
        $parsedDeadline = $deadlineRaw !== '' ? vn_parse_date($deadlineRaw) : '';
        if ($deadlineRaw !== '' && $parsedDeadline === null) {
            $errors['deadline'] = 'Hạn nộp không đúng định dạng (dd/mm/yyyy).';
        }
        $parsedPosted = $postedRaw !== '' ? vn_parse_datetime($postedRaw) : '';
        if ($postedRaw !== '' && $parsedPosted === null) {
            $errors['posted_at'] = 'Ngày đăng không đúng định dạng (dd/mm/yyyy HH:MM).';
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

        if ($data['code'] === '') {
            $data['code'] = Job::generateCode();
        } elseif (preg_match('/^TD\d{2}-\d{4}$/', $data['code']) !== 1) {
            $errors['code'] = 'Mã tin phải có định dạng TD26-0001.';
        } elseif (Job::codeExists($data['code'], $jobId)) {
            $errors['code'] = 'Mã tin đã tồn tại.';
        }

        // Ngày đăng: để trống => mặc định hôm nay (giờ hiện tại).
        if ($postedRaw === '') {
            $data['posted_at'] = date('Y-m-d H:i:s');
        } else {
            $data['posted_at'] = $parsedPosted !== null ? $parsedPosted : date('Y-m-d H:i:s');
        }
        // Hạn nộp: để trống = không giới hạn (hiển thị “Chưa xác định”).
        $data['deadline'] = $deadlineRaw !== '' && $parsedDeadline !== null ? $parsedDeadline : '';
        $data['sort_order'] = (int) $data['sort_order'];

        return [$data, $errors];
    }

    /** Chặn thao tác trên tin thuộc nhà máy ngoài phạm vi tài khoản. */
    private function guardJobBranch(?array $job): void
    {
        if ($job === null) {
            return;
        }
        Permissions::requireBranch((int) ($job['branch_id'] ?? 0));
    }

    /** @return array<int, array<string, mixed>> danh sách nhà máy tài khoản được phép chọn */
    private function branchOptionsForUser(): array
    {
        $branches = Branch::all();
        if (Permissions::allBranches()) {
            return $branches;
        }
        return array_values(array_filter(
            $branches,
            static fn(array $branch): bool => Permissions::canAccessBranch((int) $branch['id'])
        ));
    }

    private function rememberForm(array $data, array $errors): void
    {
        Session::flash('_old', $data);
        Session::flash('_errors', $errors);
        Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
    }

    /**
     * Câu thông báo thêm khi Khối của tin bị tự đồng bộ theo Khối của bộ phận.
     * Không có đồng bộ thì trả chuỗi rỗng.
     *
     * @param array<string, mixed> $data
     */
    private function blockSyncNote(array $data): string
    {
        $from = (string) ($data['_block_synced_from'] ?? '');
        $to = (string) ($data['_block_synced_to'] ?? '');
        if ($from === '' || $to === '') {
            return '';
        }
        return ' Khối đã tự đổi sang "' . Job::blockLabel($to) . '" cho khớp bộ phận (bạn chọn "'
            . Job::blockLabel($from) . '").';
    }
}
