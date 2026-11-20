<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Job
{
    /**
     * Khối tuyển dụng — CỐ ĐỊNH 2 giá trị, khớp ENUM('office','enterprise') của jobs.block.
     * Đây là nguồn duy nhất cho nhãn hiển thị ở mọi tầng (form, bộ lọc, import).
     */
    public const BLOCKS = [
        'office'     => 'Văn phòng',
        'enterprise' => 'Xí nghiệp',
    ];

    public const DEFAULT_BLOCK = 'enterprise';

    public static function blockLabel(?string $block): string
    {
        return self::BLOCKS[$block ?? ''] ?? ($block ?? '');
    }

    public static function blockValid(?string $block): bool
    {
        return $block !== null && isset(self::BLOCKS[$block]);
    }

    private const SELECT = 'SELECT j.*, b.name AS branch_name, b.slug AS branch_slug, b.address AS branch_address, b.phone AS branch_phone,
                                   c.name AS category_name, c.slug AS category_slug
                            FROM jobs j
                            LEFT JOIN branches b ON b.id = j.branch_id
                            LEFT JOIN categories c ON c.id = j.category_id';

    /**
     * Thứ tự nhà máy cố định: Tân Hương → Cai Lậy → Chợ Gạo
     * (theo branches.sort_order). Dùng cho MỌI danh sách tin.
     */
    private const BRANCH_ORDER = 'b.sort_order ASC, b.id ASC';

    /** Điều kiện tin hiển thị công khai: đã đăng, chưa xoá, còn hạn. */
    private static function publicWhere(array $filters, array &$params): string
    {
        $where = [
            "j.status = 'published'",
            'j.deleted_at IS NULL',
            '(j.deadline IS NULL OR j.deadline >= CURDATE())',
        ];

        if (($filters['q'] ?? '') !== '') {
            // MySQL native prepare không cho dùng lại một named parameter,
            // nên mỗi cột cần placeholder riêng.
            $where[] = '(j.title LIKE :kw_title OR j.description LIKE :kw_desc OR c.name LIKE :kw_cat OR b.name LIKE :kw_branch)';
            $keyword = '%' . $filters['q'] . '%';
            $params['kw_title'] = $keyword;
            $params['kw_desc'] = $keyword;
            $params['kw_cat'] = $keyword;
            $params['kw_branch'] = $keyword;
        }
        if (($filters['location'] ?? '') !== '') {
            $where[] = 'b.slug = :branch';
            $params['branch'] = $filters['location'];
        }
        if (($filters['block'] ?? '') !== '' && self::blockValid((string) $filters['block'])) {
            $where[] = 'j.block = :block';
            $params['block'] = $filters['block'];
        }
        if (($filters['department'] ?? '') !== '') {
            $where[] = 'c.slug = :category';
            $params['category'] = $filters['department'];
        }

        return ' WHERE ' . implode(' AND ', $where);
    }

    /**
     * Danh sách công khai: LUÔN xếp theo nhà máy Tân Hương → Cai Lậy → Chợ Gạo.
     * Trong mỗi nhà máy: tuỳ lựa chọn — tin gấp trước (sort=urgent) hoặc tin mới trước.
     */
    private static function orderBy(array $filters): string
    {
        $afterBranch = (($filters['sort'] ?? '') === 'urgent')
            ? 'j.is_urgent DESC, j.deadline IS NULL, j.deadline ASC, COALESCE(j.posted_at, j.created_at) DESC, j.id ASC'
            : 'COALESCE(j.posted_at, j.created_at) DESC, j.id ASC';

        return ' ORDER BY ' . self::BRANCH_ORDER . ', ' . $afterBranch;
    }

    /**
     * Phân trang tin công khai.
     * @return array{items: array<int, array<string, mixed>>, total: int, pages: int, page: int, per_page: int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 6): array
    {
        $page = max(1, $page);
        $params = [];
        $where = self::publicWhere($filters, $params);

        $countStmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM jobs j
             LEFT JOIN branches b ON b.id = j.branch_id
             LEFT JOIN categories c ON c.id = j.category_id' . $where
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            self::SELECT . $where . self::orderBy($filters) . ' LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items'    => $stmt->fetchAll(),
            'total'    => $total,
            'pages'    => $pages,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function latestPublished(int $limit = 6): array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . " WHERE j.status = 'published' AND j.deleted_at IS NULL
                             AND (j.deadline IS NULL OR j.deadline >= CURDATE())
                             ORDER BY b.sort_order ASC, b.id ASC, COALESCE(j.posted_at, j.created_at) DESC, j.id ASC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countPublished(): int
    {
        return (int) Database::connection()->query(
            "SELECT COUNT(*) FROM jobs WHERE status = 'published' AND deleted_at IS NULL
             AND (deadline IS NULL OR deadline >= CURDATE())"
        )->fetchColumn();
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . " WHERE j.slug = :slug AND j.status = 'published' AND j.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public static function incrementViews(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE jobs SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Tin nên hiện trong dialog "Tuyển gấp" ở trang chủ: tin đánh dấu tuyển gấp
     * hoặc sắp hết hạn trong 7 ngày tới.
     * @return array<int, array<string, mixed>>
     */
    public static function forUrgentDialog(int $limit = 6): array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . " WHERE j.status = 'published' AND j.deleted_at IS NULL
                             AND (j.deadline IS NULL OR j.deadline >= CURDATE())
                             AND (j.is_urgent = 1 OR (j.deadline IS NOT NULL AND j.deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)))
                             ORDER BY b.sort_order ASC, b.id ASC, j.is_urgent DESC, j.deadline IS NULL, j.deadline ASC, COALESCE(j.posted_at, j.created_at) DESC, j.id ASC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    /** @return array<int, array<string, mixed>> Tin liên quan cùng khối phòng ban. */
    public static function related(array $job, int $limit = 3): array
    {
        $sql = self::SELECT . " WHERE j.status = 'published' AND j.deleted_at IS NULL
                               AND j.id <> :id
                               AND (j.deadline IS NULL OR j.deadline >= CURDATE())";
        $params = [':id' => (int) $job['id']];

        // Không dùng lại một named parameter hai lần: native prepare của MySQL
        // không cho phép, nên tách điều kiện danh mục thành query riêng.
        if ($job['category_id'] !== null && $job['category_id'] !== '') {
            $sql .= ' AND j.category_id = :category';
            $params[':category'] = (int) $job['category_id'];
        }

        $stmt = Database::connection()->prepare(
            $sql . ' ORDER BY b.sort_order ASC, b.id ASC, COALESCE(j.posted_at, j.created_at) DESC, j.id ASC LIMIT :limit'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------ ADMIN

    /** @return array{items: array<int, array<string, mixed>>, total: int, pages: int, page: int, per_page: int} */
    public static function adminPaginate(array $filters, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $where = ['j.deleted_at IS NULL'];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(j.title LIKE :kw_title OR j.code LIKE :kw_code)';
            $keyword = '%' . $filters['q'] . '%';
            $params['kw_title'] = $keyword;
            $params['kw_code'] = $keyword;
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'j.status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['block'] ?? '') !== '' && self::blockValid((string) $filters['block'])) {
            $where[] = 'j.block = :block';
            $params['block'] = $filters['block'];
        }
        // Phạm vi nhà máy của tài khoản đang đăng nhập.
        [$branchWhere, $branchParams] = \App\Core\Permissions::branchSql('j.branch_id');
        if ($branchWhere !== '') {
            $where[] = $branchWhere;
            $params = array_merge($params, $branchParams);
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $countStmt = Database::connection()->prepare('SELECT COUNT(*) FROM jobs j' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $stmt = Database::connection()->prepare(
            self::SELECT . $whereSql
                . ' ORDER BY ' . self::BRANCH_ORDER . ', j.updated_at DESC, j.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $perPage];
    }

    public static function adminFind(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE j.id = :id AND j.deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM jobs WHERE slug = :slug' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Tự động chuyển tin đã quá hạn nộp sang trạng thái "Hết hạn".
     * Chạy lazy mỗi khi có trang dùng dữ liệu tin (hoặc qua cron).
     */
    public static function syncExpired(): int
    {
        $stmt = Database::connection()->prepare(
            "UPDATE jobs SET status = 'expired', updated_at = NOW()
             WHERE status = 'published' AND deleted_at IS NULL
               AND deadline IS NOT NULL AND deadline < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    /** Sinh mã tin kế tiếp theo định dạng TD<yy>-0001 (reset theo năm). */
    public static function generateCode(): string
    {
        $prefix = 'TD' . date('y') . '-';
        $stmt = Database::connection()->prepare(
            'SELECT code FROM jobs WHERE code LIKE :prefix ORDER BY code DESC LIMIT 1'
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = $last !== '' ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function codeExists(string $code, ?int $exceptId = null): bool
    {
        if ($code === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) FROM jobs WHERE code = :code' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['code' => $code];
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
            'INSERT INTO jobs
                (slug, code, branch_id, block, category_id, title, quantity, location, employment_type, experience,
                 salary_text, salary_min, salary_max, salary_range, description, requirements, benefits,
                 status, is_urgent, posted_at, deadline, meta_title, meta_description, sort_order, created_by)
             VALUES
                (:slug, :code, :branch_id, :block, :category_id, :title, :quantity, :location, :employment_type, :experience,
                 :salary_text, :salary_min, :salary_max, :salary_range, :description, :requirements, :benefits,
                 :status, :is_urgent, :posted_at, :deadline, :meta_title, :meta_description, :sort_order, :created_by)'
        );
        $stmt->execute(self::bindings($data, $userId));
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE jobs SET
                slug = :slug, code = :code, branch_id = :branch_id, block = :block, category_id = :category_id, title = :title,
                quantity = :quantity, location = :location, employment_type = :employment_type, experience = :experience,
                salary_text = :salary_text, salary_min = :salary_min, salary_max = :salary_max, salary_range = :salary_range,
                description = :description, requirements = :requirements, benefits = :benefits,
                status = :status, is_urgent = :is_urgent, posted_at = :posted_at, deadline = :deadline,
                meta_title = :meta_title, meta_description = :meta_description, sort_order = :sort_order
             WHERE id = :id'
        );
        $bindings = self::bindings($data, null);
        // UPDATE không có cột created_by — bỏ tham số thừa kẻo PDO báo HY093.
        unset($bindings['created_by']);
        $bindings['id'] = $id;
        $stmt->execute($bindings);
    }

    public static function changeStatus(int $id, string $status): void
    {
        // Đăng tin mà chưa có ngày đăng thì lấy thời điểm hiện tại để tin lên danh sách công khai.
        $sql = $status === 'published'
            ? 'UPDATE jobs SET status = :status, posted_at = COALESCE(posted_at, NOW()) WHERE id = :id'
            : 'UPDATE jobs SET status = :status WHERE id = :id';
        Database::connection()->prepare($sql)->execute(['status' => $status, 'id' => $id]);
    }

    /** Xoá mềm để không phá hồ sơ ứng tuyển đã tham chiếu. */
    public static function softDelete(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE jobs SET deleted_at = NOW(), status = :status WHERE id = :id');
        $stmt->execute(['status' => 'closed', 'id' => $id]);
    }

    public static function duplicate(int $id, ?int $userId): ?int
    {
        $job = self::adminFind($id);
        if ($job === null) {
            return null;
        }
        $data = $job;
        $data['title'] = $job['title'] . ' (bản sao)';
        $data['slug'] = $job['slug'] . '-ban-sao-' . date('YmdHis');
        $data['code'] = self::generateCode();
        $data['status'] = 'draft';
        $data['posted_at'] = null;
        $data['views'] = 0;
        return self::create($data, $userId);
    }

    /** @return array<int, string> id => title, dùng cho select lọc hồ sơ theo vị trí. */
    public static function optionsAll(): array
    {
        [$branchWhere, $params] = \App\Core\Permissions::branchSql('branch_id');
        $stmt = Database::connection()->prepare(
            'SELECT id, title FROM jobs WHERE deleted_at IS NULL'
            . ($branchWhere !== '' ? ' AND ' . $branchWhere : '')
            . ' ORDER BY title'
        );
        $stmt->execute($params);
        $options = [];
        foreach ($stmt->fetchAll() as $row) {
            $options[(int) $row['id']] = $row['title'];
        }
        return $options;
    }
    /** @return array<string, int> */
    public static function countsByStatus(): array
    {
        [$branchWhere, $params] = \App\Core\Permissions::branchSql('branch_id');
        $sql = 'SELECT status, COUNT(*) AS total FROM jobs WHERE deleted_at IS NULL'
            . ($branchWhere !== '' ? ' AND ' . $branchWhere : '')
            . ' GROUP BY status';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $counts = ['draft' => 0, 'published' => 0, 'paused' => 0, 'closed' => 0, 'expired' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    /**
     * Số tin có jobs.block LỆCH với categories.block của bộ phận.
     *
     * Lệch là dữ liệu sai: bộ lọc theo Khối dùng jobs.block, còn số lượng trong
     * dropdown bộ phận đếm theo categories.block → hai bên cho kết quả khác nhau.
     * Admin hiện cảnh báo để người dùng biết mà sửa (hoặc chạy migration đồng bộ).
     *
     * $scopeToUser = true (mặc định): chỉ đếm trong phạm vi nhà máy của tài khoản
     * đang đăng nhập — dùng cho cảnh báo trên giao diện. Truyền false khi cần con
     * số toàn hệ thống (CLI, migration, kiểm tra dữ liệu) vì lúc đó không có phiên.
     */
    public static function countBlockMismatch(bool $scopeToUser = true): int
    {
        $where = '';
        $params = [];
        if ($scopeToUser) {
            [$branchWhere, $branchParams] = \App\Core\Permissions::branchSql('j.branch_id');
            if ($branchWhere !== '') {
                $where = ' AND ' . $branchWhere;
                $params = $branchParams;
            }
        }

        $sql = 'SELECT COUNT(*) FROM jobs j
                JOIN categories c ON c.id = j.category_id
                WHERE j.deleted_at IS NULL
                  AND c.block IS NOT NULL
                  AND j.block <> c.block' . $where;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private static function bindings(array $data, ?int $userId): array
    {
        $block = (string) ($data['block'] ?? '');
        if (!self::blockValid($block)) {
            $block = self::DEFAULT_BLOCK;
        }

        return [
            'slug'             => $data['slug'],
            'code'             => ($data['code'] ?? '') !== '' ? $data['code'] : null,
            'branch_id'        => (int) $data['branch_id'],
            'block'            => $block,
            'category_id'      => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'title'            => $data['title'],
            'quantity'         => (int) ($data['quantity'] ?? 1),
            'location'         => ($data['location'] ?? '') !== '' ? $data['location'] : null,
            'employment_type'  => ($data['employment_type'] ?? '') !== '' ? $data['employment_type'] : null,
            'experience'       => ($data['experience'] ?? '') !== '' ? $data['experience'] : null,
            'salary_text'      => ($data['salary_text'] ?? '') !== '' ? $data['salary_text'] : null,
            'salary_min'       => ($data['salary_min'] ?? '') !== '' ? (int) $data['salary_min'] : null,
            'salary_max'       => ($data['salary_max'] ?? '') !== '' ? (int) $data['salary_max'] : null,
            'salary_range'     => ($data['salary_range'] ?? '') !== '' ? $data['salary_range'] : null,
            'description'      => $data['description'],
            'requirements'     => ($data['requirements'] ?? '') !== '' ? $data['requirements'] : null,
            'benefits'         => ($data['benefits'] ?? '') !== '' ? $data['benefits'] : null,
            'status'           => $data['status'],
            'is_urgent'        => !empty($data['is_urgent']) ? 1 : 0,
            'posted_at'        => ($data['posted_at'] ?? '') !== '' ? $data['posted_at'] : null,
            'deadline'         => ($data['deadline'] ?? '') !== '' ? $data['deadline'] : null,
            'meta_title'       => ($data['meta_title'] ?? '') !== '' ? $data['meta_title'] : null,
            'meta_description' => ($data['meta_description'] ?? '') !== '' ? $data['meta_description'] : null,
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
            'created_by'       => $userId,
        ];
    }
}
