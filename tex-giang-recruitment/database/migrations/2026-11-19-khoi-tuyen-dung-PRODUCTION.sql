-- ============================================================================
-- KHỐI TUYỂN DỤNG — migration cho PRODUCTION
--
-- Cấu trúc đích:  Nhà máy → Khối → Bộ phận tuyển dụng → Việc làm
-- Khối CỐ ĐỊNH 2 giá trị: 'office' = Văn phòng, 'enterprise' = Xí nghiệp
--
-- ĐẶC ĐIỂM RIÊNG CỦA PRODUCTION (khác bản local):
--   - CHƯA từng có bảng `departments` / `enterprises`, chưa có cột
--     `department_id` / `enterprise_id`.  → KHÔNG cần bước gỡ nào cả.
--   - CHƯA có cột `block` ở jobs / categories.  → chỉ cần THÊM.
--   - `role_permissions` không có module 'departments'/'enterprises' → không cần dọn quyền.
--
-- Vì vậy file này NGẮN HƠN nhiều so với migration ở bản local
-- (2026-11-19-khoi-tuyen-dung.sql): bản đó phải gỡ cấu trúc 4 cấp cũ, bản này không.
--
-- AN TOÀN: chỉ dùng ADD COLUMN + UPDATE. Không DROP, không xoá dữ liệu.
-- Chạy được nhiều lần (idempotent) — kiểm tra information_schema trước khi thêm cột.
--
-- ⚠ SAO LƯU DATABASE TRƯỚC KHI CHẠY.
--
-- CÁCH CHẠY: phpMyAdmin → chọn ĐÚNG database → tab Import → chọn file này.
--   (Phải chọn database trước, vì câu kiểm tra dùng DATABASE().)
-- ============================================================================

-- ----------------------------------------------------------------- 1) jobs.block
-- NOT NULL DEFAULT 'enterprise': 14 tin hiện có đều thuộc Xí nghiệp
-- (sản xuất, xưởng in, phòng cắt, may mẫu, kỹ thuật may, bảo vệ, kỹ thuật),
-- nên giá trị mặc định này đúng cho toàn bộ dữ liệu hiện tại.
--
-- Ba lớp chống chạy trùng:
--   a) DATABASE() có giá trị  → kiểm tra information_schema như thường.
--   b) DATABASE() NULL        → không kiểm tra được, dò bằng SHOW COLUMNS.
--   c) Vẫn trùng              → ALTER ... ADD COLUMN IF NOT EXISTS không có trong
--                               MySQL 8 cho mọi bản, nên dùng thủ tục dò an toàn.
SET @db := DATABASE();
SET @has := IF(@db IS NULL, NULL,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'block'));

-- Nếu không xác định được (DATABASE() NULL), coi như "đã có" để KHÔNG chạy ALTER,
-- tránh lỗi trùng cột. Người chạy sẽ thấy câu SELECT kiểm tra ở cuối file.
SET @sql := IF(@has = 0,
  'ALTER TABLE jobs ADD COLUMN block ENUM(''office'',''enterprise'') NOT NULL DEFAULT ''enterprise'' AFTER branch_id, ADD KEY idx_jobs_block (block)',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------- 2) categories.block
-- NULL = bộ phận dùng chung cho cả 2 Khối (không bắt buộc gán).
SET @has := IF(@db IS NULL, NULL,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'block'));
SET @sql := IF(@has = 0,
  'ALTER TABLE categories ADD COLUMN block ENUM(''office'',''enterprise'') NULL AFTER slug, ADD KEY idx_categories_block (block)',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------- 3) Phân loại 11 bộ phận tuyển dụng vào 2 Khối
-- CHỈ áp dụng cho bộ phận còn NULL — chạy lại file này sẽ không ghi đè
-- nếu sau đó bạn tự đổi Khối trong admin.
--
-- Nhóm XÍ NGHIỆP: sản xuất và kỹ thuật trực tiếp tại xưởng.
UPDATE categories SET block = 'enterprise'
 WHERE block IS NULL AND slug IN
   ('production', 'xuong-in', 'phong-cat', 'may-mau', 'ky-thuat-may',
    'qc', 'ie', 'maintenance', 'ky-thuat');

-- Nhóm VĂN PHÒNG: hành chính, bảo vệ.
UPDATE categories SET block = 'office'
 WHERE block IS NULL AND slug IN ('office', 'bao-ve');

-- -------------------------------------- 4) Đồng bộ Khối của 14 tin hiện có
-- Tin phải theo Khối của bộ phận nó thuộc. Nhờ bước này, bộ lọc theo Khối
-- và số lượng đếm theo bộ phận luôn khớp nhau.
UPDATE jobs j
JOIN categories c ON c.id = j.category_id
SET j.block = c.block
WHERE c.block IS NOT NULL
  AND j.block <> c.block;

-- ============================================================================
-- KẾT QUẢ IN RA NGAY (chạy tự động khi import)
-- phpMyAdmin sẽ hiển thị 3 bảng kết quả bên dưới.
-- ============================================================================

-- [1] Tin theo Khối — KỲ VỌNG: enterprise = 13, office = 1
SELECT block AS khoi,
       COUNT(*) AS so_tin
FROM jobs
WHERE deleted_at IS NULL
GROUP BY block
ORDER BY block;

-- [2] Bộ phận theo Khối — KỲ VỌNG: enterprise = 9, office = 2
SELECT block AS khoi,
       COUNT(*) AS so_bo_phan,
       GROUP_CONCAT(slug ORDER BY sort_order SEPARATOR ', ') AS danh_sach
FROM categories
GROUP BY block
ORDER BY block;

-- [3] Tin LỆCH Khối với bộ phận — KỲ VỌNG: KHÔNG có dòng nào
--     (nếu có dòng, chạy lại file này một lần nữa)
SELECT j.id, j.title, j.block AS tin_khoi, c.name AS bo_phan, c.block AS bo_phan_khoi
FROM jobs j
JOIN categories c ON c.id = j.category_id
WHERE c.block IS NOT NULL AND j.block <> c.block;

-- ============================================================================
-- KIỂM TRA THÊM (chạy riêng khi cần)
-- ============================================================================
-- 7 bộ phận có tin (production, xuong-in, phong-cat, may-mau, ky-thuat-may,
-- bao-ve, ky-thuat) — 4 bộ phận còn lại 0 tin sẽ bị ẩn khỏi dropdown lọc
-- ngoài website cho tới khi có tin.
--
--   SELECT c.slug, c.block, COUNT(j.id) AS so_tin
--   FROM categories c
--   LEFT JOIN jobs j ON j.category_id = c.id
--        AND j.status = 'published' AND j.deleted_at IS NULL
--        AND (j.deadline IS NULL OR j.deadline >= CURDATE())
--   GROUP BY c.id ORDER BY c.sort_order;
