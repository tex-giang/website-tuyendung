# Cài đặt & sử dụng khi pull về máy khác

Hướng dẫn cho người mới nhận source code từ GitHub: chạy local, sau đó deploy hosting.
Dự án nằm trong repo **https://github.com/tex-giang/website-tuyendung** (nhánh `main`),
thư mục con **`tex-giang-recruitment/`**.

---

## 1. Yêu cầu

| Thành phần | Yêu cầu |
|---|---|
| PHP | **8.3 trở lên** (đã chạy ổn trên 8.5) |
| Extension PHP | `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `openssl` |
| MySQL | 8.0+ (InnoDB, utf8mb4) |
| Web server | Apache/cPanel có `mod_rewrite` (khi deploy), hoặc PHP built-in server (khi dev) |
| Composer | **Không cần** — autoloader tự viết, không dependency PHP |
| Node.js | **Chỉ cần khi sửa giao diện** (build Tailwind). Chạy website thì không cần |

Kiểm tra nhanh:

```powershell
php -v
php -m | Select-String 'pdo_mysql|mbstring|fileinfo|openssl'
mysql --version
```

Linux/macOS:

```bash
php -v && php -m | grep -E 'pdo_mysql|mbstring|fileinfo|openssl' && mysql --version
```

---

## 2. Lấy code

```bash
git clone https://github.com/tex-giang/website-tuyendung.git
cd website-tuyendung/tex-giang-recruitment
```

> Repo có cả site tĩnh và bản PHP. Website đang chạy là thư mục `tex-giang-recruitment/`.
> Nếu được gửi file `.zip`: giải nén, vào thư mục `tex-giang-recruitment/`.

Những file **không có trong git** (phải tạo/copy riêng):

| Thiếu gì | Xử lý |
|---|---|
| `.env` | Copy từ `.env.example` rồi điền thông tin DB (bước 4) |
| `node_modules/` | Chỉ cần nếu sửa CSS: `npm ci` |
| `storage/uploads/`, `public/assets/uploads/` | File người dùng upload; khi chuyển máy copy thủ công (bước 10) |
| `vendor/` (Composer) | Không dùng |

Những thứ **đã có sẵn trong git**, không cần cài: `public/assets/vendor/` (Bootstrap, jQuery,
DataTables, SweetAlert2), `public/assets/css/utilities.css` (Tailwind build sẵn).

Quill dùng cho trình soạn thảo bài viết và được đóng gói trong `public/assets/vendor/quill/`.

---

## 3. Tạo database & import dữ liệu

Tạo database + user (MySQL CLI, hoặc phpMyAdmin → SQL):

```sql
CREATE DATABASE tex_giang_recruitment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tex_giang_user'@'localhost' IDENTIFIED BY 'MAT_KHAU_MOI';
GRANT ALL PRIVILEGES ON tex_giang_recruitment.* TO 'tex_giang_user'@'localhost';
FLUSH PRIVILEGES;
```

> Có thể dùng luôn `root` nếu máy local: đặt `DB_USER=root`, `DB_PASS=` (rỗng).

Import **theo đúng thứ tự** `schema.sql` → `seed.sql`:

**Windows – CMD (khuyến nghị, giữ đúng UTF-8):**

```bat
cd /d đường-dẫn\tex-giang-recruitment
"C:\xampp\mysql\bin\mysql.exe" -u root -p --default-character-set=utf8mb4 tex_giang_recruitment < database\schema.sql
"C:\xampp\mysql\bin\mysql.exe" -u root -p --default-character-set=utf8mb4 tex_giang_recruitment < database\seed.sql
```

*Đường dẫn `mysql.exe` tuỳ bản cài: XAMPP, Laragon, hoặc `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`.*

**Windows – PowerShell** (dùng `$OutputEncoding` để không lỗi font tiếng Việt):

```powershell
$OutputEncoding = [System.Text.UTF8Encoding]::new($false)
Get-Content -Raw .\database\schema.sql | & "C:\xampp\mysql\bin\mysql.exe" -u root -p --default-character-set=utf8mb4 tex_giang_recruitment
Get-Content -Raw .\database\seed.sql   | & "C:\xampp\mysql\bin\mysql.exe" -u root -p --default-character-set=utf8mb4 tex_giang_recruitment
```

**Linux/macOS:**

```bash
mysql -u root -p --default-character-set=utf8mb4 tex_giang_recruitment < database/schema.sql
mysql -u root -p --default-character-set=utf8mb4 tex_giang_recruitment < database/seed.sql
```

**phpMyAdmin:** chọn database → tab **Import** → chọn `schema.sql` → Import, rồi làm lại với `seed.sql`.
Chọn charset **utf-8** khi import.

> ⚠️ `schema.sql` có `DROP TABLE` — chỉ chạy khi cài mới. Máy đang có dữ liệu thật thì **không import lại schema**.

Sau khi import: 13 bảng, dữ liệu mẫu gồm 8 tin tuyển dụng, 4 bài viết, 7 banner, 3 khu sản xuất,
5 khối phòng ban, 6 chuyên mục, 32 cài đặt và tài khoản admin mặc định.

---

## 4. Tạo file `.env`

```powershell
# Windows
Copy-Item .env.example .env
```

```bash
# Linux/macOS
cp .env.example .env
```

Sửa `.env` cho máy mới:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Asia/Ho_Chi_Minh

APP_URL=http://127.0.0.1:8000
APP_BASE=

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=tex_giang_recruitment
DB_USER=tex_giang_user
DB_PASS=MAT_KHAU_MOI

COOKIE_SECURE=false
```

- `APP_BASE`: để trống nếu chạy ở tên miền gốc; đặt `/tuyendung` nếu nằm trong thư mục con.
- Production: `APP_ENV=production`, `APP_DEBUG=false`, `COOKIE_SECURE=true`.
- File `.env` **không commit** lên git (đã có trong `.gitignore`).

---

## 5. Quyền ghi thư mục

Windows thường không cần làm gì. Linux/macOS/Apache:

```bash
chmod -R 775 storage public/assets/uploads
# hoặc giao cho user web server:
sudo chown -R www-data:www-data storage public/assets/uploads
```

Cần ghi được: `storage/logs/`, `storage/uploads/cv/` (CV ứng viên — **không nằm trong public**),
`public/assets/uploads/` (ảnh bài viết/banner do admin tải lên).

---

## 6. Chạy website ở máy local

### Cách nhanh nhất: double-click file chạy sẵn

| Hệ điều hành | File | Cách dùng |
|---|---|---|
| Windows | **`RUN.bat`** | Double-click. Hoặc `RUN.bat 8080` nếu cổng 8000 bận |
| Linux/macOS | **`run.sh`** | `chmod +x run.sh` rồi `./run.sh` (hoặc `./run.sh 8080`) |

File chạy tự:
- Xác định đường dẫn dự án theo vị trí file (`%~dp0`) → **copy/move thư mục đi đâu cũng chạy được**,
  không cần `cd` và không hard-code ổ đĩa.
- Tìm PHP theo thứ tự: biến `PHP_EXE` → `PATH` → `C:\xampp\php` → `C:\laragon\bin\php` →
  `C:\php` → `C:\php\php-8.5.5-nts-Win32-vs17-x64`.
- Bật `pdo_mysql` + `mbstring`, kiểm tra cổng đang bận, rồi mở server tại
  `http://127.0.0.1:8000` và tự mở trình duyệt (đặt `NO_BROWSER=1` để không mở).
- Đóng cửa sổ / `Ctrl + C` là dừng server.

### Cách khác (dòng lệnh)

### Windows (khuyến nghị)

```powershell
cd tex-giang-recruitment
powershell -ExecutionPolicy Bypass -File tools/serve.ps1
```

Script tự bật `pdo_mysql` + `mbstring` và chạy server ở `http://127.0.0.1:8000`.
Đổi cổng nếu bận:

```powershell
$env:PORT=8080
powershell -ExecutionPolicy Bypass -File tools/serve.ps1
```

Hoặc chạy trực tiếp nếu `php.ini` đã bật extension:

```powershell
php -S 127.0.0.1:8000 -t public
```

### Linux/macOS

```bash
php -S 127.0.0.1:8000 -t public
# nếu extension chưa bật trong php.ini:
php -d extension=pdo_mysql -d extension=mbstring -S 127.0.0.1:8000 -t public
```

### XAMPP/Apache local

Cách gọn nhất vẫn là chạy `php -S` ở trên (không cần cấu hình Apache).
Nếu muốn dùng Apache: trỏ **DocumentRoot** (hoặc VirtualHost) vào thư mục `public/`,
bật `mod_rewrite`, mở `http://localhost/`.

---

## 7. Đăng nhập quản trị

| | |
|---|---|
| URL | `http://127.0.0.1:8000/admin/login` |
| Tài khoản | `admin` |
| Mật khẩu | `Admin@123dev` |

**Đổi mật khẩu ngay:** đăng nhập → bấm avatar góc phải trên → **Đổi mật khẩu**
(hoặc vào `/admin/profile` → mục “Đổi mật khẩu”).

Phân quyền có 3 vai trò: **Quản trị hệ thống** (toàn quyền), **Quản trị nhân sự**,
**Tuyển dụng** (giới hạn module). Tạo thêm user ở `/admin/users` (chỉ super_admin).

---

## 8. Kiểm tra sau khi chạy (checklist)

Mở lần lượt, tất cả phải trả trang bình thường:

| Trang | URL |
|---|---|
| Trang chủ | `http://127.0.0.1:8000/` |
| Việc làm + bộ lọc | `/jobs` |
| Chi tiết tin | `/jobs/300-cong-nhan-may-chuyen-cong-nghiep` |
| Tin tức | `/news` |
| Ứng tuyển | `/apply` |
| Liên hệ | `/contact` |
| Đăng nhập admin | `/admin/login` → dashboard |

Kiểm tra nhanh bằng CLI:

```powershell
curl.exe -s -o NUL -w "%{http_code}`n" http://127.0.0.1:8000/
curl.exe -s -o NUL -w "%{http_code}`n" http://127.0.0.1:8000/admin/login
```

Cả hai trả `200` là ổn.

---

## 9. Chỉ khi cần sửa giao diện (Tailwind)

Chạy website **không cần** Node. Chỉ khi thêm/đổi class Tailwind trong `app/Views/**` hoặc JS:

```powershell
npm ci              # cài đúng version trong package-lock.json
npm run build:css   # build một lần
npm run watch:css   # hoặc: tự build khi sửa view
```

- Sửa màu/font/bo góc: `tailwind.config.cjs`
- File build: `public/assets/css/utilities.css` (đã commit sẵn; **không sửa tay**)
- `admin-app.css`, `app.css`, `public.css`, `custom.css`: CSS viết tay, sửa trực tiếp
- Thư viện admin (Bootstrap, jQuery, DataTables, SweetAlert2) đã vendor trong
  `public/assets/vendor/` — thêm bảng mới chỉ cần class `js-datatable` (xem `docs/03-admin-guide.md`).

---

## 10. Deploy hosting thật (cPanel/Apache)

1. **Upload code**: chỉ cần đưa toàn bộ thư mục lên hosting (không cần `node_modules/`).
   Nên upload dạng zip rồi giải nén cho nhanh.
2. **DocumentRoot** trỏ vào thư mục **`public/`** (quan trọng: không trỏ vào thư mục gốc dự án,
   vì code/DB/CV nằm ngoài public).
   Nếu hosting không đổi được DocumentRoot: upload `public/*` vào `public_html/` và sửa đường dẫn
   trong `public/index.php`/`app/bootstrap.php` cho trỏ về thư mục code — hoặc đặt cả dự án trong
   `public_html/tuyendung/` và cấu hình `APP_BASE=/tuyendung`.
3. **Database**: tạo DB trong cPanel → phpMyAdmin → import `database/schema.sql` rồi `database/seed.sql`
   (charset `utf8mb4`).
4. **`.env` production**:
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tuyendung.ten-cong-ty.vn
   APP_BASE=
   DB_HOST=localhost
   DB_NAME=...
   DB_USER=...
   DB_PASS=...
   COOKIE_SECURE=true
   ```
5. **Quyền ghi**: `storage/logs`, `storage/uploads`, `public/assets/uploads` (chmod 755/775).
6. **PHP & extension**: chọn PHP 8.3+ trong cPanel, bật `pdo_mysql`, `mbstring`, `fileinfo`.
7. **Giới hạn upload** (`php.ini`/MultiPHP INI): `upload_max_filesize` và `post_max_size` **≥ 12M**
   (CV cho phép tối đa 10MB).
8. **mod_rewrite**: bật; file `public/.htaccess` đã có sẵn rewrite. Nếu deploy trong thư mục con,
   bỏ comment và sửa `RewriteBase /tuyendung/` cho khớp `APP_BASE`.
9. **HTTPS**: bật SSL (Let's Encrypt trong cPanel), đặt `COOKIE_SECURE=true`.
10. **Cron/backup**: không có cron bắt buộc; nên đặt lịch backup DB + thư mục uploads.

---

## 11. Chuyển dữ liệu thật sang máy/hosting mới

Git **không chứa** dữ liệu người dùng đã nhập và file upload. Khi chuyển:

```bash
# 1) Dump database
mysqldump -u root -p --default-character-set=utf8mb4 tex_giang_recruitment > backup.sql
# Import ở máy mới:
mysql -u root -p --default-character-set=utf8mb4 tex_giang_recruitment < backup.sql

# 2) Copy file upload
#    - CV ứng viên:      storage/uploads/cv/
#    - Ảnh CMS (bài/banner): public/assets/uploads/
```

Nếu chỉ muốn dữ liệu mẫu thì chỉ cần import `schema.sql` + `seed.sql` như bước 3.

---

## 12. Lỗi thường gặp

| Hiện tượng | Nguyên nhân & cách xử lý |
|---|---|
| `could not find driver` | Thiếu `pdo_mysql`. Bật trong `php.ini` hoặc dùng `tools/serve.ps1` |
| `Access denied for user` | Sai `DB_USER`/`DB_PASS` trong `.env`; kiểm tra lại user MySQL đã GRANT chưa |
| `Unknown database` | Chưa tạo DB / sai `DB_NAME`; import lại bước 3 |
| Trang trắng / lỗi 500 | Bật `APP_DEBUG=true` để xem lỗi; xem `storage/logs/`; thường do thiếu extension hoặc sai `.env` |
| Tiếng Việt bị lỗi font (`?`, `Ã`) | Import sai charset — import lại với `--default-character-set=utf8mb4` (hoặc phpMyAdmin chọn utf-8) |
| Vào `/` ra 404, `/index.php` vẫn được | Apache thiếu `mod_rewrite` hoặc DocumentRoot chưa trỏ `public/` |
| CSS/JS admin không đổi sau khi sửa | Xoá cache trình duyệt: **Ctrl + F5** |
| Port 8000 bận | `$env:PORT=8080` rồi chạy lại `tools/serve.ps1` |
| Upload ảnh/CV báo lỗi | Thiếu quyền ghi `storage/uploads`/`public/assets/uploads`, hoặc giới hạn upload PHP < 12M |
| Admin vào trang bị 403 | Tài khoản không có quyền module đó — dùng tài khoản super_admin |
| Ảnh upload mất sau khi pull code mới | Ảnh nằm ngoài git (`public/assets/uploads/`), không bị ghi đè khi pull; kiểm tra lại quyền/đường dẫn |

---

## 13. Cấu trúc nhanh

```
tex-giang-recruitment/
├── app/                 mã nguồn PHP (Core, Models, Controllers, Views, Config)
├── public/              ← DocumentRoot: index.php, .htaccess, assets/
│   └── assets/          css, js, images, uploads (ảnh CMS), vendor (thư viện front-end)
├── routes/              web.php (công khai) · admin.php (quản trị)
├── database/            schema.sql · seed.sql
├── storage/             logs/ · uploads/cv/ (CV riêng tư, ngoài public)
├── tools/serve.ps1      server dev Windows
├── assets/css/          tailwind.source.css (input build Tailwind)
├── docs/                01…05 hướng dẫn & tài liệu kỹ thuật
├── tailwind.config.cjs  cấu hình build CSS
└── .env.example         mẫu cấu hình môi trường
```

Tài liệu chi tiết thêm: `docs/01-ui-inventory.md`, `docs/02-database.md`,
`docs/03-admin-guide.md` (phân quyền, AJAX, bảng DataTables), `docs/04-fe-admin-field-map.md`.

> **Nâng cấp DB đang có dữ liệu:** không import lại `schema.sql`. Chạy lần lượt các file trong
> `database/migrations/` (theo thứ tự tên file): `2026-11-07-branch-contacts.sql`,
> `2026-11-08-page-content-maps.sql` (thêm bảng nội dung Về chúng tôi/Phúc lợi + cột bản đồ cho `branches`),
> `2026-11-08-menus.sql` (bảng menu website), `2026-11-08-menus-single.sql` (gộp menu header/footer thành một danh sách),
> `2026-11-08-visit-stats.sql` (bảng thống kê lượt truy cập theo ngày + thiết bị),
> `2026-11-08-job-code-td26.sql` (chuyển mã tin tuyển dụng sang định dạng TD26-0001), `2026-11-08-job-expiry.sql` (trạng thái Hết hạn + số ngày nhận hồ sơ mặc định), `2026-11-10-fix-cai-lay-accent.sql` (sửa chính tả “Cai Lậy”).
> Bản `schema.sql`/`seed.sql` mới đã gồm sẵn các bảng/cột này cho cài mới.

### Nếu hosting không đổi được DocumentRoot vào `public/`

Hosting mặc định trỏ vào `public_html/` sẽ chạy được `.../public/index.php` nhưng URL bị dư `/public`.
Có 2 cách:

1. **Khuyến nghị:** cPanel → Domains → đổi Document Root của domain/subdomain vào thư mục `public/`.
   Sau đó `.env` để `APP_BASE=` và mở `https://domain.com/`.
2. **Không đổi được DocumentRoot:** upload code sao cho gốc domain có `app/`, `public/`, `.env`…
   rồi copy file `docs/deploy-root-htaccess.txt` → đổi tên thành `.htaccess` đặt ở gốc domain.
   `.env` để `APP_BASE=`. Website chạy tại `https://domain.com/` (URL sạch, không cần `/public`).

Nếu chấp nhận URL dư `/public/` tạm thời: chỉ cần `.env` đặt `APP_BASE=/public` rồi vào
`https://domain.com/public/`.