<?php
/**
 * Danh mục module (menu) khu vực quản trị + nhãn hiển thị.
 *
 * Quyền thực tế (xem / thêm / sửa / xoá) được lưu trong bảng role_permissions và
 * chỉnh tại màn hình "Hệ thống → Phân quyền". File này chỉ khai báo danh sách
 * module để màn hình phân quyền và sidebar dùng chung một nguồn.
 *
 * - super_admin luôn được phép tất cả (bypass trong App\Core\Permissions).
 * - Module không có trong danh sách này sẽ không hiện ở màn hình phân quyền.
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
        ],
        'jobs' => [
            'label' => 'Tin tuyển dụng',
        ],
        'applications' => [
            'label' => 'Hồ sơ ứng tuyển',
        ],
        'posts' => [
            'label' => 'Tin tức & bài viết',
        ],
        'banners' => [
            'label' => 'Banner & thông báo',
        ],
        'content' => [
            'label' => 'Nội dung website',
        ],
        'categories' => [
            'label' => 'Danh mục dùng chung',
        ],
        'contact' => [
            'label' => 'Hộp thư liên hệ',
        ],
        'settings' => [
            'label' => 'Cài đặt hệ thống',
        ],
        'users' => [
            'label' => 'Người dùng quản trị',
        ],
        'roles' => [
            'label' => 'Phân quyền',
        ],
        'audit' => [
            'label' => 'Nhật ký thao tác',
        ],
        'profile' => [
            'label' => 'Hồ sơ cá nhân',
        ],
    ],
];
