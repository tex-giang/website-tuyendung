# Kiểm thử phiên bản 1.6.2 — template.xlsx nguyên bản

## Template hiện tại

- Phiên bản 1.6.2 dùng tên tài nguyên và tên tải mặc định template.xlsx. Nội dung giữ nguyên SHA-256 của mẫu người dùng gửi; tên ứng dụng là BangKeRunner.exe. Kiểm thử giao diện xác nhận tên mặc định của hộp thoại lưu.

- Nhúng nguyên file template.xlsx (13.733 byte). SHA-256 nguồn và tài nguyên cùng là c7d9386ed14081754358d11c8152f853bbb929eea1ae4b1bdf4f216ff58d724c. Không mở/lưu lại hoặc làm sạch workbook khi chuẩn bị tài nguyên.
- Build kiểm tra hash tài nguyên bằng hash nguồn và chế độ verbatim. Kiểm thử tải mẫu kiểm tra đúng byte/hash, tài nguyên _MEIPASS, và chạy dữ liệu thử được điền trên bản sao thật của template.xlsx.
- Đã chạy bản .exe 1.6.2 trong thư mục chỉ có ứng dụng: trích tài nguyên và tải mẫu qua CLI đều giống từng byte với file Downloads; không nhúng template.xlsx cũ. Qt khởi động được; dữ liệu thử trên bản sao template.xlsx ra đúng 2 dòng, tổng 100/thuế 8 và một sheet KHAI THUE; đầu vào không đổi.
- Đã đồng bộ kết thúc luồng nền trước khi giải phóng worker để tránh lỗi khi một tác vụ kết thúc rất nhanh.
- Các ghi chú bên dưới về làm trống template.xlsx thuộc phiên bản trước, không áp dụng cho template.xlsx của bản 1.6.2.

## Kiểm thử và lịch sử

- Bản 1.6 đã chạy từ thư mục chỉ có .exe: khởi động/render được Qt, tải template có hash trùng mẫu trống nhúng, chạy CLI trên fixture ra đúng 2 dòng/tổng 100/thuế 8/số hóa đơn 00012345 và chỉ sheet KHAI THUE; đầu vào không đổi. Đã sửa đường tìm DLL khi build để không lấy nhầm ICU của Poppler trên PATH.

- 46 kiểm thử tự động đạt: giữ 42 bài hồi quy nghiệp vụ/template và thêm 4 bài giao diện Qt chạy headless. Kiểm tra xử lý nền với dữ liệu thật của fixture, đầu ra một sheet/tổng 100/thuế 8, giao diện vẫn nhận sự kiện, chặn chạy chồng/đóng giữa chừng, phục hồi sau lỗi, hủy hộp thoại, chống ghi đè đầu vào và tải mẫu nhúng.
- Render cửa sổ Qt bằng nền tảng offscreen ở các trạng thái chưa chọn tệp, đã chọn, hoàn tất và mở nhật ký; đã xem ảnh và kiểm tra bố cục. Kích thước mặc định 560 × 480, nhật ký mở rộng khi cần.

- 42 kiểm thử tự động: 36 bài hồi quy và 6 bài cho template. Mẫu có đúng 3 sheet, giữ khung mẫu nhưng đã xóa nội dung từ dòng 8 ở KHAI THUE và từ dòng 2 ở hai sheet nguồn, bao gồm giá trị, công thức/cached result và sharedStrings không còn dùng ở tiêu đề.
- Trích riêng tiêu đề của mẫu thành bộ dữ liệu kiểm thử nhỏ, điền dữ liệu tổng hợp tại dòng 8 rồi chạy: số hóa đơn có/không có số 0 đầu ghép đúng; một hóa đơn tách thành hai mặt hàng; tổng 100 và thuế 8 được giữ; đầu ra vẫn chỉ có KHAI THUE.
- Kiểm tra đường dẫn tài nguyên `_MEIPASS` của ứng dụng đóng gói, chống ghi đè template nội bộ, từ chối đuôi sai và thông báo khi người dùng chưa điền dữ liệu vào mẫu.
- Bản 1.5 tạo mẫu trống từ tệp gốc mà không chỉnh sửa bản trong Downloads. Metadata ghi nguồn, hash của mẫu trống và số ô đã làm sạch trong assets/template_info.json. Build kiểm tra hash và trạng thái mẫu trống trước khi nhúng.
- Đối chiếu mẫu trống với bản gốc: styles.xml giữ nguyên byte; nội dung/kiểu ô ở tiêu đề, thuộc tính hàng, độ rộng cột và vùng gộp khớp. Đã xem bản render của cả 3 sheet sau khi làm trống.
- Kiểm tra bản 1.5 trong thư mục chỉ có một .exe: tệp mẫu tải ra có hash trùng tài nguyên trống đã kiểm tra. Chạy .exe với dữ liệu thử theo tiêu đề mẫu: đúng 2 dòng, tổng 100/thuế 8, số hóa đơn có số 0 đầu được giữ và đầu ra chỉ có KHAI THUE.
- Đầu ra mặc định chỉ có một sheet KHAI THUE, đúng định dạng .xlsx, không tự sinh tệp phụ; đầu vào không thay đổi. Kiểm tra cả đầu vào .xlsm và trường hợp KHAI THUE không phải sheet đầu.
- Chạy chính BangKeRunner_KhaiThue.exe trên tệp thật và bộ 120 hóa đơn tổng hợp: cả hai chỉ có một worksheet, không chứa VBA hoặc externalLinks; lần lượt đúng 666/600 dòng hóa đơn và các tổng tiền, thuế khớp. Tệp thật xuất ra khoảng 236 KB; bộ tổng hợp khoảng 29 KB.
- Kiểm tra giữ số liệu, SUM/cached result, định dạng, ô gộp, vùng in, hình ảnh; chuyển công thức tham chiếu sheet nguồn thành giá trị; chuyển danh sách chọn ở Header thành danh sách nhúng; trả cảnh báo trong báo cáo ứng dụng.
- Kiểm tra loại bỏ macro, nút chạy cũ, các sheet khác và chuỗi sharedStrings chỉ dùng ở nguồn. Các bài hồi quy nghiệp vụ dùng chế độ nội bộ xuất đầy đủ để so sánh với bản trước; giao diện và CLI mặc định chỉ xuất KHAI THUE.
- Ở phiên bản 1.1 (trước khi đổi đầu ra một sheet), so sánh cùng đầu vào giữa mã bản 1.0 và bản pandas cho XML KHAI THUE giống hệt. Ở phiên bản 1.2, chỉ số sharedStrings được xây dựng lại nên kiểm tra giá trị/công thức thay vì yêu cầu XML giống byte: toàn bộ ô có nội dung, độ rộng cột và vùng gộp khớp kết quả cũ trên tệp thật; đầu ra chỉ có KHAI THUE và không có phần VBA.
- Kiểm tra bổ sung: giữ kiểu dữ liệu mã hóa đơn (boolean/số/chuỗi), thứ tự nhóm, placeholder, thứ tự cộng số thực, hủy cache khi ô thay đổi, XML chứa CDATA/comment giống thẻ ô trống, định dạng ô trống, và 120 hóa đơn được tách thành 600 dòng.
- Kiểm thử gồm: khớp đầy đủ; fallback ngày cùng năm; chặn khác năm; fallback ký hiệu; chặn nhiều ứng viên; thứ tự ưu tiên diễn giải; nguồn tổng hợp dự phòng; placeholder chuỗi 1916 và số 1916; số âm; làm tròn; tách nhiều thuế suất; nhóm trùng thuế suất; nhóm tách dở; bảo toàn tổng; thiếu M/O ở nhóm 1–1; chặn nhóm 1–nhiều thiếu mốc tổng; chạy lại không sinh dòng trùng; giữ công thức; dịch tham chiếu khi chèn nhiều lần; cập nhật SUM và giá trị cache; ngày nhật ký có kiểu date; sheet bị khóa; đầu vào sai; bảo toàn sheet nguồn và phần VBA; dừng với công thức chưa hỗ trợ.
- Bản 1.0 đã được chạy ở chế độ dòng lệnh trên tệp kiểm thử có một dòng tổng, hai dòng chi tiết và công thức tổng. Đọc kết quả bằng openpyxl độc lập: đúng 2 dòng, tổng 101, thuế 10, công thức SUM cập nhật và có kết quả đã tính. Bản pandas giữ bộ kiểm thử này.
- Phép kiểm tra bản 1.1 dùng đầu vào ngày 19/11/2026: 666 dòng hóa đơn; tổng trước thuế 1.864.881.920; tổng thuế 150.656.435. Bản 1.2 cũng được đối chiếu trên bản đầu vào này. Đầu vào đã từng được xử lý, nên việc thêm dòng được kiểm tra thêm trên các bộ dữ liệu tổng hợp riêng.
- Trước phiên bản 1.2, phần xuất đầy đủ giữ nguyên hai sheet nguồn và VBA. Phiên bản 1.2 chủ động không đưa các phần này vào tệp kết quả.
- Bản 1.0 đã mở được cửa sổ `Bảng Kê Runner — V71` mà không chạy Excel/COM. Bản pandas giữ giao diện này, có thêm tên Pandas và số phiên bản.

## Giới hạn xác nhận

Các kiểm thử và đối chiếu tổng trên dữ liệu thực không chứng minh tương đương từng ô với VBA cho mọi trường hợp. Chưa có lần chạy VBA mới trong Excel để đối chiếu trực tiếp cùng đầu vào chưa xử lý. Bản 1.6 đã kiểm tra bố cục bằng render Qt offscreen. Chưa kiểm tra kéo thả/hộp thoại native trên mọi cấu hình màn hình Windows.

Ứng dụng chuyển luồng nút Chạy của mẫu V71. Phạm vi công thức được mô tả trong `HUONG_DAN.md`; gặp công thức bị ảnh hưởng ngoài phạm vi sẽ báo lỗi, không xuất kết quả với số tổng cũ.

Bản 1.6.2 nhúng template.xlsx nguyên bản trong .exe và MaNguon/assets. Không đóng gói template.xlsx cũ hoặc workbook thử chạy/kết quả.
