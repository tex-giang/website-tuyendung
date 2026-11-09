-- ============================================================================
--  MIGRATION: người liên hệ tuyển dụng theo từng khu sản xuất (chi nhánh)
--  Ngày: 2026-11-07
--  Chạy trên database hiện có:
--    mysql -u ... --default-character-set=utf8mb4 tex_giang_recruitment < database/migrations/2026-11-07-branch-contacts.sql
-- ============================================================================

CREATE TABLE IF NOT EXISTS branch_contacts (
  id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id  SMALLINT UNSIGNED NOT NULL,
  full_name  VARCHAR(120) NOT NULL,
  title      VARCHAR(80)  NULL,
  phone      VARCHAR(30)  NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_branch_contacts_branch (branch_id, sort_order),
  CONSTRAINT fk_branch_contacts_branch FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu khởi tạo từ danh sách liên hệ đang hiển thị ở trang Liên hệ
INSERT INTO branch_contacts (branch_id, full_name, title, phone, sort_order)
SELECT b.id, x.full_name, x.title, x.phone, x.sort_order
FROM (
  SELECT 'tan-huong' AS slug, 'Chị Trân'  AS full_name, 'Phụ trách tuyển dụng' AS title, '0913 131 090' AS phone, 1 AS sort_order
  UNION ALL SELECT 'tan-huong', 'Chị Ngân',  'Phụ trách tuyển dụng', '0332 629 650', 2
  UNION ALL SELECT 'tan-huong', 'Anh Linh',  'Phụ trách tuyển dụng', '0945 609 942', 3
  UNION ALL SELECT 'cho-gao',   'Chị Tuyết', 'Phụ trách tuyển dụng', '0963 181 431', 1
  UNION ALL SELECT 'cho-gao',   'Anh Sơn',   'Phụ trách tuyển dụng', '0924 772 499', 2
  UNION ALL SELECT 'cai-lay',   'Anh Nguyên','Phụ trách tuyển dụng', '0938 583 943', 1
  UNION ALL SELECT 'cai-lay',   'Chị Hằng',  'Phụ trách tuyển dụng', '0908 813 822', 2
) x
JOIN branches b ON b.slug = x.slug
WHERE NOT EXISTS (SELECT 1 FROM branch_contacts);
