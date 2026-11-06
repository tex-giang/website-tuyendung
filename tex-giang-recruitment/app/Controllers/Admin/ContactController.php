<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\ContactMessage;

final class ContactController extends Controller
{
    public function index(): void
    {
        $filters = ['status' => (string) ($_GET['status'] ?? '')];
        if (!isset(ContactMessage::STATUSES[$filters['status']])) {
            $filters['status'] = '';
        }
        $result = ContactMessage::paginate($filters, max(1, (int) ($_GET['page'] ?? 1)), 500);

        $this->view('admin/contact/index', [
            'result'   => $result,
            'filters'  => $filters,
            'statuses' => ContactMessage::STATUSES,
            'counts'   => ContactMessage::countsByStatus(),
            'page'     => ['title' => 'Hộp thư liên hệ | ' . settings('site_name'), 'nav' => 'contact'],
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $message = ContactMessage::find((int) $id);
        if ($message === null) {
            Session::flash('_error', 'Tin nhắn không tồn tại.');
            $this->redirect(url('admin/contact'));
        }
        if ($message['status'] === 'new') {
            ContactMessage::updateStatus((int) $message['id'], 'read', (int) Session::get('auth_id'));
            $message['status'] = 'read';
        }

        $this->view('admin/contact/detail', [
            'message'  => $message,
            'statuses' => ContactMessage::STATUSES,
            'page'     => ['title' => 'Tin nhắn từ ' . $message['full_name'] . ' | ' . settings('site_name'), 'nav' => 'contact'],
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): void
    {
        $messageId = (int) $id;
        $message = ContactMessage::find($messageId);
        $status = (string) ($_POST['status'] ?? '');
        if ($message === null || !isset(ContactMessage::STATUSES[$status])) {
            Session::flash('_error', 'Tin nhắn hoặc trạng thái không hợp lệ.');
            $this->redirect(url('admin/contact'));
        }
        ContactMessage::updateStatus($messageId, $status, (int) Session::get('auth_id'));
        AuditLog::record('contact.status', 'contact_message', $messageId, ['status' => $status]);
        Session::flash('_success', 'Đã cập nhật trạng thái tin nhắn.');
        $this->redirect(url('admin/contact/' . $messageId));
    }

    public function destroy(string $id): void
    {
        $messageId = (int) $id;
        $message = ContactMessage::find($messageId);
        if ($message === null) {
            Session::flash('_error', 'Tin nhắn không tồn tại.');
            $this->redirect(url('admin/contact'));
        }
        ContactMessage::delete($messageId);
        AuditLog::record('contact.delete', 'contact_message', $messageId, ['name' => $message['full_name']]);
        Session::flash('_success', 'Đã xoá tin nhắn của "' . $message['full_name'] . '".');
        $this->redirect(url('admin/contact'));
    }
}
