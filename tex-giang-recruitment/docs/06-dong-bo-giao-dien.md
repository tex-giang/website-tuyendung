# Đồng bộ giao diện: PHP → web tĩnh

> Quy ước: **PHP là nguồn** — sửa giao diện ở PHP trước, sau đó đồng bộ sang web tĩnh
> (`E:\websitetuyendung`). Không sửa ngược lại ở web tĩnh rồi copy về PHP.

## Hai nơi, hai vai trò

| | `tex-giang-recruitment/` (PHP) | `E:\websitetuyendung` (tĩnh) |
|---|---|---|
| Vai trò | Website chính thức, dữ liệu MySQL | Bản thiết kế gốc + demo tĩnh (deploy Vercel) |
| Markup | `app/Views/` (layouts, partials, public) | `*.html` ở thư mục gốc |
| CSS | `public/assets/css/` | `assets/css/` |
| JS | `public/assets/js/` (bản thật) | `assets/js/` (bản demo + vài file dùng chung) |
| Ảnh | `public/assets/images/` | `assets/images/` |
| Build CSS | `npm run build:css` → `public/assets/css/utilities.css` | `npm run build:css` → `assets/css/utilities.css` |

Hai bộ **Tailwind build riêng** (glob quét file khác nhau) — không copy `utilities.css` giữa hai bên.

## Việc gì script tự đồng bộ

Chạy sau mỗi lần sửa giao diện PHP:

```powershell
cd E:\websitetuyendung\tex-giang-recruitment
powershell -ExecutionPolicy Bypass -File tools/sync-static.ps1
```

Script copy các file **dùng chung** PHP → tĩnh và build lại Tailwind tĩnh:

| File | Nội dung |
|---|---|
| `assets/css/public.css` | CSS trang công khai (bộ lọc sticky, hero, tin tức…) |
| `assets/css/app.css` | CSS component nền |
| `assets/css/custom.css` | CSS tuỳ chỉnh |
| `assets/js/main.js` | JS dùng chung (menu mobile, popup, QR, toast…) |
| `assets/js/ui.js` | Đo chiều cao header, ẩn bộ lọc khi tới cuối, reveal |
| `assets/js/home-slider.js` | Slider hero |

Tuỳ chọn:

```powershell
... sync-static.ps1 -Check     # chỉ báo file lệch, không ghi
... sync-static.ps1 -Images    # copy thêm ảnh mới trong public/assets/images
... sync-static.ps1 -NoBuild   # không build lại Tailwind tĩnh
```

## Việc gì phải port thủ công

### 1. Markup HTML (khi đổi bố cục/khối/chữ trên trang)

Bảng đối chiếu trang:

| Trang tĩnh | View PHP | Route |
|---|---|---|
| `index.html` | `app/Views/public/home.php` | `/` |
| `jobs.html` | `app/Views/public/jobs/index.php` | `/jobs` |
| `job-detail.html` | `app/Views/public/jobs/detail.php` | `/jobs/{slug}` |
| `news.html` | `app/Views/public/news/index.php` | `/news` |
| `news-detail.html` | `app/Views/public/news/detail.php` | `/news/{slug}` |
| `apply.html` | `app/Views/public/apply/index.php` | `/apply` |
| `ung-tuyen.html` (trang nộp nhanh, layout riêng) | `app/Views/public/ung-tuyen.php` + `layouts/standalone.php` | `/tuyen-dung` |
| `application-success.html` | `app/Views/public/apply/success.php` | `/apply/success` |
| `application-error.html` | `app/Views/public/apply/error.php` | `/apply/error` |
| `contact.html` | `app/Views/public/contact.php` | `/contact` |
| `about.html` | `app/Views/public/about.php` | `/about` |
| `why-tex-giang.html` | `app/Views/public/why.php` | `/why-tex-giang` |
| `404.html` | `app/Views/public/404.php` | lỗi 404 |
| `admin.html`, `index2.html`, `why-us.html`, `search-results.html` | — | bỏ, không dùng |

Header/footer của mọi trang: `app/Views/partials/public/header.php`, `footer.php`.
Khi header/footer đổi → phải sửa **tất cả** file HTML tĩnh (mỗi trang chứa bản sao header/footer).

> Có thể dùng `tools/port-static-page.mjs` để port một trang tĩnh mới sang view PHP
> (chiều ngược lại với đồng bộ này).

### 2. JS riêng từng trang — KHÔNG copy từ PHP sang

Bản tĩnh dùng dữ liệu giả (MOCK_JOBS, localStorage) nên JS khác hẳn bản thật:

| File tĩnh | Bản PHP tương ứng | Ghi chú |
|---|---|---|
| `jobs-data.js`, `news-data.js`, `admin-data.js` | — | Dữ liệu demo, chỉ có ở bản tĩnh |
| `jobs.js`, `news.js`, `job-detail.js`, `news-detail.js` | `jobs.js`, `apply-form.js`… | Viết lại cho dữ liệu thật |
| `apply-form.js`, `apply-dialog.js`, `demo-store.js` | `apply-form.js`, `apply-dialog.js` | Khác kiến trúc (PHP POST thật) |
| `home-urgent-dialog.js` | `home-urgent-dialog.js` | Bản PHP đọc JSON từ DB, bản tĩnh đọc MOCK_JOBS |
| `admin.js` | — | Admin tĩnh cũ, đã thay bằng admin PHP |

### 3. Admin

Admin của PHP (`app/Views/admin/` + `public/assets/css/admin-app.css` + `public/assets/js/admin-*.js`)
**không có bản tương ứng** ở web tĩnh — bỏ qua khi đồng bộ.

## Checklist sau mỗi thay đổi giao diện

1. Sửa ở PHP: view (`app/Views/...`) và/hoặc CSS/JS (`public/assets/...`).
2. Nếu thêm class Tailwind: `npm run build:css` trong thư mục PHP.
3. Chạy `tools/sync-static.ps1` để đồng bộ CSS/JS dùng chung + build Tailwind tĩnh.
4. Port markup (nếu có đổi HTML) theo bảng đối chiếu ở trên.
5. Kiểm tra **cả hai**:
   - PHP: `http://127.0.0.1:8000/` (server dev) — trang liên quan + admin.
   - Tĩnh: `cd E:\websitetuyendung; npm run preview` → `http://127.0.0.1:4173/` — trang liên quan.
6. So sánh bằng mắt (hoặc chụp màn hình) hai bản: bố cục, màu, khoảng cách, hành vi (slider, popup, bộ lọc, form).

## Ghi chú

- Ảnh: PHP có thể có thêm ảnh upload (`public/assets/uploads`) — không đưa sang tĩnh.
- Nội dung chữ/số liệu/tin tuyển dụng thay đổi hàng ngày nằm trong **Admin/DB**, không sửa view —
  web tĩnh vẫn dùng dữ liệu demo của nó, không cần đồng bộ nội dung.
- Nếu deploy web tĩnh lên Vercel từ root repo: nên thêm `.vercelignore` loại `tex-giang-recruitment/`
  để không public source PHP.

## Ghi chú: danh sách người liên hệ ở footer

- **PHP (nguồn)**: footer + trang Liên hệ + chi tiết tin đọc động từ bảng `branch_contacts`
  (sửa ở **Danh mục → Khu sản xuất**). Không cần sửa view.
- **Web tĩnh**: footer chứa nút “Liên hệ chi nhánh” (cột Kết nối + nút nổi cố định góc phải) + hộp thoại danh sách liên hệ **demo hard-code**
  (cùng số với seed); trang Liên hệ cũng hard-code danh sách. Khi danh sách người phụ trách thay đổi,
  phải sửa tay block dialog/footer trong các file HTML tĩnh.

## Ghi chú: plugin fanpage + nút FB cố định

- Footer nhúng **Facebook Page Plugin**: PHP đặt trong `app/Views/partials/public/footer.php`;
  web tĩnh đặt trong **13 trang** (block `.footer-fanpage`) kèm `#fb-root` + SDK
  `connect.facebook.net/vi_VN/sdk.js`. Đổi fanpage thì sửa `data-href`/`blockquote cite` ở tất cả các nơi đó.
- Link cho nút FB float + chữ “Facebook” footer: PHP đọc **Cài đặt → Mạng xã hội → Facebook**
  (hiện `https://www.facebook.com/profile.php?id=100063719192197`); web tĩnh hard-code cùng link —
  sửa cả 2 nơi khi đổi.
- Ảnh placeholder cũ `fanpage-fb.svg` không còn dùng ở footer.

## Ghi chú: lọc khu vực → vị trí trong dialog ứng tuyển

- **PHP**: dialog ứng tuyển (`app/Views/partials/public/apply-dialog.php` + `public/assets/js/apply-dialog.js`)
  có nhóm radio chọn khu vực; danh sách vị trí được lọc theo tin đang tuyển của khu đó
  (dữ liệu render vào `#apply-dialog-jobs` dạng JSON). Chọn từ link `?job=...` sẽ tự chọn đúng khu.
- **Web tĩnh**: form demo (`assets/js/apply-form.js`) có select “Khu sản xuất” và “Vị trí” nhưng
  **không lọc** theo khu — đây là logic demo riêng, không dùng chung JS với bản PHP.

## Ghi chú: trang nộp hồ sơ rút gọn `ung-tuyen.html` (tĩnh) — PHP: `/tuyen-dung`

- Đã thêm chọn **nơi làm việc trước** (radio) rồi mới hiện **vị trí theo khu vực**; dữ liệu vị trí lấy từ
  `assets/js/jobs-data.js` (mock, mỗi job có `locationCode` — tương ứng `branch_id` bên PHP).
- Trang này là demo tĩnh, không nối database; bản thật tương ứng là trang `/apply` (PHP) — trang này
  cũng đã được lọc khu vực → vị trí từ bảng `jobs`.
- Mở thử: `http://127.0.0.1:4173/ung-tuyen.html` (sau `npm run preview` trong thư mục tĩnh).

## Ghi chú: nội dung Về chúng tôi / Phúc lợi + bản đồ (chỉ PHP)

- Trang `/about` và `/why-tex-giang` render từ các bảng `about_*`, `benefit_*` (quản trị ở
  **Nội dung → Về chúng tôi / Phúc lợi**). Bản tĩnh `about.html` / `why-tex-giang.html` vẫn là
  nội dung hard-code để tham chiếu thiết kế — không tự đồng bộ nội dung từ DB.
- Bản đồ trang `/contact` và hộp thoại liên hệ footer đọc `branches.map_url` / `branches.map_link`
  (sửa ở Admin → Danh mục → Khu sản xuất). Bản tĩnh `contact.html` vẫn hard-code 3 iframe.

## Ghi chú: menu website (chỉ PHP)

- Menu header/footer render từ bảng menus (Admin → **Menu website**). Bản tĩnh vẫn hard-code
  danh sách menu trong các file HTML — khi đổi menu phải sửa tay bản tĩnh.
- Thống kê lượt truy cập (`/admin/visits`) và khối *Thống kê truy cập* ở footer là tính năng riêng của bản PHP — bản tĩnh không có.
