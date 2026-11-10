<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditLog
{
    public const ACTION_LABELS = [
        'auth.login' => 'Đăng nhập', 'auth.login_failed' => 'Đăng nhập thất bại', 'auth.logout' => 'Đăng xuất',
        'job.create' => 'Tạo tin', 'job.update' => 'Sửa tin', 'job.status' => 'Đổi trạng thái tin',
        'job.duplicate' => 'Nhân bản tin', 'job.delete' => 'Xóa tin',
        'application.status' => 'Đổi trạng thái hồ sơ', 'application.cv_download' => 'Tải CV',
        'application.appointment' => 'Lưu lịch phỏng vấn', 'application.note' => 'Thêm ghi chú hồ sơ',
        'post.create' => 'Tạo bài viết', 'post.update' => 'Sửa bài viết', 'post.status' => 'Đổi trạng thái bài',
        'post.delete' => 'Xóa bài viết', 'post.upload_image' => 'Tải ảnh bài viết',
        'banner.create' => 'Tạo banner', 'banner.update' => 'Sửa banner', 'banner.toggle' => 'Bật/tắt banner',
        'banner.delete' => 'Xóa banner',
        'settings.update' => 'Lưu cài đặt', 'content.update' => 'Lưu nội dung',
        'pagecontent.about_save' => 'Lưu trang Về chúng tôi', 'pagecontent.benefits_save' => 'Lưu trang Phúc lợi',
        'menu.create' => 'Thêm menu', 'menu.update' => 'Sửa menu', 'menu.delete' => 'Xóa menu',
        'taxonomy.create' => 'Thêm danh mục', 'taxonomy.update' => 'Sửa danh mục',
        'taxonomy.delete' => 'Xóa danh mục', 'taxonomy.toggle' => 'Bật/tắt danh mục',
        'contact.status' => 'Xử lý tin nhắn', 'contact.delete' => 'Xóa tin nhắn',
        'user.create' => 'Tạo tài khoản', 'user.update' => 'Sửa tài khoản',
        'user.status' => 'Đổi trạng thái tài khoản',
        'user.password_reset' => 'Đặt lại mật khẩu', 'user.password_change' => 'Đổi mật khẩu',
    ];

    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, meta, ip_address)
                 VALUES (:user, :action, :type, :entity, :meta, :ip)'
            );
            $stmt->execute([
                'user'   => \App\Core\Session::get('auth_id'),
                'action' => $action,
                'type'   => $entityType,
                'entity' => $entityId,
                'meta'   => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
                'ip'     => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ]);
        } catch (\Throwable $e) {
            // Nhật ký không được làm hỏng thao tác chính.
            error_log('[TEX-GIANG] audit_log lỗi: ' . $e->getMessage());
        }
    }

    /** @return array{items:array,total:int,pages:int,page:int,per_page:int} */
    public static function paginate(array $filters = [], int $page = 1, int $perPage = 30): array
    {
        $page = max(1, $page);
        $where = ['1 = 1'];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $keyword = '%' . $filters['q'] . '%';
            $matches = [];
            foreach (['a.action', 'a.ip_address', 'u.full_name', 'a.entity_type', 'a.meta'] as $index => $column) {
                $name = 'kw_' . $index;
                $matches[] = $column . ' LIKE :' . $name;
                $params[$name] = $keyword;
            }
            if (preg_match('/^#?(\d{1,19})$/D', $filters['q'], $entity)) {
                $matches[] = 'a.entity_id = :kw_entity';
                $params['kw_entity'] = $entity[1];
            }
            $actions = [];
            foreach (self::ACTION_LABELS as $code => $label) {
                if (mb_stripos($label, $filters['q']) !== false) {
                    $name = 'action_' . count($actions);
                    $actions[] = ':' . $name;
                    $params[$name] = $code;
                }
            }
            if ($actions !== []) {
                $matches[] = 'a.action IN (' . implode(', ', $actions) . ')';
            }
            $where[] = '(' . implode(' OR ', $matches) . ')';
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $countStmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $stmt = Database::connection()->prepare(
            'SELECT a.*, u.full_name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id'
            . $whereSql . ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $perPage];
    }
}
