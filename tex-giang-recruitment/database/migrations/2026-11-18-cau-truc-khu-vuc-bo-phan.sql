-- ============================================================================
-- Cấu trúc: Khu vực → Bộ phận (2 loại: Xí nghiệp | Phòng ban) → Bộ phận tuyển → Việc làm
--
-- Thay đổi so với migration trước:
--   1. Bảng `departments` (Bộ phận) — mỗi bộ phận thuộc 1 khu vực và có 1 LOẠI:
--        'enterprise' = Xí nghiệp   |   'office' = Phòng ban
--   2. `categories.department_id`  — Bộ phận tuyển thuộc bộ phận nào.
--   3. `jobs.department_id`        — tin tuyển dụng thuộc bộ phận nào.
--
-- Bảng `enterprises` cũ được gộp vào `departments` (loại = enterprise).
-- Chạy được nhiều lần (idempotent).
-- ============================================================================

-- ------------------------------------------------------------------ 1) Bộ phận
CREATE TABLE IF NOT EXISTS departments (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id   SMALLINT UNSIGNED NOT NULL,
  kind        ENUM('enterprise','office') NOT NULL DEFAULT 'enterprise',
  slug        VARCHAR(80)  NOT NULL,
  name        VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_departments_slug (slug),
  KEY idx_departments_branch (branch_id, kind, sort_order),
  CONSTRAINT fk_departments_branch FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------- 2) Chuyển dữ liệu từ enterprises
SET @has_old := (SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enterprises');

SET @sql := IF(@has_old = 1,
  'INSERT IGNORE INTO departments (id, branch_id, kind, slug, name, description, sort_order, is_active)
   SELECT id, branch_id, ''enterprise'', slug, name, description, sort_order, is_active FROM enterprises',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------- 3) Cột department_id trên bảng con
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'department_id');
SET @sql := IF(@has = 0,
  'ALTER TABLE categories ADD COLUMN department_id SMALLINT UNSIGNED NULL AFTER enterprise_id, ADD KEY idx_categories_department (department_id), ADD CONSTRAINT fk_categories_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'department_id');
SET @sql := IF(@has = 0,
  'ALTER TABLE jobs ADD COLUMN department_id SMALLINT UNSIGNED NULL AFTER enterprise_id, ADD KEY idx_jobs_department (department_id), ADD CONSTRAINT fk_jobs_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------- 4) Chuyển dữ liệu cũ sang cột mới
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'enterprise_id');
SET @sql := IF(@has_col = 1,
  'UPDATE jobs SET department_id = enterprise_id WHERE department_id IS NULL AND enterprise_id IS NOT NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'enterprise_id');
SET @sql := IF(@has_col = 1,
  'UPDATE categories SET department_id = enterprise_id WHERE department_id IS NULL AND enterprise_id IS NOT NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------- 5) Tạo bộ phận (Xí nghiệp) cho mỗi khu vực
-- Mỗi khu vực đang có được 1 xí nghiệp cùng tên để gán dữ liệu cũ, tránh mồ côi.
INSERT IGNORE INTO departments (branch_id, kind, slug, name, description, sort_order, is_active)
SELECT b.id, 'enterprise', b.slug, b.name, CONCAT('Xí nghiệp ', b.name), b.sort_order, 1
FROM branches b;

UPDATE jobs j
JOIN departments d ON d.branch_id = j.branch_id AND d.kind = 'enterprise'
SET j.department_id = d.id
WHERE j.department_id IS NULL;

-- --------------------------------------------- 6) Quyền cho module Bộ phận
INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, 'departments', 1, 1, 1, 1 FROM roles r WHERE r.name IN ('super_admin', 'hr_admin');
INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, 'departments', 1, 0, 0, 0 FROM roles r WHERE r.name = 'recruiter';

-- Bỏ dòng quyền của module cũ (đã gộp vào `departments`).
DELETE FROM role_permissions WHERE module IN ('enterprises');
