# TEX-GIANG RECRUITMENT — Website tuyển dụng động (PHP MVC)

Website tuyển dụng của **Công ty Cổ phần TEX-GIANG (TGI)** — 3 khu sản xuất Tân Hương, Cái Lậy, Chợ Gạo (Đồng Tháp).

Dự án là bản chuyển từ site tĩnh sang **PHP thuần + PDO/MySQL**, giữ nguyên toàn bộ thiết kế (**Tailwind CSS build cục bộ**, Vanilla JS), thêm trang quản trị để **thêm/sửa/xoá** tin tuyển dụng và cấu hình website.

## Yêu cầu máy chủ

| Thành phần | Yêu cầu |
|---|---|
| PHP | 8.3 trở lên (đã kiểm thử trên 8.5) |
| Extension | `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `openssl` |
| MySQL | 8.0+ (InnoDB, utf8mb4) |
| Web server | Apache/cPanel có `mod_rewrite` (kèm `.htaccess`), hoặc Nginx cấu hình `try_files` |
| Composer | **Không cần** — autoloader tự viết, không có dependency runtime |
| Node.js | **Chỉ cần khi sửa giao diện** để build lại Tailwind (trên server không cần, `utilities.css` đã build sẵn) |

## Cấu trúc

```
tex-giang-recruitment/
├── app/
│   ├── bootstrap.php          khởi động: autoload, .env, session, xử lý lỗi
│   ├── helpers.php            e(), url(), asset(), settings(), slugify()...
│   ├── Config/permissions.php ma trận phân quyền admin (module × vai trò)
│   ├── Core/                  Env, Database (PDO), Router, Controller, Session, Csrf, Response, Permissions
│   ├── Middleware/            Auth, Guest, Csrf, Can (phân quyền module)
│   ├── Models/                Job, Application, Post, Banner, Setting, User, Branch, Category, PostCategory…
│   ├── Controllers/           Public/{Home,Job,Page,Application}, Admin/{Auth,Dashboard,Job,Application,
│   │                          Post,Banner,Content,Settings,Taxonomy,Contact,User,Profile,Audit}
│   └── Views/                 layouts/ + partials/ + public/ + admin/
├── assets/css/tailwind.source.css   input build Tailwind
├── public/                    ← DocumentRoot duy nhất
│   ├── index.php              front controller
│   ├── .htaccess              rewrite + header bảo mật
│   └── assets/                css (đã build) · js · images · uploads (ảnh CMS)
├── routes/                    web.php (công khai) · admin.php (quản trị)
├── database/                  schema.sql · seed.sql
├── storage/                   logs/ · uploads/cv (ngoài public)
├── tools/serve.ps1            server dev Windows
├── package.json · tailwind.config.cjs   build CSS (Node, chỉ dùng khi phát triển giao diện)
├── docs/                      01-ui-inventory · 02-database · 03-admin-guide · 04-fe-admin-field-map
│   └── reference/static-site/ bản gốc site tĩnh (HTML/CSS/JS demo, docs, tools) để đối chiếu
└── .env.example
```

## Cài đặt local (Windows, XAMPP/WAMP hoặc PHP CLI)

1. **Bật extension** trong `php.ini`: bỏ dấu `;` ở `extension=pdo_mysql` và `extension=mbstring` (file DLL đã có trong `ext/`).
2. **Tạo database** và import:
   ```sql
   CREATE DATABASE tex_giang_recruitment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   Import lần lượt `database/schema.sql` rồi `database/seed.sql` bằng phpMyAdmin hoặc:
   ```bash
   mysql -u root -p --default-character-set=utf8mb4 tex_giang_recruitment < database/schema.sql
   mysql -u root -p --default-character-set=utf8mb4 tex_giang_recruitment < database/seed.sql
   ```
3. **Tạo file `.env`** từ `.env.example`, điền thông tin DB.
4. **Chạy**:
   ```powershell
   # Windows: script đã bật sẵn extension cần thiết
   powershell -ExecutionPolicy Bypass -File tools/serve.ps1

   # hoặc nếu php.ini đã bật extension:
   php -S 127.0.0.1:8000 -t public
   ```
5. Mở http://127.0.0.1:8000 và http://127.0.0.1:8000/admin/login

## Giao diện & Tailwind CSS

Giao diện dùng **Tailwind CSS 3.4.17 build cục bộ** (không CDN, chạy được offline).

| File | Vai trò |
|---|---|
| `assets/css/tailwind.source.css` | Input `@tailwind base/components/utilities` — **sửa file này khi cần thêm plugin/layer** |
| `tailwind.config.cjs` | Token màu thương hiệu (accent, ink, muted, line, surface…), font, bán kính, bóng; quét `app/Views/**/*.php` + `public/assets/js/**/*.js` |
| `public/assets/css/utilities.css` | **File build — không sửa tay** (bị ghi đè mỗi lần build) |
| `public/assets/css/app.css`, `public.css`, `custom.css` | CSS component/hoàn thiện viết tay (giữ nguyên từ bản thiết kế) |
| `public/assets/css/admin-app.css` | CSS riêng cho giao diện quản trị (không phụ thuộc Tailwind) |
| `public/assets/vendor/sweetalert2/` | SweetAlert2 11.26.25 (vendor cục bộ) cho thông báo AJAX ở trang quản trị |
| `public/assets/vendor/{bootstrap,jquery,datatables}/` | Bootstrap 5.3.3 + jQuery 3.7.1 + DataTables 2.3.8 (vendor cục bộ) cho bảng quản trị |

Build lại sau khi thêm/sửa class Tailwind trong view hoặc JS:

```powershell
npm ci              # lần đầu
npm run build:css   # build một lần (minify) → public/assets/css/utilities.css
npm run watch:css   # dev: tự build lại khi sửa view
```

- Chỉ cần **commit file `public/assets/css/utilities.css` đã build** → deploy lên hosting không cần Node.
- Class Tailwind dùng trong view PHP hoặc chuỗi JS đều được quét; class ghép động hoàn toàn bằng PHP (ví dụ `'bg-' . $color`) thì **không** được quét — hãy viết đủ class trong view/JS.
- Giao diện admin hiện dùng class riêng `.admin-*` trong `admin-app.css`; vẫn có thể dùng utility Tailwind trong view admin nếu muốn.

## Tài khoản quản trị mặc định

```
Tên đăng nhập: admin
Mật khẩu     : Admin@123dev
```

> Đổi mật khẩu ngay sau khi cài: đăng nhập → **Hồ sơ cá nhân** (`/admin/profile`) → nhập mật khẩu hiện tại + mật khẩu mới.
> Nếu quên mật khẩu (chưa đăng nhập được), tạo hash mới rồi cập nhật DB:
> ```bash
> php -r "echo password_hash('MAT_KHAU_MOI', PASSWORD_DEFAULT), PHP_EOL;"
> ```
> ```sql
> UPDATE users SET password_hash = 'HASH_MOI' WHERE username = 'admin';
> ```

## Route đang hoạt động

| Method | URL | Chức năng |
|---|---|---|
| GET | `/` | Trang chủ (hero slider, tìm kiếm, việc làm nổi bật, CTA) |
| GET | `/jobs` | Danh sách + lọc khu/phòng ban/lương + sắp xếp + phân trang (server-side) |
| GET | `/jobs/{slug}` | Chi tiết tin, share, QR, tin tương tự |
| GET/POST | `/apply` | Form ứng tuyển thật: validate server-side, upload CV ≤10MB vào `storage/uploads/cv` |
| GET | `/apply/success` · `/apply/error` | Trang xác nhận (kèm mã hồ sơ) / trang lỗi |
| GET | `/news` | Tin tức: nổi bật, lọc chuyên mục, phân trang, đăng ký nhận tin |
| GET | `/news/{slug}` | Chi tiết bài viết + đọc thêm |
| GET/POST | `/news/subscribe` | Đăng ký nhận tin (lưu vào `contact_messages`) |
| GET | `/about`, `/why-tex-giang` | Trang giới thiệu / phúc lợi (design gốc) |
| GET/POST | `/contact` | Liên hệ: bản đồ 3 khu, form hỏi đáp lưu DB |
| GET/POST | `/admin/login` | Đăng nhập session + CSRF |
| POST | `/admin/logout` | Đăng xuất |
| GET | `/admin/dashboard` | KPI thật từ DB, hồ sơ mới nhất |
| GET | `/admin/jobs` | Danh sách tin, lọc trạng thái |
| GET/POST | `/admin/jobs/create` | Thêm tin |
| GET/POST | `/admin/jobs/{id}/edit` | Sửa tin |
| POST | `/admin/jobs/{id}/status` | Đổi trạng thái nhanh |
| POST | `/admin/jobs/{id}/duplicate` | Nhân bản thành bản nháp |
| POST | `/admin/jobs/{id}/delete` | Xoá mềm (giữ hồ sơ ứng tuyển) |
| GET | `/admin/applications` | Hồ sơ ứng tuyển: tìm, lọc trạng thái/vị trí, phân trang |
| GET | `/admin/applications/{id}` | Chi tiết hồ sơ + timeline + ghi chú + lịch phỏng vấn |
| POST | `/admin/applications/{id}/status` | Đổi trạng thái, ghi lịch sử |
| GET | `/admin/applications/{id}/cv` | Tải CV (chỉ khi đăng nhập, file ngoài public) |
| GET | `/admin/posts` · `/admin/posts/create` · `/admin/posts/{id}/edit` | CRUD tin tức + upload ảnh |
| POST | `/admin/posts/{id}/status` · `/admin/posts/{id}/delete` | Đổi trạng thái / xoá mềm bài viết |
| GET | `/admin/banners` · `/admin/banners/create` · `/admin/banners/{id}/edit` | CRUD banner 4 loại + lịch hiệu lực |
| POST | `/admin/banners/{id}/toggle` · `/admin/banners/{id}/delete` | Bật/tắt / xoá banner |
| GET/POST | `/admin/content` | Soạn nội dung trang chủ (hero, giới thiệu, số liệu) |
| GET/POST | `/admin/settings` | Cấu hình chung, liên hệ, mạng xã hội, SEO |
| GET | `/admin/contact` · `/admin/contact/{id}` | Hộp thư liên hệ + chi tiết/xử lý |
| GET | `/admin/categories` + `/{type}/{id}/edit` | Danh mục: khu sản xuất, khối phòng ban, chuyên mục tin tức |
| GET | `/admin/users` (super_admin) | Quản lý tài khoản quản trị, đặt lại mật khẩu |
| GET | `/admin/profile` | Hồ sơ cá nhân + đổi mật khẩu |
| GET | `/admin/audit` | Nhật ký thao tác quản trị |

### Phân quyền admin

Ma trận quyền nằm ở `app/Config/permissions.php` (module × vai trò), thực thi bằng middleware ở từng route:

| Module | super_admin | hr_admin | recruiter |
|---|:--:|:--:|:--:|
| Dashboard, Tin tuyển dụng, Hồ sơ, Hồ sơ cá nhân | ✅ | ✅ | ✅ |
| Tin tức, Banner, Nội dung, Danh mục, Hộp thư, Nhật ký | ✅ | ✅ | ✖ |
| Cài đặt hệ thống, Người dùng | ✅ | ✖ | ✖ |

`super_admin` bypass toàn bộ. Truy cập sai quyền trả trang 403; sidebar chỉ hiển thị module trong quyền.
Chi tiết cách suy ra trường từ FE + thiết kế Admin + ma trận quyền: [docs/04-fe-admin-field-map.md](docs/04-fe-admin-field-map.md).

## Triển khai cPanel

1. **Upload mã nguồn** lên hosting. Nên đặt code **ngoài** `public_html` (ví dụ `/home/user/tex-giang/`) rồi trỏ domain vào thư mục `public`.
   Nếu hosting không đổi được DocumentRoot: upload toàn bộ vào `public_html`, sửa `.htaccess` gốc của hosting để `RewriteRule ^ public/index.php [L]` và chặn truy cập `app/`, `database/`, `storage/`, `routes/`.
2. **Tạo database + user** trong cPanel → MySQL Databases (nhớ prefix, ví dụ `user_texgiang`). Gán full privileges.
3. **Import** `database/schema.sql` rồi `database/seed.sql` qua phpMyAdmin (tab Import, charset utf-8).
   Nếu đã có dữ liệu thật, **không** import `schema.sql` (file này DROP bảng); chỉ import seed khi tạo mới.
4. **Tạo `.env`** tại thư mục gốc dự án:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tuyendung.ten-mien.com
   APP_BASE=
   DB_HOST=localhost
   DB_NAME=user_texgiang
   DB_USER=user_texgiang
   DB_PASS=...
   COOKIE_SECURE=true
   ```
5. **Phân quyền**: `storage/uploads` và `storage/logs` ghi được (755 hoặc 775 tuỳ hosting).
6. **Bật HTTPS** (AutoSSL) — bắt buộc để `COOKIE_SECURE=true` hoạt động.
7. **Đổi mật khẩu admin** (mục trên), xoá `seed.sql` khỏi server nếu đã import xong.
8. Kiểm tra: `/`, `/jobs`, `/admin/login`, đăng nhập, thêm/sửa tin.
9. Sao lưu định kỳ database + thư mục `storage/uploads/cv`.

## Bảo mật đã có

- Session auth (`HttpOnly`, `SameSite=Lax`, `Secure` khi HTTPS), `session_regenerate_id` khi đăng nhập.
- CSRF token cho mọi form POST; sai token → quay lại trang trước, không ghi dữ liệu.
- Mọi output escape bằng `e()` (`htmlspecialchars ENT_QUOTES`).
- PDO prepared statement, `EMULATE_PREPARES=false` → không nối chuỗi SQL.
- Xoá mềm tin tuyển dụng, giữ toàn vẹn hồ sơ.
- `.env`, `app/`, `database/`, `storage/` nằm ngoài DocumentRoot; `.htaccess` chặn file ẩn.
- Audit log cho đăng nhập và thao tác tin tuyển dụng.

## Còn lại (roadmap)

1. Phân quyền theo từng bản ghi (recruiter chỉ thấy hồ sơ thuộc khu phụ trách); rate limit form liên hệ.
2. Nhập nốt 9 bài viết còn lại từ `news-data.js` (DB đang có 4 bài) qua module Tin tức.
3. Kiểm thử bảo mật độc lập, rà soát trên hosting cPanel thật (upload limit, quyền `storage/`), backup định kỳ.

> Lưu ý upload ảnh/CV: trên hosting cần đặt `upload_max_filesize` và `post_max_size` ≥ `12M`
> (form giới hạn CV 10MB, ảnh 5MB) — kiểm tra trong cPanel → Select PHP Version → Options hoặc `.user.ini`.

Xem chi tiết tiến độ: [docs/01-ui-inventory.md](docs/01-ui-inventory.md) · thiết kế DB: [docs/02-database.md](docs/02-database.md) · hướng dẫn quản trị: [docs/03-admin-guide.md](docs/03-admin-guide.md) · bản đồ trường FE→Admin + phân quyền: [docs/04-fe-admin-field-map.md](docs/04-fe-admin-field-map.md).
