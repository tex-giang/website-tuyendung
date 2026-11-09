<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Post
{
    private const SELECT = 'SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
                            FROM posts p LEFT JOIN post_categories pc ON pc.id = p.category_id';

    /** @return array{items: array<int, array<string, mixed>>, total: int, pages: int, page: int} */
    public static function paginate(int $page = 1, int $perPage = 6, ?string $categorySlug = null): array
    {
        $page = max(1, $page);
        $where = ["p.status = 'published'", 'p.deleted_at IS NULL'];
        $params = [];

        if ($categorySlug !== null && $categorySlug !== '') {
            $where[] = 'pc.slug = :category';
            $params['category'] = $categorySlug;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $countStmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM posts p LEFT JOIN post_categories pc ON pc.id = p.category_id' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            self::SELECT . $whereSql . ' ORDER BY p.published_at DESC, p.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $perPage];
    }

    /** @return array<int, array<string, mixed>> */
    public static function latest(int $limit = 3): array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . " WHERE p.status = 'published' AND p.deleted_at IS NULL
                             ORDER BY p.published_at DESC, p.id DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . " WHERE p.slug = :slug AND p.status = 'published' AND p.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public static function incrementViews(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE posts SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function categories(): array
    {
        return Database::connection()->query(
            'SELECT * FROM post_categories WHERE is_active = 1 ORDER BY sort_order, name'
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> Chuyên mục kèm số bài đã đăng. */
    public static function categoriesWithCounts(): array
    {
        return Database::connection()->query(
            "SELECT pc.*, COUNT(p.id) AS post_count
             FROM post_categories pc
             LEFT JOIN posts p ON p.category_id = pc.id AND p.status = 'published' AND p.deleted_at IS NULL
             WHERE pc.is_active = 1
             GROUP BY pc.id
             ORDER BY pc.sort_order, pc.name"
        )->fetchAll();
    }

    public static function totalPublished(?string $categorySlug = null): int
    {
        $sql = "SELECT COUNT(*) FROM posts p LEFT JOIN post_categories pc ON pc.id = p.category_id
                WHERE p.status = 'published' AND p.deleted_at IS NULL";
        $params = [];
        if ($categorySlug !== null && $categorySlug !== '') {
            $sql .= ' AND pc.slug = :category';
            $params['category'] = $categorySlug;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> Bài cùng chuyên mục (hoặc mới nhất) để đọc thêm. */
    public static function related(array $post, int $limit = 3): array
    {
        $sql = self::SELECT . " WHERE p.status = 'published' AND p.deleted_at IS NULL AND p.id <> :id";
        $params = ['id' => (int) $post['id']];
        if (!empty($post['category_id'])) {
            $sql .= ' AND (p.category_id = :category)';
            $params['category'] = (int) $post['category_id'];
        }
        $stmt = Database::connection()->prepare($sql . ' ORDER BY p.published_at DESC, p.id DESC LIMIT :limit');
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $related = $stmt->fetchAll();
        if ($related !== []) {
            return $related;
        }
        // Fallback: bài mới nhất nhưng vẫn loại trừ bài đang xem (tránh "Đọc thêm" lặp lại chính nó).
        $stmt = Database::connection()->prepare(
            self::SELECT . " WHERE p.status = 'published' AND p.deleted_at IS NULL AND p.id <> :id
                             ORDER BY p.published_at DESC, p.id DESC LIMIT :limit"
        );
        $stmt->bindValue(':id', (int) $post['id'], \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------ ADMIN

    /** @return array{items:array,total:int,pages:int,page:int,per_page:int} */
    public static function adminPaginate(array $filters, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(p.title LIKE :kw_title OR p.slug LIKE :kw_slug)';
            $keyword = '%' . $filters['q'] . '%';
            $params['kw_title'] = $keyword;
            $params['kw_slug'] = $keyword;
        }
        if (($filters['status'] ?? '') !== '' && in_array($filters['status'], ['draft', 'published'], true)) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category';
            $params['category'] = (int) $filters['category_id'];
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $countStmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM posts p' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $stmt = Database::connection()->prepare(
            self::SELECT . $whereSql . ' ORDER BY p.updated_at DESC, p.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $perPage];
    }

    public static function adminFind(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE p.id = :id AND p.deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE slug = :slug' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data, ?int $userId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO posts (slug, title, category_id, excerpt, body, image_path, image_alt, image_caption,
                                status, published_at, created_by)
             VALUES (:slug, :title, :category, :excerpt, :body, :image, :image_alt, :image_caption,
                     :status, :published_at, :created_by)'
        );
        $stmt->execute(self::adminBindings($data, $userId));
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE posts SET slug = :slug, title = :title, category_id = :category, excerpt = :excerpt,
                    body = :body, image_path = :image, image_alt = :image_alt, image_caption = :image_caption,
                    status = :status, published_at = :published_at
             WHERE id = :id'
        );
        $bindings = self::adminBindings($data, null);
        // UPDATE không có cột created_by — bỏ tham số thừa kẻo PDO báo HY093.
        unset($bindings['created_by']);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function changeStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE posts SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function softDelete(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE posts SET deleted_at = NOW(), status = :status WHERE id = :id');
        $stmt->execute(['status' => 'draft', 'id' => $id]);
    }

    private static function adminBindings(array $data, ?int $userId): array
    {
        return [
            'slug'          => $data['slug'],
            'title'         => $data['title'],
            'category'      => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'excerpt'       => ($data['excerpt'] ?? '') !== '' ? $data['excerpt'] : null,
            'body'          => $data['body'],
            'image'         => ($data['image_path'] ?? '') !== '' ? $data['image_path'] : null,
            'image_alt'     => ($data['image_alt'] ?? '') !== '' ? $data['image_alt'] : null,
            'image_caption' => ($data['image_caption'] ?? '') !== '' ? $data['image_caption'] : null,
            'status'        => $data['status'],
            'published_at'  => ($data['published_at'] ?? '') !== '' ? $data['published_at'] : null,
            'created_by'    => $userId,
        ];
    }
}
