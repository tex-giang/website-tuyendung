-- Migration: menu website lưu trong DB (bật/tắt hiển thị ngoài public)
-- Chạy: mysql -u root --default-character-set=utf8mb4 tex_giang_recruitment < file.sql

CREATE TABLE IF NOT EXISTS menus (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  location enum('header','footer') NOT NULL DEFAULT 'header',
  label varchar(120) NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  sort_order smallint NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY location_active (location, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menus (location, label, url, sort_order) VALUES
  ('header', 'Trang chủ',    '',               1),
  ('header', 'Việc làm',     'jobs',           2),
  ('header', 'Về chúng tôi', 'about',          3),
  ('header', 'Phúc lợi',     'why-tex-giang',  4),
  ('header', 'Tin tức',      'news',           5),
  ('header', 'Liên hệ',      'contact',        6),
  ('footer', 'Trang chủ',    '',               1),
  ('footer', 'Việc làm',     'jobs',           2),
  ('footer', 'Về chúng tôi', 'about',          3),
  ('footer', 'Phúc lợi',     'why-tex-giang',  4),
  ('footer', 'Tin tức',      'news',           5),
  ('footer', 'Liên hệ',      'contact',        6);
