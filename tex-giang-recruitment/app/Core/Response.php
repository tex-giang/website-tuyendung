<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function isAjaxRequest(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    public static function wantsAdminFragment(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
            && ($_SERVER['HTTP_X_ADMIN_FRAGMENT'] ?? '') === '1'
            && self::isAjaxRequest();
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    /** Quay lại trang trước, fallback về trang chủ. */
    public static function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        // Chỉ nhận referer cùng host để tránh open redirect.
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($referer !== '' && $host !== '' && str_contains($referer, $host)) {
            self::redirect($referer);
        }
        self::redirect($fallback);
    }

    public static function status(int $code): void
    {
        http_response_code($code);
    }
}
