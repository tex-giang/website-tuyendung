<?php
/**
 * Nạp một file dump .sql vào database đang cấu hình trong .env.
 *
 *   php tools/import-sql.php <duong-dan-dump.sql> [--force]
 *
 * KHÁC run-sql.php: file này dùng cho dump lớn (CREATE + INSERT + khoá ngoại),
 * xử lý đúng thứ tự câu lệnh và tạm tắt kiểm tra khoá ngoại trong lúc nạp.
 * Không hỏi xác nhận — người gọi phải tự đảm bảo đã sao lưu.
 */
declare(strict_types = 1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/app/bootstrap.php';

$file = $argv[1] ?? '';
if ($file === '') {
    fwrite(STDERR, "Dùng: php tools/import-sql.php <duong-dan-dump.sql>\n");
    exit(1);
}
if (!is_file($file)) {
    fwrite(STDERR, "Không tìm thấy file: {$file}\n");
    exit(1);
}

$size = filesize($file);
printf("File: %s (%.1f KB)\n", basename($file), $size / 1024);

$sql = (string) file_get_contents($file);

// Bỏ BOM nếu có.
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;

// Comment điều kiện của mysqldump: `/*!40101 SET ... */;`
// Phải giữ NỘI DUNG bên trong và dấu ';' — nếu bỏ cả cụm, nhiều câu SET sẽ dính
// vào nhau thành một câu hỏng (đã gặp: "SET @OLD_... SET @OLD_...").
$sql = preg_replace_callback(
    '#/\*!\d*\s*(.*?)\s*\*/;#s',
    static fn(array $m): string => $m[1] . ';',
    $sql
) ?? $sql;

// Bỏ comment dòng, và comment khối thường (không phải comment điều kiện).
$sql = preg_replace('/^[ \t]*--.*$/m', '', $sql) ?? $sql;
$sql = preg_replace('#/\*(?!!).*?\*/#s', '', $sql) ?? $sql;

/** Tách câu lệnh theo ';' ở cấp ngoài, tôn trọng chuỗi. */
$statements = [];
$buf = '';
$inS = false;
$inD = false;
$inB = false;
$len = strlen($sql);
for ($i = 0; $i < $len; $i++) {
    $ch = $sql[$i];
    $prev = $i > 0 ? $sql[$i - 1] : '';
    if ($ch === "'" && !$inD && !$inB && $prev !== '\\') {
        $inS = !$inS;
    } elseif ($ch === '"' && !$inS && !$inB && $prev !== '\\') {
        $inD = !$inD;
    } elseif ($ch === '`' && !$inS && !$inD) {
        $inB = !$inB;
    }
    if ($ch === ';' && !$inS && !$inD && !$inB) {
        $statements[] = trim($buf);
        $buf = '';
        continue;
    }
    $buf .= $ch;
}
if (trim($buf) !== '') {
    $statements[] = trim($buf);
}

printf("Số câu lệnh: %d\n", count($statements));

$pdo = App\Core\Database::connection();
$dbName = (string) env('DB_NAME', '');

echo "Database đích: {$dbName}\n\n";

// Tắt kiểm tra khoá ngoại để nạp được theo bất kỳ thứ tự nào.
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('SET UNIQUE_CHECKS = 0');
$pdo->exec('SET autocommit = 0');

$ok = 0;
$skipped = 0;
$fail = 0;
$started = microtime(true);

try {
    foreach ($statements as $index => $st) {
        if ($st === '') {
            continue;
        }
        // Bỏ qua câu lệnh điều khiển không cần thiết hoặc do chính script này quản lý.
        if (preg_match('/^(CREATE DATABASE|USE |SET NAMES|SET time_zone|SET SQL_MODE|START TRANSACTION|COMMIT|BEGIN)/i', $st) === 1) {
            $skipped++;
            continue;
        }
        try {
            $pdo->exec($st);
            $ok++;
        } catch (Throwable $e) {
            $fail++;
            if ($fail <= 10) {
                fwrite(STDERR, '[' . ($index + 1) . '] LỖI: '
                    . mb_substr(preg_replace('/\s+/', ' ', $st), 0, 100) . "…\n    → " . $e->getMessage() . "\n");
            }
        }

        if ($ok % 200 === 0 && $ok > 0) {
            echo "  … đã chạy {$ok} câu lệnh\n";
        }
    }
    $pdo->exec('COMMIT');
} catch (Throwable $e) {
    $pdo->exec('ROLLBACK');
    fwrite(STDERR, "LỖI NGHIÊM TRỌNG, đã rollback: " . $e->getMessage() . "\n");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    exit(1);
}

$pdo->exec('SET autocommit = 1');
$pdo->exec('SET UNIQUE_CHECKS = 1');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

printf(
    "\nXong trong %.1fs: %d câu OK, %d bỏ qua, %d lỗi\n",
    microtime(true) - $started,
    $ok,
    $skipped,
    $fail
);

exit($fail > 0 ? 1 : 0);
