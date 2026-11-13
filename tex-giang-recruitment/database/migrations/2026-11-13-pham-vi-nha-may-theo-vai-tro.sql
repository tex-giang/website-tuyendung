-- ============================================================================
-- Phân quyền theo nhà máy cho VAI TRÒ (bổ sung cho phạm vi theo tài khoản)
-- Chạy được nhiều lần (idempotent): không lỗi nếu đã chạy trước đó.
-- ============================================================================

-- 1) Phạm vi nhà máy của vai trò ---------------------------------------------
SET @has_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'all_branches'
);
SET @sql := IF(
  @has_col = 0,
  'ALTER TABLE roles ADD COLUMN all_branches TINYINT(1) NOT NULL DEFAULT 1 AFTER description',
  'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

-- 3) 3 vai trò mẫu theo nhà máy (chỉ tạo nếu chưa có) ------------------------
INSERT IGNORE INTO roles (name, description, all_branches) VALUES
  ('quan_tri_nha_may_tan_huong', 'Quản trị nhà máy Tân Hương', 0),
  ('quan_tri_nha_may_cai_lay',   'Quản trị nhà máy Cai Lậy',   0),
  ('quan_tri_nha_may_cho_gao',   'Quản trị nhà máy Chợ Gạo',   0);

INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, m.module, m.can_view, m.can_create, m.can_update, m.can_delete
FROM roles r
JOIN (
  SELECT 'dashboard' AS module, 1 AS can_view, 0 AS can_create, 0 AS can_update, 0 AS can_delete UNION ALL
  SELECT 'jobs', 1, 1, 1, 0 UNION ALL
  SELECT 'applications', 1, 0, 1, 0 UNION ALL
  SELECT 'profile', 1, 0, 1, 0
) m
WHERE r.name IN ('quan_tri_nha_may_tan_huong', 'quan_tri_nha_may_cai_lay', 'quan_tri_nha_may_cho_gao');

INSERT IGNORE INTO role_branches (role_id, branch_id)
SELECT r.id, b.id FROM roles r JOIN branches b
WHERE (r.name = 'quan_tri_nha_may_tan_huong' AND b.slug = 'tan-huong')
   OR (r.name = 'quan_tri_nha_may_cai_lay'   AND b.slug = 'cai-lay')
   OR (r.name = 'quan_tri_nha_may_cho_gao'   AND b.slug = 'cho-gao');
