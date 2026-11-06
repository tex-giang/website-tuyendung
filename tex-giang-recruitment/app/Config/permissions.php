<?php
/**
 * Ma trận phân quyền khu vực quản trị.
 *
 * Quy tắc: mỗi module admin khai báo danh sách vai trò được phép truy cập.
 * - super_admin luôn được phép tất cả module (bypass trong Permissions).
 * - Muốn đổi quyền: sửa danh sách roles của module tương ứng, không cần sửa route.
 * - Module không khai báo => chỉ super_admin vào được.
 */
return [
    'role_labels' => [
        'super_admin' => 'Quản trị hệ thống',
        'hr_admin'    => 'Quản trị nhân sự',
        'recruiter'   => 'Nhân viên tuyển dụng',
    ],

    'modules' => [
        'dashboard' => [
            'label' => 'Bảng điều khiển',
            'roles' => ['super_admin', 'hr_admin', 'recruiter'],
        ],
        'jobs' => [
            'label' => 'Tin tuyển dụng',
            'roles' => ['super_admin', 'hr_admin', 'recruiter'],
        ],
        'applications' => [
            'label' => 'Hồ sơ ứng tuyển',
            'roles' => ['super_admin', 'hr_admin', 'recruiter'],
        ],
        'posts' => [
            'label' => 'Tin tức & bài viết',
            'roles' => ['super_admin', 'hr_admin'],
        ],
        'banners' => [
            'label' => 'Banner & thông báo',
            'roles' => ['super_admin', 'hr_admin'],
        ],
        'content' => [
            'label' => 'Nội dung website',
            'roles' => ['super_admin', 'hr_admin'],
        ],
        'categories' => [
            'label' => 'Danh mục dùng chung',
            'roles' => ['super_admin', 'hr_admin'],
        ],
        'contact' => [
            'label' => 'Hộp thư liên hệ',
            'roles' => ['super_admin', 'hr_admin'],
        ],
        'settings' => [
            'label' => 'Cài đặt hệ thống',
            'roles' => ['super_admin'],
        ],
        'users' => [
            'label' => 'Người dùng quản trị',
            'roles' => ['super_admin'],
        ],
        'audit' => [
            'label' => 'Nhật ký thao tác',
            'roles' => ['super_admin', 'hr_admin'],
        ],
        'profile' => [
            'label' => 'Hồ sơ cá nhân',
            'roles' => ['super_admin', 'hr_admin', 'recruiter'],
        ],
    ],
];
