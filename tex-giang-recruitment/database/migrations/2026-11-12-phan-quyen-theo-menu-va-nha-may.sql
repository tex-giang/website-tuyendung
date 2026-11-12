-- ============================================================================
-- Phân quyền theo menu (xem / thêm / sửa / xoá) + phạm vi nhà máy
-- Chạy 1 lần trên database đã cài đặt. Idempotent ở mức có thể.
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
-- all_branches = 1: xem toàn bộ nhà máy (không giới hạn)
-- all_branches = 0: chỉ xem các nhà máy liệt kê trong user_branches
ALTER TABLE users
  ADD COLUMN all_branches TINYINT(1) NOT NULL DEFAULT 1 AFTER role_id;

CREATE TABLE IF NOT EXISTS user_branches (
  user_id   BIGINT UNSIGNED   NOT NULL,
  branch_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, branch_id),
  KEY idx_user_branches_branch (branch_id),
  CONSTRAINT fk_user_branches_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_user_branches_branch FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Dữ liệu mặc định: giữ đúng hành vi cũ -----------------------------------
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
  SELECT 'settings', 1, 0, 1, 0 UNION ALL
  SELECT 'users', 1, 1, 1, 1 UNION ALL
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

-- 4) Tài khoản cũ: mặc định xem toàn bộ nhà máy
UPDATE users SET all_branches = 1 WHERE all_branches IS NULL;
