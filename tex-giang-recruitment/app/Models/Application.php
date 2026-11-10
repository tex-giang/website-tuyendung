<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Application
{
    public const STATUSES = [
        'new'       => 'Mới nhận',
        'contacted' => 'Đã liên hệ',
        'interview' => 'Hẹn phỏng vấn',
        'hired'     => 'Trúng tuyển',
        'rejected'  => 'Không phù hợp',
    ];

    private const SELECT = 'SELECT a.*, j.title AS job_title, j.slug AS job_slug, j.code AS job_code
                            FROM applications a LEFT JOIN jobs j ON j.id = a.job_id';

    /** @return array{items: array<int, array<string, mixed>>, total: int, pages: int, page: int} */
    public static function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $where = ['1 = 1'];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(a.full_name LIKE :kw_name OR a.phone LIKE :kw_phone OR a.email LIKE :kw_email)';
            $keyword = '%' . $filters['q'] . '%';
            $params['kw_name'] = $keyword;
            $params['kw_phone'] = $keyword;
            $params['kw_email'] = $keyword;
        }
        if (($filters['status'] ?? '') !== '' && isset(self::STATUSES[$filters['status']])) {
            $where[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['job_id'] ?? '') !== '') {
            $where[] = 'a.job_id = :job_id';
            $params['job_id'] = (int) $filters['job_id'];
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $countStmt = Database::connection()->prepare('SELECT COUNT(*) FROM applications a' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $stmt = Database::connection()->prepare(
            self::SELECT . $whereSql . ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $perPage];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE a.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function latest(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function history(int $applicationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.*, u.full_name AS changed_by_name
             FROM application_history h LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.application_id = :id ORDER BY h.created_at, h.id'
        );
        $stmt->execute(['id' => $applicationId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO applications
                (job_id, full_name, phone, email, gender, birth_year, address, message,
                 cv_original_name, cv_stored_name, cv_mime, cv_size, status, source)
             VALUES
                (:job_id, :full_name, :phone, :email, :gender, :birth_year, :address, :message,
                 :cv_original_name, :cv_stored_name, :cv_mime, :cv_size, :status, :source)'
        );
        $stmt->execute([
            'job_id'           => !empty($data['job_id']) ? (int) $data['job_id'] : null,
            'full_name'        => $data['full_name'],
            'phone'            => $data['phone'],
            'email'            => ($data['email'] ?? '') !== '' ? $data['email'] : null,
            'gender'           => in_array($data['gender'] ?? '', ['nu', 'nam', 'khac'], true) ? $data['gender'] : null,
            'birth_year'       => !empty($data['birth_year']) ? (int) $data['birth_year'] : null,
            'address'          => ($data['address'] ?? '') !== '' ? $data['address'] : null,
            'message'          => ($data['message'] ?? '') !== '' ? $data['message'] : null,
            'cv_original_name' => ($data['cv_original_name'] ?? '') !== '' ? $data['cv_original_name'] : null,
            'cv_stored_name'   => ($data['cv_stored_name'] ?? '') !== '' ? $data['cv_stored_name'] : null,
            'cv_mime'          => ($data['cv_mime'] ?? '') !== '' ? $data['cv_mime'] : null,
            'cv_size'          => !empty($data['cv_size']) ? (int) $data['cv_size'] : null,
            'status'           => 'new',
            'source'           => $data['source'] ?? 'Website',
        ]);
        $id = (int) $pdo->lastInsertId();

        $history = $pdo->prepare(
            'INSERT INTO application_history (application_id, status, note) VALUES (:id, :status, :note)'
        );
        $history->execute([
            'id'     => $id,
            'status' => 'new',
            'note'   => 'Hồ sơ gửi từ form ứng tuyển trên website.',
        ]);

        return $id;
    }

    /** Cập nhật các trường của một thao tác và ghi lịch sử trong cùng transaction. */
    public static function updateReview(int $id, array $changes, ?string $note, int $userId): bool
    {
        $columns = array_intersect_key($changes, array_flip(['status', 'admin_note', 'appointment_at', 'appointment_location']));
        return Database::transaction(static function () use ($id, $columns, $note, $userId): bool {
            $pdo = Database::connection();
            $lock = $pdo->prepare('SELECT status FROM applications WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $id]);
            $currentStatus = $lock->fetchColumn();
            $lock->closeCursor();
            if ($currentStatus === false) {
                return false;
            }

            $assignments = ['handled_by = :user'];
            foreach (array_keys($columns) as $column) {
                $assignments[] = $column . ' = :' . $column;
            }
            $stmt = $pdo->prepare('UPDATE applications SET ' . implode(', ', $assignments) . ' WHERE id = :id');
            $stmt->execute($columns + ['user' => $userId, 'id' => $id]);

            $history = $pdo->prepare(
                'INSERT INTO application_history (application_id, status, note, changed_by)
                 VALUES (:id, :status, :note, :user)'
            );
            $history->execute([
                'id' => $id,
                'status' => $columns['status'] ?? $currentStatus,
                'note' => $note,
                'user' => $userId,
            ]);
            return true;
        });
    }

    /** @return array<string, int> */
    public static function countsByStatus(): array
    {
        $rows = Database::connection()->query(
            'SELECT status, COUNT(*) AS total FROM applications GROUP BY status'
        )->fetchAll();
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public static function total(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM applications')->fetchColumn();
    }

    public static function newCount(): int
    {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn();
    }
}
