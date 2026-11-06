<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditLog
{
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
            $where[] = '(a.action LIKE :kw_action OR a.ip_address LIKE :kw_ip OR u.full_name LIKE :kw_user)';
            $keyword = '%' . $filters['q'] . '%';
            $params['kw_action'] = $keyword;
            $params['kw_ip'] = $keyword;
            $params['kw_user'] = $keyword;
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
