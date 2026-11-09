<?php
/**
 * Cron: tự động chuyển tin tuyển dụng quá hạn sang trạng thái "Hết hạn".
 * Chạy định kỳ (cPanel → Cron Jobs), ví dụ mỗi giờ:
 *   /usr/local/bin/php /home/taikhoan/tex-giang-recruitment/tools/cron-expire-jobs.php
 *
 * Lưu ý: website cũng tự chạy việc này (lazy) khi có người xem trang tin/dashboard,
 * nên cron chỉ để chắc chắn cập nhật kể cả khi không có truy cập.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $changed = \App\Models\Job::syncExpired();
    echo '[' . date('Y-m-d H:i:s') . '] Đã chuyển ' . $changed . " tin quá hạn sang trạng thái Hết hạn.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] Lỗi: ' . $e->getMessage() . "\n");
    exit(1);
}
