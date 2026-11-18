-- ============================================================================
-- Cấu trúc 4 cấp: Nhà máy → Xí nghiệp → Bộ phận tuyển → Việc làm
--
-- 1. Bảng mới `enterprises` (xí nghiệp), mỗi xí nghiệp thuộc 1 nhà máy.
-- 2. `categories.enterprise_id`  — bộ phận tuyển thuộc xí nghiệp nào.
-- 3. `jobs.enterprise_id`        — tin tuyển dụng thuộc xí nghiệp nào.
--
-- Chạy được nhiều lần (idempotent).
-- ============================================================================

-- ---------------------------------------------------------------- 1) Xí nghiệp
CREATE TABLE IF NOT EXISTS enterprises (
  id            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id     SMALLINT UNSIGNED NOT NULL,
  slug          VARCHAR(80)  NOT NULL,
  name          VARCHAR(120) NOT NULL,
  description   VARCHAR(255) NULL,
  sort_order    SMALLINT     NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enterprises_slug (slug),
  KEY idx_enterprises_branch (branch_id, sort_order),
  CONSTRAINT fk_enterprises_branch FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------- 2) Cột enterprise_id (bảng con)
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'enterprise_id');
SET @sql := IF(@has = 0,
  'ALTER TABLE categories ADD COLUMN enterprise_id SMALLINT UNSIGNED NULL AFTER id, ADD KEY idx_categories_enterprise (enterprise_id), ADD CONSTRAINT fk_categories_enterprise FOREIGN KEY (enterprise_id) REFERENCES enterprises (id) ON DELETE SET NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'enterprise_id');
SET @sql := IF(@has = 0,
  'ALTER TABLE jobs ADD COLUMN enterprise_id SMALLINT UNSIGNED NULL AFTER branch_id, ADD KEY idx_jobs_enterprise (enterprise_id), ADD CONSTRAINT fk_jobs_enterprise FOREIGN KEY (enterprise_id) REFERENCES enterprises (id) ON DELETE SET NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------- 3) Tạo xí nghiệp mặc định theo nhà máy
-- Mỗi nhà máy đang có được 1 xí nghiệp cùng tên để gán dữ liệu cũ vào, tránh
-- tin tuyển dụng bị mồ côi xí nghiệp sau khi nâng cấp.
INSERT IGNORE INTO enterprises (branch_id, slug, name, description, sort_order, is_active)
SELECT b.id, b.slug, b.name, CONCAT('Xí nghiệp ', b.name), b.sort_order, 1
FROM branches b;

-- ------------------------------------------- 4) Gán dữ liệu cũ vào xí nghiệp mặc định
UPDATE jobs j
JOIN enterprises e ON e.branch_id = j.branch_id
SET j.enterprise_id = e.id
WHERE j.enterprise_id IS NULL;

-- Bộ phận tuyển là danh mục DÙNG CHUNG cho mọi xí nghiệp (enterprise_id = NULL).
-- Chỉ gán enterprise_id khi muốn giới hạn một bộ phận vào đúng một xí nghiệp.
-- Vì vậy KHÔNG tự gán hàng loạt ở đây.

-- ------------------------------------------- 5) Quyền cho module Bộ phận
-- Module mới cần có dòng trong role_permissions, nếu không menu sẽ bị ẩn.
INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, 'departments', 1, 1, 1, 1 FROM roles r
WHERE r.name IN ('super_admin', 'hr_admin');

-- Nhân viên tuyển dụng: xem xí nghiệp, không sửa.
INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, 'departments', 1, 0, 0, 0 FROM roles r
WHERE r.name = 'recruiter';