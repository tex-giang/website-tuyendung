-- Thêm địa điểm phỏng vấn cho cơ sở dữ liệu đã cài đặt.
ALTER TABLE applications ADD COLUMN appointment_location VARCHAR(255) NULL AFTER appointment_at;
