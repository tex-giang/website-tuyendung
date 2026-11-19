-- ============================================================================
-- Đồng bộ Khối của tin tuyển dụng theo Khối của bộ phận.
--
-- VẤN ĐỀ: `jobs.block` (Khối của tin) và `categories.block` (Khối của bộ phận)
-- là hai cột độc lập, có thể lệch nhau. Lệch xảy ra khi:
--   - Migration 2026-11-18 gán mọi tin vào Khối 'enterprise' (hồi đó chỉ có
--     bảng departments với 1 loại), sau đó 2026-11-19 phân loại lại bộ phận
--     thành 2 Khối → tin thuộc bộ phận Văn phòng vẫn mang block 'enterprise'.
--   - Người dùng đổi Khối của tin nhưng không đổi bộ phận (hoặc ngược lại).
--
-- HẬU QUẢ: bộ lọc theo Khối và số lượng đếm theo bộ phận cho ra kết quả khác
-- nhau — dropdown ghi "Văn phòng & nhân sự (1)" nhưng chọn Khối Văn phòng ra 0 tin.
--
-- HƯỚNG SỬA: tin phải theo Khối của bộ phận nó thuộc. Chỉ áp dụng khi bộ phận
-- ĐÃ có Khối (categories.block NOT NULL); bộ phận dùng chung (NULL) thì giữ
-- nguyên Khối hiện tại của tin.
--
-- Chạy được nhiều lần (idempotent).
-- ============================================================================

-- Xem trước các dòng lệch (chạy tay nếu muốn kiểm tra trước khi sửa):
--   SELECT j.id, j.title, j.block AS tin_khoi, c.name AS bo_phan, c.block AS bo_phan_khoi
--   FROM jobs j JOIN categories c ON c.id = j.category_id
--   WHERE c.block IS NOT NULL AND j.block <> c.block;

UPDATE jobs j
JOIN categories c ON c.id = j.category_id
SET j.block = c.block,
    j.updated_at = NOW()
WHERE c.block IS NOT NULL
  AND j.block <> c.block;

-- Báo cáo số dòng còn lệch sau khi sửa (kỳ vọng 0).
SELECT COUNT(*) AS so_tin_con_lech
FROM jobs j
JOIN categories c ON c.id = j.category_id
WHERE c.block IS NOT NULL AND j.block <> c.block;
