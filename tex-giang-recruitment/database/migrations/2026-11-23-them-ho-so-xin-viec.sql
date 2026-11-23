-- ============================================================================
-- Thêm trường "Hồ sơ xin việc" cho tin tuyển dụng (jobs.application_documents)
-- Giấy tờ ứng viên cần chuẩn bị — LONGTEXT NULL, mỗi dòng là 1 giấy tờ,
-- hiển thị thành mục "Hồ sơ xin việc" ở trang chi tiết tin tuyển dụng.
--
-- Chạy được nhiều lần (idempotent): không lỗi nếu cột đã tồn tại.
-- Chạy: php -d extension=pdo_mysql -d extension=mbstring tools/run-sql.php database/migrations/2026-11-23-them-ho-so-xin-viec.sql
-- Hoặc import trực tiếp bằng phpMyAdmin (charset utf8mb4).
-- ============================================================================

SET @has_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'application_documents'
);
SET @sql := IF(
  @has_col = 0,
  'ALTER TABLE jobs ADD COLUMN application_documents LONGTEXT NULL AFTER benefits',
  'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
