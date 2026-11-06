<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Kết nối PDO dùng chung (singleton theo request).
 * PDO + prepared statement, tắt emulate để MySQL xử lý tham số thật.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $name = env('DB_NAME', '');
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            self::$connection = new PDO($dsn, env('DB_USER', ''), env('DB_PASS', ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        }

        return self::$connection;
    }

    /** Chạy trong transaction, tự rollback khi có lỗi. */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
