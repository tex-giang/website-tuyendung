<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Menu;

/**
 * Quản trị menu website (header/footer). Menu bật (is_active=1) mới hiển thị ngoài public.
 */
final class MenuController extends Controller
{
    public function index(): void
    {
        $this->view('admin/menus/index', [
            'menus' => Menu::all(),
            'page'  => ['title' => 'Menu website | ' . settings('site_name'), 'nav' => 'menus'],
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $menu = Menu::find((int) $id);
        if ($menu === null) {
            Session::flash('_error', 'Menu không tồn tại.');
            $this->redirect(url('admin/menus'));
        }
        $this->form($menu);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->fail($errors, url('admin/menus/create'));
        }

        if ((int) ($data['sort_order'] ?? 0) === 0) {
            $data['sort_order'] = Menu::maxSort() + 1;
        }

        $id = Menu::create($data);
        AuditLog::record('menu.create', 'menu', $id, ['label' => $data['label']]);
        $this->success('Đã thêm menu "' . $data['label'] . '".');
    }

    public function update(string $id): void
    {
        $menuId = (int) $id;
        if (Menu::find($menuId) === null) {
            $this->fail(['menu' => 'Menu không tồn tại.'], url('admin/menus'));
        }

        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->fail($errors, url('admin/menus/' . $menuId . '/edit'));
        }

        Menu::update($menuId, $data);
        AuditLog::record('menu.update', 'menu', $menuId, ['label' => $data['label']]);
        $this->success('Đã cập nhật menu.');
    }

    public function destroy(string $id): void
    {
        $menuId = (int) $id;
        $menu = Menu::find($menuId);
        if ($menu === null) {
            $this->fail(['menu' => 'Menu không tồn tại.'], url('admin/menus'));
        }
        Menu::delete($menuId);
        AuditLog::record('menu.delete', 'menu', $menuId, ['label' => $menu['label']]);
        $this->success('Đã xoá menu "' . $menu['label'] . '".');
    }

    public function toggle(string $id): void
    {
        $menuId = (int) $id;
        if (Menu::find($menuId) === null) {
            $this->fail(['menu' => 'Menu không tồn tại.'], url('admin/menus'));
        }
        Menu::toggleActive($menuId);
        $this->success('Đã đổi trạng thái hiển thị.');
    }

    /** @param array<string, mixed>|null $menu */
    private function form(?array $menu): void
    {
        $old = Session::pull('_old', []);
        $errors = Session::pull('_errors', []);

        $values = $menu ?? ['id' => null, 'label' => '', 'url' => '', 'sort_order' => 0, 'is_active' => 1];
        if ($old !== []) {
            $values = array_merge($values, $old);
        }

        $this->view('admin/menus/form', [
            'menu'      => $menu,
            'values'    => $values,
            'errors'    => $errors,
            'page'      => ['title' => ($menu === null ? 'Thêm menu' : 'Sửa menu') . ' | ' . settings('site_name'), 'nav' => 'menus'],
        ], 'layouts/admin');
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validate(array $post): array
    {
        $data = [
            'label'      => trim((string) ($post['label'] ?? '')),
            'url'        => trim((string) ($post['url'] ?? '')),
            'sort_order' => (int) ($post['sort_order'] ?? 0),
            'is_active'  => !empty($post['is_active']) ? 1 : 0,
        ];
        $errors = [];

        if ($data['label'] === '' || mb_strlen($data['label']) > 120) {
            $errors['label'] = 'Nhãn menu 1–120 ký tự.';
        }
        if ($data['url'] !== '' && mb_strlen($data['url']) > 255) {
            $errors['url'] = 'Đường dẫn tối đa 255 ký tự.';
        }
        if ($data['url'] !== '' && preg_match('#^(javascript|vbscript|data):#i', $data['url'])) {
            $errors['url'] = 'Đường dẫn không hợp lệ.';
        }

        return [$data, $errors];
    }

    /** @param array<string, string> $errors */
    private function fail(array $errors, string $redirect): never
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResult(false, 'Vui lòng kiểm tra lại các trường được đánh dấu.', $redirect, $errors);
        }
        Session::flash('_errors', $errors);
        Session::flash('_error', 'Vui lòng kiểm tra lại các trường được đánh dấu.');
        $this->redirect($redirect);
    }

    private function success(string $message): never
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, $message, url('admin/menus'));
        }
        Session::flash('_success', $message);
        $this->redirect(url('admin/menus'));
    }
}
