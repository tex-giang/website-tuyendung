<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PostCategory
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT * FROM post_categories ORDER BY sort_order, name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM post_categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM post_categories WHERE slug = :slug' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function postCount(int $id): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM posts WHERE category_id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO post_categories (slug, name, sort_order, is_active)
             VALUES (:slug, :name, :sort_order, :is_active)'
        );
        $stmt->execute(self::bindings($data));
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE post_categories SET slug = :slug, name = :name, sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $bindings = self::bindings($data);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM post_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function toggleActive(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE post_categories SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function bindings(array $data): array
    {
        return [
            'slug'       => $data['slug'],
            'name'       => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active'  => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
