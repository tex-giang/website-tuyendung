<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Job;
use App\Services\UploadService;

final class ApplicationController extends Controller
{
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
            $appointment = str_replace('T', ' ', $value('appointment_at'));
            if (strlen($appointment) === 16) {
                $appointment .= ':00';
            }
            $date = $appointment === '' ? null : false;
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $appointment)) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $appointment);
            }
            if ($date === false || ($date !== null && ($date->format('Y-m-d H:i:s') !== $appointment || (int) $date->format('Y') < 1000))) {
                $errors['appointment_at'] = 'Lịch phỏng vấn không đúng định dạng.';
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
