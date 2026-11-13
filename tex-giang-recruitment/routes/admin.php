<?php
declare(strict_types=1);

use App\Controllers\Admin\ApplicationController;
use App\Controllers\Admin\AuditController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BannerController;
use App\Controllers\Admin\ContactController;
use App\Controllers\Admin\ContentController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\VisitStatController;
use App\Controllers\Admin\JobController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\PageContentController;
use App\Controllers\Admin\PostController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\TaxonomyController;
use App\Controllers\Admin\UserController;
use App\Core\Permissions;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

/**
 * Mọi route admin đi qua: đăng nhập (Auth) → phân quyền (Permissions) →
 * CSRF nếu là thao tác ghi.
 *
 * $action: view | create | update | delete — nhóm quyền theo menu.
 * Ma trận quyền lưu trong bảng role_permissions, chỉnh tại /admin/roles.
 */
$guard = static function (string $module, string $action = 'view'): array {
    $middleware = [
        static fn(): mixed => AuthMiddleware::handle(),
        static fn(): mixed => Permissions::require($module, $action),
    ];
    if ($action !== 'view') {
        $middleware[] = static fn(): mixed => CsrfMiddleware::handle(admin: true);
    }
    return $middleware;
};

// Đăng nhập / đăng xuất
$router->get('/admin/login', [AuthController::class, 'showLogin'], [
    static fn(): mixed => GuestMiddleware::handle(),
]);
$router->post('/admin/login', [AuthController::class, 'login'], [
    static fn(): mixed => GuestMiddleware::handle(),
    static fn(): mixed => CsrfMiddleware::handle(admin: true),
]);
$router->post('/admin/logout', [AuthController::class, 'logout'], [
    static fn(): mixed => AuthMiddleware::handle(),
    static fn(): mixed => CsrfMiddleware::handle(admin: true),
]);

$router->get('/admin', static fn(): never => redirect(url('admin/dashboard')), $guard('dashboard'));

// Bảng điều khiển
$router->get('/admin/dashboard', [DashboardController::class, 'index'], $guard('dashboard'));

// Thống kê truy cập
$router->get('/admin/visits', [VisitStatController::class, 'index'], $guard('dashboard'));

// Tin tuyển dụng
$router->get('/admin/jobs', [JobController::class, 'index'], $guard('jobs'));
$router->get('/admin/jobs/create', [JobController::class, 'create'], $guard('jobs', 'create'));
$router->post('/admin/jobs/create', [JobController::class, 'store'], $guard('jobs', 'create'));
$router->get('/admin/jobs/template', [JobController::class, 'template'], $guard('jobs'));
$router->post('/admin/jobs/import', [JobController::class, 'import'], $guard('jobs', 'create'));
$router->get('/admin/jobs/{id}/edit', [JobController::class, 'edit'], $guard('jobs', 'update'));
$router->post('/admin/jobs/{id}/edit', [JobController::class, 'update'], $guard('jobs', 'update'));
$router->post('/admin/jobs/{id}/status', [JobController::class, 'changeStatus'], $guard('jobs', 'update'));
$router->post('/admin/jobs/{id}/duplicate', [JobController::class, 'duplicate'], $guard('jobs', 'create'));
$router->post('/admin/jobs/{id}/delete', [JobController::class, 'destroy'], $guard('jobs', 'delete'));

// Hồ sơ ứng tuyển
$router->get('/admin/applications', [ApplicationController::class, 'index'], $guard('applications'));
$router->get('/admin/applications/{id}', [ApplicationController::class, 'show'], $guard('applications'));
$router->post('/admin/applications/{id}/status', [ApplicationController::class, 'updateStatus'], $guard('applications', 'update'));
$router->get('/admin/applications/{id}/cv', [ApplicationController::class, 'downloadCv'], $guard('applications'));

// Tin tức & bài viết
$router->get('/admin/posts', [PostController::class, 'index'], $guard('posts'));
$router->get('/admin/posts/create', [PostController::class, 'create'], $guard('posts', 'create'));
$router->post('/admin/posts/create', [PostController::class, 'store'], $guard('posts', 'create'));

// Upload ảnh cho trình soạn thảo — token CSRF gửi kèm body/query.
$router->post('/admin/posts/upload-image', [PostController::class, 'uploadImage'], [
    static fn(): mixed => AuthMiddleware::handle(),
    static fn(): mixed => Permissions::require('posts', 'create'),
]);

$router->get('/admin/posts/{id}/edit', [PostController::class, 'edit'], $guard('posts', 'update'));
$router->post('/admin/posts/{id}/edit', [PostController::class, 'update'], $guard('posts', 'update'));
$router->post('/admin/posts/{id}/status', [PostController::class, 'changeStatus'], $guard('posts', 'update'));
$router->post('/admin/posts/{id}/delete', [PostController::class, 'destroy'], $guard('posts', 'delete'));

// Banner & thông báo
$router->get('/admin/banners', [BannerController::class, 'index'], $guard('banners'));
$router->get('/admin/banners/create', [BannerController::class, 'create'], $guard('banners', 'create'));
$router->post('/admin/banners/create', [BannerController::class, 'store'], $guard('banners', 'create'));
$router->get('/admin/banners/{id}/edit', [BannerController::class, 'edit'], $guard('banners', 'update'));
$router->post('/admin/banners/{id}/edit', [BannerController::class, 'update'], $guard('banners', 'update'));
$router->post('/admin/banners/{id}/toggle', [BannerController::class, 'toggle'], $guard('banners', 'update'));
$router->post('/admin/banners/{id}/delete', [BannerController::class, 'destroy'], $guard('banners', 'delete'));

// Danh mục dùng chung: khu sản xuất / khối phòng ban / chuyên mục tin tức
$router->get('/admin/categories', [TaxonomyController::class, 'index'], $guard('categories'));
$router->get('/admin/categories/{type}/create', [TaxonomyController::class, 'create'], $guard('categories', 'create'));
$router->post('/admin/categories/{type}/create', [TaxonomyController::class, 'store'], $guard('categories', 'create'));
$router->get('/admin/categories/{type}/{id}/edit', [TaxonomyController::class, 'edit'], $guard('categories', 'update'));
$router->post('/admin/categories/{type}/{id}/edit', [TaxonomyController::class, 'update'], $guard('categories', 'update'));
$router->post('/admin/categories/{type}/{id}/delete', [TaxonomyController::class, 'destroy'], $guard('categories', 'delete'));
$router->post('/admin/categories/{type}/{id}/toggle', [TaxonomyController::class, 'toggle'], $guard('categories', 'update'));

// Nội dung website & cài đặt hệ thống
$router->get('/admin/content', [ContentController::class, 'index'], $guard('content'));
$router->post('/admin/content', [ContentController::class, 'update'], $guard('content', 'update'));
// Menu website (header/footer) — bật/tắt hiển thị ngoài public.
$router->get('/admin/menus', [MenuController::class, 'index'], $guard('content'));
$router->get('/admin/menus/create', [MenuController::class, 'create'], $guard('content', 'create'));
$router->post('/admin/menus/create', [MenuController::class, 'store'], $guard('content', 'create'));
$router->get('/admin/menus/{id}/edit', [MenuController::class, 'edit'], $guard('content', 'update'));
$router->post('/admin/menus/{id}/edit', [MenuController::class, 'update'], $guard('content', 'update'));
$router->post('/admin/menus/{id}/delete', [MenuController::class, 'destroy'], $guard('content', 'delete'));
$router->post('/admin/menus/{id}/toggle', [MenuController::class, 'toggle'], $guard('content', 'update'));

// Trang Về chúng tôi / Phúc lợi — sửa toàn bộ bằng form repeater (kiểu Người liên hệ)
$router->get('/admin/pages/about', [PageContentController::class, 'about'], $guard('content'));
$router->post('/admin/pages/about', [PageContentController::class, 'aboutSave'], $guard('content', 'update'));
$router->get('/admin/pages/benefits', [PageContentController::class, 'benefits'], $guard('content'));
$router->post('/admin/pages/benefits', [PageContentController::class, 'benefitsSave'], $guard('content', 'update'));


$router->get('/admin/settings', [SettingsController::class, 'index'], $guard('settings'));
$router->post('/admin/settings', [SettingsController::class, 'update'], $guard('settings', 'update'));

// Hộp thư liên hệ
$router->get('/admin/contact', [ContactController::class, 'index'], $guard('contact'));
$router->get('/admin/contact/{id}', [ContactController::class, 'show'], $guard('contact'));
$router->post('/admin/contact/{id}/status', [ContactController::class, 'updateStatus'], $guard('contact', 'update'));
$router->post('/admin/contact/{id}/delete', [ContactController::class, 'destroy'], $guard('contact', 'delete'));

// Phân quyền theo vai trò (ma trận menu × thao tác + phạm vi nhà máy)
$router->get('/admin/roles', [RoleController::class, 'index'], $guard('roles'));
$router->get('/admin/roles/create', [RoleController::class, 'create'], $guard('roles', 'create'));
$router->post('/admin/roles/create', [RoleController::class, 'store'], $guard('roles', 'create'));
$router->get('/admin/roles/{id}/edit', [RoleController::class, 'edit'], $guard('roles', 'update'));
$router->post('/admin/roles/{id}/edit', [RoleController::class, 'update'], $guard('roles', 'update'));
$router->post('/admin/roles/{id}/delete', [RoleController::class, 'destroy'], $guard('roles', 'delete'));

// Người dùng quản trị & hồ sơ cá nhân
$router->get('/admin/users', [UserController::class, 'index'], $guard('users'));
$router->get('/admin/users/create', [UserController::class, 'create'], $guard('users', 'create'));
$router->post('/admin/users/create', [UserController::class, 'store'], $guard('users', 'create'));
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit'], $guard('users', 'update'));
$router->post('/admin/users/{id}/edit', [UserController::class, 'update'], $guard('users', 'update'));
$router->post('/admin/users/{id}/password', [UserController::class, 'resetPassword'], $guard('users', 'update'));
$router->post('/admin/users/{id}/status', [UserController::class, 'changeStatus'], $guard('users', 'update'));
$router->get('/admin/profile', [ProfileController::class, 'index'], $guard('profile'));
$router->post('/admin/profile/password', [ProfileController::class, 'updatePassword'], $guard('profile', 'update'));

// Nhật ký thao tác quản trị
$router->get('/admin/audit', [AuditController::class, 'index'], $guard('audit'));
