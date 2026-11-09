# Hướng dẫn trang quản trị

Trang quản trị: `/admin/login` · tài khoản mặc định `admin / Admin@123dev` (đổi ngay).

## 1. Vai trò & quyền

Ma trận quyền được khai báo tại `app/Config/permissions.php` và thực thi ở route middleware.

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

`super_admin` được phép toàn bộ. Vào URL ngoài quyền sẽ nhận **trang 403** (không phải 404), sidebar không hiển thị module ngoài quyền.
Muốn đổi quyền: sửa danh sách `roles` của module trong `app/Config/permissions.php`.
Hiện phân quyền theo **module**; chưa giới hạn theo từng bản ghi — xem mục "Còn lại".

## 2. Các module

### Tin tuyển dụng (`/admin/jobs`)
- Thêm/sửa/nhân bản/xoá mềm; đổi trạng thái nhanh ngay trên bảng.
- Trạng thái: `draft` (nháp) → `published` (đăng) → `paused` (tạm dừng) / `closed` (đóng) / `expired` (hết hạn).
- **Tự động tính hạn nộp**: đăng tin mà để trống *Hạn nộp* → hệ thống tự đặt = ngày đăng + **số ngày nhận hồ sơ mặc định**
  (Cài đặt hệ thống → Thông tin chung, mặc định 30 ngày).
- **Tự động chuyển trạng thái**: tin đang đăng mà quá `deadline` sẽ tự chuyển sang **Hết hạn** (chạy khi mở trang
  tin/dashboard hoặc qua cron `tools/cron-expire-jobs.php`), và biến mất khỏi website công khai.
- **Đăng lại tin hết hạn**: phải cập nhật *Hạn nộp* mới (không được để ngày đã qua) rồi mới chọn *Đang đăng*.
- Tin chỉ hiển thị công khai khi `published`, chưa xoá và `deadline` chưa qua.
- Xoá là **xoá mềm**: hồ sơ ứng tuyển liên quan vẫn giữ nguyên.

### Hồ sơ ứng tuyển (`/admin/applications`)
- Tìm theo tên/SĐT/email, lọc trạng thái và theo vị trí.
- Chi tiết: thông tin ứng viên, nội dung gửi, tải CV, ghi chú nội bộ, hẹn lịch phỏng vấn.
- Mỗi lần lưu trạng thái ghi thêm một mốc vào lịch sử xử lý.
- CV lưu ngoài `public` (`storage/uploads/cv/…`), tải qua controller có kiểm tra đăng nhập.

### Tin tức & bài viết (`/admin/posts`)
- Thêm/sửa/xoá bài, đổi trạng thái nháp/đã đăng, phân loại theo chuyên mục.
- Ảnh đại diện: JPG/PNG/WebP ≤ 5MB, lưu vào `public/assets/uploads/posts/`.
- Nội dung bài: mỗi đoạn cách nhau **một dòng trống**; hiển thị thành các đoạn văn.

### Banner & thông báo (`/admin/banners`)
Bốn loại, đúng vị trí ngoài website:

| Loại | Vị trí hiển thị |
|---|---|
| `hero` | Slide ảnh đầu trang chủ (bắt buộc có ảnh) |
| `notice` | Dải thông báo mảnh dưới header |
| `popup` | Thẻ nổi góc trái |
| `ad` | Dải CTA nền xanh giữa trang chủ |

Có lịch `starts_at`/`ends_at` và công tắc bật/tắt. Banner tắt hoặc hết hạn tự ẩn.

### Nội dung website (`/admin/content`)
Soạn nội dung trang chủ: tiêu đề hero 2 dòng, mô tả hero, 2 đoạn giới thiệu, triết lý, dòng footer và 4 cặp số liệu.
Lưu là website cập nhật ngay (không cần build lại CSS).

### Cài đặt hệ thống (`/admin/settings`)
Tên website/pháp nhân, số tin mỗi trang, số ngày nhận hồ sơ mặc định, email/Zalo OA, mạng xã hội, SEO. Zalo nhận **link OA đầy đủ** hoặc số Zalo.

### Danh mục dùng chung (`/admin/categories`)
Ba nhóm dữ liệu nền cho toàn website:

| Nhóm | Dùng ở đâu | Lưu ý xoá |
|---|---|---|
| Khu sản xuất | Bộ lọc địa điểm, chi nhánh footer, chọn khu khi đăng tin | Chặn xoá nếu còn tin tuyển dụng thuộc khu |
| Khối phòng ban | Bộ lọc phòng ban, thẻ việc làm | Xoá được (tin liên quan chuyển về "không chọn") |
| Chuyên mục tin tức | Bộ lọc chuyên mục ở trang Tin tức | Chặn xoá nếu còn bài viết thuộc chuyên mục |

Mỗi danh mục có bật/tắt: tắt sẽ ẩn khỏi bộ lọc công khai nhưng dữ liệu cũ vẫn giữ.

### Hộp thư liên hệ (`/admin/contact`)
Tin nhắn từ form Liên hệ. Trạng thái: mới → đã xem → đã phản hồi. Có nút gọi/trả lời email nhanh.

### Người dùng quản trị (`/admin/users`) — chỉ super_admin
Thêm/sửa tài khoản, khoá/mở, đặt lại mật khẩu. Hệ thống chặn tự khoá mình và chặn xoá vai trò cuối cùng còn hoạt động.

### Hồ sơ cá nhân (`/admin/profile`)
Đổi mật khẩu của chính mình (yêu cầu mật khẩu hiện tại).

### Nhật ký thao tác (`/admin/audit`)
Ghi lại đăng nhập/đăng xuất, thao tác tin tuyển dụng, hồ sơ, bài viết, banner, cài đặt, người dùng: thời gian, người thực hiện, IP.

## 3. Quy ước nhập liệu

- **Mô tả công việc / Yêu cầu ứng viên**: mỗi dòng = một gạch đầu dòng.
- **Quyền lợi**: mỗi dòng `Tiêu đề | Mô tả` để hiển thị thành thẻ 2 cột.
- **Bài viết**: đoạn văn cách nhau một dòng trống.
- **Slug**: để trống sẽ tự sinh từ tiêu đề (tiếng Việt không dấu), có kiểm tra trùng.
- **Ngày/giờ**: theo giờ Việt Nam (`Asia/Ho_Chi_Minh`).

## 4. Bảo mật đang áp dụng

- Session `HttpOnly`, `SameSite=Lax`, `Secure` khi HTTPS; đổi session id sau khi đăng nhập.
- CSRF token bắt buộc cho mọi form POST (admin và công khai).
- Chống dò mật khẩu: tối đa 5 lần sai / 15 phút mỗi phiên.
- Tải CV: kiểm MIME bằng `finfo`, giới hạn 10MB, tên random, nằm ngoài `public`.
- Ảnh CMS: kiểm MIME + `getimagesize`, giới hạn 5MB, chỉ ghi trong `public/assets/uploads`.
- Mọi thao tác ghi đều ghi audit log (lỗi ghi log không làm hỏng thao tác chính).

## 5. Còn lại

1. Phân quyền theo **từng bản ghi** (ví dụ recruiter chỉ thấy hồ sơ thuộc khu mình phụ trách) — hiện đã phân quyền đủ theo module.
2. Nhập nốt 9 bài viết còn lại trong dữ liệu tĩnh cũ (DB đang có 4 bài; thêm trực tiếp qua module Tin tức).
3. Kiểm thử bảo mật độc lập trước khi mở công khai (rate limit form liên hệ, backup định kỳ).

## Thông báo & lưu bằng AJAX (SweetAlert2)

Các màn quản trị dùng **SweetAlert2 11.26.25** (vendor cục bộ ở `public/assets/vendor/sweetalert2/`,
không gọi CDN) + `public/assets/js/admin-ajax.js`. Form vẫn là POST thật, chỉ nâng cấp bằng JS
(progressive enhancement): tắt JS thì form POST truyền thống + flash message vẫn chạy như cũ.

Cách dùng trong view:

| Thuộc tính | Tác dụng |
|---|---|
| `data-ajax` trên `<form>` | Gửi bằng `fetch`, nhận JSON, hiện SweetAlert2 thay vì tải lại trang |
| `data-ajax-reload` | Thành công thì hiện toast rồi tự tải lại danh sách (dùng cho đổi trạng thái) |
| `data-confirm="..."` | Hộp thoại xác nhận SweetAlert2 trước khi gửi (thay `window.confirm`) |
| `data-ajax` + `data-confirm` | Xác nhận → gửi AJAX → toast/hộp thoại kết quả |
| `<select data-submit-onchange>` trong form `data-ajax` | Đổi giá trị là gửi AJAX luôn |

Luồng server: controller kiểm tra `isAjaxRequest()` (header `X-Requested-With` hoặc `Accept: application/json`)
rồi trả `jsonResult(ok, message, redirect, errors)` → JSON `{ok, message, errors, redirect}`.
Lỗi validate trả HTTP 422 kèm danh sách lỗi từng trường; client hiện hộp đỏ, đánh dấu
`aria-invalid` và cuộn tới trường lỗi đầu tiên. Thành công hiện hộp xanh, lưu flash vào
`sessionStorage` rồi chuyển trang đích (trang mới hiện thêm toast xác nhận).

Đã bật cho: **Tin tức & bài viết** (tạo/sửa, đổi trạng thái, xoá) và **Tin tuyển dụng**
(tạo/sửa, đổi trạng thái, nhân bản, xoá). Muốn bật cho module khác: thêm `data-ajax` vào form
ở view + thêm nhánh `isAjaxRequest()`/`jsonResult()` vào controller tương ứng.

## Bảng dùng chung: Bootstrap 5 + DataTables 2

Mọi bảng danh sách trong admin dùng chung một markup:

```html
<div class="admin-table-wrap">
  <table class="admin-table table table-hover align-middle js-datatable">
    ...
  </table>
</div>
```

`public/assets/js/admin-tables.js` tự khởi tạo DataTables tiếng Việt cho mọi bảng `.js-datatable`:
tìm nhanh, sắp xếp mọi cột, phân trang 10/25/50/Tất cả, thông tin dòng, cột `is-actions`
không sắp xếp/tìm kiếm. Ô ngày `dd/mm/yyyy [hh:mm]` được tự chuyển `data-order` ISO để
sắp xếp đúng. Bảng ≤ 8 dòng tự chạy chế độ gọn (không thanh công cụ, vẫn sắp xếp được);
có thể ép bằng `data-dt-compact` trên `<table>`.

Thư viện vendor cục bộ: `public/assets/vendor/{bootstrap,jquery,datatables}/` (không CDN).
Bảng chỉ-đọc dạng key–value (chi tiết hồ sơ, hồ sơ cá nhân) vẫn dùng `.admin-table--kv`,
không gắn DataTables. Phân trang do DataTables xử lý phía client; controller nạp tối đa
500 dòng/trang để đủ dữ liệu cho tìm kiếm/sắp xếp.

## Người liên hệ tuyển dụng theo chi nhánh

Mỗi khu sản xuất (Tân Hương / Cái Lậy / Chợ Gạo) có danh sách người phụ trách tuyển dụng:
họ tên + số điện thoại. Danh sách này hiển thị tự động ở:
- **Footer**: nút **“Liên hệ chi nhánh”** ở cột *Kết nối* — bấm vào mở hộp thoại danh sách 3 khu
  (thứ tự Tân Hương → Cái Lậy → Chợ Gạo, mỗi người kèm số điện thoại bấm gọi được).
- **Nút nổi cố định** (góc phải dưới, luôn hiển thị khi cuộn): nút điện thoại màu xanh mở cùng
  hộp thoại liên hệ — nằm cạnh nút Lên đầu trang và Zalo.
- Khối “Liên hệ ứng tuyển” ở **trang Liên hệ**.
- Sidebar **chi tiết tin tuyển dụng** (theo khu của tin).

Cách sửa: **Danh mục dùng chung** → bảng *Khu sản xuất* → **Sửa** → khối **“Người liên hệ tuyển dụng”**:

- `+ Thêm người liên hệ` để thêm dòng mới (họ tên bắt buộc, SĐT bắt buộc).
- Nút **Xoá** ở cuối mỗi dòng để bỏ người đó.
- Bấm **Lưu thay đổi** — danh sách cũ của khu được thay bằng danh sách mới (tối đa 20 người/khu).
- Số điện thoại nhận số, khoảng trắng, `+ - . ( )`; hiển thị đúng như nhập, link gọi tự lấy phần số.

Hotline chung đã được bỏ khỏi header/các trang và **bỏ luôn khỏi Cài đặt hệ thống**; số điện thoại
công khai giờ chỉ lấy từ danh sách người liên hệ này.

## Fanpage Facebook (plugin + nút cố định)

- Footer cột *Kết nối* nhúng **Facebook Page Plugin chính thức**:
  `data-href="https://www.facebook.com/congtycophantexgiang"`, tự co theo cột
  (`data-width="500"`, `data-adapt-container-width="true"`), cần internet để tải.
- Muốn đổi fanpage: sửa `data-href` + `blockquote cite` + link dự phòng trong
  `app/Views/partials/public/footer.php` (và bản tĩnh — xem `docs/06`).
- Link cho nút **FB** nổi và chữ “Facebook” ở footer vẫn lấy từ
  **Cài đặt hệ thống → Mạng xã hội → Facebook**.
- Ảnh `public/assets/images/fanpage-fb.svg` (placeholder cũ) không còn dùng — có thể xoá.
- **Zalo OA**: dán link Zalo OA đầy đủ (dạng `https://zalo.me/...`) vào **Cài đặt → Liên hệ → Zalo**;
  nếu chỉ nhập số điện thoại, hệ thống tự tạo link `https://zalo.me/<số>`.
- Nút **FB** tròn nằm trong cụm nút cố định góc phải dưới (cùng nút gọi điện và Zalo), luôn hiển thị khi cuộn.

## Soạn bài viết bằng CKEditor 4

- Trang **Tin tức & bài viết → Thêm/Sửa bài**: ô *Nội dung* dùng **CKEditor 4.22.1** (vendor cục bộ
  `public/assets/vendor/ckeditor4/`, giao diện tiếng Việt) thay cho textarea thuần.
- **Chèn ảnh**: bấm nút *Image* → tab *Upload* → chọn ảnh. Ảnh được tải lên **server**
  (`POST /admin/posts/upload-image`, có CSRF token + kiểm tra quyền `posts`), lưu tại
  `public/assets/uploads/posts/YYYY-MM/` với tên dạng `post-YYYYMMDD-HHMMSS-<random>.<ext>`.
  Chỉ nhận JPG/PNG/WebP ≤ 5 MB. Endpoint tự trả **JSON** cho CKEditor 4.22 (filetools) và **JS callback** cho chế độ iframe cũ — không còn lỗi “phản hồi sai”.
- **Xoá ảnh**: ảnh nằm trong nội dung bài được theo dõi bằng **đường dẫn** (`assets/uploads/...`).
  Khi **lưu bài** mà ảnh bị gỡ khỏi nội dung → file bị xoá; khi **xoá bài viết** → xoá toàn bộ ảnh
  trong nội dung + ảnh đại diện. Ảnh thay thế ảnh đại diện cũ cũng bị dọn.
- Nội dung HTML được **lọc allowlist** ở server (`sanitize_html()`): bỏ `<script>`, `<iframe>`,
  thuộc tính `on*`, link `javascript:`; giữ các thẻ định dạng cơ bản + ảnh + liên kết.
- Không có `required` trên textarea (CKEditor ẩn nó); server vẫn bắt buộc nội dung ≥ 20 ký tự.

## Nội dung trang Về chúng tôi & Phúc lợi (dạng repeater)

Mỗi trang là **một form duy nhất** kiểu “Người liên hệ tuyển dụng”: các khối danh sách dòng,
mỗi dòng có ô nhập + nút **Xoá**, cuối khối có nút **+ Thêm dòng**; bấm **Lưu thay đổi** là lưu
tất cả (AJAX, có thông báo căn giữa).

- **Nội dung → Về chúng tôi**: 3 khối — *Mốc phát triển* (mốc + nội dung + tick “Đang vận hành”),
  *Tầm nhìn — Sứ mệnh — Giá trị*, *Chứng nhận đang áp dụng*.
- **Nội dung → Phúc lợi**: mỗi **nhóm phúc lợi** là một khối (tiêu đề + icon + mô tả); bên trong có
  các dòng phúc lợi với nút **+ Thêm dòng**; cuối trang là **Bảng so sánh** dạng dòng.
  Có nút **+ Thêm nhóm phúc lợi** / **Xoá nhóm**.
- Tiêu đề + đoạn dẫn 2 trang sửa ở **Nội dung website → khối “Trang Về chúng tôi & Phúc lợi”**.
- Lưu là website công khai (`/about`, `/why-tex-giang`) cập nhật ngay.
## Menu website (dùng chung header + footer)

- Menu **Nội dung → Menu website** quản lý **một danh sách duy nhất**, hiển thị giống nhau ở
  **menu đầu trang** và **cột Menu chân trang** (không còn tách riêng vị trí).
- Thêm/sửa/xoá, đổi thứ tự, bật/tắt từng mục; **chỉ mục đang bật** mới hiển thị ngoài website.
- Trường *Đường dẫn*: `jobs`, `about`, `why-tex-giang`, `news`, `contact`…; để trống = trang chủ.
- Thao tác bật/tắt/xoá chạy AJAX, có SweetAlert xác nhận.
## Bản đồ theo khu vực

- Vào **Danh mục dùng chung → Khu sản xuất → Sửa**, có 2 trường:
  - **Link nhúng bản đồ** (`map_url`): dán link `https://www.google.com/maps/embed?...` **hoặc cả mã `<iframe>`**
    (hệ thống tự lấy `src`) — hiển thị ở trang **Liên hệ**.
  - **Link mở Google Maps** (`map_link`): link `https://maps.app.goo.gl/...` cho nút “Mở Google Maps”
    và “Bản đồ →” trong hộp thoại liên hệ ở footer.
- Sửa lưu xong là bản đồ ngoài website đổi theo ngay. Để trống → hiện ô “Chưa cấu hình bản đồ”.

## Ẩn menu = ẩn luôn trang đó

- Menu website chỉ có **một danh sách dùng chung**; mục bị tắt sẽ ẩn ở cả header và footer.
- Trang tương ứng với mục menu bị tắt cũng **không truy cập được nữa** (trả 404 “Trang này đang được ẩn khỏi menu website”),
  và các link trỏ tới trang đó ở trang chủ cũng tự ẩn.
- Muốn mở lại: **Nội dung → Menu website** → bật mục đó.

## Nút thao tác dạng icon + chọn icon có hình

- Các nút **Sửa / Xoá / Nhân bản / Ẩn-Hiện / Bật-Tắt** trong bảng quản trị đổi sang **icon**
  (có tooltip + aria-label), gọn và đẹp hơn.
- Ở **Phúc lợi**, chọn icon nhóm bằng **lưới icon có hình** (12 icon brand) thay vì dropdown tên.
- Mỗi dòng/nhóm trong 2 trang nội dung có nút **↑ / ↓** để chèn dòng lên trên / xuống dưới;
  thứ tự được lưu lại đúng khi bấm Lưu.

## Nhân bản tin tuyển dụng

- Bấm **Nhân bản** chỉ **nạp dữ liệu tin gốc vào form tạo mới**, chưa ghi DB.
- Kiểm tra/sửa nội dung rồi bấm **Lưu** thì bản sao mới thực sự được tạo (trạng thái nháp).

## Thống kê truy cập

- Menu **Tuyển dụng → Thống kê truy cập** (`/admin/visits`).
- Hiển thị: **ngày tháng năm · tổng lượt truy cập · tách theo thiết bị** (Máy tính / Điện thoại / Máy tính bảng),
  kèm 4 thẻ tổng quan (hôm nay, 7 ngày, 30 ngày, tổng cộng), biểu đồ 14 ngày và bảng chi tiết theo ngày.
- Lọc kỳ: 7 / 30 / 90 ngày, 6 tháng, 1 năm.
- **Chống tăng khi F5**: mỗi thiết bị/trình duyệt (cookie `tg_visitor`, 1 năm) chỉ được tính **1 lượt/ngày**;
  refresh liên tục không làm tăng lượt. Truy cập bằng thiết bị/trình duyệt khác sẽ tính lượt mới.
- Bot/crawler và công cụ tự động (curl, PowerShell, Postman…) bị loại khỏi thống kê; trang quản trị không tính lượt.
- Dữ liệu ghi tự động khi khách xem trang công khai, không cần cấu hình gì thêm.
- **Footer công khai** có khối *Thống kê truy cập*: lượt hôm nay, lượt truy cập tháng và tổng lượt truy cập (thay cho khối “Kết nối” cũ).

## Cron tự động hết hạn tin (tuỳ chọn)

Website đã tự chuyển tin quá hạn mỗi khi có truy cập. Muốn chắc chắn cập nhật cả khi không ai truy cập,
thêm Cron Job trong cPanel chạy mỗi giờ:

```
/usr/local/bin/php /home/TAI_KHOAN/tex-giang-recruitment/tools/cron-expire-jobs.php
```
