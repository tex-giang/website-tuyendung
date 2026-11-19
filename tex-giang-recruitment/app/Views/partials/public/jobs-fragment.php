<?php
/**
 * FRAGMENT trả về khi lọc/sắp xếp/phân trang bằng fetch trên trang /jobs.
 *
 * Chứa:
 *   - Các ô lọc dưới dạng CON TRỰC TIẾP (để JS đọc theo id và cập nhật từng ô;
 *     CSS grid ngang của #jobs-filter-form vì thế vẫn đúng).
 *   - [data-jobs-results]: danh sách tin + chips + phân trang.
 *
 * KHÔNG chứa <html>/header/footer, KHÔNG bọc wrapper quanh các ô lọc.
 * Dữ liệu vào giống public/jobs/index.
 */
require BASE_PATH . '/app/Views/partials/public/jobs-filter-fields.php';
require BASE_PATH . '/app/Views/partials/public/jobs-results.php';
