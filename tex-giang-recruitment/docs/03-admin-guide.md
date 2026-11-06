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
- Trạng thái: `draft` (nháp) → `published` (đăng) → `paused` (tạm dừng) / `closed` (đóng).
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
Tên website/pháp nhân, địa chỉ, giờ làm việc, số tin mỗi trang, hotline/email/Zalo/map, mạng xã hội, SEO.

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