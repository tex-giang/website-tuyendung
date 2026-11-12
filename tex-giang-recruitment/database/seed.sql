-- ============================================================================
-- TEX-GIANG RECRUITMENT — DỮ LIỆU KHỞI TẠO
-- Import SAU schema.sql. Nội dung lấy từ website tĩnh hiện tại (đã xác minh ở
-- FACT-SHEET.md). Không còn dữ liệu "demo ngẫu nhiên": đây là dữ liệu thật của
-- website, quản trị viên sửa/xoá/thêm tiếp trong trang quản trị.
-- Tài khoản quản trị mặc định: admin / Admin@123dev  → ĐỔI NGAY sau khi đăng nhập.
-- ============================================================================

SET NAMES utf8mb4;

-- 1. PHÂN QUYỀN -------------------------------------------------------------
INSERT INTO roles (name, description) VALUES
  ('super_admin', 'Quản trị toàn hệ thống'),
  ('hr_admin',    'Quản trị nhân sự, tin tuyển dụng và hồ sơ'),
  ('recruiter',   'Nhân viên tuyển dụng: xem và xử lý hồ sơ');

-- Ma trận quyền mặc định (xem / thêm / sửa / xoá). super_admin luôn toàn quyền.
INSERT INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, m.module, m.can_view, m.can_create, m.can_update, m.can_delete
FROM roles r
JOIN (
  SELECT 'dashboard' AS module, 1 AS can_view, 0 AS can_create, 0 AS can_update, 0 AS can_delete UNION ALL
  SELECT 'jobs', 1, 1, 1, 1 UNION ALL
  SELECT 'applications', 1, 0, 1, 0 UNION ALL
  SELECT 'posts', 1, 1, 1, 1 UNION ALL
  SELECT 'banners', 1, 1, 1, 1 UNION ALL
  SELECT 'content', 1, 0, 1, 0 UNION ALL
  SELECT 'categories', 1, 1, 1, 1 UNION ALL
  SELECT 'contact', 1, 0, 1, 1 UNION ALL
  SELECT 'settings', 1, 0, 1, 0 UNION ALL
  SELECT 'users', 1, 1, 1, 1 UNION ALL
  SELECT 'audit', 1, 0, 0, 0 UNION ALL
  SELECT 'profile', 1, 0, 1, 0
) m
WHERE r.name IN ('super_admin', 'hr_admin');

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_update, can_delete)
SELECT r.id, m.module, m.can_view, m.can_create, m.can_update, m.can_delete
FROM roles r
JOIN (
  SELECT 'dashboard' AS module, 1 AS can_view, 0 AS can_create, 0 AS can_update, 0 AS can_delete UNION ALL
  SELECT 'jobs', 1, 1, 1, 0 UNION ALL
  SELECT 'applications', 1, 0, 1, 0 UNION ALL
  SELECT 'profile', 1, 0, 1, 0
) m
WHERE r.name = 'recruiter';

-- 2. TÀI KHOẢN QUẢN TRỊ ĐẦU TIÊN -------------------------------------------
INSERT INTO users (role_id, username, password_hash, full_name, email, phone)
SELECT id, 'admin',
       '$2y$12$Agv9RuuGFaSD2hXZPJka9u8bAYunAjwcKJnJzOT59JoYy6LKyHCby',
       'Quản trị TEX-GIANG', 'nhansu@tex-giang.com.vn', '02733937274'
FROM roles WHERE name = 'super_admin';

-- 3. KHU SẢN XUẤT ----------------------------------------------------------
INSERT INTO branches (slug, name, address, phone, sort_order) VALUES
  ('tan-huong', 'Tân Hương', 'Lô BII-8, đường D3, KCN Tân Hương, Xã Tân Hương, Tỉnh Đồng Tháp', '02733937274', 1),
  ('cai-lay',   'Cai Lậy',   'Ấp Bình Quới, Xã Bình Phú, Tỉnh Đồng Tháp', '02733937274', 2),
('cho-gao',   'Chợ Gạo',   'Ấp Hòa Lợi Tiểu, Xã Bình Ninh, Tỉnh Đồng Tháp', '02733937274', 3);

-- 3b. NGƯỜI LIÊN HỆ TUYỂN DỤNG THEO KHU SẢN XUẤT ---------------------------
INSERT INTO branch_contacts (branch_id, full_name, title, phone, sort_order) VALUES
  ((SELECT id FROM branches WHERE slug='tan-huong'), 'Chị Trân',   'Phụ trách tuyển dụng', '0913 131 090', 1),
  ((SELECT id FROM branches WHERE slug='tan-huong'), 'Chị Ngân',   'Phụ trách tuyển dụng', '0332 629 650', 2),
  ((SELECT id FROM branches WHERE slug='tan-huong'), 'Anh Linh',   'Phụ trách tuyển dụng', '0945 609 942', 3),
  ((SELECT id FROM branches WHERE slug='cho-gao'),   'Chị Tuyết',  'Phụ trách tuyển dụng', '0963 181 431', 1),
  ((SELECT id FROM branches WHERE slug='cho-gao'),   'Anh Sơn',    'Phụ trách tuyển dụng', '0924 772 499', 2),
  ((SELECT id FROM branches WHERE slug='cai-lay'),   'Anh Nguyên', 'Phụ trách tuyển dụng', '0938 583 943', 1),
  ((SELECT id FROM branches WHERE slug='cai-lay'),   'Chị Hằng',   'Phụ trách tuyển dụng', '0908 813 822', 2);

-- 4. KHỐI PHÒNG BAN --------------------------------------------------------
INSERT INTO categories (slug, name, description, sort_order) VALUES
  ('production',  'Sản xuất may mặc',        'Công nhân may, tổ trưởng chuyền, học nghề', 1),
  ('qc',          'Kiểm soát chất lượng',    'QA/QC, kiểm phẩm, giám sát chất lượng',      2),
  ('ie',          'Kỹ thuật & định mức IE',  'Kỹ sư công nghiệp, định mức, mẫu rập',       3),
  ('maintenance', 'Bảo trì cơ điện',         'Bảo trì máy may, điện xưởng',                4),
  ('office',      'Văn phòng & nhân sự',     'Kế toán, merchandiser, hành chính, lái xe',  5);

-- 5. CHUYÊN MỤC TIN TỨC ----------------------------------------------------
INSERT INTO post_categories (slug, name, sort_order) VALUES
  ('tuyen-dung',           'Tuyển dụng',              1),
  ('nghe-nghiep',          'Nghề nghiệp',             2),
  ('dao-tao',              'Đào tạo',                 3),
  ('doi-song',             'Đời sống',                4),
  ('an-toan-chat-luong',   'An toàn – Chất lượng',    5),
  ('hoat-dong-noi-bo',     'Hoạt động nội bộ',        6);

-- 6. CẤU HÌNH WEBSITE ------------------------------------------------------
INSERT INTO settings (`key`, `value`, `group`) VALUES
  ('site_name',        'Tuyển dụng TEX-GIANG', 'general'),
  ('legal_name',       'Công ty Cổ phần TEX-GIANG (TGI)', 'general'),
  ('email',            'nhansu@tex-giang.com.vn', 'contact'),
  ('zalo',             '02733937274', 'contact'),
  ('facebook',         'https://www.facebook.com/profile.php?id=100063719192197', 'social'),
  ('youtube',          'https://www.youtube.com/', 'social'),
  ('website',          'https://tex-giang.com.vn', 'social'),
  ('seo_title',        'Tuyển dụng TEX-GIANG — Tân Hương, Cai Lậy, Chợ Gạo', 'seo'),
  ('seo_description',  'Cổng tuyển dụng của Công ty Cổ phần TEX-GIANG (TGI) . Xe đưa rước, cơm ca, thưởng tháng 13, BHXH đầy đủ.', 'seo'),
  ('jobs_per_page',    '6', 'general'),
  ('jobs_deadline_days', '30', 'general'),
  ('content_hero_title_1',  'Một công việc ổn định.', 'content'),
  ('content_hero_title_2',  'Thu nhập xứng đáng.', 'content'),
  ('content_hero_subtitle', 'Cổng tuyển dụng chính thức của TEX-GIANG — may mặc xuất khẩu tại 3 khu sản xuất Tân Hương, Cai Lậy, Chợ Gạo (Đồng Tháp).', 'content'),
  ('content_about_1', 'Công ty Cổ phần TEX-GIANG (TGI) thành lập tháng 3/2007, vận hành 3 khu sản xuất tại Tân Hương, Cai Lậy, Chợ Gạo; xuất khẩu sang châu Âu, Mỹ, Úc và New Zealand.', 'content'),
  ('content_about_2', 'Triết lý “Vì con người — Vì chất lượng — Vì tương lai bền vững”: môi trường làm việc an toàn, nhà xưởng thoáng mát, thu nhập xứng đáng với người lao động.', 'content'),
  ('content_footer_intro', 'Cổng tuyển dụng chính thức của Công ty Cổ phần TEX-GIANG.', 'content'),
  ('stat_1_value', '2007',    'content'), ('stat_1_label', 'Thành lập tại KCN Tân Hương', 'content'),
  ('stat_2_value', '3',       'content'), ('stat_2_label', 'Khu sản xuất tại Đồng Tháp', 'content'),
  ('stat_3_value', '3.500',   'content'), ('stat_3_label', 'Cán bộ công nhân viên', 'content'),
  ('stat_4_value', '8 triệu', 'content'), ('stat_4_label', 'Sản phẩm xuất khẩu mỗi năm', 'content');

-- 7. BANNER ---------------------------------------------------------------
INSERT INTO banners (name, type, title, subtitle, description, image_path, image_path_small, image_alt, link_url, link_label, cta_label, sort_order) VALUES
  ('Slide 1 — Cổng nhà máy', 'hero', NULL, NULL, NULL,
   'assets/images/banner-texgiang.webp', 'assets/images/banner-texgiang-800.webp',
   'Cổng Nhà máy Tân Hương 2 — Công ty Cổ phần TEX-GIANG, KCN Tân Hương, Đồng Tháp', NULL, NULL, NULL, 1),
  ('Slide 2 — Cai Lậy', 'hero', 'Một trong ba khu sản xuất của TEX-GIANG tại Đồng Tháp', 'Chi nhánh Cai Lậy', NULL,
   'assets/images/company/cai-lay.webp', NULL,
   'Chi nhánh TEX-GIANG tại Cai Lậy, tỉnh Đồng Tháp', NULL, NULL, NULL, 2),
  ('Slide 3 — Đào tạo nghề', 'hero', 'Học nghề may miễn phí, có trợ cấp lương học việc', 'Đào tạo nghề', NULL,
   'assets/images/company/training.webp', NULL,
   'Đào tạo nghề may cho người lao động mới tại TEX-GIANG', NULL, NULL, NULL, 3),
  ('Slide 4 — Con người', 'hero', 'Môi trường làm việc gắn kết, ổn định lâu dài', 'Con người', NULL,
   'assets/images/company/team-event.webp', NULL,
   'Hoạt động gắn kết cán bộ công nhân viên TEX-GIANG', NULL, NULL, NULL, 4),
  ('Dải thông báo đầu trang', 'notice',
   'Thưởng nóng 2.000.000đ cho công nhân may mới — đào tạo miễn phí, có lương ngay tháng đầu.',
   NULL, NULL, NULL, NULL, NULL, '/apply', 'Xem điều kiện', NULL, 1),
  ('Thẻ nổi — Thưởng nóng', 'popup',
   'Thưởng nóng 2.000.000đ cho công nhân may mới', 'Đợt tuyển cuối năm 2026',
   'Học nghề miễn phí có lương · xe đưa rước · ăn ca miễn phí.',
   NULL, NULL, NULL, '/apply', NULL, 'Nộp hồ sơ ngay', 1),
  ('Dải CTA thưởng nóng', 'ad',
   'Thưởng nóng cho công nhân may mới', NULL,
   '2.000.000đ khi làm đủ 2 tháng, kèm xe đưa rước tận nhà, ăn ca miễn phí và đào tạo nghề có lương.',
   NULL, NULL, NULL, '/apply', NULL, 'Đăng ký nhận thưởng', 1);

-- 8. TIN TUYỂN DỤNG --------------------------------------------------------
INSERT INTO jobs
  (slug, code, branch_id, category_id, title, quantity, location, employment_type, experience,
   salary_text, salary_min, salary_max, salary_range, description, requirements, benefits,
   status, is_urgent, posted_at, deadline, meta_title, meta_description, created_by)
VALUES
  ('300-cong-nhan-may-chuyen-cong-nghiep', 'TD26-0001',
   (SELECT id FROM branches WHERE slug='tan-huong'), (SELECT id FROM categories WHERE slug='production'),
   '300 Công nhân may chuyền công nghiệp (áo polo, jacket xuất khẩu)', 300,
   'Khu Tân Hương — Nhà máy 2', 'Toàn thời gian, chính thức', 'Không yêu cầu, có đào tạo',
   '8.000.000 - 16.000.000 VNĐ', 8000000, 16000000, '8-12',
   'Thực hiện các công đoạn may trên dây chuyền: ráp thân, may tay, may cổ, tra dây kéo, may lai, vắt sổ.\nVận hành máy may 1 kim điện tử, 2 kim, vắt sổ, kansai tại Nhà máy Tân Hương 2.\nĐảm bảo sản phẩm đạt tiêu chuẩn xuất khẩu US/EU theo checklist của tổ trưởng.\nChưa biết may sẽ được đào tạo nghề miễn phí 7–15 ngày, có lương học việc.',
   'Có sức khỏe tốt, tuổi 18–45, không yêu cầu kinh nghiệm.\nBiết may là lợi thế; người chưa biết may được đào tạo có lương.\nTrung thực, chăm chỉ, tuân thủ giờ giấc và an toàn lao động.',
   'Thu nhập 8 – 16 triệu/tháng|Lương sản phẩm + thưởng năng suất + phụ cấp chuyên cần.\nThưởng nóng 2.000.000đ|Áp dụng cho công nhân may mới, đủ điều kiện sau 2 tháng.\nBHXH, BHYT, cơm ca|Đóng bảo hiểm đầy đủ, ăn ca miễn phí tại nhà máy.\nXe đưa rước tận nhà|Nhiều tuyến qua Châu Thành, Tân Hương và nhà máy lân cận.',
   'published', 1, '2026-10-28 08:00:00', '2026-11-30',
   'Công nhân may công nghiệp | Tuyển dụng TEX-GIANG',
   'Tuyển 300 công nhân may công nghiệp tại Nhà máy Tân Hương 2, Đồng Tháp. Thu nhập 8–16 triệu/tháng, đào tạo nghề miễn phí có lương.',
   (SELECT id FROM users WHERE username='admin')),

  ('giam-sat-kiem-pham-qc', 'TD26-0002',
   (SELECT id FROM branches WHERE slug='tan-huong'), (SELECT id FROM categories WHERE slug='qc'),
   '05 Giám sát kiểm phẩm chất lượng QC may mặc (QC Team Leader)', 5,
   'Khu Tân Hương — Nhà máy 1', 'Toàn thời gian, chính thức', 'Có kinh nghiệm QC may',
   '12.000.000 - 18.000.000 VNĐ', 12000000, 18000000, '12-18',
   'Kiểm tra thông số kỹ thuật, chất lượng đường may trên chuyền may xuất khẩu.\nQuản lý tổ kiểm phẩm và phân công công việc hằng ngày.\nLàm việc với khách hàng quốc tế về tiêu chuẩn chất lượng.',
   'Có kinh nghiệm kiểm phẩm trong ngành may xuất khẩu.\nĐọc hiểu thông số kỹ thuật và tài liệu tiếng Anh cơ bản.\nCó khả năng quản lý tổ nhóm 5–10 người.',
   'Thu nhập 12 – 18 triệu/tháng|Lương theo năng lực + thưởng hiệu quả công việc.\nBHXH, BHYT đầy đủ|Đóng bảo hiểm theo quy định, nghỉ phép năm.\nCơm ca miễn phí|Suất ăn tại nhà máy, xe đưa rước theo tuyến.',
   'published', 1, '2026-10-26 08:00:00', '2026-11-25',
   'Giám sát kiểm phẩm QC | Tuyển dụng TEX-GIANG',
   'Tuyển 05 giám sát kiểm phẩm chất lượng QC tại TEX-GIANG, Tân Hương, Đồng Tháp.',
   (SELECT id FROM users WHERE username='admin')),

  ('ky-su-cong-nghiep-ie', 'TD26-0003',
   (SELECT id FROM branches WHERE slug='tan-huong'), (SELECT id FROM categories WHERE slug='ie'),
   '02 Kỹ sư công nghiệp & định mức năng suất (IE Engineer)', 2,
   'Khu Tân Hương — Nhà máy 2', 'Toàn thời gian, chính thức', 'Có kinh nghiệm IE may mặc',
   '15.000.000 - 22.000.000 VNĐ', 15000000, 22000000, 'over-18',
   'Bấm giờ thao tác, chuẩn hóa quy trình may GSD.\nCân bằng chuyền may và áp dụng các giải pháp cải tiến tinh gọn Lean Manufacturing.\nTheo dõi năng suất, đề xuất định mức cho từng mã hàng.',
   'Tốt nghiệp chuyên ngành kỹ thuật, quản lý công nghiệp hoặc tương đương.\nSử dụng được GSD hoặc công cụ bấm giờ, cân bằng chuyền.\nƯu tiên kinh nghiệm trong ngành may xuất khẩu.',
   'Thu nhập 15 – 22 triệu/tháng|Theo năng lực và kết quả công việc.\nLộ trình phát triển rõ ràng|Đào tạo nội bộ, cơ hội làm việc với khách hàng quốc tế.\nBHXH, BHYT, thưởng tháng 13|Chế độ đầy đủ theo quy định.',
   'published', 0, '2026-10-25 08:00:00', '2026-11-28',
   'Kỹ sư công nghiệp IE | Tuyển dụng TEX-GIANG',
   'Tuyển 02 kỹ sư công nghiệp & định mức năng suất (IE Engineer) tại TEX-GIANG, Tân Hương.',
   (SELECT id FROM users WHERE username='admin')),

  ('quan-ly-don-hang-merchandiser', 'TD26-0004',
   (SELECT id FROM branches WHERE slug='cai-lay'), (SELECT id FROM categories WHERE slug='office'),
   '03 Chuyên viên quản lý đơn hàng ngành may (Merchandiser)', 3,
   'Khu Cai Lậy', 'Toàn thời gian, chính thức', 'Có kinh nghiệm merchandiser',
   '13.000.000 - 20.000.000 VNĐ', 13000000, 20000000, '12-18',
   'Làm việc trực tiếp với khách hàng nước ngoài.\nTheo dõi tiến độ phát triển mẫu, cung ứng nguyên phụ liệu may.\nTheo dõi xuất hàng FOB/CMT đúng hạn.',
   'Tiếng Anh giao dịch tốt (đọc hiểu email, tài liệu kỹ thuật).\nCó kinh nghiệm merchandiser ngành may xuất khẩu.\nKỹ năng theo dõi tiến độ và phối hợp nhiều bộ phận.',
   'Thu nhập 13 – 20 triệu/tháng|Lương cứng + thưởng theo đơn hàng.\nMôi trường quốc tế|Làm việc trực tiếp với khách hàng châu Âu, Mỹ.\nBHXH, BHYT đầy đủ|Xe đưa rước, cơm ca theo chế độ công ty.',
   'published', 0, '2026-10-24 08:00:00', '2026-11-26',
   'Quản lý đơn hàng may | Tuyển dụng TEX-GIANG',
   'Tuyển 03 chuyên viên quản lý đơn hàng ngành may tại chi nhánh Cai Lậy, TEX-GIANG.',
   (SELECT id FROM users WHERE username='admin')),

  ('nhan-vien-bao-tri-may-may', 'TD26-0005',
   (SELECT id FROM branches WHERE slug='tan-huong'), (SELECT id FROM categories WHERE slug='maintenance'),
   '04 Nhân viên bảo trì máy may công nghiệp & điện xưởng', 4,
   'Khu Tân Hương', 'Toàn thời gian, chính thức', 'Có kinh nghiệm bảo trì máy may',
   '9.500.000 - 14.500.000 VNĐ', 9500000, 14500000, '8-12',
   'Bảo trì, sửa chữa máy may 1 kim, 2 kim, kansai, máy cắt tự động.\nKiểm tra và xử lý sự cố hệ thống điện nhà xưởng sản xuất.\nGhi chép lịch bảo trì định kỳ theo kế hoạch.',
   'Có kinh nghiệm sửa chữa máy may công nghiệp hoặc điện xưởng.\nCó chứng chỉ nghề điện/cơ khí là lợi thế.\nSẵn sàng trực ca khi có sự cố.',
   'Thu nhập 9,5 – 14,5 triệu/tháng|Lương theo tay nghề + phụ cấp trực ca.\nBHXH, BHYT, cơm ca|Chế độ đầy đủ theo quy định.\nThưởng tháng 13|Theo kết quả công việc năm.',
   'published', 0, '2026-10-22 08:00:00', '2026-11-22',
   'Bảo trì máy may | Tuyển dụng TEX-GIANG',
   'Tuyển 04 nhân viên bảo trì máy may công nghiệp & điện xưởng tại TEX-GIANG, Tân Hương.',
   (SELECT id FROM users WHERE username='admin')),

  ('ke-toan-chi-phi-gia-thanh', 'TD26-0006',
   (SELECT id FROM branches WHERE slug='cho-gao'), (SELECT id FROM categories WHERE slug='office'),
   '02 Kế toán chi phí & giá thành may xuất khẩu', 2,
   'Khu Chợ Gạo', 'Toàn thời gian, chính thức', 'Có kinh nghiệm kế toán giá thành',
   '11.000.000 - 16.000.000 VNĐ', 11000000, 16000000, '8-12',
   'Tập hợp chi phí nguyên phụ liệu vải sợi, tính giá thành đơn hàng may xuất khẩu.\nKiểm soát định mức và lập báo cáo tài chính nội bộ.\nPhối hợp với sản xuất và merchandiser về chi phí đơn hàng.',
   'Tốt nghiệp kế toán, tài chính; có kinh nghiệm kế toán giá thành.\nThành thạo Excel, ưu tiên biết phần mềm kế toán.\nCẩn thận, trung thực, chịu được áp lực tiến độ.',
   'Thu nhập 11 – 16 triệu/tháng|Theo năng lực và kinh nghiệm.\nBHXH, BHYT đầy đủ|Chế độ theo quy định công ty.\nXe đưa rước, cơm ca|Hỗ trợ đi lại và ăn ca tại nhà máy.',
   'closed', 0, '2026-10-20 08:00:00', '2026-10-31',
   'Kế toán chi phí | Tuyển dụng TEX-GIANG',
   'Tuyển 02 kế toán chi phí & giá thành may xuất khẩu tại chi nhánh Chợ Gạo.',
   (SELECT id FROM users WHERE username='admin')),

  ('hoc-nghe-may-mien-phi', 'TD26-0007',
   (SELECT id FROM branches WHERE slug='tan-huong'), (SELECT id FROM categories WHERE slug='production'),
   '100 Học nghề may miễn phí (được trợ cấp lương học việc)', 100,
   'Khu Tân Hương — trung tâm đào tạo', 'Toàn thời gian sau đào tạo', 'Không yêu cầu kinh nghiệm',
   'Hỗ trợ lương học việc + Cơm ca', NULL, NULL, 'under-8',
   'Dành cho lao động chưa từng may vá.\nĐào tạo 7 – 15 ngày các kỹ năng may cơ bản.\nBao cơm trưa và bố trí việc làm ngay sau khi hoàn thành khóa học.',
   'Nam/nữ từ 18 tuổi, sức khỏe tốt.\nKhông yêu cầu kinh nghiệm may.\nCó nguyện vọng làm việc lâu dài tại TEX-GIANG.',
   'Miễn phí đào tạo|Không thu bất kỳ khoản phí nào.\nTrợ cấp lương học việc|Hỗ trợ trong thời gian học nghề.\nCơm ca miễn phí|Suất ăn tại nhà máy trong thời gian học.\nViệc làm ngay|Bố trí vào chuyền may sau khi hoàn thành khóa học.',
   'published', 0, '2026-10-18 08:00:00', '2026-11-30',
   'Học nghề may miễn phí | Tuyển dụng TEX-GIANG',
   'Tuyển 100 học viên học nghề may miễn phí, có trợ cấp lương học việc tại TEX-GIANG, Tân Hương.',
   (SELECT id FROM users WHERE username='admin')),

  ('lai-xe-dua-don-cbcnv', 'TD26-0008',
   (SELECT id FROM branches WHERE slug='tan-huong'), (SELECT id FROM categories WHERE slug='office'),
   'Nhân viên lái xe đưa đón cán bộ công nhân viên (bằng D/E)', 2,
   'Khu Tân Hương — tuyến Châu Thành', 'Toàn thời gian, chính thức', 'Có giấy phép lái xe D/E',
   '9.000.000 - 11.000.000 VNĐ', 9000000, 11000000, '8-12',
   'Lái xe đưa đón cán bộ công nhân viên theo tuyến được phân công.\nKiểm tra xe, bảo dưỡng cơ bản và báo cáo sự cố.\nĐảm bảo an toàn và đúng giờ.',
   'Có giấy phép lái xe hạng D/E còn hiệu lực.\nCó kinh nghiệm lái xe khách hoặc xe đưa đón công nhân.\nSức khỏe tốt, không sử dụng chất kích thích khi làm việc.',
   'Thu nhập 9 – 11 triệu/tháng|Lương cứng + phụ cấp trách nhiệm.\nBHXH, BHYT đầy đủ|Chế độ theo quy định.\nCơm ca|Suất ăn tại nhà máy.',
   'closed', 0, '2026-10-10 08:00:00', '2026-10-20',
   'Lái xe đưa đón | Tuyển dụng TEX-GIANG',
   'Tuyển nhân viên lái xe đưa đón cán bộ công nhân viên tại TEX-GIANG, tuyến Châu Thành.',
   (SELECT id FROM users WHERE username='admin'));

-- 9. TIN TỨC --------------------------------------------------------------
INSERT INTO posts (slug, title, category_id, excerpt, body, image_path, image_alt, image_caption, status, published_at, created_by) VALUES
  ('tuyen-300-cong-nhan-tan-huong', 'Việc làm may tại Tân Hương',
   (SELECT id FROM post_categories WHERE slug='tuyen-dung'),
   'Tìm vị trí phù hợp với tay nghề, nơi ở và mong muốn thu nhập của bạn.',
   'Danh sách việc làm cho biết nơi làm việc, thu nhập tham khảo, số lượng và hạn nhận hồ sơ của từng vị trí. Hãy mở chi tiết trước khi chọn ứng tuyển để kiểm tra đúng mã tin.\n\nNgười đã biết may nên ghi rõ công đoạn và loại máy từng sử dụng. Người chưa có kinh nghiệm có thể chọn vị trí học nghề để được tư vấn lộ trình phù hợp.\n\nMức thu nhập trong tin được cập nhật theo từng đợt tuyển. Khi trao đổi với tuyển dụng, hãy hỏi rõ lương cơ bản, lương sản phẩm, phụ cấp, thưởng và điều kiện áp dụng.',
   'assets/images/company/tan-huong-2.webp', 'Toàn cảnh Nhà máy Tân Hương 2',
   'Nhà máy Tân Hương 2 · Ảnh TEX-GIANG.', 'published', '2026-11-03 08:00:00',
   (SELECT id FROM users WHERE username='admin')),

  ('qc-lam-gi-tren-chuyen-may', 'QC làm gì trên chuyền may?',
   (SELECT id FROM post_categories WHERE slug='nghe-nghiep'),
   'Tìm hiểu công việc kiểm phẩm và cách chuẩn bị kinh nghiệm cho buổi trao đổi.',
   'QC theo dõi chất lượng sản phẩm trong từng công đoạn, đối chiếu thông số kỹ thuật và ghi nhận lỗi cần xử lý. Công việc cần phối hợp với sản xuất và kỹ thuật để hạn chế lỗi lặp lại.\n\nTrong hồ sơ, hãy nêu loại sản phẩm đã kiểm, công đoạn phụ trách và kinh nghiệm đọc thông số. Nếu từng hướng dẫn tổ kiểm phẩm, bạn có thể mô tả quy mô và cách xử lý một tình huống cụ thể.\n\nYêu cầu kinh nghiệm và cách đánh giá tay nghề được xác nhận theo từng tin tuyển dụng. Bạn không cần gửi tài liệu nội bộ hoặc dữ liệu khách hàng cũ để minh chứng kinh nghiệm.',
   'assets/images/company/quality-system.webp', 'Sơ đồ kiểm soát chất lượng từ nguyên liệu đến giao hàng',
   'Quy trình kiểm soát chất lượng · Nguồn: TEX-GIANG, QA System.', 'published', '2026-11-02 08:00:00',
   (SELECT id FROM users WHERE username='admin')),

  ('bat-dau-hoc-nghe-may', 'Bắt đầu học nghề may',
   (SELECT id FROM post_categories WHERE slug='dao-tao'),
   'Những điều nên trao đổi khi bạn chưa có kinh nghiệm may công nghiệp.',
   'Khi đăng ký học nghề, hãy cho tuyển dụng biết bạn đã từng sử dụng máy may hay chưa, nơi ở và thời điểm có thể bắt đầu. Những thông tin này giúp trao đổi việc bố trí lớp và nơi làm việc.\n\nHỏi rõ thời gian học, nội dung thực hành, người hướng dẫn và cách đánh giá cuối khóa. Mức hỗ trợ, giờ học và công việc sau đào tạo cần được xác nhận trước khi tham gia.\n\nChuẩn bị tinh thần học từng thao tác và tuân thủ hướng dẫn an toàn. Không cần tự mua máy hoặc trang bị khi chưa có hướng dẫn cụ thể từ đơn vị tiếp nhận.',
   'assets/images/company/training.webp', 'Tập huấn phòng chống buôn bán người và xâm hại tình dục năm 2020',
   'Hoạt động tập huấn tháng 02/2020 · Ảnh lưu trữ TEX-GIANG.', 'published', '2026-11-01 08:00:00',
   (SELECT id FROM users WHERE username='admin')),

  ('chuan-bi-cho-ngay-hoi-viec-lam', 'Chuẩn bị cho ngày hội việc làm',
   (SELECT id FROM post_categories WHERE slug='nghe-nghiep'),
   'Một cuộc trao đổi ngắn sẽ hiệu quả hơn khi bạn đã xác định điều cần hỏi.',
   'Trước khi đến ngày hội, hãy xác nhận thời gian, địa điểm và đơn vị tổ chức qua kênh chính thức. Chọn trước một vài vị trí phù hợp để tránh mất thời gian tìm kiếm tại gian hàng.\n\nBạn nên nhớ số điện thoại liên hệ, kinh nghiệm làm việc và thời điểm có thể nhận việc. Có thể chuẩn bị CV nếu vị trí yêu cầu; hỏi đơn vị tuyển dụng trước khi mang theo giấy tờ cá nhân.\n\nSau buổi trao đổi, ghi lại tên vị trí, địa điểm làm việc và bước tiếp theo đã được hẹn.',
   'assets/images/company/tan-huong-2.webp', 'Toàn cảnh Nhà máy Tân Hương 2',
   'Nhà máy Tân Hương 2 · Ảnh TEX-GIANG.', 'published', '2026-10-30 08:00:00',
   (SELECT id FROM users WHERE username='admin'));

-- 7b. NỘI DUNG TRANG VỀ CHÚNG TÔI / PHÚC LỢI --------------------------
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


-- 7c. BẢN ĐỒ THEO KHU SẢN XUẤT -----------------------------------------
UPDATE branches SET map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3923.203881427061!2d106.3586310739038!3d10.484587964511139!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310ab768cef00851%3A0x75cf2c4b6dcf43cd!2zQ8O0bmcgVHkgQ-G7lSBQaOG6p24gVGV4LUdpYW5n!5e0!3m2!1svi!2s!4v1793765708852!5m2!1svi!2s', map_link = 'https://maps.app.goo.gl/ju91J21xxb4KCDEe8' WHERE slug = 'tan-huong';
UPDATE branches SET map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3925.2436624600177!2d106.52494427390207!3d10.32237476740696!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310aadd3a04d3db5%3A0x6df16003b97e9aba!2zQ8O0bmcgVHkgQ1AgVGV4LUdpYW5nIGNoaSBuaMOhbmggQ2jhu6MgR-G6oW8!5e0!3m2!1svi!2s!4v1793765777202!5m2!1svi!2s', map_link = 'https://maps.app.goo.gl/ouoWhsTAtT7F4ma28' WHERE slug = 'cho-gao';
UPDATE branches SET map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6832.276131181911!2d106.08859122953558!3d10.41266722163212!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310a97c35289cfa5%3A0x38fbaef867a1615!2zQ8O0bmcgdHkgY-G7lSBwaOG6p24gVEVYIC0gR0lBTkc!5e0!3m2!1svi!2s!4v1793765738154!5m2!1svi!2s', map_link = 'https://maps.app.goo.gl/Nvd2xR3BMxEWgCSz7' WHERE slug = 'cai-lay';


-- 7d. TIÊU ĐỀ TRANG VỀ CHÚNG TÔI / PHÚC LỢI ---------------------------
INSERT INTO settings (`key`, `value`, `group`) VALUES
  ('content_about_title', 'May xuất khẩu tại Tân Hương từ năm 2007', 'content'),
  ('content_about_lead', 'Công ty Cổ phần TEX-GIANG (TGI) thành lập tháng 3/2007, vận hành 3 khu sản xuất tại Tân Hương, Cai Lậy và Chợ Gạo.', 'content'),
  ('content_why_title', 'Vì sao chọn TEX-GIANG', 'content'),
  ('content_why_lead', 'Bốn điều người lao động hỏi nhiều nhất khi đến nhà máy: môi trường có an toàn không, thu nhập bao nhiêu, có được dạy nghề không, và đi lại thế nào.', 'content')
ON DUPLICATE KEY UPDATE `key` = `key`;

-- 7e. MENU WEBSITE (header/footer) --------------------------------------
INSERT INTO menus (label, url, sort_order) VALUES
  ('Trang chủ',    '',              1),
  ('Việc làm',     'jobs',          2),
  ('Về chúng tôi', 'about',         3),
  ('Phúc lợi',     'why-tex-giang', 4),
  ('Tin tức',      'news',          5),
  ('Liên hệ',      'contact',       6);
