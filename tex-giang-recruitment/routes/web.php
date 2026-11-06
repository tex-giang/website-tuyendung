<?php
declare(strict_types=1);

use App\Controllers\Public\HomeController;
use App\Controllers\Public\JobController;
use App\Controllers\Public\ApplicationController;
use App\Controllers\Public\PageController;
use App\Middleware\CsrfMiddleware;

$csrf = static fn(): array => [static fn(): mixed => CsrfMiddleware::handle()];

$router->get('/', [HomeController::class, 'index']);

$router->get('/jobs', [JobController::class, 'index']);
$router->get('/jobs/{slug}', [JobController::class, 'show']);

// Ứng tuyển trực tuyến: form POST thật + upload CV.
$router->get('/apply', [ApplicationController::class, 'show']);
$router->post('/apply', [ApplicationController::class, 'submit'], $csrf());
$router->get('/apply/success', [ApplicationController::class, 'success']);
$router->get('/apply/error', [ApplicationController::class, 'error']);

$router->get('/news', [PageController::class, 'news']);
$router->get('/news/{slug}', [PageController::class, 'newsDetail']);
$router->post('/news/subscribe', [PageController::class, 'subscribe'], $csrf());

$router->get('/about', [PageController::class, 'about']);
$router->get('/why-tex-giang', [PageController::class, 'why']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [PageController::class, 'contactSubmit'], $csrf());
