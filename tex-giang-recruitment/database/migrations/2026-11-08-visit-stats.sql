-- Migration: thống kê lượt truy cập theo ngày + dedupe theo thiết bị/trình duyệt
-- Chạy: mysql -u root --default-character-set=utf8mb4 tex_giang_recruitment < file.sql

CREATE TABLE IF NOT EXISTS visit_stats (
  stat_date date NOT NULL,
  total int unsigned NOT NULL DEFAULT 0,
  mobile int unsigned NOT NULL DEFAULT 0,
  desktop int unsigned NOT NULL DEFAULT 0,
  tablet int unsigned NOT NULL DEFAULT 0,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_devices (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  stat_date date NOT NULL,
  visitor_id char(32) NOT NULL,
  device_type enum('mobile','desktop','tablet') NOT NULL DEFAULT 'desktop',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_visit_day_device (stat_date, visitor_id),
  KEY idx_visit_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
