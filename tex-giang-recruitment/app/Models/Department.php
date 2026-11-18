<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Bộ phận — cấp giữa Khu vực và Bộ phận tuyển.
 *
 * Cây phân cấp:
 *   Khu vực (branches) → Bộ phận (departments) → Bộ phận tuyển (categories) → Việc làm (jobs)
 *
 * Bộ phận có 2 LOẠI:
 *   - enterprise : Xí nghiệp
 *   - office     : Phòng ban
 */
final class Department
{
    public const KINDS = [
        'enterprise' => 'Xí nghiệp',
        'office'     => 'Phòng ban',
    ];

    public static function kindLabel(string $kind): string
    {
        return self::KINDS[$kind] ?? $kind;
    }

    /** @return array<int, array<string, mixed>> */
    public static function active(): array
    {
        return Database::connection()->query(
            'SELECT d.*, b.name AS branch_name, b.slug AS branch_slug
             FROM departments d
             JOIN branches b ON b.id = d.branch_id
             WHERE d.is_active = 1
             ORDER BY b.sort_order, b.id, d.kind, d.sort_order, d.name'
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT d.*, b.name AS branch_name, b.slug AS branch_slug
             FROM departments d
             JOIN branches b ON b.id = d.branch_id
             ORDER BY b.sort_order, b.id, d.kind, d.sort_order, d.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM departments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM departments WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * id => "Khu vực · Loại · Tên" để hiển thị ở dropdown.
     *
     * @return array<int, string>
     */
    public static function idOptions(): array
    {
        $options = [];
        foreach (self::all() as $row) {
            $options[(int) $row['id']] = self::label($row);
        }
        return $options;
    }

    /** @param array<string, mixed> $row */
    public static function label(array $row): string
    {
        $name = (string) ($row['name'] ?? '');
        $kind = self::kindLabel((string) ($row['kind'] ?? 'enterprise'));
        $branch = (string) ($row['branch_name'] ?? '');

        return $branch !== '' ? $branch . ' · ' . $kind . ': ' . $name : $kind . ': ' . $name;
    }

    /**
     * Bộ phận theo từng khu vực — dùng cho dropdown 2 cấp.
     *
     * @return array<int, list<array{id:int, slug:string, name:string, kind:string}>>
     */
    public static function groupedByBranch(): array
    {
        $grouped = [];
        foreach (self::active() as $row) {
            $grouped[(int) $row['branch_id']][] = [
                'id'   => (int) $row['id'],
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'kind' => (string) $row['kind'],
            ];
        }
        return $grouped;
    }

    public static function jobCount(int $id): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM jobs WHERE department_id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function categoryCount(int $id): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM categories WHERE department_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM departments WHERE slug = :slug' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ------------------------------------------------------------------ ADMIN

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO departments (branch_id, kind, slug, name, description, sort_order, is_active)
             VALUES (:branch_id, :kind, :slug, :name, :description, :sort_order, :is_active)'
        );
        $stmt->execute(self::bindings($data));
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE departments SET branch_id = :branch_id, kind = :kind, slug = :slug, name = :name,
                    description = :description, sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $bindings = self::bindings($data);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM departments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function toggleActive(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE departments SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** @return array<string, mixed> */
    private static function bindings(array $data): array
    {
        $kind = (string) ($data['kind'] ?? 'enterprise');
        if (!isset(self::KINDS[$kind])) {
            $kind = 'enterprise';
        }

        return [
            'branch_id'   => (int) $data['branch_id'],
            'kind'        => $kind,
            'slug'        => (string) $data['slug'],
            'name'        => (string) $data['name'],
            'description' => ($data['description'] ?? '') !== '' ? (string) $data['description'] : null,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
