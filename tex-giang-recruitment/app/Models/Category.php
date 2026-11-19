<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Category
{
    /**
     * Khối tuyển dụng — CỐ ĐỊNH 2 giá trị, khớp ENUM của categories.block.
     * Bộ phận có block = NULL được coi là dùng chung cho cả 2 Khối.
     */
    public const BLOCKS = [
        'office'     => 'Văn phòng',
        'enterprise' => 'Xí nghiệp',
    ];

    public static function blockLabel(?string $block): string
    {
        if ($block === null || $block === '') {
            return 'Dùng chung';
        }
        return self::BLOCKS[$block] ?? $block;
    }

    /** @return array<int, array<string, mixed>> */
    public static function active(): array
    {
        return Database::connection()->query(
            'SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name'
        )->fetchAll();
    }

    /**
     * Bộ phận đang hoạt động thuộc một Khối — gồm cả bộ phận dùng chung (block NULL).
     * Dùng cho dropdown phụ thuộc ở form tin tuyển dụng và bộ lọc ngoài website.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeByBlock(?string $block): array
    {
        if ($block === null || $block === '' || !isset(self::BLOCKS[$block])) {
            return self::active();
        }
        $stmt = Database::connection()->prepare(
            'SELECT * FROM categories
              WHERE is_active = 1 AND (block IS NULL OR block = :block)
              ORDER BY sort_order, name'
        );
        $stmt->execute(['block' => $block]);
        return $stmt->fetchAll();
    }

    /**
     * Bộ phận kèm số tin CHƯA XOÁ (mọi trạng thái) — dùng cho form quản trị.
     *
     * Khác activeWithJobs(): hàm kia chỉ đếm tin đang hiển thị công khai (published,
     * còn hạn) vì phục vụ ứng viên ngoài website. Ở admin, một bộ phận gắn với tin
     * nháp vẫn là "đang dùng" nên phải hiện.
     *
     * @return array<int, array<string, mixed>> mỗi dòng có thêm khoá job_count
     */
    public static function withJobCounts(): array
    {
        $sql = 'SELECT c.*, COUNT(j.id) AS job_count
                FROM categories c
                LEFT JOIN jobs j ON j.category_id = c.id AND j.deleted_at IS NULL
                GROUP BY c.id
                HAVING job_count > 0
                ORDER BY c.block IS NULL, c.block, c.sort_order, c.name';

        return Database::connection()->query($sql)->fetchAll();
    }

    /**
     * Bộ phận CHỈ GỒM những bộ phận đang có ít nhất 1 tin hiển thị công khai,
     * kèm số tin đang tuyển — dùng cho dropdown lọc/bộ phận ngoài website để
     * ứng viên không chọn phải bộ phận trống (bấm vào ra 0 kết quả).
     *
     * "Đang có tin" = tin status='published', chưa xoá mềm, còn hạn nộp —
     * đúng cùng điều kiện với Job::publicWhere() nên số đếm khớp kết quả lọc.
     *
     * @return array<int, array<string, mixed>> mỗi dòng có thêm khoá job_count
     */
    public static function activeWithJobs(?string $block = null, ?string $branchSlug = null): array
    {
        $where = ['c.is_active = 1'];
        $params = [];

        if ($block !== null && $block !== '' && isset(self::BLOCKS[$block])) {
            $where[] = '(c.block IS NULL OR c.block = :block)';
            $params['block'] = $block;
        }
        if ($branchSlug !== null && $branchSlug !== '') {
            $where[] = 'b.slug = :branch';
            $params['branch'] = $branchSlug;
        }

        $sql = 'SELECT c.*, COUNT(j.id) AS job_count
                FROM categories c
                JOIN jobs j ON j.category_id = c.id
                     AND j.status = \'published\' AND j.deleted_at IS NULL
                     AND (j.deadline IS NULL OR j.deadline >= CURDATE())
                JOIN branches b ON b.id = j.branch_id'
            . ' WHERE ' . implode(' AND ', $where)
            . ' GROUP BY c.id'
            . ' ORDER BY c.sort_order, c.name';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * slug => "Tên (n)" cho dropdown lọc — chỉ bộ phận đang có tin.
     *
     * @return array<string, string>
     */
    public static function optionsWithJobs(?string $block = null, ?string $branchSlug = null, bool $showCount = true): array
    {
        $options = [];
        foreach (self::activeWithJobs($block, $branchSlug) as $row) {
            $name = (string) $row['name'];
            $count = (int) ($row['job_count'] ?? 0);
            $options[(string) $row['slug']] = $showCount ? $name . ' (' . $count . ')' : $name;
        }
        return $options;
    }

    /**
     * id => name, nhóm theo Khối để hiển thị dropdown quang cảnh.
     *
     * @return array<string, array<int, string>> block => [id => name]
     */
    public static function idOptionsByBlock(): array
    {
        $grouped = [];
        foreach (self::all() as $row) {
            $key = (string) ($row['block'] ?? '');
            $grouped[$key][(int) $row['id']] = (string) $row['name'];
        }
        return $grouped;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT * FROM categories ORDER BY block IS NULL, block, sort_order, name'
        )->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<string, string> slug => name */
    public static function options(): array
    {
        $options = [];
        foreach (self::active() as $category) {
            $options[$category['slug']] = $category['name'];
        }
        return $options;
    }

    // ------------------------------------------------------------------ ADMIN

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug' . ($exceptId !== null ? ' AND id <> :id' : '');
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
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM jobs WHERE category_id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO categories (slug, block, name, description, sort_order, is_active)
             VALUES (:slug, :block, :name, :description, :sort_order, :is_active)'
        );
        $stmt->execute(self::bindings($data));
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE categories SET slug = :slug, block = :block, name = :name, description = :description,
                    sort_order = :sort_order, is_active = :is_active WHERE id = :id'
        );
        $bindings = self::bindings($data);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function toggleActive(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE categories SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function bindings(array $data): array
    {
        $block = (string) ($data['block'] ?? '');
        if (!isset(self::BLOCKS[$block])) {
            $block = ''; // rỗng = dùng chung cho cả 2 Khối
        }

        return [
            'slug'        => $data['slug'],
            'block'       => $block !== '' ? $block : null,
            'name'        => $data['name'],
            'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
