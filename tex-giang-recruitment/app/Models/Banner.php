<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Banner
{
    /** @return array<int, array<string, mixed>> */
    public static function activeByType(string $type): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM banners
             WHERE type = :type AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY sort_order, id'
        );
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    public static function firstActive(string $type): ?array
    {
        return self::activeByType($type)[0] ?? null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function allByType(string $type): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM banners WHERE type = :type ORDER BY sort_order, id');
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            "SELECT * FROM banners ORDER BY FIELD(type, 'hero', 'notice', 'popup', 'ad'), sort_order, id"
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM banners WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO banners (name, type, title, subtitle, description, image_path, image_path_small,
                                  image_alt, link_url, link_label, cta_label, is_active, sort_order, starts_at, ends_at)
             VALUES (:name, :type, :title, :subtitle, :description, :image, :image_small,
                     :image_alt, :link_url, :link_label, :cta_label, :is_active, :sort_order, :starts_at, :ends_at)'
        );
        $stmt->execute(self::bindings($data));
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE banners SET name = :name, type = :type, title = :title, subtitle = :subtitle,
                    description = :description, image_path = :image, image_path_small = :image_small,
                    image_alt = :image_alt, link_url = :link_url, link_label = :link_label, cta_label = :cta_label,
                    is_active = :is_active, sort_order = :sort_order, starts_at = :starts_at, ends_at = :ends_at
             WHERE id = :id'
        );
        $bindings = self::bindings($data);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM banners WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function toggleActive(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE banners SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function bindings(array $data): array
    {
        return [
            'name'        => $data['name'],
            'type'        => $data['type'],
            'title'       => ($data['title'] ?? '') !== '' ? $data['title'] : null,
            'subtitle'    => ($data['subtitle'] ?? '') !== '' ? $data['subtitle'] : null,
            'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
            'image'       => ($data['image_path'] ?? '') !== '' ? $data['image_path'] : null,
            'image_small' => ($data['image_path_small'] ?? '') !== '' ? $data['image_path_small'] : null,
            'image_alt'   => ($data['image_alt'] ?? '') !== '' ? $data['image_alt'] : null,
            'link_url'    => ($data['link_url'] ?? '') !== '' ? $data['link_url'] : null,
            'link_label'  => ($data['link_label'] ?? '') !== '' ? $data['link_label'] : null,
            'cta_label'   => ($data['cta_label'] ?? '') !== '' ? $data['cta_label'] : null,
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'starts_at'   => ($data['starts_at'] ?? '') !== '' ? $data['starts_at'] : null,
            'ends_at'     => ($data['ends_at'] ?? '') !== '' ? $data['ends_at'] : null,
        ];
    }
}
