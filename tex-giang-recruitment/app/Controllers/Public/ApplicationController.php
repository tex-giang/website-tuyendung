<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Application;
use App\Models\Branch;
use App\Models\Job;
use App\Services\UploadService;

final class ApplicationController extends Controller
{
    /** Giới hạn chống spam: tối đa 5 hồ sơ / giờ / phiên trình duyệt. */
    private const RATE_LIMIT = 5;
    private const RATE_WINDOW = 3600;

    public function show(): void
    {
        $this->renderForm();
    }

    public function submit(): void
    {
        $this->enforceRateLimit();

        // Honeypot: bot điền field ẩn thì coi như thành công nhưng không ghi DB.
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(true, 'Đã gửi hồ sơ.', url('apply/success'));
            }
            $this->redirect(url('apply/success'));
        }

        $data = [
            'full_name'  => trim((string) ($_POST['full_name'] ?? '')),
            'phone'      => trim((string) ($_POST['phone'] ?? '')),
            'email'      => trim((string) ($_POST['email'] ?? '')),
            'birth_year' => trim((string) ($_POST['birth_year'] ?? '')),
            'address'    => trim((string) ($_POST['address'] ?? '')),
            'gender'     => trim((string) ($_POST['gender'] ?? '')),
            'job_slug'   => trim((string) ($_POST['job_slug'] ?? '')),
            'branch_slug' => trim((string) ($_POST['branch_slug'] ?? '')),
            'message'    => trim((string) ($_POST['message'] ?? '')),
            'terms'      => !empty($_POST['terms']),
        ];

        $errors = $this->validate($data);
        if ($errors !== []) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Hồ sơ chưa hoàn tất. Vui lòng kiểm tra các mục được đánh dấu.', null, $errors);
            }
            Session::flash('_errors', $errors);
            Session::flash('_old', $data);
            Session::flash('_error', 'Hồ sơ chưa hoàn tất. Vui lòng kiểm tra các mục được đánh dấu.');
            $this->redirect(url('apply'));
        }

        $job = null;
        if ($data['job_slug'] !== '' && $data['job_slug'] !== 'khac') {
            $job = Job::findPublishedBySlug($data['job_slug']);
        }

        // Ghi chú thêm khu mong muốn nếu ứng viên chọn "CV tự do / vị trí khác".
        $note = $data['message'];
        $branch = $data['branch_slug'] !== '' ? Branch::findBySlug($data['branch_slug']) : null;
        if ($branch !== null) {
            $note = trim('Khu mong muốn: ' . $branch['name'] . ($note !== '' ? "\n" . $note : ''));
        }

        $upload = UploadService::storeCv($_FILES['cv'] ?? null);
        if (!$upload['ok']) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, (string) $upload['error'], null, ['cv' => (string) $upload['error']]);
            }
            Session::flash('_error', $upload['error']);
            Session::flash('_old', $data);
            $this->redirect(url('apply/error'));
        }

        try {
            $applicationId = Application::create([
                'job_id'           => $job !== null ? (int) $job['id'] : null,
                'full_name'        => $data['full_name'],
                'phone'            => $this->normalizePhone($data['phone']),
                'email'            => $data['email'],
                'gender'           => in_array($data['gender'], ['nu', 'nam', 'khac'], true) ? $data['gender'] : '',
                'birth_year'       => $data['birth_year'],
                'address'          => $data['address'],
                'message'          => $note,
                'source'           => 'Website — form ứng tuyển',
                ...$upload['data'],
            ]);
        } catch (\Throwable $e) {
            if (($upload['data']['cv_stored_name'] ?? '') !== '') {
                UploadService::delete((string) $upload['data']['cv_stored_name']);
            }
            error_log('[TEX-GIANG] Lỗi lưu hồ sơ: ' . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Không lưu được hồ sơ do lỗi hệ thống. Vui lòng thử lại hoặc dùng trang ứng tuyển đầy đủ.');
            }
            Session::flash('_error', 'Không lưu được hồ sơ do lỗi hệ thống. Vui lòng liên hệ người phụ trách nhà máy (trang Liên hệ) để được hỗ trợ.');
            $this->redirect(url('apply/error'));
        }

        Session::put('_last_application', [
            'id'        => $applicationId,
            'full_name' => $data['full_name'],
            'job_title' => $job['title'] ?? null,
        ]);

        $used = Session::get('_apply_times', []);
        $used[] = time();
        Session::put('_apply_times', $used);

        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, 'Đã gửi hồ sơ.', url('apply/success'));
        }
        $this->redirect(url('apply/success'));
    }

    /** Trang nộp hồ sơ nhanh (/tuyen-dung): họ tên, SĐT, nhà máy → vị trí. */
    public function quickShow(): void
    {
        $done = (string) ($_GET['done'] ?? '') === '1';
        $last = Session::pull('_quick_last', []);

        if ($done && $last === []) {
            $this->redirect(url('tuyen-dung'));
        }

        $jobs = Job::latestPublished(50);
        $branches = Branch::active();

        $jobData = [];
        foreach ($jobs as $job) {
            $jobData[] = [
                'slug'   => (string) $job['slug'],
                'title'  => trim((string) preg_replace('/^\d+\s+/', '', (string) $job['title'])),
                'branch' => (string) ($job['branch_slug'] ?? ''),
                'salary' => ($job['salary_text'] ?? '') !== '' ? salary_short((string) $job['salary_text']) : '',
            ];
        }

        $old = Session::pull('_quick_old', []);
        $errors = Session::pull('_quick_errors', []);

        $this->view('public/ung-tuyen', [
            'branches' => $branches,
            'jobData'  => $jobData,
            'old'      => $old,
            'errors'   => $errors,
            'done'     => $done,
            'last'     => $last,
            'page'     => [
                'title'       => 'Nộp hồ sơ ứng tuyển | ' . settings('site_name'),
                'description' => 'Nộp hồ sơ ứng tuyển TEX-GIANG trong 1 phút: họ tên, số điện thoại, nhà máy và vị trí mong muốn.',
                'body_class'  => 'tg-public gf-page',
                'robots'      => 'noindex',
                'scripts'     => ['quick-apply.js'],
            ],
        ], 'layouts/standalone');
    }

    public function quickSubmit(): void
    {
        $this->enforceRateLimit();

        // Honeypot: bot điền field ẩn thì coi như thành công nhưng không ghi DB.
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            $this->redirect(url('apply/success'));
        }

        $data = [
            'full_name'   => trim((string) ($_POST['full_name'] ?? '')),
            'phone'       => trim((string) ($_POST['phone'] ?? '')),
            'branch_slug' => trim((string) ($_POST['branch_slug'] ?? '')),
            'job_slug'    => trim((string) ($_POST['job_slug'] ?? '')),
            'terms'       => !empty($_POST['terms']),
        ];

        $errors = [];
        if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
            $errors['full_name'] = 'Nhập họ và tên (2–150 ký tự).';
        }
        if (!preg_match('/^(?:0|\+?84)[35789]\d{8}$/', $this->normalizePhone($data['phone']))) {
            $errors['phone'] = 'Nhập số di động Việt Nam hợp lệ, ví dụ 0918 123 456.';
        }

        $branch = $data['branch_slug'] !== '' ? Branch::findBySlug($data['branch_slug']) : null;
        if ($branch === null || (int) ($branch['is_active'] ?? 0) !== 1) {
            $errors['branch_slug'] = 'Chọn nhà máy bạn muốn làm việc.';
        }

        $job = null;
        if ($data['job_slug'] === '') {
            $errors['job_slug'] = 'Chọn vị trí bạn muốn ứng tuyển.';
        } elseif ($data['job_slug'] !== 'khac') {
            $job = Job::findPublishedBySlug($data['job_slug']);
            if ($job === null) {
                $errors['job_slug'] = 'Vị trí đã chọn không còn tuyển. Vui lòng chọn lại.';
            } elseif ($branch !== null && (string) ($job['branch_slug'] ?? '') !== $data['branch_slug']) {
                $errors['job_slug'] = 'Vị trí đã chọn không thuộc nhà máy bạn chọn.';
            }
        }

        if (!$data['terms']) {
            $errors['terms'] = 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ.';
        }

        if ($errors !== []) {
            Session::flash('_quick_errors', $errors);
            Session::flash('_quick_old', $data);
            Session::flash('_error', 'Hồ sơ chưa hoàn tất. Vui lòng kiểm tra các mục được đánh dấu.');
            $this->redirect(url('tuyen-dung'));
        }

        $note = 'Khu mong muốn: ' . (string) $branch['name'] . ' (nộp nhanh)';

        try {
            $applicationId = Application::create([
                'job_id'    => $job !== null ? (int) $job['id'] : null,
                'full_name' => $data['full_name'],
                'phone'     => $this->normalizePhone($data['phone']),
                'message'   => $note,
                'source'    => 'Website — form nộp nhanh',
            ]);
        } catch (\Throwable $e) {
            error_log('[TEX-GIANG] Lỗi lưu hồ sơ nhanh: ' . $e->getMessage());
            Session::flash('_error', 'Không lưu được hồ sơ do lỗi hệ thống. Vui lòng thử lại hoặc dùng trang ứng tuyển đầy đủ.');
            $this->redirect(url('apply/error'));
        }

        $code = sprintf('TG-%s-%04d', date('Y'), $applicationId);
        Session::put('_quick_last', [
            'id'        => $applicationId,
            'code'      => $code,
            'full_name' => $data['full_name'],
            'job_title' => $job['title'] ?? null,
        ]);
        Session::put('_last_application', [
            'id'        => $applicationId,
            'full_name' => $data['full_name'],
            'job_title' => $job['title'] ?? null,
        ]);

        $used = Session::get('_apply_times', []);
        $used[] = time();
        Session::put('_apply_times', $used);

        $this->redirect(url('tuyen-dung') . '?done=1');
    }
    public function success(): void
    {
        $last = Session::pull('_last_application', []);
        $this->view('public/apply/success', [
            'last' => $last,
            'page' => [
                'title'      => 'Đã gửi hồ sơ | ' . settings('site_name'),
                'body_class' => 'tg-public page-application-success flex min-h-screen flex-col',
                'nav'        => '',
                'main_class' => 'notice-main',
                'robots'     => 'noindex',
            ],
        ]);
    }

    public function error(): void
    {
        $message = Session::pull('_error', 'Có lỗi khi gửi hồ sơ. Vui lòng thử lại.');
        $this->view('public/apply/error', [
            'message' => $message,
            'page'    => [
                'title'      => 'Chưa gửi được hồ sơ | ' . settings('site_name'),
                'body_class' => 'tg-public page-application-error flex min-h-screen flex-col',
                'nav'        => '',
                'main_class' => 'notice-main',
                'robots'     => 'noindex',
            ],
        ]);
    }

    private function renderForm(): void
    {
        $jobs = Job::latestPublished(20);
        $branches = Branch::options();

        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        // Prefill từ query (?job=slug&vung=khu) khi mở từ trang chi tiết.
        $prefillJob = trim((string) ($_GET['job'] ?? ''));
        if ($prefillJob !== '' && !isset($old['job_slug'])) {
            $old['job_slug'] = $prefillJob;
        }
        $prefillBranch = trim((string) ($_GET['vung'] ?? ''));
        if ($prefillBranch !== '' && !isset($old['branch_slug'])) {
            $old['branch_slug'] = $prefillBranch;
        }

        $this->view('public/apply/index', [
            'jobs'     => $jobs,
            'branches' => $branches,
            'old'      => $old,
            'errors'   => $errors,
            'page'     => [
                'title'       => 'Nộp hồ sơ ứng tuyển | ' . settings('site_name'),
                'description' => 'Nộp hồ sơ trực tuyến trong 2 phút, không cần tài khoản. Ban tuyển dụng '
                    . settings('legal_name') . ' liên hệ trong 24 giờ làm việc.',
                'body_class'  => 'tg-public page-apply',
                'nav'         => '',
                'scripts'     => ['apply-form.js'],
            ],
        ]);
    }

    private function enforceRateLimit(): void
    {
        $now = time();
        $times = array_values(array_filter(
            (array) Session::get('_apply_times', []),
            static fn(int $t): bool => $t > $now - self::RATE_WINDOW
        ));
        Session::put('_apply_times', $times);

        if (count($times) >= self::RATE_LIMIT) {
            if ($this->isAjaxRequest()) {
                $this->jsonResult(false, 'Bạn đã gửi ' . self::RATE_LIMIT . ' hồ sơ trong một giờ. Vui lòng liên hệ người phụ trách nhà máy (xem trang Liên hệ) nếu cần gửi thêm.');
            }
            Session::flash('_error', 'Bạn đã gửi ' . self::RATE_LIMIT . ' hồ sơ trong một giờ. '
                . 'Vui lòng liên hệ người phụ trách nhà máy (xem trang Liên hệ) nếu cần gửi thêm.');
            $this->redirect(url('apply/error'));
        }
    }

    /** @return array<string, string> */
    private function validate(array $data): array
    {
        $errors = [];

        if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
            $errors['full_name'] = 'Nhập họ và tên (2–150 ký tự).';
        }

        $phone = $this->normalizePhone($data['phone']);
        if (!preg_match('/^(?:0|\+?84)[35789]\d{8}$/', $phone)) {
            $errors['phone'] = 'Nhập số di động Việt Nam hợp lệ, ví dụ 0918 123 456.';
        }

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Kiểm tra lại địa chỉ email.';
        }

        if ($data['birth_year'] !== '') {
            $year = (int) $data['birth_year'];
            $minYear = (int) date('Y') - 60;
            $maxYear = (int) date('Y') - 18;
            if ($year < $minYear || $year > $maxYear) {
                $errors['birth_year'] = 'Năm sinh phải trong khoảng ' . $minYear . '–' . $maxYear . ' (đủ 18 tuổi).';
            }
        }

        if (mb_strlen($data['address']) < 3 || mb_strlen($data['address']) > 255) {
            $errors['address'] = 'Nhập địa chỉ hiện tại để xếp tuyến xe đưa rước.';
        }

        if (mb_strlen($data['message']) > 2000) {
            $errors['message'] = 'Phần giới thiệu tối đa 2000 ký tự.';
        }

        if ($data['job_slug'] !== '' && $data['job_slug'] !== 'khac'
            && Job::findPublishedBySlug($data['job_slug']) === null) {
            $errors['job_slug'] = 'Vị trí đã chọn không còn tuyển. Vui lòng chọn lại.';
        }

        if ($data['branch_slug'] !== '' && !isset(Branch::options()[$data['branch_slug']])) {
            $errors['branch_slug'] = 'Khu sản xuất không hợp lệ.';
        }

        if (!isset($errors['job_slug']) && !isset($errors['branch_slug'])
            && $data['job_slug'] !== '' && $data['job_slug'] !== 'khac' && $data['branch_slug'] !== '') {
            $jobForBranch = Job::findPublishedBySlug($data['job_slug']);
            if ($jobForBranch !== null && (string) ($jobForBranch['branch_slug'] ?? '') !== $data['branch_slug']) {
                $errors['job_slug'] = 'Vị trí đã chọn không thuộc nhà máy bạn chọn. Vui lòng chọn lại.';
            }
        }

        if (!$data['terms']) {
            $errors['terms'] = 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ.';
        }

        return $errors;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[\s().-]/', '', $phone) ?? '';
        if (str_starts_with($digits, '+84')) {
            $digits = '0' . substr($digits, 3);
        } elseif (str_starts_with($digits, '84') && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        }
        return $digits;
    }
}
