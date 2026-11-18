-- ============================================================================
-- Bổ sung bộ phận (categories) cho 4 poster tuyển dụng tháng 11/2026.
-- Import tin tuyển dụng chỉ nhận bộ phận ĐÃ CÓ trong bảng categories,
-- nên cần thêm các bộ phận dưới đây trước khi import file Excel.
-- Chạy được nhiều lần (INSERT IGNORE theo slug duy nhất).
-- ============================================================================

INSERT IGNORE INTO categories (slug, name, sort_order) VALUES
  ('xuong-in',     'Xưởng in',   6),
  ('phong-cat',    'Phòng cắt',  7),
  ('may-mau',      'May mẫu',    8),
  ('ky-thuat-may', 'Kỹ thuật may', 9),
  ('bao-ve',       'Bảo vệ',     10),
  ('ky-thuat',     'Kỹ thuật',   11);
