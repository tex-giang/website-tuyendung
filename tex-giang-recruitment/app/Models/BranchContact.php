<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Người liên hệ tuyển dụng theo khu sản xuất (chi nhánh).
 * Một chi nhánh có nhiều dòng họ tên + số điện thoại, hiển thị ở footer và trang Liên hệ.
 */
final class BranchContact
{
    /** @return array<int, array<string, mixed>> Danh sách liên hệ đang bật của một chi nhánh. */
    public static function byBranch(int $branchId, bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM branch_contacts WHERE branch_id = :branch_id'
            . ($onlyActive ? ' AND is_active = 1' : '')
            . ' ORDER BY sort_order, id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['branch_id' => $branchId]);
        return $stmt->fetchAll();
    }

    /**
     * Toàn bộ liên hệ đang bật, gom theo branch_id.
     * @return array<int, array<int, array<string, mixed>>> [branch_id => contacts[]]
     */
    public static function activeGrouped(): array
    {
        $rows = Database::connection()->query(
            'SELECT * FROM branch_contacts WHERE is_active = 1 ORDER BY branch_id, sort_order, id'
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['branch_id']][] = $row;
        }
        return $grouped;
    }

    /** @return array<int, array<string, mixed>> Toàn bộ liên hệ (kể cả đang tắt) — dùng cho admin. */
    public static function allGrouped(): array
    {
        $rows = Database::connection()->query(
            'SELECT * FROM branch_contacts ORDER BY branch_id, sort_order, id'
        )->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['branch_id']][] = $row;
        }
        return $grouped;
    }

    public static function countForBranch(int $branchId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM branch_contacts WHERE branch_id = :id');
        $stmt->execute(['id' => $branchId]);
        return (int) $stmt->fetchColumn();
    }

    /** Số liên hệ đang bật của toàn hệ thống (badge sidebar / dashboard). */
    public static function activeCount(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM branch_contacts WHERE is_active = 1')->fetchColumn();
    }

    /**
     * Thay toàn bộ danh sách liên hệ của một chi nhánh (xoá cũ + thêm mới trong transaction).
     * @param array<int, array{full_name:string,title:string,phone:string,is_active:int}> $contacts
     */
    public static function replaceForBranch(int $branchId, array $contacts): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM branch_contacts WHERE branch_id = :id')->execute(['id' => $branchId]);
            $insert = $pdo->prepare(
                'INSERT INTO branch_contacts (branch_id, full_name, title, phone, sort_order, is_active)
                 VALUES (:branch_id, :full_name, :title, :phone, :sort_order, :is_active)'
            );
            $order = 0;
            foreach ($contacts as $contact) {
                $order++;
                $insert->execute([
                    'branch_id'  => $branchId,
                    'full_name'  => $contact['full_name'],
                    'title'      => $contact['title'] !== '' ? $contact['title'] : null,
                    'phone'      => $contact['phone'],
                    'sort_order' => $order,
                    'is_active'  => (int) ($contact['is_active'] ?? 1),
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Số điện thoại đầu tiên đang bật của chi nhánh (dùng làm nút gọi nhanh). */
    public static function primaryPhone(int $branchId): ?string
    {
        $contacts = self::byBranch($branchId);
        return $contacts !== [] ? (string) $contacts[0]['phone'] : null;
    }

    /** Chuẩn hoá số để dùng trong href="tel:". */
    public static function telHref(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        return $digits;
    }
}
