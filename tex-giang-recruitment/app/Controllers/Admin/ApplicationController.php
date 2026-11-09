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
        $application = Application::find($applicationId);
        if ($application === null) {
            $this->formError('Hồ sơ không tồn tại.', url('admin/applications'), status: 404);
        }

        $status = (string) ($_POST['status'] ?? '');
        $note = trim((string) ($_POST['admin_note'] ?? ''));
        $appointment = trim((string) ($_POST['appointment_at'] ?? ''));

        if (!isset(Application::STATUSES[$status])) {
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/applications/' . $applicationId), ['status' => 'Trạng thái không hợp lệ.']);
        }
        if (mb_strlen($note) > 2000) {
            $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/applications/' . $applicationId), ['admin_note' => 'Ghi chú tối đa 2000 ký tự.']);
        }

        $appointmentAt = null;
        if ($appointment !== '') {
            $appointment = str_replace('T', ' ', $appointment);
            $timestamp = strtotime($appointment);
            if ($timestamp === false) {
                $this->formError('Vui lòng kiểm tra lại các trường được đánh dấu.', url('admin/applications/' . $applicationId), ['appointment_at' => 'Lịch phỏng vấn không đúng định dạng.']);
            }
            $appointmentAt = date('Y-m-d H:i:s', $timestamp);
        }

        Application::updateStatus($applicationId, $status, $note !== '' ? $note : null, (int) Session::get('auth_id'), $appointmentAt);
        AuditLog::record('application.status', 'application', $applicationId, ['status' => $status]);
        $this->formSuccess('Đã cập nhật hồ sơ sang "' . Application::STATUSES[$status] . '".', url('admin/applications/' . $applicationId));
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
