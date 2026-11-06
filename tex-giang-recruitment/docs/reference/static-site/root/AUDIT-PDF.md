# RÀ SOÁT WEBSITE DEMO THEO "NỘI DUNG THIẾT KẾ WEBSITE TUYỂN DỤNG"

> Nguồn: `Nội dung Web tuyển dụng.pdf` (2 trang, 10 nhóm chức năng) — ngày 29/10/2026, lập biểu Trần Châu Kha.
> Rà soát: 30/10/2026 trên bản demo tĩnh tại `D:\Workspace\HTML\website-tuyendung`.
> Ký hiệu: ✅ đạt · 🟡 đạt một phần / demo · ⛔ cần backend.

---

## Bảng đối chiếu 10 nhóm chức năng

| # | Nhóm trong PDF | Trạng thái | Đã có ở đâu |
|---|---|---|---|
| 1 | **Trang chủ**: banner, tìm kiếm (từ khoá/công ty/địa điểm), vị trí nổi bật, giới thiệu – môi trường – phúc lợi, responsive | ✅ | `index.html`: hero + banner WebP; form `name=keyword|company|location` → `jobs.html`; 3 `job-card`; khối giới thiệu + link phúc lợi; 4 breakpoint 1536/1440/768/390 |
| 2 | **Tiện ích**: hotline–email, banner chính, banner pop-up, banner quảng cáo, Zalo, liên kết website, chia sẻ FB/Zalo, QR | ✅ | Hotline 3 nơi + `tel:`; email footer; banner chính = ảnh hero; **banner pop-up** = thẻ nổi góc trái (`#recruitment-popup`, hiện sau khi cuộn 60%, 1 lần/phiên); banner quảng cáo = dải CTA xanh; `https://zalo.me` (nút nổi + footer); link `texgiang.com.vn`, `tgapparel.com.vn`; share FB/Zalo + copy link ở `job-detail`, `news-detail`; QR modal (`#qr-modal`) |
| 3 | **Danh sách việc làm**: tìm kiếm, lọc công ty/địa điểm, hiển thị chức danh–đơn vị–địa điểm–lương–hạn nộp, phân trang, sắp xếp, trạng thái, link chi tiết/ứng tuyển | ✅ | `jobs.html` + `assets/js/jobs.js`: lọc 5 tiêu chí (từ khoá, đơn vị, địa điểm, phòng ban, lương); **phân trang 6 tin/trang** (`#jobs-pager`, "Hiển thị 1–6 trong 8"); sắp xếp mới nhất/ưu tiên; trạng thái đang tuyển – tuyển gấp – đã đóng (`.is-closed`); mỗi dòng có `job-detail.html?id=` + `apply.html?job=` |
| 4 | **Ứng tuyển trực tuyến**: họ tên/điện thoại/email, chọn vị trí, giới thiệu, đính kèm CV, kiểm tra dữ liệu + thông báo, lưu hồ sơ tập trung | ✅ / ⛔ | `apply.html` + `apply.js`: 4 nhóm field, select vị trí (prefill theo `?job=`), textarea giới thiệu, dropzone CV (PDF/DOC/DOCX/JPG/PNG ≤10MB, progress + preview + xoá), validate SĐT/số lượng, toast lỗi, redirect `application-success.html` / lỗi sang `application-error.html`. **Lưu tập trung: chưa có backend** — hồ sơ hiện là dữ liệu mẫu trong `admin/applications.html` |
| 5 | **Chi tiết công việc**: mô tả, yêu cầu, lương–phúc lợi–thời gian–địa điểm, số lượng, ngày đăng, hạn nộp, nút ứng tuyển + liên hệ, share FB/Zalo/copy | ✅ | `job-detail.html`: mô tả, **yêu cầu ứng viên**, quyền lợi, địa điểm; meta thu nhập/số lượng/kinh nghiệm/hình thức + **Đăng ngày 15/10/2026 · Hạn nộp 30/11/2026**; card ứng tuyển + liên hệ; share **Facebook, Zalo, sao chép liên kết**, QR, in tin |
| 6 | **Về chúng tôi**: tổng quan, tầm nhìn–sứ mệnh–giá trị, hình ảnh nhà máy/văn phòng/đội ngũ | ✅ | `about.html`: hero + ảnh nhà máy (WebP), dải 4 số, "Chúng tôi làm gì", triết lý, tầm nhìn/sứ mệnh/giá trị, chứng nhận; **3 ảnh** `assets/images/company/*.webp` (xưởng, môi trường, xã hội) |
| 7 | **Tin tức nghề nghiệp**: hoạt động nội bộ, thông báo tuyển dụng, sự kiện; admin thêm/xoá/sửa | ✅ | `news.html` (1 bài nổi bật + 2 bài), `news-detail.html` (nội dung + share); admin: `admin/content.html` → bảng bài viết có **Xem / Sửa / Xoá** + nút **Thêm bài** (demo) |
| 8 | **Tại sao chọn chúng tôi**: 3–4 tiêu chí, admin sửa được | ✅ | `why-tex-giang.html`: 4 trụ cột + bảng so sánh; admin: `admin/content.html` panel **“Trang giới thiệu & Tại sao chọn”** (4 tiêu chí + giới thiệu + tầm nhìn) |
| 9 | **Trang liên hệ**: thông tin bộ phận tuyển dụng, bản đồ Maps | ✅ | `contact.html`: địa chỉ/hotline/email/giờ tiếp nhận, 3 tuyến xe, **Google Maps iframe 16:9**, form hỏi đáp (toast demo) |
| 10 | **Trang quản trị**: quản lý tin (tạo/sửa/**sao chép**/ẩn-đóng/hạn/trạng thái), hồ sơ (xem/tải CV/tìm/lọc vị trí/ghi chú/trạng thái), thống kê (tổng tin, đang tuyển, hồ sơ, **theo vị trí**, theo nguồn), nội dung (trang chủ, giới thiệu, tin tức, banner, liên hệ, chuyên mục), đăng nhập + lưu dữ liệu | ✅ / ⛔ | `admin/`: `jobs.html` (sửa, **nhân bản**, đóng/mở, xoá, hạn nộp, trạng thái), `job-form.html` (tạo/sửa), `applications.html` (tìm kiếm, lọc trạng thái, **lọc theo vị trí**, tải CV demo, modal cập nhật trạng thái, ghi chú), `application-detail.html` (hồ sơ + timeline + ghi chú), `dashboard.html` (4 KPI, hồ sơ theo tháng, **hồ sơ theo vị trí**, nguồn ứng viên), `content.html` (trang chủ/giới thiệu/tại sao chọn/tin tức/liên hệ), `banners.html` (banner chính, dải thông báo, **pop-up**), `settings.html` (pháp nhân, SEO, vận hành), `login.html` (demo). **Đăng nhập thật + lưu CSDL: cần backend** |

---

## Phần cần backend/CSDL (ngoài phạm vi demo tĩnh)

| Việc | Ghi chú |
|---|---|
| Lưu hồ sơ ứng viên tập trung | cần bảng `applications`, upload file thật, chống CSRF, kiểm MIME ở server |
| Đăng nhập quản trị thật | `password_hash()` + session; hiện `login.html` chỉ mô phỏng |
| CRUD tin/bài/banner ghi vào DB | hiện là form demo + toast |
| Gửi email/Zalo thông báo kết quả | cần SMTP hoặc Zalo OA |
| Đếm số liệu thật | dashboard đang dùng số mẫu 24 tin / 142 hồ sơ |

## Ghi chú kỹ thuật phát hiện khi rà

- Nút **nhân bản tin** từng bị bỏ khi redesign → đã khôi phục ở `admin/jobs.html`.
- **Popup từng che nội dung màn đầu** (fail kiểm tra `late-title`/`covered-text`) → đổi thành thẻ nổi góc trái, hiện sau khi cuộn 60%, 1 lần/phiên; đã kiểm lại `inspect.mjs` = 0 FAIL.
- Bộ lọc "vị trí" ở trang hồ sơ trước đây chỉ là select tĩnh → đã nối với bảng (kết hợp cả tìm kiếm + trạng thái).
- Thống kê "theo vị trí" chưa có → đã thêm panel ở dashboard.
- Public pages đã chuyển sang icon SVG nội bộ; **9 trang admin vẫn nạp Bootstrap Icons qua CDN** — không sai, nhưng nên đồng bộ (dùng `assets/images/icons/*.svg` như public) khi có thời gian.
