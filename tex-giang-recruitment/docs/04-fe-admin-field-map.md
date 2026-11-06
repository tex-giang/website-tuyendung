# Từ FE suy ra toàn bộ trường → thiết kế lại Admin + phân quyền

Ngày: 06/11/2026. Mục tiêu: **từ giao diện công khai (FE) + controller/model, liệt kê mọi trường dữ liệu thực sự được dùng**, đối chiếu với form quản trị để biết trường nào còn thiếu, rồi thiết kế lại trang Admin cho phủ hết — kèm ma trận phân quyền.

## 1. Cách lấy dữ liệu

| Nguồn FE | Suy ra trường |
|---|---|
| `app/Views/public/*` + `partials/public/*` | Trường được in ra màn hình (title, giá, mô tả, ảnh, ngày…) |
| `app/Controllers/Public/*` | Trường dùng để lọc, sắp xếp, prefill form (slug, salary_range, category/branch, status…) |
| `app/Models/*` + `database/schema.sql` | Trường lưu trong DB, kể cả trường dùng nội bộ/audit |
| `app/Views/admin/*` (đối chiếu) | Trường Admin đã cho sửa / chỉ hiển thị / còn thiếu |

Kết quả cuối: **mọi trường FE đọc đều có nguồn quản trị**; các trường hệ thống (id, timestamp, deleted_at, created_by) chỉ hiển thị hoặc không lộ ra.

## 2. Đối chiếu theo bảng dữ liệu

Ký hiệu: ✅ Admin sửa được · 👁 chỉ hiển thị · 🔒 hệ thống, không cần sửa · 🆕 vừa bổ sung trong đợt này.

### jobs — 23/23 trường nội dung sửa được

| Nhóm trường | Trường | FE dùng ở đâu | Admin |
|---|---|---|---|
| Định danh | `slug`, `code` | URL `/jobs/{slug}`, mã tin (TG-01) | ✅ |
| Phân loại | `branch_id`, `category_id` | Bộ lọc địa điểm/phòng ban, thẻ việc làm | ✅ (select từ bảng danh mục) |
| Tiêu đề & số lượng | `title`, `quantity` | Danh sách + chi tiết + card | ✅ |
| Nơi làm | `location`, `employment_type`, `experience` | Chi tiết tin (dl 4 ô) | ✅ |
| Thu nhập | `salary_text`, `salary_min`, `salary_max`, `salary_range` | Hiển thị "8 – 16 triệu", bộ lọc mức lương | ✅ |
| Nội dung | `description`, `requirements`, `benefits` | Bullet list + card quyền lợi | ✅ (quy ước dòng ở docs/03) |
| Xuất bản | `status`, `is_urgent`, `posted_at`, `deadline`, `sort_order` | Hiển thị/ẩn, chip "Tuyển gấp", "Đăng ngày", "Hạn nộp" | ✅ |
| SEO | `meta_title`, `meta_description` | `<title>`, meta description | ✅ |
| Hệ thống | `id`, `views`, `created_by`, timestamps, `deleted_at` | Lượt xem hiển thị admin | 👁/🔒 |

### applications — 3 trường xử lý + 8 trường hiển thị

| Nhóm | Trường | FE/nguồn | Admin |
|---|---|---|---|
| Ứng viên | `full_name`, `phone`, `email`, `gender`, `birth_year`, `address`, `message` | Form `/apply` | 👁 (không sửa hồ sơ gốc) |
| Vị trí | `job_id` | `/apply?job=slug` | 👁 |
| CV | `cv_original_name`, `cv_stored_name`, `cv_mime`, `cv_size` | Upload `/apply` | 👁 + tải file |
| Xử lý | `status`, `admin_note`, `appointment_at` | — | ✅ |
| Hệ thống | `source`, `handled_by`, timestamps | — | 👁 |

### posts — 10/10 trường nội dung sửa được

`slug`, `title`, `category_id`, `excerpt`, `body`, `image_path`, `image_alt`, `image_caption`, `status`, `published_at` → ✅
`views`, `created_by`, timestamps, `deleted_at` → 👁/🔒

### banners — 15 trường sửa được (thêm `image_path_small` 🆕)

`name`, `type`, `title`, `subtitle`, `description`, `image_path`, **`image_path_small`**, `image_alt`, `link_url`, `link_label`, `cta_label`, `is_active`, `sort_order`, `starts_at`, `ends_at` → ✅
`focal_x`, `focal_y`: DB có nhưng FE chưa dùng → chưa đưa vào form (ghi chú để dành).

### branches / categories / post_categories — 🆕 toàn bộ có module quản trị

| Bảng | Trường Admin sửa | Trước đây |
|---|---|---|
| `branches` | `slug`, `name`, `address`, `phone`, `map_url`, `sort_order`, `is_active` | ❌ Không có UI — phải sửa SQL |
| `categories` | `slug`, `name`, `description`, `sort_order`, `is_active` | ❌ |
| `post_categories` | `slug`, `name`, `sort_order`, `is_active` | ❌ |

Đây là **lỗ hổng lớn nhất** của bản trước: FE lọc theo khu/phòng ban/chuyên mục nhưng admin không có nơi quản lý. Nay gộp vào module **Danh mục dùng chung** (`/admin/categories`) với 3 nhóm, có đếm số tin/bài đang dùng và chặn xoá khi còn dữ liệu tham chiếu.

### settings — 32/32 khoá đều sửa được

- 17 khoá cấu hình: `/admin/settings` (general 6 · contact 6 · social 3 · seo 2)
- 15 khoá nội dung: `/admin/content` (hero 3 · giới thiệu 3 · 4 cặp số liệu 8 · footer 1)

### users / audit_logs / contact_messages

- `users`: `username`🔒(không đổi), `full_name`, `email`, `phone`, `role_id`, `is_active`, `password` ✅; `last_login_at` 👁.
- `contact_messages`: `status` ✅, các trường còn lại 👁 + xoá.
- `audit_logs`: chỉ đọc (nhật ký).

## 3. Kết quả: độ phủ form quản trị

| Module Admin | Số trường quản trị | Trạng thái |
|---|---|---|
| Tin tuyển dụng | 23 | Đủ 100% trường nội dung |
| Hồ sơ ứng tuyển | 3 + tải CV | Đủ nghiệp vụ xử lý |
| Tin tức | 10 | Đủ 100% |
| Banner | 15 | Đủ 100% trường FE dùng |
| Danh mục (3 loại) | 7 + 5 + 4 | 🆕 thêm module |
| Cấu hình + nội dung | 32 | Đủ 100% khoá |
| Người dùng | 7 | Đủ |

## 4. Thiết kế lại giao diện Admin

### 4.1 Điều hướng theo nghiệp vụ (thay vì danh sách phẳng)

```
Tuyển dụng   → Bảng điều khiển · Tin tuyển dụng (badge số tin đang đăng) · Hồ sơ ứng tuyển (badge số mới)
Nội dung     → Tin tức & bài viết · Banner & thông báo · Nội dung website · Danh mục dùng chung
               · Hộp thư liên hệ (badge số chưa đọc)
Hệ thống     → Cài đặt hệ thống · Người dùng quản trị · Nhật ký thao tác
```
- Mục ngoài quyền của vai trò **không render** (không chỉ ẩn bằng CSS).
- Footer sidebar hiển thị tên + **vai trò** đang đăng nhập.
- Badge số việc cần xử lý: tin đang đăng, hồ sơ mới, tin nhắn chưa đọc.

### 4.2 Nguyên tắc form

1. **Nhóm fieldset theo nghiệp vụ**, mỗi nhóm một card: thông tin chính → nội dung → xuất bản/SEO.
2. Field bắt buộc có dấu `*` + lỗi hiển thị ngay dưới ô (`field__error`), lỗi tổng hiện đầu form.
3. Trường hệ thống/không sửa được hiển thị dạng bảng key–value (`applications detail`) hoặc text readonly, không render input.
4. Upload ảnh: xem trước ảnh hiện tại, tick "gỡ ảnh", ảnh nhỏ mobile riêng cho slide.
5. Quy ước nội dung nhiều dòng in ngay dưới textarea (bullet / `Tiêu đề | Mô tả` / đoạn cách dòng trống).
6. Bảng danh sách có: tìm kiếm, lọc trạng thái/danh mục, phân trang, thao tác nhanh (đổi trạng thái, sửa, xoá có xác nhận).
7. Ngày giờ theo `Asia/Ho_Chi_Minh`, hiển thị `d/m/Y H:i`; input `datetime-local`/`date`.

## 5. Phân quyền Admin

### 5.1 Ma trận quyền (nguồn: `app/Config/permissions.php`)

| Module | super_admin | hr_admin | recruiter |
|---|:--:|:--:|:--:|
| Bảng điều khiển | ✅ | ✅ | ✅ |
| Tin tuyển dụng | ✅ | ✅ | ✅ |
| Hồ sơ ứng tuyển | ✅ | ✅ | ✅ |
| Tin tức & bài viết | ✅ | ✅ | ✖ |
| Banner & thông báo | ✅ | ✅ | ✖ |
| Nội dung website | ✅ | ✅ | ✖ |
| Danh mục dùng chung | ✅ | ✅ | ✖ |
| Hộp thư liên hệ | ✅ | ✅ | ✖ |
| Cài đặt hệ thống | ✅ | ✖ | ✖ |
| Người dùng quản trị | ✅ | ✖ | ✖ |
| Nhật ký thao tác | ✅ | ✅ | ✖ |
| Hồ sơ cá nhân | ✅ | ✅ | ✅ |

`super_admin` bypass toàn bộ; module chưa khai báo mặc định chỉ super_admin.

### 5.2 Cơ chế thực thi (3 lớp, không thể lách bằng URL)

1. **Route middleware**: mỗi route admin đi qua `Auth → Permissions::require(module) → CSRF (nếu POST)`.
   Ví dụ `$router->get('/admin/posts', ..., $guard('posts'))`.
2. **Controller**: `UserController` kiểm tra super_admin lần nữa (defense in depth) và chặn tự khoá / mất super_admin cuối.
3. **Giao diện**: sidebar chỉ render module trong quyền; truy cập trực tiếp URL sai quyền nhận **trang 403** kèm tên vai trò và module bị chặn.

Đổi quyền chỉ cần sửa `app/Config/permissions.php` (không sửa route/controller).

### 5.3 Đã kiểm chứng

| Vai trò | Kết quả quét 12 module |
|---|---|
| super_admin | 12/12 → 200 |
| hr_admin | 10/12 → 200; `settings`, `users` → 403 |
| recruiter | 4/12 → 200; 8 module còn lại → 403 |

Đã kiểm thêm: sidebar recruiter không chứa mục ngoài quyền; POST vào module bị chặn trả 403 trước khi chạm CSRF/controller; trang 403 hiển thị đúng vai trò + module.

## 6. Còn lại

1. Phân quyền **theo bản ghi** (ví dụ recruiter chỉ xem hồ sơ của khu mình phụ trách) — hiện là theo module.
2. `focal_x/focal_y` của banner: chưa cần vì FE chưa dùng; nếu sau này cần crop ảnh thì mở 2 trường này.
3. Hai field `settings` dạng `map_embed` có thể dài; UI đã cho textarea 3 dòng — đủ dùng.
