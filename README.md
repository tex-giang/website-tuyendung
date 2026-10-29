# HỆ THỐNG WEBSITE TUYỂN DỤNG CÔNG TY CỔ PHẦN TEX-GIANG & TG APPAREL
> **Phong cách:** Corporate Recruitment Website kết hợp Modern Industrial Branding  
> **Đơn vị thực hiện:** IT-P.KT-NS & Senior UI/UX Frontend Specialist  
> **Địa bàn:** Nhà máy Tân Hương 2, Lô BII-8, KCN Tân Hương, Xã Tân Hương, Huyện Châu Thành, Tỉnh Đồng Tháp  

---

## 1. CÔNG NGHỆ & THƯ VIỆN SỬ DỤNG
- **HTML5:** Cấu trúc ngữ nghĩa (semantic), phân cấp heading, tối ưu accessibility và SEO.
- **Tailwind CSS (CDN):** Thiết kế layout, responsive mobile-first, bảng màu thương hiệu chuẩn.
- **Bootstrap Icons (v1.11.3 CDN):** Hệ thống biểu tượng thống nhất toàn trang.
- **Google Fonts (Be Vietnam Pro):** Font chữ hỗ trợ tiếng Việt hoàn hảo, dễ đọc cho người lao động.
- **JavaScript thuần (Vanilla JS):** Tương tác frontend mượt mà, độc lập, không phụ thuộc framework nặng nề.
- **Tài sản thương hiệu thực tế:**
  - `assets/images/logo-tgi.png`: Logo nhận diện chính thức TGI TEX-GIANG.
  - `assets/images/banner-texgiang.png`: Banner toàn cảnh Nhà máy Tân Hương 2 với khẩu hiệu *"Kiến tạo giá trị - Cùng phát triển bền vững"* & *"Vì con người - Vì chất lượng - Vì tương lai bền vững"*.

---

## 2. BẢNG MÀU THƯƠNG HIỆU (DESIGN SYSTEM)
- **Xanh thương hiệu (Brand Green):** `#087A55` / `#006837` (Lấy trực tiếp từ nhận diện banner nhà máy).
- **Xanh Navy (Corporate Blue):** `#12345A` / `#0B223D` (Tôn vinh sự vững chắc, đáng tin cậy).
- **Xanh lá nhạt (Soft Mint Background):** `#EAF6F0` / `#F0FDF4`.
- **Màu nền giao diện:** `#F5F7FA` (Thân thiện với mắt, hiện đại).
- **Màu chữ chính:** `#1F2937` (Độ tương phản cao, dễ đọc).
- **Màu nhấn nút (CTA Accent):** `#F26522` / `#F59E0B` (Nổi bật hành động nộp hồ sơ, gọi hotline).

---

## 3. CẤU TRÚC THƯ MỤC DỰ ÁN

```
E:\websitetuyendung\
│
├── index.html                   # [Trang 1] Trang chủ: Banner thực tế, tìm kiếm nhanh, việc làm nóng, tiện ích
├── jobs.html                    # [Trang 2] Danh sách việc làm: Bộ lọc đa chiều, filter chips, phân trang
├── job-detail.html              # [Trang 3] Chi tiết công việc: JD, quyền lợi, chia sẻ FB/Zalo, QR, form nộp nhanh
├── apply.html                   # [Trang 4] Ứng tuyển trực tuyến: Không cần tài khoản, kéo thả CV, validation
├── about.html                   # [Trang 5] Về chúng tôi: Giới thiệu TEX-GIANG, sứ mệnh, giá trị cốt lõi, WRAP/ISO
├── why-tex-giang.html           # [Trang 8] Tại sao chọn chúng tôi: 4 trụ cột phúc lợi, so sánh đãi ngộ, testimonials
├── news.html                    # [Trang 6] Tin tức nghề nghiệp: Bài viết nổi bật, ngày hội việc làm, hoạt động công đoàn
├── news-detail.html             # [Trang 7] Chi tiết tin tức: Nội dung chi tiết, chia sẻ mạng xã hội
├── contact.html                 # [Trang 9] Liên hệ: Bản đồ Google Maps KCN Tân Hương, lộ trình xe đưa đón
├── search-results.html          # [Trang 10] Trang kết quả tìm kiếm việc làm
├── 404.html                     # [Trang 11] Trang báo lỗi không tìm thấy nội dung
├── application-success.html     # [Trang 11] Thông báo gửi hồ sơ ứng tuyển thành công
├── application-error.html       # [Trang 11] Thông báo lỗi tải hồ sơ / gửi dữ liệu
│
├── components/                  # [Module dùng chung - Sẵn sàng cho PHP include]
│   ├── header.html              # Header & Thanh điều hướng chung
│   ├── footer.html              # Footer & Tiện ích floating (Hotline, Zalo, QR)
│   ├── job-card.html            # Card vị trí tuyển dụng mẫu
│   └── admin-sidebar.html       # Sidebar menu bảng điều khiển quản trị
│
├── admin/                       # [Nhóm Giao Diện Quản Trị Tuyển Dụng HR]
│   ├── login.html               # [Trang 12] Đăng nhập quản trị viên (Demo Mode)
│   ├── dashboard.html           # [Trang 13] Dashboard thống kê tuyển dụng, biểu đồ hồ sơ, lối tắt
│   ├── jobs.html                # [Trang 14] Quản lý việc làm: Bảng tin đăng, đóng/mở tin, nhân bản, xóa
│   ├── job-form.html            # [Trang 15] Thêm & Chỉnh sửa việc làm: Trình soạn thảo rich text, hẹn nộp
│   ├── applications.html        # [Trang 16] Quản lý hồ sơ ứng viên: Xem thông tin, tải CV, lọc trạng thái
│   ├── application-detail.html  # [Trang 17] Chi tiết ứng viên: Timeline xử lý, ghi chú nội bộ HR, liên hệ
│   ├── content.html             # [Trang 18] Quản lý nội dung CMS: Trang chủ, slogan, tin tức, liên hệ
│   ├── banners.html             # [Trang 19] Quản lý banner: Banner chính, banner popup ưu đãi, hướng dẫn tỷ lệ
│   └── settings.html            # [Trang 20] Cài đặt hệ thống: SEO metadata, thông tin pháp nhân, hồ sơ dự án
│
├── assets/
│   ├── css/
│   │   └── custom.css           # CSS tùy biến: Hiệu ứng pulse sóng âm, thanh cuộn hiện đại, glassmorphism
│   ├── js/
│   │   ├── main.js              # Xử lý mobile menu, toast notification, pop-up banner, mã QR, share API
│   │   ├── jobs.js              # Dữ liệu mẫu việc làm, tìm kiếm, lọc đa tiêu chí, xóa chip lọc, sắp xếp
│   │   ├── apply.js             # Form validation, kéo thả tệp CV, kiểm tra dung lượng/định dạng, giả lập upload
│   │   └── admin.js             # Drawer sidebar mobile, tìm kiếm bảng, modal cập nhật trạng thái, xuất Excel
│   └── images/
│       ├── banner-texgiang.png  # Ảnh banner chính thức Nhà máy Tân Hương 2
│       └── logo-tgi.png         # Logo chính thức TGI TEX-GIANG
└── README.md
```

---

## 4. HƯỚNG DẪN MỞ & XEM BẢN DEMO
1. **Không yêu cầu cài đặt phần mềm:** Toàn bộ thư viện (Tailwind CSS, Bootstrap Icons, Google Fonts, QR API) đều chạy trực tiếp qua CDN chính thức.
2. **Mở trực tiếp trên trình duyệt:** Nhấp đúp chuột vào bất kỳ tệp `.html` nào:
   - Trang chủ ứng viên: `index.html`
   - Tìm việc làm: `jobs.html`
   - Đăng nhập quản trị HR: `admin/login.html` &rarr; Nhấn "Đăng Nhập Hệ Thống" để vào `admin/dashboard.html`.
3. **Mở qua máy chủ cục bộ (khuyến nghị cho trải nghiệm mượt nhất):**
   - Với VS Code: Cài extension *Live Server*, nhấn chuột phải chọn *Open with Live Server*.
   - Với Python: Chạy lệnh `python -m http.server 8080` trong thư mục `E:\websitetuyendung`.
   - Với Node.js: Chạy lệnh `npx serve .`

---

## 5. LƯU Ý VỀ DỮ LIỆU DEMO
- Toàn bộ số liệu hồ sơ ứng viên (142 ứng viên), số điện thoại cá nhân mẫu, chi tiết mức lương và lịch trình phỏng vấn trong bản thiết kế này là **Dữ liệu giả định (Mock Data)** phục vụ mục đích kiểm thử UI/UX.
- Hệ thống có hiển thị thông báo rõ ràng: *"Dữ liệu demo chưa lưu thật vào cơ sở dữ liệu"* để tránh hiểu nhầm.

---

## 6. LỘ TRÌNH TÍCH HỢP BACKEND PHP & MYSQL (GIAI ĐOẠN TIẾP THEO)
1. **Chuyển đổi Components sang PHP Partials:**
   - Thay thế Header bằng `<?php include 'components/header.html'; ?>`.
   - Thay thế Footer bằng `<?php include 'components/footer.html'; ?>`.
   - Thay thế Sidebar Admin bằng `<?php include 'components/admin-sidebar.html'; ?>`.
2. **Thiết kế Cơ Sở Dữ Liệu MySQL:**
   - Bảng `jobs`: `id`, `title`, `company`, `department`, `salary`, `deadline`, `description`, `requirements`, `benefits`, `status`, `created_at`.
   - Bảng `applications`: `id`, `job_id`, `fullname`, `phone`, `email`, `cv_filepath`, `note`, `status` (`new`, `called`, `interview`, `passed`, `rejected`), `created_at`.
   - Bảng `banners`: `id`, `title`, `image_url`, `link_url`, `type` (`hero`, `popup`), `status`, `order_index`.
   - Bảng `users`: `id`, `username`, `password_hash`, `fullname`, `role`.
3. **Bảo mật & Tối ưu hóa:**
   - Xử lý mã hóa mật khẩu bằng `password_hash()` với thuật toán BCRYPT / ARGON2.
   - Kiểm tra định dạng tệp CV nghiêm ngặt ở tầng backend (MIME type `application/pdf`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, giới hạn 10MB) và lưu file vào thư mục upload được bảo vệ.
   - Chống CSRF token trên toàn bộ form ứng tuyển và quản trị.
   - Chuẩn bị sẵn API Endpoint JSON cho ứng tuyển nhanh trên điện thoại.
