<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Permissions;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

final class ApplicationApiAuth
{
    /** @return array{id: int, role: string}|null null nếu dùng phiên quản trị. */
    public static function authorize(): ?array
    {
        // Apache có thể thêm tiền tố REDIRECT_ khi rewrite qua index.php và trình xử lý CGI.
        $authorization = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if ($authorization === '') {
            Session::start();
            AuthMiddleware::handle();
            Permissions::require('applications');
            return null;
        }

        $tokenHash = (string) env('APPLICATIONS_API_TOKEN_SHA256', '');
        $userId = filter_var(env('APPLICATIONS_API_USER_ID', ''), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($userId === false
            || !preg_match('/\A[a-f0-9]{64}\z/', $tokenHash)
            || !preg_match('/\ABearer +([a-f0-9]{64})\z/i', trim($authorization), $matches)
            || !hash_equals($tokenHash, hash('sha256', $matches[1]))) {
            self::invalidToken();
        }

        $user = User::find($userId);
        if ($user === null || (int) $user['is_active'] !== 1) {
            self::invalidToken();
        }
        if (!Permissions::roleCan($user['role_name'], 'applications')) {
            Response::json([
                'ok' => false,
                'code' => 'forbidden',
                'message' => 'Tài khoản tích hợp không có quyền xem hồ sơ ứng tuyển.',
            ], 403);
        }

        return ['id' => $userId, 'role' => $user['role_name']];
    }

    private static function invalidToken(): never
    {
        header('WWW-Authenticate: Bearer realm="applications", error="invalid_token"');
        Response::json([
            'ok' => false,
            'code' => 'invalid_token',
            'message' => 'Token tích hợp không hợp lệ hoặc đã bị thu hồi.',
        ], 401);
    }
}
