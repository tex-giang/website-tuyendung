<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * CRUD chung cho các bảng nội dung trang (Về chúng tôi / Phúc lợi).
 * Mỗi bảng có cấu trúc cột riêng; controller truyền tên bảng trong allowlist.
 */
final class ContentItem
{
    /** @var array<int, string> */
    private const TABLES = [
        'about_milestones',
        'about_values',
        'about_certifications',
        'benefit_groups',
        'benefit_items',
        'benefit_comparisons',
    ];

    private static function table(string $table): string
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new \InvalidArgumentException('Bảng nội dung không hợp lệ: ' . $table);
        }
        return $table;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(string $table): array
    {
        $table = self::table($table);
        return Database::connection()
            ->query('SELECT * FROM `' . $table . '` ORDER BY sort_order, id')
            ->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function active(string $table): array
    {
        $table = self::table($table);
        return Database::connection()
            ->query('SELECT * FROM `' . $table . '` WHERE is_active = 1 ORDER BY sort_order, id')
            ->fetchAll();
    }

    public static function find(string $table, int $id): ?array
    {
        $table = self::table($table);
        $stmt = Database::connection()->prepare('SELECT * FROM `' . $table . '` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @param array<string, mixed> $data */
    public static function create(string $table, array $data): int
    {
        $table = self::table($table);
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
        $pdo = Database::connection();
        $pdo->prepare($sql)->execute($data);
        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(string $table, int $id, array $data): void
    {
        $table = self::table($table);
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = '`' . $column . '` = :' . $column;
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $data['id'] = $id;
        Database::connection()->prepare($sql)->execute($data);
    }

    public static function delete(string $table, int $id): void
    {
        $table = self::table($table);
        Database::connection()->prepare('DELETE FROM `' . $table . '` WHERE id = :id')->execute(['id' => $id]);
    }

    public static function toggleActive(string $table, int $id): void
    {
        $table = self::table($table);
        Database::connection()
            ->prepare('UPDATE `' . $table . '` SET is_active = 1 - is_active WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function maxSort(string $table): int
    {
        $table = self::table($table);
        return (int) Database::connection()
            ->query('SELECT COALESCE(MAX(sort_order), 0) FROM `' . $table . '`')
            ->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public static function byGroup(string $table, int $groupId): array
    {
        $table = self::table($table);
        $stmt = Database::connection()->prepare(
            'SELECT * FROM `' . $table . '` WHERE group_id = :gid ORDER BY sort_order, id'
        );
        $stmt->execute(['gid' => $groupId]);
        return $stmt->fetchAll();
    }

    public static function deleteByIds(string $table, array $ids): void
    {
        $table = self::table($table);
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        Database::connection()->prepare('DELETE FROM `' . $table . '` WHERE id IN (' . $placeholders . ')')->execute($ids);
    }

    public static function count(string $table): int
    {
        $table = self::table($table);
        return (int) Database::connection()->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    }
}
