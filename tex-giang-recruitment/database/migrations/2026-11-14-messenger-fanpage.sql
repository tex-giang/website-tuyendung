-- ============================================================================
-- Nút chat Messenger fanpage (m.me) — chỉ thêm 1 dòng cấu hình.
-- Chạy được nhiều lần (INSERT IGNORE), không ảnh hưởng dữ liệu khác.
-- ============================================================================

INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES
  ('messenger', 'https://m.me/107200507519091', 'social');
