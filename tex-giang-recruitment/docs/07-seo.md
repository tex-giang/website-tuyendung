# 07 — SEO: để website lên Google

> Tài liệu vận hành. Đọc mục 1 trước — thiếu bước đó thì mọi thứ bên dưới vô nghĩa.

## 1. Điều kiện tiên quyết: website phải có địa chỉ công khai

Google chỉ lập chỉ mục trang tải được từ Internet. Bản đang chạy `APP_URL=http://127.0.0.1:8000`
(`APP_ENV=local`) là máy nội bộ — **không có cách nào lên Google**, kể cả khi code SEO đã đúng.

Checklist khi đưa lên hosting:

1. Trỏ domain về hosting, bật HTTPS.
2. Trong `.env` của server:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tuyendung.ten-mien-that.vn
   APP_BASE=
   ```
   `APP_URL` là gốc của **mọi** URL trong sitemap và JSON-LD — sai giá trị này thì
   Google nhận được link `127.0.0.1` và bỏ qua toàn bộ.
3. DocumentRoot trỏ vào `public/` để `.htaccess` rewrite `robots.txt` / `sitemap.xml` về `index.php`.
   Nếu buộc phải chạy trong thư mục con thì đặt `APP_BASE=/ten-thu-muc`.
4. `APP_DEBUG=false`: khi bật, mọi lỗi in ra HTML và Google đọc được thông tin nội bộ.

Kiểm tra sau khi deploy:

```bash
curl -s https://tuyendung.ten-mien-that.vn/robots.txt
curl -s https://tuyendung.ten-mien-that.vn/sitemap.xml | head -30
curl -s https://tuyendung.ten-mien-that.vn/jobs/<slug> | grep -o 'canonical[^>]*'
```

## 2. Đã bổ sung trong mã nguồn

| Thành phần | Ở đâu | Tác dụng |
|---|---|---|
| `Seo::canonical()` | `app/Views/layouts/public.php` | Mỗi trang có `<link rel="canonical">` bỏ hết query (`?page=`, `?chuyen-muc=`, `utm_*`) — Google gộp biến thể về một URL |
| `robots.txt` | route `/robots.txt` → `SeoController::robots()` | Cho phép thu thập, chặn `/admin`, khai báo `Sitemap:` |
| `sitemap.xml` | route `/sitemap.xml` → `SeoController::sitemap()` | Sinh từ DB: trang tĩnh + tin đang đăng + bài đã đăng, có `lastmod` |
| **JobPosting** | `Seo::jobPosting()` — trang chi tiết tin | Nguồn duy nhất để tin lên **Google Jobs**: tiêu đề, mô tả, lương, địa điểm, hạn nộp, số lượng |
| `NewsArticle` | `Seo::article()` — trang chi tiết bài | Google hiểu bài viết, lấy được ảnh + ngày đăng |
| `Organization` + `WebSite` | trang chủ | Gốc thực thể, nối các trang con về một tổ chức |
| `BreadcrumbList` | các trang chính | Hiện đường dẫn trong kết quả tìm kiếm thay vì URL thô |
| `ItemList` | `/jobs`, `/news` | Báo cho Google đây là trang danh sách |
| `noindex` | `/apply`, `/apply/success`, `/apply/error`, 404, `/tuyen-dung` | Không lập chỉ mục trang không có giá trị tìm kiếm |
| `og:image` tuyệt đối | layout | Trước đây là đường dẫn tương đối nên Facebook/Zalo không tải được ảnh chia sẻ |
| Favicon | `tools/build-favicons.ps1` | `favicon.ico` (16/32/48) + PNG 32/16 + `apple-touch-icon` 180 + `icon-192/512` |

Cấu trúc dữ liệu nằm ở `app/Core/Seo.php`. Mọi node được gói trong một `@graph` để giảm số thẻ `<script>`.

## 3. Sau khi deploy: gửi Google

1. **Google Search Console** → thêm property dạng *Domain* → xác minh bằng DNS TXT (Cloudflare).
2. *Sitemaps* → gửi `https://tuyendung.ten-mien-that.vn/sitemap.xml`.
3. *URL Inspection* → dán URL trang chủ + 1 tin tuyển dụng → **Request indexing**.
4. Kiểm tra cấu trúc dữ liệu: [Rich Results Test](https://search.google.com/test/rich-results)
   dán URL một tin tuyển dụng, phải báo **JobPosting — hợp lệ**.
5. Google Jobs thường mất **3–7 ngày** mới hiển thị; kết quả tìm kiếm thường 1–4 tuần.

## 4. Bảo trì

- Tin hết hạn: `Job::syncExpired()` chạy mỗi lần tải trang job, tự rời sitemap → không cần dọn tay.
- Đăng tin/bài mới: sitemap tự cập nhật, không phải sửa file.
- Nếu đổi domain: chỉ cần sửa `APP_URL`, không phải sửa code.
- Đừng chặn `/assets/` trong `robots.txt` ngoài `vendor/`: Google cần tải CSS/JS để đánh giá
  chất lượng trang trên điện thoại.

## 5. Phát triển local

PHP built-in server tự trả 404 cho đường dẫn **có phần mở rộng** mà không trùng file thật, nên
`/robots.txt` và `/sitemap.xml` không tới được `index.php`. Chạy kèm router:

```powershell
php -S 127.0.0.1:8000 -t public public/router.php
```

Trên hosting thật không dùng `router.php` — `.htaccess` đã lo việc rewrite.
