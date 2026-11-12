<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Branch
{
    /** @return array<int, array<string, mixed>> */
    public static function active(): array
    {
        return Database::connection()->query(
            'SELECT * FROM branches WHERE is_active = 1 ORDER BY sort_order, name'
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM branches ORDER BY sort_order, name')->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM branches WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<string, string> slug => name, dùng cho select bộ lọc */
    public static function options(): array
    {
        $options = [];
        foreach (self::active() as $branch) {
            $options[$branch['slug']] = $branch['name'];
        }
        return $options;
    }

    /** @return array<int, string> id => name, dùng cho chọn nhà máy của tài khoản */
    public static function idOptions(): array
    {
        $options = [];
        foreach (self::all() as $branch) {
            $options[(int) $branch['id']] = (string) $branch['name'];
        }
        return $options;
    }

    // ------------------------------------------------------------------ ADMIN

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM branches WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM branches WHERE slug = :slug' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function jobCount(int $id): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM jobs WHERE branch_id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO branches (slug, name, address, phone, map_url, map_link, sort_order, is_active)
             VALUES (:slug, :name, :address, :phone, :map_url, :map_link, :sort_order, :is_active)'
        );
        $stmt->execute(self::bindings($data));
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE branches SET slug = :slug, name = :name, address = :address, phone = :phone,
                    map_url = :map_url, map_link = :map_link, sort_order = :sort_order, is_active = :is_active WHERE id = :id'
        );
        $bindings = self::bindings($data);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function delete(int $id): bool
    {
        if (self::jobCount($id) > 0) {
            return false;
        }
        $stmt = Database::connection()->prepare('DELETE FROM branches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return true;
    }

    public static function toggleActive(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE branches SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function bindings(array $data): array
    {
        return [
            'slug'       => $data['slug'],
            'name'       => $data['name'],
            'address'    => ($data['address'] ?? '') !== '' ? $data['address'] : null,
            'phone'      => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            'map_url'    => ($data['map_url'] ?? '') !== '' ? $data['map_url'] : null,
            'map_link'   => ($data['map_link'] ?? '') !== '' ? $data['map_link'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active'  => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
