<?php
declare(strict_types=1);

use App\Controllers\Admin\ApplicationController;
use App\Controllers\Admin\AuditController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BannerController;
use App\Controllers\Admin\ContactController;
use App\Controllers\Admin\ContentController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\JobController;
use App\Controllers\Admin\PostController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\TaxonomyController;
use App\Controllers\Admin\UserController;
use App\Core\Permissions;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

/**
 * Mọi route admin đi qua: đăng nhập (Auth) → phân quyền module (Permissions) →
 * CSRF nếu là thao tác ghi. Ma trận quyền ở app/Config/permissions.php.
 */
$guard = static function (string $module, bool $write = false): array {
    $middleware = [
        static fn(): mixed => AuthMiddleware::handle(),
        static fn(): mixed => Permissions::require($module),
    ];
    if ($write) {
        $middleware[] = static fn(): mixed => CsrfMiddleware::handle();
    }
    return $middleware;
};

// Đăng nhập / đăng xuất
$router->get('/admin/login', [AuthController::class, 'showLogin'], [
    static fn(): mixed => GuestMiddleware::handle(),
]);
$router->post('/admin/login', [AuthController::class, 'login'], [
    static fn(): mixed => GuestMiddleware::handle(),
    static fn(): mixed => CsrfMiddleware::handle(),
]);
$router->post('/admin/logout', [AuthController::class, 'logout'], [
    static fn(): mixed => AuthMiddleware::handle(),
    static fn(): mixed => CsrfMiddleware::handle(),
]);

$router->get('/admin', static fn(): never => redirect(url('admin/dashboard')), $guard('dashboard'));

// Bảng điều khiển
$router->get('/admin/dashboard', [DashboardController::class, 'index'], $guard('dashboard'));

// Tin tuyển dụng
$router->get('/admin/jobs', [JobController::class, 'index'], $guard('jobs'));
$router->get('/admin/jobs/create', [JobController::class, 'create'], $guard('jobs'));
$router->post('/admin/jobs/create', [JobController::class, 'store'], $guard('jobs', true));
$router->get('/admin/jobs/{id}/edit', [JobController::class, 'edit'], $guard('jobs'));
$router->post('/admin/jobs/{id}/edit', [JobController::class, 'update'], $guard('jobs', true));
$router->post('/admin/jobs/{id}/status', [JobController::class, 'changeStatus'], $guard('jobs', true));
$router->post('/admin/jobs/{id}/duplicate', [JobController::class, 'duplicate'], $guard('jobs', true));
$router->post('/admin/jobs/{id}/delete', [JobController::class, 'destroy'], $guard('jobs', true));

// Hồ sơ ứng tuyển
$router->get('/admin/applications', [ApplicationController::class, 'index'], $guard('applications'));
$router->get('/admin/applications/{id}', [ApplicationController::class, 'show'], $guard('applications'));
$router->post('/admin/applications/{id}/status', [ApplicationController::class, 'updateStatus'], $guard('applications', true));
$router->get('/admin/applications/{id}/cv', [ApplicationController::class, 'downloadCv'], $guard('applications'));

// Tin tức & bài viết
$router->get('/admin/posts', [PostController::class, 'index'], $guard('posts'));
$router->get('/admin/posts/create', [PostController::class, 'create'], $guard('posts'));
$router->post('/admin/posts/create', [PostController::class, 'store'], $guard('posts', true));
$router->get('/admin/posts/{id}/edit', [PostController::class, 'edit'], $guard('posts'));
$router->post('/admin/posts/{id}/edit', [PostController::class, 'update'], $guard('posts', true));
$router->post('/admin/posts/{id}/status', [PostController::class, 'changeStatus'], $guard('posts', true));
$router->post('/admin/posts/{id}/delete', [PostController::class, 'destroy'], $guard('posts', true));

// Banner & thông báo
$router->get('/admin/banners', [BannerController::class, 'index'], $guard('banners'));
$router->get('/admin/banners/create', [BannerController::class, 'create'], $guard('banners'));
$router->post('/admin/banners/create', [BannerController::class, 'store'], $guard('banners', true));
$router->get('/admin/banners/{id}/edit', [BannerController::class, 'edit'], $guard('banners'));
$router->post('/admin/banners/{id}/edit', [BannerController::class, 'update'], $guard('banners', true));
$router->post('/admin/banners/{id}/toggle', [BannerController::class, 'toggle'], $guard('banners', true));
$router->post('/admin/banners/{id}/delete', [BannerController::class, 'destroy'], $guard('banners', true));

// Danh mục dùng chung: khu sản xuất / khối phòng ban / chuyên mục tin tức
$router->get('/admin/categories', [TaxonomyController::class, 'index'], $guard('categories'));
$router->get('/admin/categories/{type}/create', [TaxonomyController::class, 'create'], $guard('categories'));
$router->post('/admin/categories/{type}/create', [TaxonomyController::class, 'store'], $guard('categories', true));
$router->get('/admin/categories/{type}/{id}/edit', [TaxonomyController::class, 'edit'], $guard('categories'));
$router->post('/admin/categories/{type}/{id}/edit', [TaxonomyController::class, 'update'], $guard('categories', true));
$router->post('/admin/categories/{type}/{id}/delete', [TaxonomyController::class, 'destroy'], $guard('categories', true));
$router->post('/admin/categories/{type}/{id}/toggle', [TaxonomyController::class, 'toggle'], $guard('categories', true));

// Nội dung website & cài đặt hệ thống
$router->get('/admin/content', [ContentController::class, 'index'], $guard('content'));
$router->post('/admin/content', [ContentController::class, 'update'], $guard('content', true));
$router->get('/admin/settings', [SettingsController::class, 'index'], $guard('settings'));
$router->post('/admin/settings', [SettingsController::class, 'update'], $guard('settings', true));

// Hộp thư liên hệ
$router->get('/admin/contact', [ContactController::class, 'index'], $guard('contact'));
$router->get('/admin/contact/{id}', [ContactController::class, 'show'], $guard('contact'));
$router->post('/admin/contact/{id}/status', [ContactController::class, 'updateStatus'], $guard('contact', true));
$router->post('/admin/contact/{id}/delete', [ContactController::class, 'destroy'], $guard('contact', true));

// Người dùng quản trị (chỉ super_admin) & hồ sơ cá nhân
$router->get('/admin/users', [UserController::class, 'index'], $guard('users'));
$router->get('/admin/users/create', [UserController::class, 'create'], $guard('users'));
$router->post('/admin/users/create', [UserController::class, 'store'], $guard('users', true));
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit'], $guard('users'));
$router->post('/admin/users/{id}/edit', [UserController::class, 'update'], $guard('users', true));
$router->post('/admin/users/{id}/password', [UserController::class, 'resetPassword'], $guard('users', true));
$router->get('/admin/profile', [ProfileController::class, 'index'], $guard('profile'));
$router->post('/admin/profile/password', [ProfileController::class, 'updatePassword'], $guard('profile', true));

// Nhật ký thao tác quản trị
$router->get('/admin/audit', [AuditController::class, 'index'], $guard('audit'));
