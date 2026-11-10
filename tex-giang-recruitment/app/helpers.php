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
    $url = url('assets/' . $path);

    // Cache-busting: thêm ?v=<mtime> để trình duyệt tự tải lại khi file đổi.
    $file = BASE_PATH . '/public/assets/' . $path;
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
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

/** Link Zalo: nhận link Zalo OA đầy đủ (https://zalo.me/...) hoặc số điện thoại Zalo. */
function zalo_url(): string
{
    $value = trim(settings('zalo'));
    if ($value === '') {
        return '';
    }
    return preg_match('#^https?://#i', $value) === 1 ? $value : 'https://zalo.me/' . $value;
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

/**
 * Làm sạch HTML từ trình soạn thảo (allowlist thẻ/thuộc tính, bỏ script/iframe/on*).
 * Chỉ dùng cho nội dung do quản trị viên nhập.
 */
function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $allowed = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'strike' => [], 'ul' => [], 'ol' => [], 'li' => [], 'h2' => [], 'h3' => [], 'h4' => [],
        'blockquote' => [], 'figure' => [], 'figcaption' => [], 'hr' => [], 'pre' => [], 'code' => [],
        'sup' => [], 'sub' => [], 'span' => ['class' => true], 'div' => ['class' => true],
        'img' => ['src' => true, 'alt' => true, 'title' => true, 'width' => true, 'height' => true, 'class' => true],
        'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true, 'class' => true],
        'table' => ['class' => true], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan' => true, 'rowspan' => true], 'td' => ['colspan' => true, 'rowspan' => true],
    ];
    $drop = [
        'script' => true, 'style' => true, 'iframe' => true, 'object' => true, 'embed' => true,
        'form' => true, 'input' => true, 'button' => true, 'textarea' => true, 'select' => true,
        'option' => true, 'link' => true, 'meta' => true, 'base' => true, 'noscript' => true,
        'template' => true, 'svg' => true, 'math' => true, 'video' => true, 'audio' => true,
        'canvas' => true, 'map' => true, 'applet' => true,
    ];

    $doc = new \DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?><div id="tg-sanitize-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $doc->getElementsByTagName('div')->item(0);
    if ($root === null) {
        return '';
    }

    $walk = static function (\DOMNode $parent) use (&$walk, $allowed, $drop): void {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                $parent->removeChild($child);
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (isset($drop[$tag])) {
                $parent->removeChild($child);
                continue;
            }
            if (!isset($allowed[$tag])) {
                // Thẻ lạ: bỏ thẻ, giữ lại nội dung con.
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            $attrNames = [];
            foreach ($child->attributes as $attribute) {
                $attrNames[] = $attribute->nodeName;
            }
            foreach ($attrNames as $name) {
                $lower = strtolower($name);
                $value = (string) $child->getAttribute($name);
                if (str_starts_with($lower, 'on') || !isset($allowed[$tag][$lower])) {
                    $child->removeAttribute($name);
                    continue;
                }
                if ($lower === 'href' || $lower === 'src') {
                    $value = trim($value);
                    if (preg_match('/^\s*(javascript|vbscript|data):/i', $value)) {
                        $child->removeAttribute($name);
                        continue;
                    }
                    if (!preg_match('#^(https?:|mailto:|tel:|/|\./|\.\./|\#)#i', $value)) {
                        $child->removeAttribute($name);
                        continue;
                    }
                    $child->setAttribute($name, $value);
                }
            }
            if ($tag === 'a' && strtolower($child->getAttribute('target')) === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            $walk($child);
        }
    };
    $walk($root);

    $result = '';
    foreach ($root->childNodes as $child) {
        $result .= $doc->saveHTML($child);
    }
    return trim($result);
}

/**
 * Trích các đường dẫn ảnh upload (assets/uploads/...) có trong HTML.
 *
 * @return array<int,string>
 */
function content_image_paths(string $html): array
{
    if ($html === '') {
        return [];
    }
    preg_match_all('/(?:src|href)\s*=\s*["\']([^"\']*assets\/uploads\/[^"\']+)["\']/i', $html, $matches);
    $paths = [];
    foreach ($matches[1] as $url) {
        $position = strpos($url, 'assets/uploads/');
        if ($position === false) {
            continue;
        }
        $paths[] = substr($url, $position);
    }
    return array_values(array_unique($paths));
}


/** Lấy URL nhúng bản đồ: chấp nhận link embed trực tiếp hoặc cả mã <iframe>. */
function map_embed_src(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('/src=["\']([^"\']+)["\']/i', $value, $matches) === 1) {
        $value = trim($matches[1]);
    }
    return preg_match('#^https?://#i', $value) === 1 ? $value : '';
}


/**
 * Trang thuộc menu đang bật? Nếu menu tương ứng bị ẩn (is_active=0) thì trả false
 * để các link/blocks ngoài website không hiển thị trang đó nữa.
 */
function menu_link_visible(string $url): bool
{
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (\App\Models\Menu::all() as $menu) {
            $map[rtrim((string) $menu['url'], '/')] = (int) $menu['is_active'];
        }
    }
    $key = rtrim(trim($url), '/');
    if (!array_key_exists($key, $map)) {
        return true; // không nằm trong menu => không bị quản lý
    }
    return $map[$key] === 1;
}

/** Icon Bootstrap cho admin — nhận màu theo currentColor. */
function admin_icon_svg(string $name, int $size = 16): string
{
    $map = [
        'plus'       => 'bi-plus-lg',
        'pencil'     => 'bi-pencil',
        'trash'      => 'bi-trash',
        'eye'        => 'bi-eye',
        'eye-off'    => 'bi-eye-slash',
        'copy'       => 'bi-copy',
        'arrow-up'   => 'bi-arrow-up',
        'arrow-down' => 'bi-arrow-down',
        'arrow-left' => 'bi-arrow-left',
        'arrow-right'=> 'bi-arrow-right',
        'check'      => 'bi-check-lg',
        'x'          => 'bi-x-lg',
    ];
    $icon = $map[$name] ?? ('bi-' . ltrim($name, 'bi-'));
    return '<i class="bi ' . e($icon) . '" style="font-size:' . (int) $size . 'px;line-height:1;" aria-hidden="true"></i>';
}

/** Render thẻ icon Bootstrap nhanh: bi('plus-lg', 'me-1') */
function bi(string $name, string $extraClass = ''): string
{
    $icon = str_starts_with($name, 'bi-') ? $name : ('bi-' . $name);
    $cls = 'bi ' . $icon . ($extraClass !== '' ? ' ' . $extraClass : '');
    return '<i class="' . e($cls) . '" aria-hidden="true"></i>';
}
