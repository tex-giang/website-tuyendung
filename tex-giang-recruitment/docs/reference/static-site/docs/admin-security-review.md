# Security review — frontend quản trị demo

Phạm vi: `assets/js/admin.js`, `assets/js/admin-data.js`, markup sinh từ `tools/build-admin.mjs` và server xem trước `tools/serve.mjs`. Review sau khi triển khai, theo skill security-review; không thay đổi mã trong lượt review này.

## Kết luận

Không phát hiện đường thực thi HTML/script từ nội dung editor qua các sink đã kiểm tra. Không có API ghi dữ liệu, upload server hoặc xác thực được triển khai trong phạm vi này. Điều này **không phải chứng nhận bảo mật cho một hệ thống quản trị production**.

## Bề mặt và kết quả

| Bề mặt | Bằng chứng nguồn | Đánh giá |
|---|---|---|
| Văn bản form, ghi chú, bài viết | `assets/js/admin.js:7` định nghĩa escape ký tự HTML; renderer danh sách, preview và CMS gọi escape ở các giá trị văn bản; `admin-preview-body` tại dòng 612 chỉ nhận template đã escape từ các hàm nội bộ | Payload `<img onerror>` và `<svg onload>` đã kiểm tra trong browser như văn bản, không sinh element thực thi |
| Query ID và điều hướng quay lại | `renderDetail`, `initJob`, `candidateLink` trong `admin.js` tìm bản ghi theo ID; URL trở lại chỉ chấp nhận đường `applications.html` cùng query | ID thiếu/sai hiện trạng thái không tìm thấy; không tự trả bản ghi đầu; không nhận URL quay lại tuỳ ý |
| Dữ liệu phiên trình duyệt | `assets/js/admin-data.js:181` đọc sessionStorage rồi kiểm tra version, shape, ID/quan hệ, enum, chuỗi, số và ngày; `admin-data.js:290` ghi phiên trước khi công bố state trong bộ nhớ | Phiên sai schema được bỏ để hiển thị fixtures ban đầu và báo phục hồi. Kiểm tra birth/quantity là số trước khi đưa vào template |
| Xuất CSV | `assets/js/admin.js:835`, `exportCSV` quote cell, nhân đôi dấu quote và thêm dấu nháy đơn với tiền tố công thức `=`, `+`, `-`, `@` | Xuất file local từ đúng tập đang lọc; không tạo công thức từ dữ liệu đưa vào ô |
| Chọn ảnh banner | `assets/js/admin.js:777`, `loadBanner` kiểm MIME, dung lượng 5 MB, decode bitmap, giới hạn 20 triệu pixel và ảnh lưu khoảng 1,2 triệu ký tự; raster hoá lại thành WebP | Không chấp nhận SVG/HTML. Epoch chặn ảnh chọn trước ghi đè ảnh chọn sau; lỗi giữ nguyên ảnh cũ. Không có upload HTTP |
| Server xem trước | `tools/serve.mjs:11` chặn dot path/node_modules/tools và phương thức ngoài GET/HEAD; kiểm đường ở dòng 13; MIME allowlist và nosniff ở dòng 16; bind 127.0.0.1 tại dòng 20 | Server chỉ đọc để preview local; không mở host mạng LAN hoặc endpoint ghi |

## Giới hạn có chủ ý — Informational

**DEMO-1: Không có ranh giới xác thực hoặc phân quyền server.** Các trang admin là tài sản tĩnh. “Vào bản demo” đi vào fixtures, không tạo phiên đăng nhập thật; handler đăng nhập ghi rõ chưa kết nối xác thực (`assets/js/admin.js`, nhánh `admin-login-form`). Bất kỳ ai có các file đều có thể mở chúng và sửa dữ liệu demo của phiên mình. Không dùng phiên demo hoặc nút ẩn làm bằng chứng quyền truy cập dữ liệu thật.

**DEMO-2: Session storage không phải kho dữ liệu nghiệp vụ.** Ghi chú và metadata demo tồn tại trong sessionStorage để chuyển trang/reload. Không lưu CV/CCCD hoặc mật khẩu. Ảnh banner được lưu dưới dạng WebP để thử giao diện, giới hạn bởi quota trình duyệt. Khi tích hợp thật cần thay repository bằng API có kiểm tra quyền, schema, lưu tệp và audit log ở server.

## Các trường hợp đã loại trừ

- Không có SQL/shell/API mutation sink từ form; không đánh giá SQL injection hoặc CSRF vào backend chưa tồn tại.
- Preview chỉ dùng template/text đã escape, không dùng `eval`, rich HTML tuỳ ý, iframe từ input hoặc `document.write`.
- Tệp “hồ sơ mẫu” là Blob văn bản sinh cục bộ, được ghi rõ không phải CV/CCCD thật.
- URL fanpage được kiểm protocol http/https; đường thông báo chỉ nhận trang `.html` hoặc https. Các URL này chưa được dùng để fetch dữ liệu server.
- Browser test kiểm lỗi quota giữ nguyên form, không báo thành công giả; dữ liệu phiên sai không được dùng tiếp để render markup.

## Ngoài phạm vi

Backend/API, danh tính người dùng, quyền tải hồ sơ thật, malware scanning, cấu hình hosting production, retention/backup, mã và thay đổi public từ các công việc khác. Server preview chỉ dành cho máy đang phát triển.
