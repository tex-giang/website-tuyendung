# Bước 1 — Kiểm kê giao diện tĩnh → web động

Ngày lập: 06/11/2026 · Nguồn: `E:\websitetuyendung` (site tĩnh) → `E:\websitetuyendung\tex-giang-recruitment` (PHP).

## 1. Trang công khai: 17 file tĩnh → route PHP

| # | File tĩnh | Nội dung chức năng | Route PHP | Trạng thái |
|---|---|---|---|---|
| 1 | `index.html` | Hero slider, tìm kiếm nhanh, 4 số liệu, carousel việc làm, dải CTA, giới thiệu, image story | `GET /` | ✅ đã port, dữ liệu DB + dialog "Tuyển gấp" tự mở |
| 2 | `jobs.html` | Bộ lọc 4 tiêu chí + sắp xếp + phân trang + trạng thái đóng | `GET /jobs` | ✅ server-side filter/phân trang |
| 3 | `job-detail.html` | JD, yêu cầu, quyền lợi, địa điểm, card ứng tuyển, share, QR | `GET /jobs/{slug}` | ✅ |
| 4 | `apply.html` | Form ứng tuyển, chọn vị trí, kéo thả CV, validate | `GET/POST /apply` | ✅ form POST thật + upload CV; dialog ứng tuyển nhanh mở từ mọi nút "Ứng tuyển" |
| 5 | `ung-tuyen.html` | Bản ứng tuyển rút gọn | gộp vào `/apply` | ✅ |
| 6 | `application-success.html` | Xác nhận đã gửi hồ sơ | `GET /apply/success` | ✅ kèm mã hồ sơ HS-xxxxx |
| 7 | `application-error.html` | Báo lỗi gửi hồ sơ | `GET /apply/error` | ✅ |
| 8 | `about.html` | Giới thiệu, tầm nhìn, timeline chứng nhận | `GET /about` | ✅ port nguyên design |
| 9 | `why-tex-giang.html` | 4 trụ cột phúc lợi, bảng so sánh | `GET /why-tex-giang` | ✅ port nguyên design |
| 10 | `news.html` | Bài nổi bật + danh sách tin, lọc chuyên mục, phân trang | `GET /news` | ✅ dữ liệu `posts` |
| 11 | `news-detail.html` | Chi tiết bài + share + đọc thêm | `GET /news/{slug}` | ✅ |
| 12 | `contact.html` | Địa chỉ, bản đồ 3 khu, form hỏi đáp | `GET/POST /contact` | ✅ lưu `contact_messages` |
| 13 | `search-results.html` | Chuyển hướng tìm kiếm | gộp vào `/jobs?q=` | ✅ thay bằng route |
| 14 | `404.html` | Trang lỗi | router 404 | ✅ |
| 15 | `admin.html` | Redirect vào admin | `/admin` | ✅ |
| 16 | `why-us.html` | Redirect | `/why-tex-giang` | ✅ |
| 17 | `index2.html` | Bản thử hero cũ | bỏ | — |

## 2. Trang quản trị: 10 file tĩnh → route admin

| # | File tĩnh | Chức năng | Route PHP | Trạng thái |
|---|---|---|---|---|
| 1 | `login.html` | Đăng nhập (demo) | `GET/POST /admin/login` | ✅ session thật |
| 2 | `dashboard.html` | KPI, hồ sơ theo tháng/vị trí/nguồn | `GET /admin/dashboard` | ✅ KPI thật |
| 3 | `jobs.html` | Danh sách tin, đóng/mở, nhân bản, xoá | `GET /admin/jobs` | ✅ |
| 4 | `job-form.html` | Thêm/sửa tin, rich text, hẹn nộp | `GET/POST /admin/jobs/create`, `/admin/jobs/{id}/edit` | ✅ |
| 5 | `applications.html` | Danh sách hồ sơ, lọc trạng thái/vị trí, tải CV | `GET /admin/applications` | ✅ lọc + tìm + tải CV |
| 6 | `application-detail.html` | Timeline, ghi chú nội bộ, liên hệ | `GET /admin/applications/{id}` | ✅ timeline + đổi trạng thái |
| 7 | `content.html` | Sửa trang chủ/slogan/giới thiệu/tin tức | `GET/POST /admin/content` | ✅ hero, giới thiệu, 4 số liệu |
| 8 | `banners.html` | Banner chính, thông báo, popup | `GET/POST /admin/banners` | ✅ 4 loại + lịch hiệu lực |
| 9 | `settings.html` | SEO, pháp nhân, cấu hình | `GET/POST /admin/settings` | ✅ 4 nhóm cấu hình |
| 10 | — (mới) | Người dùng quản trị, audit log, hộp thư | `/admin/users`, `/admin/audit`, `/admin/contact` | ✅ |

## 3. Dữ liệu tĩnh → bảng MySQL

| Nguồn tĩnh | Trường dữ liệu | Bảng đích |
|---|---|---|
| `assets/js/jobs-data.js` (8 tin) | title, quantity, location, department, salary, deadline, tags, status, description | `jobs` + `branches`, `categories` |
| `admin-data.js` → seedJobs | requirements, benefits, salaryMin/Max, state | `jobs` |
| `admin-data.js` → candidates | name, phone, email, birth, address, jobId, status, note, source, message, history | `applications`, `application_history` |
| `news-data.js` (14 bài) | title, category, ảnh, alt, caption, excerpt, paragraphs, date | `posts`, `post_categories` (đã seed 4 bài) |
| `admin-data.js` → settings/content | siteName, legalName, address, SEO, hotline, ưu đãi… | `settings` (nhóm general/contact/seo/social/content) |
| `admin-data.js` → banner | ảnh, alt, focal, notice, link, enabled | `banners` (4 hero + notice + popup + ad) |
| `assets/js/main.js` | QR, share, popup, toast, mobile menu | giữ nguyên, chỉ đổi `.html` → route PHP |
| `assets/js/jobs.js` | lọc client + phân trang client | thay bằng lọc server + GET params |
| `assets/js/home-jobs-slider.js` | slider đọc MOCK_JOBS | thay `home-jobs.js` đọc card do PHP render |

## 4. Quyết định kỹ thuật đã chốt

- **Auth**: chuyển từ JWT cookie sang **PHP session** (`Session` + CSRF token). Xoá `JwtService`, `RefreshToken`, `firebase/php-jwt`.
- **Không dùng Composer ở runtime**: autoloader PSR-4 tự viết trong `app/bootstrap.php`; xoá `vlucas/phpdotenv`, dùng `Env` tự parse `.env`. Phù hợp shared hosting.
- **DB**: PDO + prepared statement, `EMULATE_PREPARES=false`, utf8mb4. Không dùng `mysqli`.
- **Giao diện**: port nguyên CSS/JS/ảnh tĩnh sang `public/assets`; header/footer tách partial; mọi asset đi qua `asset()` để đổi domain/thư mục con được.
- **Xoá toàn bộ demo**: bỏ fixtures JS, sessionStorage, nút "Vào bản demo", dữ liệu seed coi là nội dung thật (xem `FACT-SHEET.md`).

## 5. Còn lại (không còn module bắt buộc)

1. Phân quyền chi tiết theo module/bản ghi cho `hr_admin`/`recruiter` (hiện chỉ chặn trang người dùng).
2. Nhập nốt 9 bài viết còn lại từ `news-data.js` qua module Tin tức (DB đang có 4 bài seed).
3. Kiểm thử bảo mật độc lập + rà soát trên hosting cPanel thật (upload limit, quyền `storage/`), backup định kỳ.
4. Rate limit cho form Liên hệ (form Ứng tuyển đã có giới hạn 5 hồ sơ/giờ/phiên).
