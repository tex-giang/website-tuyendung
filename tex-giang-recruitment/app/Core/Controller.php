<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base controller: render view trong layout, JSON, redirect.
 * View nhận $content để layout chèn nội dung; dữ liệu trang nằm trong $page.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/public'): void
    {
        $data['page'] = array_merge([
            'title'       => settings('seo_title', settings('site_name', 'Tuyển dụng TEX-GIANG')),
            'description' => settings('seo_description', ''),
            'body_class'  => 'tg-public',
            'nav'         => '',
            'canonical'   => null,
            'og_image'    => null,
        ], $data['page'] ?? []);

        extract($data, EXTR_SKIP);

        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('Không tìm thấy view: ' . $view);
        }

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        $layoutFile = BASE_PATH . '/app/Views/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Không tìm thấy layout: ' . $layout);
        }
        require $layoutFile;
    }

    /** Render nội dung không qua layout (dùng cho lỗi, tiện ích). */
    protected function viewRaw(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/app/Views/' . $view . '.php';
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $to): never
    {
        Response::redirect($to);
    }

    /** Request đến từ fetch/AJAX hay không (dùng để trả JSON thay vì redirect). */
    protected function isAjaxRequest(): bool
    {
        return Response::isAjaxRequest();
    }

    /**
     * Kết quả thao tác: AJAX trả JSON {ok, message, errors, redirect};
     * không phải AJAX thì controller tự flash + redirect như cũ.
     */
    protected function jsonResult(bool $ok, string $message, ?string $redirect = null, array $errors = [], ?int $status = null): never
    {
        $this->json([
            'ok'       => $ok,
            'message'  => $message,
            'errors'   => $errors,
            'redirect' => $redirect,
        ], $status ?? ($ok ? 200 : 422));
    }

    protected function formSuccess(string $message, string $redirect): never
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResult(true, $message, $redirect);
        }
        Session::flash('_success', $message);
        $this->redirect($redirect);
    }

    protected function formError(string $message, string $redirect, array $errors = [], ?array $old = null, int $status = 422): never
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResult(false, $message, null, $errors, $status);
        }
        if ($old !== null) {
            Session::flash('_old', $old);
        }
        if ($errors !== []) {
            Session::flash('_errors', $errors);
        }
        Session::flash('_error', $message);
        $this->redirect($redirect);
    }

    protected function back(): never
    {
        Response::back();
    }

    /** Nếu trang thuộc menu đang bị ẩn (is_active=0) thì trả 404 — không hiển thị trang đó. */
    protected function guardMenu(string $url): void
    {
        if (!menu_link_visible($url)) {
            $this->notFound('Trang này đang được ẩn khỏi menu website.');
        }
    }

    protected function notFound(string $message = 'Không tìm thấy trang.'): never
    {
        http_response_code(404);
        $this->view('public/404', [
            'message' => $message,
            'page'    => ['title' => 'Không tìm thấy trang | ' . settings('site_name'), 'nav' => ''],
        ]);
        exit;
    }
}
