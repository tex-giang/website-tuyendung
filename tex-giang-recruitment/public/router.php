<?php
/**
 * Router cho PHP built-in server — CHỈ dùng khi phát triển local:
 *
 *   php -S 127.0.0.1:8000 -t public public/router.php
 *
 * Vì sao cần file này: server tích hợp sẵn của PHP tự trả 404 cho mọi đường dẫn
 * CÓ phần mở rộng mà không trùng file thật. Nghĩa là /robots.txt và /sitemap.xml
 * (hai route sinh động trong SeoController) không bao giờ tới được index.php,
 * dù trên Apache/cPanel file .htaccess đã rewrite đúng.
 *
 * Trên hosting thật KHÔNG dùng file này — DocumentRoot trỏ vào public/ và
 * .htaccess chuyển mọi request không phải file/dir thật về index.php.
 */
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$target = __DIR__ . $path;

// File tĩnh có thật (css, js, ảnh, favicon.ico...): để server tự phục vụ.
if ($path !== '/' && is_file($target)) {
    return false;
}

require __DIR__ . '/index.php';
