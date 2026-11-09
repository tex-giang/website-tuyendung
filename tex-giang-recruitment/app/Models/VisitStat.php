<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Thống kê lượt truy cập theo ngày + theo thiết bị. */
final class VisitStat
{
    /** @return array<int, array<string, mixed>> */
    public static function byRange(int $days = 30): array
    {
        $days = max(1, min(366, $days));
        $stmt = Database::connection()->prepare(
            'SELECT * FROM visit_stats WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             ORDER BY stat_date DESC'
        );
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<string, int> */
    public static function summary(int $days = 30): array
    {
        $days = max(1, min(366, $days));
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(SUM(total), 0) AS total,
                    COALESCE(SUM(mobile), 0) AS mobile,
                    COALESCE(SUM(desktop), 0) AS desktop,
                    COALESCE(SUM(tablet), 0) AS tablet
             FROM visit_stats WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)'
        );
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch() ?: [];
        return [
            'total'   => (int) ($row['total'] ?? 0),
            'mobile'  => (int) ($row['mobile'] ?? 0),
            'desktop' => (int) ($row['desktop'] ?? 0),
            'tablet'  => (int) ($row['tablet'] ?? 0),
        ];
    }

    /** @return array<string, int> total hôm nay / hôm qua / 7 ngày / 30 ngày / tổng cộng */
    public static function quick(): array
    {
        $pdo = Database::connection();
        $today = (int) $pdo->query('SELECT COALESCE(total,0) FROM visit_stats WHERE stat_date = CURDATE()')->fetchColumn();
        $yesterday = (int) $pdo->query('SELECT COALESCE(total,0) FROM visit_stats WHERE stat_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->fetchColumn();
        $week = (int) $pdo->query('SELECT COALESCE(SUM(total),0) FROM visit_stats WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)')->fetchColumn();
        $month = (int) $pdo->query('SELECT COALESCE(SUM(total),0) FROM visit_stats WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)')->fetchColumn();
        $all = (int) $pdo->query('SELECT COALESCE(SUM(total),0) FROM visit_stats')->fetchColumn();

        $devices = $pdo->query(
            'SELECT COALESCE(SUM(mobile),0) m, COALESCE(SUM(desktop),0) d, COALESCE(SUM(tablet),0) t FROM visit_stats'
        )->fetch() ?: [];

        return [
            'today'     => $today,
            'yesterday' => $yesterday,
            'week'      => $week,
            'month'     => $month,
            'all'       => $all,
            'mobile'    => (int) ($devices['m'] ?? 0),
            'desktop'   => (int) ($devices['d'] ?? 0),
            'tablet'    => (int) ($devices['t'] ?? 0),
        ];
    }

    /** Chuỗi ngày liên tục cho biểu đồ (điền 0 cho ngày không có lượt). */
    public static function chart(int $days = 14): array
    {
        $days = max(1, min(90, $days));
        $rows = self::byRange($days);
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['stat_date']] = (int) $row['total'];
        }
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $out[] = ['date' => $date, 'total' => $map[$date] ?? 0];
        }
        return $out;
    }

    /**
     * Số liệu hiển thị ở footer công khai: ngày hiện tại + tổng lượt truy cập.
     * @return array{date:string, today:int, month:int, total:int}
     */
    public static function footer(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $pdo = Database::connection();
            $total = (int) $pdo->query('SELECT COALESCE(SUM(total),0) FROM visit_stats')->fetchColumn();
            $today = (int) $pdo->query('SELECT COALESCE(total,0) FROM visit_stats WHERE stat_date = CURDATE()')->fetchColumn();
            $month = (int) $pdo->query("SELECT COALESCE(SUM(total),0) FROM visit_stats WHERE stat_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
        } catch (\Throwable $e) {
            $total = 0;
            $today = 0;
            $month = 0;
        }
        return $cache = [
            'date'  => date('d/m/Y'),
            'today' => $today,
            'month' => $month,
            'total' => $total,
        ];
    }

    public static function hasData(): bool
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM visit_stats')->fetchColumn() > 0;
    }
}
