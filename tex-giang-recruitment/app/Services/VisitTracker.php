<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Thống kê lượt truy cập theo ngày, chống tăng khi F5:
 * mỗi "thiết bị/trình duyệt" (visitor_id lưu cookie 1 năm) chỉ được tính 1 lần/ngày.
 * Nhờ UNIQUE(stat_date, visitor_id) nên refresh liên tục không tăng lượt.
 */
final class VisitTracker
{
    private const COOKIE = 'tg_visitor';

    public static function track(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            return;
        }

        $path = current_path();
        if (!self::shouldTrack($path)) {
            return;
        }

        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (self::isBot($userAgent)) {
            return;
        }

        try {
            $pdo = Database::connection();
            $statDate = date('Y-m-d');
            $visitorId = self::visitorId();
            $device = self::device($userAgent);

            // Dedupe: mỗi visitor_id/ngày chỉ ghi 1 lần.
            $insert = $pdo->prepare(
                'INSERT IGNORE INTO visit_devices (stat_date, visitor_id, device_type)
                 VALUES (:stat_date, :visitor_id, :device_type)'
            );
            $insert->execute([
                'stat_date'   => $statDate,
                'visitor_id'  => $visitorId,
                'device_type' => $device,
            ]);
            if ($insert->rowCount() < 1) {
                return; // Thiết bị này đã được đếm hôm nay (F5 không tăng)
            }

            $column = in_array($device, ['mobile', 'desktop', 'tablet'], true) ? $device : 'desktop';
            $sql = 'INSERT INTO visit_stats (stat_date, total, mobile, desktop, tablet)
                    VALUES (:stat_date, 1,
                        :mobile, :desktop, :tablet)
                    ON DUPLICATE KEY UPDATE
                        total = total + 1,
                        ' . $column . ' = ' . $column . ' + 1';
            $pdo->prepare($sql)->execute([
                'stat_date' => $statDate,
                'mobile'    => $column === 'mobile' ? 1 : 0,
                'desktop'   => $column === 'desktop' ? 1 : 0,
                'tablet'    => $column === 'tablet' ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            error_log('[TEX-GIANG] Lỗi thống kê truy cập: ' . $e->getMessage());
        }
    }

    private static function visitorId(): string
    {
        $cookie = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (preg_match('/^[a-f0-9]{32}$/', $cookie) === 1) {
            return $cookie;
        }

        $id = bin2hex(random_bytes(16));
        $_COOKIE[self::COOKIE] = $id;
        setcookie(self::COOKIE, $id, [
            'expires'  => time() + 365 * 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        return $id;
    }

    private static function device(string $userAgent): string
    {
        if ($userAgent === '') {
            return 'desktop';
        }
        if (preg_match('/ipad|tablet|playbook|silk/i', $userAgent) === 1) {
            return 'tablet';
        }
        if (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone|blackberry|opera mini/i', $userAgent) === 1) {
            return 'mobile';
        }
        return 'desktop';
    }

    private static function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return false;
        }
        return preg_match(
            '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|curl|wget|python-requests|powershell|httpclient|go-http-client|postmanruntime|insomnia|node-fetch|axios|okhttp|java\/|libwww/i',
            $userAgent
        ) === 1;
    }

    private static function shouldTrack(string $path): bool
    {
        if (str_starts_with($path, '/admin')) {
            return false;
        }
        return preg_match('#^/(assets|storage|favicon\.ico|robots\.txt|sitemap\.xml)#', $path) !== 1;
    }
}
