<?php
declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token theo session. Mọi form POST (public và admin) phải có token.
 * Token lưu trong session, so sánh bằng hash_equals để tránh timing attack.
 */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }
        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        $session = Session::get('_csrf_token');
        if (!is_string($session) || $session === '' || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($session, $token);
    }

    public static function checkRequest(): bool
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return is_string($token) && self::verify($token);
    }
}
