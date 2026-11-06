<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    /** @return array<string, string> */
    public static function all(): array
    {
        $rows = Database::connection()->query('SELECT `key`, `value` FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = (string) ($row['value'] ?? '');
        }
        return $settings;
    }

    public static function get(string $key, string $default = ''): string
    {
        $stmt = Database::connection()->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string) $value;
    }

    /** @param array<string, string> $values */
    public static function setMany(array $values): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (`key`, `value`, `group`)
             VALUES (:key, :value, :group)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `group` = VALUES(`group`)'
        );
        foreach ($values as $key => $value) {
            $stmt->execute([
                'key'   => $key,
                'value' => (string) $value,
                'group' => self::groupForKey($key),
            ]);
        }
    }

    private static function groupForKey(string $key): string
    {
        foreach (['content_', 'stat_'] as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return 'content';
            }
        }
        return match (true) {
            str_starts_with($key, 'seo_')       => 'seo',
            str_starts_with($key, 'hotline'),
            str_starts_with($key, 'email'),
            str_starts_with($key, 'zalo'),
            str_starts_with($key, 'fax'),
            str_starts_with($key, 'map_')       => 'contact',
            str_starts_with($key, 'facebook'),
            str_starts_with($key, 'youtube'),
            str_starts_with($key, 'website')    => 'social',
            default                             => 'general',
        };
    }
}
