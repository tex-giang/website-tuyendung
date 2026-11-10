-- Migration: nội dung trang Về chúng tôi + Phúc lợi (bảng riêng) và bản đồ theo khu
-- Chạy: mysql -u root --default-character-set=utf8mb4 tex_giang_recruitment < file.sql

ALTER TABLE branches
  ADD COLUMN map_link VARCHAR(500) NULL AFTER map_url;

UPDATE branches SET map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3923.203881427061!2d106.3586310739038!3d10.484587964511139!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310ab768cef00851%3A0x75cf2c4b6dcf43cd!2zQ8O0bmcgVHkgQ-G7lSBQaOG6p24gVGV4LUdpYW5n!5e0!3m2!1svi!2s!4v1793765708852!5m2!1svi!2s', map_link = 'https://maps.app.goo.gl/ju91J21xxb4KCDEe8' WHERE slug = 'tan-huong';
UPDATE branches SET map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3925.2436624600177!2d106.52494427390207!3d10.32237476740696!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310aadd3a04d3db5%3A0x6df16003b97e9aba!2zQ8O0bmcgVHkgQ1AgVGV4LUdpYW5nIGNoaSBuaMOhbmggQ2jhu6MgR-G6oW8!5e0!3m2!1svi!2s!4v1793765777202!5m2!1svi!2s', map_link = 'https://maps.app.goo.gl/ouoWhsTAtT7F4ma28' WHERE slug = 'cho-gao';
UPDATE branches SET map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6832.276131181911!2d106.08859122953558!3d10.41266722163212!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310a97c35289cfa5%3A0x38fbaef867a1615!2zQ8O0bmcgdHkgY-G7lSBwaOG6p24gVEVYIC0gR0lBTkc!5e0!3m2!1svi!2s!4v1793765738154!5m2!1svi!2s', map_link = 'https://maps.app.goo.gl/Nvd2xR3BMxEWgCSz7' WHERE slug = 'cai-lay';

CREATE TABLE IF NOT EXISTS about_milestones (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  year_label varchar(40) NOT NULL,
  text varchar(500) NOT NULL,
  is_highlight tinyint(1) NOT NULL DEFAULT 0,
  sort_order smallint NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS about_values (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  title varchar(120) NOT NULL,
  body varchar(800) NOT NULL,
  sort_order smallint NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS about_certifications (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  label varchar(200) NOT NULL,
  sort_order smallint NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_groups (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  title varchar(200) NOT NULL,
  icon varchar(80) NOT NULL DEFAULT 'check2',
  intro varchar(500) NOT NULL DEFAULT '',
  sort_order smallint NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_items (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  group_id smallint unsigned NOT NULL,
  text varchar(500) NOT NULL,
  sort_order smallint NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY group_id (group_id),
  CONSTRAINT fk_benefit_items_group FOREIGN KEY (group_id) REFERENCES benefit_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_comparisons (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  label varchar(120) NOT NULL,
  tex_giang varchar(300) NOT NULL,
  other_value varchar(300) NOT NULL,
  sort_order smallint NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO about_milestones (year_label, text, is_highlight, sort_order) VALUES
  ('2007', 'Thành lập, khởi công nhà máy trong KCN Tân Hương.', 0, 1),
  ('2012', 'Đạt chứng nhận SA8000.', 0, 2),
  ('2014', 'Bổ sung công nghệ laser cut và dán (lamination).', 0, 3),
  ('03/2015', 'Khai trương nhà máy Cai Lậy, cách trụ sở 40 km; đạt ISO 9001 do SGS cấp.', 0, 4),
  ('2016', 'Đạt OEKO-TEX STEP.', 0, 5),
  ('2021–2022', 'Mở nhà máy Chợ Gạo; tham gia Higg FEM và FSLM.', 0, 6),
  ('08/2024', 'Nhận Nhà máy Tân Hương 2 rộng 4,8 ha trong KCN Tân Hương.', 0, 7),
  ('Hiện tại', '3 khu sản xuất: Tân Hương, Cai Lậy và Chợ Gạo; khoảng 3.500 lao động.', 1, 8);

INSERT INTO about_values (title, body, sort_order) VALUES
  ('Tầm nhìn', 'Trở thành doanh nghiệp may mặc xuất khẩu dẫn đầu khu vực miền Nam, tiên phong sản xuất xanh và là nơi làm việc đáng gắn bó với người lao động.', 1),
  ('Sứ mệnh', 'Giao đúng hẹn những lô hàng đạt chuẩn quốc tế, đồng thời nâng cao thu nhập và đời sống của công nhân viên tại Đồng Tháp.', 2),
  ('Giá trị cốt lõi', 'Tâm — Tín — Tinh — Đoàn — Nghĩa: tận tâm với việc, giữ chữ tín với khách hàng, tinh gọn trong sản xuất, đoàn kết nội bộ và nghĩa tình với người lao động.', 3);

INSERT INTO about_certifications (label, sort_order) VALUES
  ('SA8000 — trách nhiệm xã hội (từ 2012)', 1),
  ('ISO 9001 — do SGS chứng nhận (từ 2015)', 2),
  ('OEKO-TEX STEP (từ 2016)', 3),
  ('Higg FEM & FSLM (từ 2022)', 4);

INSERT INTO benefit_groups (title, icon, intro, sort_order) VALUES
  ('Môi trường làm việc an toàn, mát', 'shield-check', 'Nhà xưởng tại KCN Tân Hương có điều hòa, ánh sáng chuẩn bảo vệ mắt, hệ thống hút bụi vải và PCCC tự động.', 1),
  ('Thu nhập minh bạch, trả đúng hạn', 'check2', 'Lương tính theo sản phẩm và năng suất chuyền; mức cụ thể trao đổi khi phỏng vấn. Ngoài lương còn các khoản thưởng và phụ cấp đang áp dụng.', 2),
  ('Đào tạo nghề miễn phí, có lương', 'briefcase', 'Công ty tuyển cả người chưa có tay nghề may; người mới được kèm tại chuyền theo từng công đoạn (1 kim, 2 kim, vắt sổ) trước khi vào định mức chính thức.', 3),
  ('Xe đưa rước và ăn ca miễn phí', 'bus-front', 'Công ty bố trí xe đưa đón công nhân ở xa miễn phí giữa các nhà máy Tân Hương, Cai Lậy và Chợ Gạo.', 4);

INSERT INTO benefit_items (group_id, text, sort_order) VALUES
  (1, 'Đạt SA8000 từ 2012 — trách nhiệm xã hội.', 1),
  (1, 'ISO 9001 do SGS chứng nhận từ 2015; OEKO-TEX STEP từ 2016.', 2),
  (1, 'Tham gia Higg FEM & FSLM từ 2022; điện mặt trời tại tất cả nhà máy.', 3),
  (2, 'Thưởng tháng 13; thưởng dịp 30/4, 1/5, 2/9, Tết Dương lịch.', 1),
  (2, 'Thưởng hoàn thành kế hoạch, thi đua, thâm niên; tiền nuôi con nhỏ, tiền xăng xe.', 2),
  (2, 'BHXH, BHYT, BHTN đầy đủ theo luật lao động.', 3),
  (3, 'Học tại xưởng, có tổ trưởng kỹ thuật hướng dẫn trực tiếp.', 1),
  (3, 'Lộ trình lên chuyền phó, chuyền trưởng, kỹ thuật mẫu.', 2),
  (3, 'Thời gian học và mức lương học việc trao đổi khi phỏng vấn.', 3),
  (4, 'Cơm trưa và cơm tăng ca tại nhà máy.', 1),
  (4, 'Đồng phục, bảo hộ lao động theo ca sản xuất.', 2),
  (4, 'Điểm đón và giờ xe do nhân sự xác nhận theo từng nhà máy.', 3);

INSERT INTO benefit_comparisons (label, tex_giang, other_value, sort_order) VALUES
  ('Thưởng tuyển mới', '2.000.000đ tiền mặt', 'Không có hoặc điều kiện khắt khe', 1),
  ('Người chưa biết may', 'Đào tạo miễn phí, có lương học việc', 'Chỉ tuyển thợ sẵn tay nghề', 2),
  ('Đi lại', 'Xe đưa rước miễn phí nhiều tuyến', 'Tự túc phương tiện', 3),
  ('Bữa ăn ca', 'Miễn phí, đổi món mỗi ngày', 'Trừ vào lương', 4),
  ('Ngày trả lương', 'Mùng 10 hằng tháng qua tài khoản', 'Trễ hạn hoặc chia đợt', 5);

INSERT INTO settings (`key`, `value`, `group`) VALUES
  ('content_about_title', 'May xuất khẩu tại Tân Hương từ năm 2007', 'content'),
  ('content_about_lead', 'Công ty Cổ phần TEX-GIANG (TGI) thành lập tháng 3/2007, vận hành 3 khu sản xuất tại Tân Hương, Cai Lậy và Chợ Gạo.', 'content'),
  ('content_why_title', 'Vì sao chọn TEX-GIANG', 'content'),
  ('content_why_lead', 'Bốn điều người lao động hỏi nhiều nhất khi đến nhà máy: môi trường có an toàn không, thu nhập bao nhiêu, có được dạy nghề không, và đi lại thế nào.', 'content')
ON DUPLICATE KEY UPDATE `key` = `key`;
