<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Middleware\ApplicationApiAuth;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Job;
use App\Services\UploadService;

final class ApplicationController extends Controller
{
    public function newApplications(): never
    {
        $integrationUser = ApplicationApiAuth::authorize();
        $page = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $perPage = filter_var($_GET['per_page'] ?? '20', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100],
        ]);
        if ($page === false || $perPage === false) {
            $this->json([
                'ok' => false,
                'code' => 'invalid_pagination',
                'message' => 'page phải là số nguyên dương, per_page phải là số nguyên từ 1 đến 100.',
            ], 400);
        }

        $result = Database::transaction(
            static fn(): array => Application::paginate(['status' => 'new'], $page, $perPage, $integrationUser)
        );
        $result['items'] = array_map(static fn(array $application): array => [
            'id' => (int) $application['id'],
            'job_id' => $application['job_id'] !== null ? (int) $application['job_id'] : null,
            'job_title' => $application['job_title'],
            'status' => $application['status'],
            'created_at' => $application['created_at'],
        ], $result['items']);

        $this->json(['ok' => true] + $result);
    }

    public function index(): void
    {
        $filters = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'job_id' => (string) ($_GET['job_id'] ?? ''),
        ];
        if ($filters['status'] !== '' && !isset(Application::STATUSES[$filters['status']])) {
            $filters['status'] = '';
        }

        $result = Application::paginate($filters, max(1, (int) ($_GET['page'] ?? 1)));

        $this->view('admin/applications/index', [
            'result'   => $result,
            'filters'  => $filters,
            'statuses' => Application::STATUSES,
            'counts'   => Application::countsByStatus(),
            'jobs'     => Job::optionsAll(),
            'page'     => ['title' => 'Hồ sơ ứng tuyển | ' . settings('site_name'), 'nav' => 'applications'],
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $application = Application::find((int) $id);
        if ($application === null) {
            Session::flash('_error', 'Hồ sơ không tồn tại.');
            $this->redirect(url('admin/applications'));
        }

        $this->view('admin/applications/detail', [
            'application' => $application,
            'history'     => Application::history((int) $application['id']),
            'statuses'    => Application::STATUSES,
            'page'        => ['title' => 'Hồ sơ ' . $application['full_name'] . ' | ' . settings('site_name'), 'nav' => 'applications'],
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): void
    {
        $applicationId = (int) $id;
        $target = url('admin/applications/' . $applicationId);
        // Application::find đã lọc theo phạm vi nhà máy của tài khoản.
        if (Application::find($applicationId) === null) {
            $this->formError('Hồ sơ không tồn tại.', url('admin/applications'), status: 404);
        }
        // Yêu cầu từ form cũ có thể vẫn gửi cả ba trường.
        $section = $_POST['section'] ?? 'all';
        if (!in_array($section, ['all', 'status', 'appointment', 'note'], true)) {
            $this->formError('Thao tác cập nhật không hợp lệ.', $target);
        }

        $value = static fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
        $changes = [];
        $errors = [];
        $historyNote = null;
        $message = 'Đã cập nhật hồ sơ.';

        if ($section === 'all' || $section === 'status') {
            $status = $value('status');
            if (!isset(Application::STATUSES[$status])) {
                $errors['status'] = 'Trạng thái không hợp lệ.';
            } else {
                $changes['status'] = $status;
                $message = 'Đã cập nhật hồ sơ sang “' . Application::STATUSES[$status] . '”.';
            }
        }

        if ($section === 'all' || $section === 'appointment') {
            $appointment = trim(str_replace('T', ' ', $value('appointment_at')));
            $date = $appointment === '' ? null : false;
            if ($appointment !== '') {
                $parsed = vn_parse_datetime($appointment);
                if ($parsed !== null) {
                    $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $parsed);
                }
            }
            if ($date === false) {
                $errors['appointment_at'] = 'Lịch phỏng vấn không đúng định dạng (dd/mm/yyyy HH:MM).';
            }
            $changes['appointment_at'] = $date instanceof \DateTimeImmutable ? $date->format('Y-m-d H:i:s') : null;
            if ($section === 'appointment') {
                if (!is_string($_POST['appointment_at'] ?? null)) {
                    $errors['appointment_at'] = 'Ngày giờ phỏng vấn không hợp lệ.';
                }
                if (!is_string($_POST['appointment_location'] ?? null)) {
                    $errors['appointment_location'] = 'Địa điểm không hợp lệ.';
                }
                $location = $value('appointment_location');
                if (mb_strlen($location) > 255) {
                    $errors['appointment_location'] = 'Địa điểm tối đa 255 ký tự.';
                }
                if ($appointment === '' && $location !== '') {
                    $errors['appointment_at'] = 'Chọn ngày giờ phỏng vấn hoặc để trống cả hai ô để hủy lịch.';
                }
                $changes['appointment_location'] = $location !== '' ? $location : null;
                $message = $date instanceof \DateTimeImmutable ? 'Đã lưu lịch phỏng vấn.' : 'Đã hủy lịch phỏng vấn.';
                $historyNote = $date instanceof \DateTimeImmutable
                    ? 'Lịch phỏng vấn: ' . $date->format('H:i d/m/Y') . ($location !== '' ? ' · ' . $location : '')
                    : $message;
            } elseif ($appointment === '') {
                $changes['appointment_location'] = null;
            }
        }

        if ($section === 'all' || $section === 'note') {
            $note = $value('admin_note');
            if (mb_strlen($note) > 2000) {
                $errors['admin_note'] = 'Ghi chú tối đa 2000 ký tự.';
            } elseif ($section === 'note' && $note === '') {
                $errors['admin_note'] = 'Nhập ghi chú nội bộ.';
            }
            $changes['admin_note'] = $note !== '' ? $note : null;
            $historyNote = $changes['admin_note'];
            if ($section === 'note') {
                $message = 'Đã thêm ghi chú nội bộ.';
            }
        }

        if ($errors !== []) {
            $old = array_intersect_key($_POST, array_flip(['status', 'appointment_at', 'appointment_location', 'admin_note']));
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', $target, $errors, array_filter($old, 'is_string'));
        }

        if (!Application::updateReview($applicationId, $changes, $historyNote, (int) Session::get('auth_id'))) {
            $this->formError('Hồ sơ không tồn tại.', url('admin/applications'), status: 404);
        }
        $event = match ($section) {
            'appointment' => 'application.appointment',
            'note' => 'application.note',
            default => 'application.status',
        };
        AuditLog::record($event, 'application', $applicationId, array_intersect_key($changes, ['status' => true]));
        $this->formSuccess($message, $target);
    }

    /** Tải CV — chỉ người đã đăng nhập, file nằm ngoài public. */
    public function downloadCv(string $id): void
    {
        $application = Application::find((int) $id);
        if ($application === null || empty($application['cv_stored_name'])) {
            Session::flash('_error', 'Hồ sơ này không có tệp CV.');
            $this->redirect(url('admin/applications'));
        }

        $path = UploadService::absolutePath((string) $application['cv_stored_name']);
        if ($path === null) {
            Session::flash('_error', 'Tệp CV không còn trên hệ thống.');
            $this->redirect(url('admin/applications/' . (int) $application['id']));
        }

        $downloadName = (string) ($application['cv_original_name'] ?: 'CV-' . $application['full_name'] . '.pdf');
        $downloadName = preg_replace('/[^\p{L}\p{N}._\- ]+/u', '_', $downloadName) ?: 'CV.pdf';

        AuditLog::record('application.cv_download', 'application', (int) $application['id']);

        header('Content-Type: ' . ($application['cv_mime'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }
}
