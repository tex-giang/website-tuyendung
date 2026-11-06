# Bước 2 — Thiết kế cơ sở dữ liệu

Ngày: 06/11/2026 · MySQL 8.0+ · InnoDB · `utf8mb4_unicode_ci`
File: `database/schema.sql` (DDL) + `database/seed.sql` (dữ liệu khởi tạo)

## 1. Danh sách bảng

| Bảng | Vai trò | Quan hệ chính |
|---|---|---|
| `roles` | 3 vai trò: super_admin, hr_admin, recruiter | 1-n `users` |
| `users` | Tài khoản quản trị (session auth) | n-1 `roles` |
| `branches` | Khu sản xuất: Tân Hương, Cái Lậy, Chợ Gạo | 1-n `jobs` |
| `categories` | Khối phòng ban (production, qc, ie, maintenance, office) | 1-n `jobs` |
| `jobs` | Tin tuyển dụng | n-1 `branches`, `categories`; 1-n `applications` |
| `applications` | Hồ sơ ứng tuyển (kèm metadata CV) | n-1 `jobs` (nullable = CV tự do) |
| `application_history` | Lịch sử đổi trạng thái hồ sơ | n-1 `applications` (CASCADE) |
| `post_categories` | Chuyên mục tin tức | 1-n `posts` |
| `posts` | Tin tức/bài viết | n-1 `post_categories` |
| `banners` | Hero slider, dải thông báo, popup, dải CTA | độc lập |
| `settings` | Key-value cấu hình + nội dung soạn thảo | độc lập, gom nhóm bằng `group` |
| `contact_messages` | Form liên hệ từ website | n-1 `users` (người xử lý) |
| `audit_logs` | Nhật ký thao tác quản trị | n-1 `users` |

## 2. Những điểm thiết kế quan trọng

### `jobs`
- `status`: `draft` → `published` → `paused` / `closed`. Tin công khai = `published` **và** `deleted_at IS NULL` **và** `deadline >= CURDATE()`.
- Xoá tin là **xoá mềm** (`deleted_at`) để không phá hồ sơ ứng tuyển đã tham chiếu.
- `salary_text` để hiển thị, `salary_min/max` để tính toán, `salary_range` (enum 4 mức) để lọc — khớp 4 option bộ lọc của giao diện tĩnh.
- `slug` duy nhất để URL đẹp (`/jobs/{slug}`), `code` duy nhất cho mã tin (TG-01).
- Nội dung nhiều dòng quy ước:
  - `description`, `requirements`: mỗi dòng = một bullet.
  - `benefits`: mỗi dòng `Tiêu đề | Mô tả` → render thành card 2 cột.
- `views`, `sort_order`, `is_urgent` phục vụ hiển thị/ưu tiên.

### `applications`
- `job_id` cho phép NULL (ứng tuyển không chọn vị trí = CV tự do).
- Metadata CV tách khỏi file: `cv_original_name` (tên gốc để hiển thị), `cv_stored_name` (tên random trên đĩa), `cv_mime`, `cv_size`. File nằm ở `storage/uploads/cv` — **ngoài** `public`, tải xuống qua controller có kiểm tra đăng nhập.
- `status`: new, contacted, interview, hired, rejected — đúng 5 trạng thái của admin tĩnh.
- `source`: Website / Zalo / QR / Giới thiệu.
- Mỗi lần đổi trạng thái ghi một dòng `application_history` trong cùng transaction.

### `banners`
- `type`: `hero` (slider đầu trang), `notice` (dải trên cùng), `popup` (thẻ nổi), `ad` (dải CTA).
- `starts_at` / `ends_at` cho phép hẹn lịch chiến dịch.

### `settings`
- Key-value để admin sửa không cần sửa code. Nhóm:
  - `general`: site_name, legal_name, address, working_hours, jobs_per_page
  - `contact`: hotline, hotline_display, email, zalo, fax, map_embed
  - `social`: facebook, youtube, website
  - `seo`: seo_title, seo_description
  - `content`: hero title/subtitle, about, philosophy, stat_1..4, footer_intro
- Đọc bằng `settings('key')` (cache trong request); ghi bằng `Setting::setMany()`.

## 3. Dữ liệu seed

- 3 roles, 1 tài khoản `admin` (mật khẩu mặc định `Admin@123dev` — **đổi ngay**).
- 3 chi nhánh, 5 khối phòng ban, 6 chuyên mục tin tức.
- 7 banner (4 hero + notice + popup + ad).
- 8 tin tuyển dụng lấy từ nội dung site tĩnh (2 tin đã hết hạn để ở trạng thái `closed`).
- 4 bài viết đầu tiên trong 14 bài của `news-data.js`.
- 32 khoá cấu hình/nội dung.

Phần còn lại của 14 bài có thể thêm qua trang quản trị tin tức ở bước sau (bảng `posts` đã sẵn sàng).

## 4. Ghi chú vận hành

- Import: `mysql ... < database/schema.sql` rồi `< database/seed.sql` (bắt buộc charset utf8mb4; dùng phpMyAdmin trên cPanel hoặc `mysql --default-character-set=utf8mb4`).
- `schema.sql` có `DROP TABLE` — chỉ chạy khi tạo mới, **không** chạy lại trên dữ liệu thật.
- Mọi bảng dùng `utf8mb4_unicode_ci`, có index cho cột lọc (`status`, `posted_at`, `deadline`, `salary_range`, `created_at`) và FK có `ON DELETE SET NULL/CASCADE` phù hợp.
