<?php
/**
 * Chạy một file .sql qua PDO, tách câu lệnh theo dấu chấm phẩy.
 *
 * Vì sao cần: trên Windows, `mysql` client của XAMPP thường thiếu plugin
 * caching_sha2_password.dll nên không kết nối được user MySQL 8. Script này
 * dùng đúng kết nối PDO của ứng dụng (đọc .env), chạy được mọi câu lệnh DDL/DML.
 *
 *   php -d extension=pdo_mysql -d extension=mbstring tools/run-sql.php <file.sql>
 *
 * Lưu ý: chỉ hiểu cú pháp SQL thông thường. Câu lệnh chứa dấu ';' BÊN TRONG
 * chuỗi vẫn được xử lý đúng (có theo dõi trạng thái quote), nhưng không hỗ trợ
 * DELIMITER / stored procedure. Các migration của dự án không dùng 2 thứ đó.
 */
declare(strict_types = 1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/app/bootstrap.php';

$file = $argv[1] ?? '';
if ($file === '') {
    fwrite(STDERR, "Dùng: php tools/run-sql.php <đường-dẫn-file.sql>\n");
    exit(1);
}
$path = is_file($file) ? $file : dirname(__DIR__) . '/' . ltrim($file, '/');
if (!is_file($path)) {
    fwrite(STDERR, "Không tìm thấy file SQL: {$file}\n");
    exit(1);
}

$sql = (string) file_get_contents($path);

// Bỏ comment dòng (-- ...) để preview câu lệnh sạch; nội dung trong chuỗi giữ nguyên.
$sql = preg_replace('/^[ \t]*--.*$/m', '', $sql) ?? $sql;

$statements = [];
$buffer = '';
$inSingle = false;
$inDouble = false;
$length = strlen($sql);

for ($i = 0; $i < $length; $i++) {
    $char = $sql[$i];
    $prev = $i > 0 ? $sql[$i - 1] : '';

    if ($char === "'" && !$inDouble && $prev !== '\\') {
        $inSingle = !$inSingle;
    } elseif ($char === '"' && !$inSingle && $prev !== '\\') {
        $inDouble = !$inDouble;
    }

    if ($char === ';' && !$inSingle && !$inDouble) {
        $statements[] = trim($buffer);
        $buffer = '';
        continue;
    }
    $buffer .= $char;
}
if (trim($buffer) !== '') {
    $statements[] = trim($buffer);
}

$pdo = App\Core\Database::connection();
$ok = 0;
$failed = 0;

foreach ($statements as $index => $statement) {
    if ($statement === '') {
        continue;
    }
    $preview = preg_replace('/\s+/', ' ', mb_substr($statement, 0, 64));
    try {
        $pdo->exec($statement);
        $ok++;
    } catch (Throwable $e) {
        $failed++;
        fwrite(STDERR, '[' . ($index + 1) . "] LỖI: {$preview}…\n    → " . $e->getMessage() . "\n");
    }
}

echo "Đã chạy {$ok} câu lệnh" . ($failed > 0 ? ", {$failed} lỗi" : ', không lỗi')
    . ' từ ' . basename($path) . PHP_EOL;
exit($failed > 0 ? 1 : 0);
