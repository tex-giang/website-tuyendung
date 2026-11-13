-- ============================================================================
-- Phân quyền theo menu (xem / thêm / sửa / xoá) + phạm vi nhà máy theo tài khoản
-- Chạy được nhiều lần (idempotent): không lỗi nếu đã chạy trước đó.
-- ============================================================================

-- 1) Ma trận quyền theo vai trò × module -------------------------------------
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id    TINYINT UNSIGNED NOT NULL,
  module     VARCHAR(50)      NOT NULL,
  can_view   TINYINT(1)       NOT NULL DEFAULT 0,
  can_create TINYINT(1)       NOT NULL DEFAULT 0,
  can_update TINYINT(1)       NOT NULL DEFAULT 0,
  can_delete TINYINT(1)       NOT NULL DEFAULT 0,
  PRIMARY KEY (role_id, module),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Phạm vi nhà máy của từng tài khoản --------------------------------------
-- all_branches = 1: xem toàn bộ nhà máy; = 0: chỉ các nhà máy trong user_branches
SET @has_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'all_branches'
);
SET @sql := IF(
  @has_col = 0,
  'ALTER TABLE users ADD COLUMN all_branches TINYINT(1) NOT NULL DEFAULT 1 AFTER role_id',
  'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS user_branches (
  user_id   BIGINT UNSIGNED   NOT NULL,
  branch_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, branch_id),
  KEY idx_user_branches_branch (branch_id),
  CONSTRAINT fk_user_branches_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_user_branches_branch FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Quyền mặc định cho các vai trò có sẵn -----------------------------------
-- INSERT IGNORE: không ghi đè quyền đã chỉnh trong màn hình Phân quyền.
INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, m.module, m.can_view, m.can_create, m.can_update, m.can_delete
FROM roles r
JOIN (
  SELECT 'dashboard' AS module, 1 AS can_view, 0 AS can_create, 0 AS can_update, 0 AS can_delete UNION ALL
  SELECT 'jobs', 1, 1, 1, 1 UNION ALL
  SELECT 'applications', 1, 0, 1, 0 UNION ALL
  SELECT 'posts', 1, 1, 1, 1 UNION ALL
  SELECT 'banners', 1, 1, 1, 1 UNION ALL
  SELECT 'content', 1, 0, 1, 0 UNION ALL
  SELECT 'categories', 1, 1, 1, 1 UNION ALL
  SELECT 'contact', 1, 0, 1, 1 UNION ALL
  SELECT 'settings', 0, 0, 0, 0 UNION ALL
  SELECT 'users', 0, 0, 0, 0 UNION ALL
  SELECT 'audit', 1, 0, 0, 0 UNION ALL
  SELECT 'profile', 1, 0, 1, 0
) m
WHERE r.name IN ('super_admin', 'hr_admin');

-- recruiter: chỉ tuyển dụng, không xoá
INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, m.module, m.can_view, m.can_create, m.can_update, m.can_delete
FROM roles r
JOIN (
  SELECT 'dashboard' AS module, 1 AS can_view, 0 AS can_create, 0 AS can_update, 0 AS can_delete UNION ALL
  SELECT 'jobs', 1, 1, 1, 0 UNION ALL
  SELECT 'applications', 1, 0, 1, 0 UNION ALL
  SELECT 'profile', 1, 0, 1, 0
) m
WHERE r.name = 'recruiter';

-- 4) Tài khoản cũ: mặc định xem toàn bộ nhà máy ------------------------------
UPDATE users SET all_branches = 1 WHERE all_branches IS NULL;
