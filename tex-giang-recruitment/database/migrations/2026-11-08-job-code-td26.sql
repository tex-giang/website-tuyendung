-- Chuyển toàn bộ mã tin tuyển dụng sang định dạng TD<yy>-0001 (TD26-0001, TD26-0002, ...)
-- Chạy: mysql -u root --default-character-set=utf8mb4 tex_giang_recruitment < file.sql

SET @n := 0;
UPDATE jobs
SET code = CONCAT('TD26-', LPAD(@n := @n + 1, 4, '0'))
ORDER BY id;
