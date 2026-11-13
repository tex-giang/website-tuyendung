-- ============================================================================
-- Phân quyền theo nhà máy cho VAI TRÒ (bổ sung cho phạm vi theo tài khoản)
-- Chạy 1 lần trên database đã cài đặt.
-- ============================================================================

-- 1) Phạm vi nhà máy của vai trò
ALTER TABLE roles
  ADD COLUMN all_branches TINYINT(1) NOT NULL DEFAULT 1 AFTER description;

CREATE TABLE IF NOT EXISTS role_branches (
  role_id   TINYINT UNSIGNED  NOT NULL,
  branch_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, branch_id),
  KEY idx_role_branches_branch (branch_id),
  CONSTRAINT fk_role_branches_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
  CONSTRAINT fk_role_branches_branch FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Vai trò cũ: mặc định xem toàn bộ nhà máy (giữ nguyên hành vi trước đó)
UPDATE roles SET all_branches = 1 WHERE all_branches IS NULL;
