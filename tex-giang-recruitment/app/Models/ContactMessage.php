<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ContactMessage
{
    public const STATUSES = [
        'new'     => 'Mới nhận',
        'read'    => 'Đã xem',
        'replied' => 'Đã phản hồi',
    ];

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO contact_messages (full_name, phone, email, subject, message, status)
             VALUES (:full_name, :phone, :email, :subject, :message, :status)'
        );
        $stmt->execute([
            'full_name' => $data['full_name'],
            'phone'     => $data['phone'],
            'email'     => ($data['email'] ?? '') !== '' ? $data['email'] : null,
            'subject'   => ($data['subject'] ?? '') !== '' ? $data['subject'] : null,
            'message'   => $data['message'],
            'status'    => 'new',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    /** @return array{items:array,total:int,pages:int,page:int,per_page:int} */
    public static function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $where = ['1 = 1'];
        $params = [];
        if (($filters['status'] ?? '') !== '' && isset(self::STATUSES[$filters['status']])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $countStmt = Database::connection()->prepare('SELECT COUNT(*) FROM contact_messages' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $stmt = Database::connection()->prepare(
            'SELECT * FROM contact_messages' . $whereSql . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $perPage];
    }

    public static function newCount(): int
    {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function updateStatus(int $id, string $status, ?int $userId = null): void
    {
        if (!isset(self::STATUSES[$status])) {
            return;
        }
        $stmt = Database::connection()->prepare(
            'UPDATE contact_messages SET status = :status, handled_by = :user WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'user' => $userId, 'id' => $id]);
    }

    public static function markReadIfNew(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE contact_messages SET status = 'read', handled_by = :user WHERE id = :id AND status = 'new'"
        );
        $stmt->execute(['user' => $userId, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM contact_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** @return array<string, int> */
    public static function countsByStatus(): array
    {
        $rows = Database::connection()->query(
            'SELECT status, COUNT(*) AS total FROM contact_messages GROUP BY status'
        )->fetchAll();
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }
}
