<?php
/**
 * Hàm tiện ích dùng toàn cục (view, controller, middleware).
 * Giữ ít và rõ: đọc .env, escape, URL, cấu hình, định dạng nội dung.
 */
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Core\Session;

/** Đọc biến môi trường đã nạp từ .env */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = env($key, null);
    if ($value === null) {
        return $default;
    }
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function env_int(string $key, int $default = 0): int
{
    $value = env($key, null);
    return $value === null ? $default : (int) $value;
}

/** Escape HTML — dùng ở mọi chỗ in dữ liệu người dùng nhập. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL nội bộ, tự thêm tiền tố thư mục khi deploy trong thư mục con (APP_BASE). */
function url(string $path = ''): string
{
    $base = rtrim((string) env('APP_BASE', ''), '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '/' : $path);
}

function asset(string $path = ''): string
{
    $path = ltrim($path, '/');
    // Chấp nhận cả đường dẫn lưu trong DB có tiền tố "assets/" (ví dụ ảnh banner).
    if (str_starts_with($path, 'assets/')) {
        $path = substr($path, strlen('assets/'));
    }
    return url('assets/' . $path);
}

function current_path(): string
{
    $base = rtrim((string) env('APP_BASE', ''), '/');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    return '/' . trim($path, '/');
}

/** Trả 'is-active' nếu đường dẫn hiện tại khớp — dùng cho nav. */
function nav_active(string $key, string $current): string
{
    return $key === $current ? 'is-active' : '';
}

/** Toàn bộ settings (cache theo request) */
function settings_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $cache = \App\Models\Setting::all();
    } catch (\Throwable $e) {
        error_log('[TEX-GIANG] Không đọc được settings: ' . $e->getMessage());
        $cache = [];
    }
    return $cache;
}

function settings(string $key, string $default = ''): string
{
    $all = settings_all();
    $value = $all[$key] ?? null;
    return ($value === null || $value === '') ? $default : (string) $value;
}

function setting_int(string $key, int $default = 0): int
{
    $value = settings($key, '');
    return $value === '' ? $default : (int) $value;
}

/** Dữ liệu form cũ sau khi validate lỗi (flash) */
function old(string $key, string $default = ''): string
{
    $old = Session::pull('_old', []);
    return (string) ($old[$key] ?? $default);
}

/** Lỗi validate của field (flash) */
function errors(string $field): ?string
{
    static $bag = null;
    if ($bag === null) {
        $bag = Session::pull('_errors', []);
    }
    return $bag[$field] ?? null;
}

function has_errors(): bool
{
    static $has = null;
    if ($has === null) {
        $has = Session::get('_errors', []) !== [];
    }
    return $has;
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return Csrf::field();
}

function redirect(string $to): never
{
    Response::redirect($to);
}

/** Tạo slug từ tiếng Việt: "Kỹ sư IE" -> "ky-su-ie" */
function slugify(string $text): string
{
    $text = trim(mb_strtolower($text, 'UTF-8'));
    $map = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd', 'ß' => 'ss',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/** Định dạng ngày Việt Nam an toàn với null */
function vn_date(?string $date, string $format = 'd/m/Y'): string
{
    if ($date === null || $date === '') {
        return '';
    }
    $ts = strtotime($date);
    return $ts === false ? '' : date($format, $ts);
}

/** Tách văn bản nhiều dòng thành mảng dòng không rỗng */
function text_lines(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static fn(string $l): bool => $l !== ''));
}

/** Dòng "Tiêu đề | Mô tả" -> mảng ['title' => ..., 'desc' => ...] cho card quyền lợi */
function benefit_items(?string $text): array
{
    $items = [];
    foreach (text_lines($text) as $line) {
        if (!str_contains($line, '|')) {
            $items[] = ['title' => $line, 'desc' => ''];
            continue;
        }
        [$title, $desc] = explode('|', $line, 2);
        $items[] = ['title' => trim($title), 'desc' => trim($desc)];
    }
    return $items;
}

/** Cắt văn bản thuần theo độ dài ký tự, an toàn UTF-8 */
function excerpt(string $text, int $limit = 180): string
{
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($plain, 'UTF-8') <= $limit) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $limit, 'UTF-8'), ' .,;:') . '…';
}

/** "8.000.000 - 16.000.000 VNĐ" -> "8 – 16 triệu/tháng" (giữ nguyên nếu không có số) */
function salary_short(?string $salary, string $unit = 'triệu/tháng'): string
{
    $salary = (string) $salary;
    if (!preg_match_all('/\d{1,3}(?:\.\d{3})+/', $salary, $m)) {
        return $salary;
    }
    $toTrieu = static function (string $raw): string {
        $number = (float) str_replace('.', '', $raw) / 1000000;
        return rtrim(rtrim(number_format($number, 1, ',', '.'), '0'), ',');
    };
    if (count($m[0]) >= 2) {
        return $toTrieu($m[0][0]) . ' – ' . $toTrieu($m[0][1]) . ' ' . $unit;
    }
    return $toTrieu($m[0][0]) . ' ' . $unit;
}

function job_status_label(?string $status): string
{
    return match ($status) {
        'published' => 'Đang tuyển',
        'paused'    => 'Tạm dừng',
        'closed'    => 'Đã đóng',
        'draft'     => 'Bản nháp',
        default     => 'Không rõ',
    };
}

/** Chip trạng thái hiển thị trên card trang chủ / danh sách */
function job_chip(array $job): array
{
    if (($job['status'] ?? '') === 'closed') {
        return ['label' => 'Đã đóng tuyển', 'muted' => true];
    }
    if ((int) ($job['is_urgent'] ?? 0) === 1) {
        return ['label' => 'Tuyển gấp', 'muted' => false];
    }
    if (($job['experience'] ?? '') !== '' && str_contains(mb_strtolower((string) $job['experience'], 'UTF-8'), 'không')) {
        return ['label' => 'Có đào tạo', 'muted' => false];
    }
    return ['label' => 'Đang tuyển', 'muted' => false];
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Tạo URL giữ nguyên bộ lọc hiện tại, thay một vài tham số. */
function url_with(array $query, array $overrides = []): string
{
    $merged = array_merge($query, $overrides);
    $merged = array_filter($merged, static fn($v): bool => $v !== null && $v !== '');
    $qs = http_build_query($merged);
    return current_path() . ($qs === '' ? '' : '?' . $qs);
}

/** Giá trị an toàn cho thuộc tính input */
function input_value(string $value): string
{
    return e($value);
}
