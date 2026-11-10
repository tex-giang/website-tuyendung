<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session thuần PHP — thay cho JWT cookie của bản skeleton.
 * Ưu điểm trên shared hosting: không cần thư viện, CSRF lưu cùng session,
 * PHP tự dọn session hết hạn.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $base = rtrim(env('APP_BASE', ''), '/');
        session_name('texgiang_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $base === '' ? '/' : $base . '/',
            'secure'   => env_bool('COOKIE_SECURE', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Ghi flash — đọc một lần ở request kế tiếp. */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Đọc một giá trị dùng một lần: ưu tiên flash, sau đó tới biến session
     * thường (ví dụ "_last_application" ghi ngay trước khi redirect).
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        if (array_key_exists($key, $_SESSION)) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $value;
        }
        return $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function login(array $user): void
    {
        self::regenerate();
        self::syncUser($user);
    }

    public static function syncUser(array $user): void
    {
        self::put('auth_id', (int) $user['id']);
        self::put('auth_user', [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role_name'],
        ]);
    }

    public static function logout(): void
    {
        self::destroy();
    }

    public static function user(): ?array
    {
        return self::get('auth_user');
    }

    public static function check(): bool
    {
        return self::get('auth_id') !== null;
    }

    public static function role(): ?string
    {
        return self::get('auth_user')['role'] ?? null;
    }
}
