-- Gộp menu header/footer thành MỘT danh sách dùng chung.
-- Chạy: mysql -u root --default-character-set=utf8mb4 tex_giang_recruitment < file.sql

DELETE FROM menus WHERE location = 'footer';
ALTER TABLE menus DROP COLUMN location;
