-- Migration: trạng thái "Hết hạn" cho tin tuyển dụng + số ngày nhận hồ sơ mặc định
-- Chạy: mysql -u root --default-character-set=utf8mb4 tex_giang_recruitment < file.sql

ALTER TABLE jobs
  MODIFY status ENUM('draft','published','paused','closed','expired') NOT NULL DEFAULT 'draft';

INSERT INTO settings (`key`, `value`, `group`)
VALUES ('jobs_deadline_days', '30', 'general')
ON DUPLICATE KEY UPDATE `key` = `key`;
