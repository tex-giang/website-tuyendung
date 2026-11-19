-- ============================================================================
-- Khối tuyển dụng: Nhà máy → Khối → Bộ phận tuyển → Việc làm
--
-- Khối là CỐ ĐỊNH, chỉ có 2 giá trị:
--   'office'     = Văn phòng
--   'enterprise' = Xí nghiệp
--
-- Thay đổi so với migration 2026-11-18:
--   1. Thêm `jobs.block` ENUM('office','enterprise') NOT NULL.
--   2. Gỡ `jobs.department_id` và `categories.department_id`.
--   3. Xoá bảng `departments` và `enterprises` (đã gộp vào cột block).
--   4. `categories.block` — bộ phận tuyển thuộc Khối nào (NULL = dùng chung).
--   5. Phân loại lại 11 bộ phận tuyển hiện có vào 2 Khối.
--
-- Chạy được nhiều lần (idempotent). Chạy SAU schema.sql và các migration cũ.
-- ============================================================================

-- ----------------------------------------------------------------- 1) jobs.block
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'block');
SET @sql := IF(@has = 0,
  'ALTER TABLE jobs ADD COLUMN block ENUM(''office'',''enterprise'') NOT NULL DEFAULT ''enterprise'' AFTER branch_id, ADD KEY idx_jobs_block (block)',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------- 2) categories.block
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'block');
SET @sql := IF(@has = 0,
  'ALTER TABLE categories ADD COLUMN block ENUM(''office'',''enterprise'') NULL AFTER slug, ADD KEY idx_categories_block (block)',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------- 3) Phân loại bộ phận tuyển hiện có vào 2 Khối
-- Sản xuất / kỹ thuật tại xưởng → Xí nghiệp.
UPDATE categories SET block = 'enterprise'
 WHERE block IS NULL AND slug IN
   ('production', 'qc', 'ie', 'maintenance', 'xuong-in', 'phong-cat', 'may-mau', 'ky-thuat-may', 'ky-thuat');

-- Hành chính / bảo vệ / văn phòng → Văn phòng.
UPDATE categories SET block = 'office'
 WHERE block IS NULL AND slug IN ('office', 'bao-ve');

-- Bộ phận còn lại (thêm sau này) chưa xác định: để NULL = hiện ở cả 2 Khối.

-- ------------------------- 4) Gỡ khoá ngoại + cột cũ trên jobs và categories
SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs'
              AND COLUMN_NAME = 'department_id' AND REFERENCED_TABLE_NAME = 'departments' LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE jobs DROP FOREIGN KEY `', @fk, '`'), 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories'
              AND COLUMN_NAME = 'department_id' AND REFERENCED_TABLE_NAME = 'departments' LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE categories DROP FOREIGN KEY `', @fk, '`'), 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs'
              AND COLUMN_NAME = 'enterprise_id' AND REFERENCED_TABLE_NAME = 'enterprises' LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE jobs DROP FOREIGN KEY `', @fk, '`'), 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories'
              AND COLUMN_NAME = 'enterprise_id' AND REFERENCED_TABLE_NAME = 'enterprises' LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE categories DROP FOREIGN KEY `', @fk, '`'), 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Chuyển dữ liệu cũ trước khi bỏ cột: jobs.department_id trỏ về xí nghiệp
-- cùng tên nhà máy (do migration cũ tạo) → chỉ còn 2 loại office/enterprise.
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'department_id');
SET @sql := IF(@has = 1,
  'UPDATE jobs j JOIN departments d ON d.id = j.department_id
   SET j.block = d.kind WHERE j.block IS NULL OR j.block = ''''',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Bỏ index rồi mới bỏ được cột.
SET @idx := (SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_department' LIMIT 1);
SET @sql := IF(@idx IS NOT NULL, 'ALTER TABLE jobs DROP INDEX idx_jobs_department', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_enterprise' LIMIT 1);
SET @sql := IF(@idx IS NOT NULL, 'ALTER TABLE jobs DROP INDEX idx_jobs_enterprise', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND INDEX_NAME = 'idx_categories_department' LIMIT 1);
SET @sql := IF(@idx IS NOT NULL, 'ALTER TABLE categories DROP INDEX idx_categories_department', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND INDEX_NAME = 'idx_categories_enterprise' LIMIT 1);
SET @sql := IF(@idx IS NOT NULL, 'ALTER TABLE categories DROP INDEX idx_categories_enterprise', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'department_id');
SET @sql := IF(@has = 1, 'ALTER TABLE jobs DROP COLUMN department_id', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'enterprise_id');
SET @sql := IF(@has = 1, 'ALTER TABLE jobs DROP COLUMN enterprise_id', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'department_id');
SET @sql := IF(@has = 1, 'ALTER TABLE categories DROP COLUMN department_id', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'enterprise_id');
SET @sql := IF(@has = 1, 'ALTER TABLE categories DROP COLUMN enterprise_id', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------- 5) Xoá bảng departments và enterprises
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS enterprises;

-- --------------------------------------------- 6) Bỏ quyền module departments
-- Module này không còn tồn tại; Khối là cột cố định nên không cần màn hình riêng.
DELETE FROM role_permissions WHERE module IN ('departments', 'enterprises');
