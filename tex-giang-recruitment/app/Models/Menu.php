<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Menu
{
    /** @return array<int, array<string, mixed>> */
    public static function active(int $limit = 50): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM menus WHERE is_active = 1 ORDER BY sort_order, id LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM menus ORDER BY sort_order, id')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM menus WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO menus (label, url, sort_order, is_active)
             VALUES (:label, :url, :sort_order, :is_active)'
        );
        $stmt->execute(self::bindings($data));
        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE menus SET label = :label, url = :url,
                    sort_order = :sort_order, is_active = :is_active WHERE id = :id'
        );
        $bindings = self::bindings($data);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM menus WHERE id = :id')->execute(['id' => $id]);
    }

    public static function toggleActive(int $id): void
    {
        Database::connection()->prepare('UPDATE menus SET is_active = 1 - is_active WHERE id = :id')->execute(['id' => $id]);
    }

    public static function maxSort(): int
    {
        return (int) Database::connection()->query('SELECT COALESCE(MAX(sort_order), 0) FROM menus')->fetchColumn();
    }

    /** @return array<string, mixed> */
    private static function bindings(array $data): array
    {
        return [
            'label'      => $data['label'],
            'url'        => (string) ($data['url'] ?? ''),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active'  => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
