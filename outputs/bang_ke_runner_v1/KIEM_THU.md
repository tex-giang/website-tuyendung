# Kiểm thử phiên bản 1.0.0 — 18/11/2026

## Đã thực hiện

- 20 kiểm thử tự động bằng `unittest`, tất cả đạt.
- Kiểm thử gồm: khớp đầy đủ; fallback ngày cùng năm; chặn khác năm; fallback ký hiệu; chặn nhiều ứng viên; thứ tự ưu tiên diễn giải; nguồn tổng hợp dự phòng; placeholder chuỗi 1916 và số 1916; số âm; làm tròn; tách nhiều thuế suất; nhóm trùng thuế suất; nhóm tách dở; bảo toàn tổng; thiếu M/O ở nhóm 1–1; chặn nhóm 1–nhiều thiếu mốc tổng; chạy lại không sinh dòng trùng; giữ công thức; dịch tham chiếu khi chèn nhiều lần; cập nhật SUM và giá trị cache; ngày nhật ký có kiểu date; sheet bị khóa; đầu vào sai; bảo toàn sheet nguồn và phần VBA; dừng với công thức chưa hỗ trợ.
- Chạy chính bản `BangKeRunner.exe` ở chế độ dòng lệnh trên tệp kiểm thử có một dòng tổng, hai dòng chi tiết và công thức tổng. Đọc kết quả bằng openpyxl độc lập: đúng 2 dòng, tổng 101, thuế 10, công thức SUM cập nhật và có kết quả đã tính.
- Chạy chính `.exe` trên `Bang_Ke_FINAL_V71.xlsm` hiện có: 1.264 dòng hóa đơn; tổng trước thuế 3.727.064.179; tổng thuế 287.951.286. Tổng trước và sau bằng nhau; 430 khớp đầy đủ, 4 khớp bổ sung, 33 nhóm không khớp và 12 nhóm mơ hồ; 45 vấn đề được ghi nhật ký. Tệp đầu vào này đã có dữ liệu từng được xử lý bằng VBA, nên lần chạy kiểm tra này không thêm dòng mới.
- So sánh nguyên byte nội dung hai sheet nguồn và `xl/vbaProject.bin`: được giữ nguyên.
- Xác nhận bản `.exe` mở được cửa sổ `Bảng Kê Runner — V71` mà không chạy Excel/COM.

## Giới hạn xác nhận

Các kiểm thử và đối chiếu tổng trên dữ liệu thực không chứng minh tương đương từng ô với VBA cho mọi trường hợp. Chưa có lần chạy VBA mới trong Excel để đối chiếu trực tiếp cùng đầu vào chưa xử lý. Việc chụp giao diện tự động không hoàn tất do công cụ Computer Use báo `window capture timed out`; chưa xác nhận bố cục bằng ảnh chụp.

Ứng dụng chuyển luồng nút Chạy của mẫu V71. Phạm vi công thức được mô tả trong `HUONG_DAN.md`; gặp công thức bị ảnh hưởng ngoài phạm vi sẽ báo lỗi, không xuất kết quả với số tổng cũ.

Tệp kiểm thử có dữ liệu hóa đơn thật không nằm trong gói phân phối hoặc trong `.exe`.
