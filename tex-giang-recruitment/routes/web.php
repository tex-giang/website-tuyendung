<?php
declare(strict_types=1);

use App\Controllers\Public\HomeController;
use App\Controllers\Public\JobController;
use App\Controllers\Public\ApplicationController;
use App\Controllers\Public\PageController;
use App\Controllers\Public\SeoController;
use App\Middleware\CsrfMiddleware;

$csrf = static fn(): array => [static fn(): mixed => CsrfMiddleware::handle()];

$router->get('/', [HomeController::class, 'index']);

$router->get('/jobs', [JobController::class, 'index']);
$router->get('/jobs/{slug}', [JobController::class, 'show']);

// Ứng tuyển trực tuyến: form POST thật + upload CV.
$router->get('/apply', [ApplicationController::class, 'show']);
$router->post('/apply', [ApplicationController::class, 'submit'], $csrf());
// Trang nộp hồ sơ nhanh (4 bước: họ tên, SĐT, khu vực → vị trí).
$router->get('/tuyen-dung', [ApplicationController::class, 'quickShow']);
$router->post('/tuyen-dung', [ApplicationController::class, 'quickSubmit'], $csrf());

// Alias cũ
$router->get('/ung-tuyen', [ApplicationController::class, 'quickShow']);
$router->post('/ung-tuyen', [ApplicationController::class, 'quickSubmit'], $csrf());

$router->get('/apply/success', [ApplicationController::class, 'success']);
$router->get('/apply/error', [ApplicationController::class, 'error']);

$router->get('/news', [PageController::class, 'news']);
$router->get('/news/{slug}', [PageController::class, 'newsDetail']);
$router->post('/news/subscribe', [PageController::class, 'subscribe'], $csrf());

// SEO: robots.txt + sitemap.xml sinh từ database (tin/bài mới tự vào sitemap).
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);

$router->get('/about', [PageController::class, 'about']);
$router->get('/why-tex-giang', [PageController::class, 'why']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [PageController::class, 'contactSubmit'], $csrf());
