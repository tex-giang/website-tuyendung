# Bảng Kê Runner V71

Ứng dụng Windows chạy độc lập, không cần cài Microsoft Excel, Python hoặc kết nối mạng.

## Sử dụng

1. Mở `BangKeRunner.exe`.
2. Bấm **Chọn tệp…**, chọn một tệp `.xlsx` hoặc `.xlsm` có đủ:
   - `KHAI THUE`
   - `chi tiet hoa don` (hoặc `hoa don chi tiet`)
   - `tong hop hoa don`
3. Bấm **RUN — Xử lý bảng kê** và chọn tên/nơi lưu kết quả.
4. Đợi thông báo hoàn tất. Bấm **Mở nơi lưu kết quả** để lấy tệp.

Tệp kết quả giữ cùng đuôi với tệp đầu vào. Chọn một tên khác tệp gốc. Nếu chọn tên kết quả đã tồn tại, hộp thoại Lưu sẽ hỏi trước khi thay thế. Không sửa hoặc lưu tệp đầu vào trong lúc ứng dụng đang đọc.

Tệp lớn có thể mất một vài phút. Không cần nhấn Run nhiều lần. Tệp gốc được giữ nguyên nên không tạo thêm bản `_backup_` như macro; mọi thay đổi chỉ nằm trong tệp kết quả.

## Logic xử lý

Được chuyển từ `ChayTatCaKhaiThue` → `CapNhatKhaiThueTuChiTiet` trong VBA của `Bang_Ke_FINAL_V71.xlsm` được đọc ở phiên làm việc ngày 18/11/2026:

- Tự nhận diện cột từ tiêu đề, hỗ trợ tiêu đề có dấu tiếng Việt. Sheet chi tiết lấy Diễn giải tại cột W như mã gốc.
- Khớp đầy đủ bằng **ký hiệu + số hóa đơn + ngày + MST**; khóa đầy đủ không yêu cầu mẫu số, đúng mã VBA.
- Khớp bổ sung khi thiếu/sai ngày hoặc sai ký hiệu chỉ với một ứng viên duy nhất, đủ khóa phụ và khớp tổng tiền/thuế. Không ghép khác năm khi hai ngày đều hợp lệ.
- Kiểm tra nhóm chi tiết với đúng một hóa đơn tổng hợp. Trường hợp exact 1–1 được miễn kiểm tra tổng hợp như mã gốc.
- Bỏ qua các nhóm mơ hồ, dòng trùng, nhóm tách dở hoặc nhóm không thể phân bổ.
- Điền I:J:K:L còn trống theo thứ tự chi tiết → tổng hợp; không ghi đè công thức. Riêng mô tả được ưu tiên thay bằng diễn giải nguồn.
- Bổ sung thông tin đầu hóa đơn khi nguồn thống nhất; tách dòng chi tiết theo thuế suất, giữ A:H và P:Q của dòng gốc.
- Chọn nguồn tiền hàng/doanh số trước thuế gần tổng gốc nhất; làm tròn và điều chỉnh dòng khác 0 cuối cùng để bảo toàn tổng.
- Làm tròn số lượng 2 số lẻ và đơn giá thành số nguyên theo Excel ROUND; giữ tổng trước thuế và tiền thuế, không tính lại chúng bằng số lượng × đơn giá.
- Phân biệt số 1916 hợp lệ và chuỗi placeholder `"1916"` bị thiếu.
- Gia hạn validation thuế suất, cập nhật SUM tổng đơn giản, kiểm tra tổng và ghi `NHAT_KY_VBA`/`NHAT_KY_DOI_CHIEU` (tối đa 500 vấn đề mỗi lần chạy như mã gốc).
- Giữ các sheet nguồn và phần ZIP không thay đổi, bao gồm VBA của tệp `.xlsm`. Không thực thi bất kỳ macro đầu vào nào.

Các sheet nhật ký được hiển thị trong tệp kết quả để dễ xem. Bản `.exe` không nhúng dữ liệu hóa đơn của người dùng.

## Phạm vi và khác biệt cần biết

Ứng dụng thực hiện luồng của nút **Chạy**, không chạy VBA hoặc cung cấp các nút macro phụ riêng lẻ. Đây là bản chuyển logic, không phải một bộ máy tính Excel đầy đủ.

- Đầu vào hỗ trợ `.xlsx`, `.xlsm` không mã hóa. Không hỗ trợ `.xls`, `.xlsb` và sheet KHAI THUE đang khóa.
- Giữ nguyên công thức nguồn và giá trị đã lưu của chúng. Công thức trên KHAI THUE và công thức có tham chiếu bị ảnh hưởng do chèn dòng được tính bằng bộ xử lý có phạm vi giới hạn: tham chiếu ô/vùng, phép toán số học, SUM, ROUND, ABS, MIN, MAX, COUNT, COUNTA, PRODUCT, AVERAGE, SUBTOTAL thông dụng với dữ liệu không ẩn/lọc.
- Nếu gặp công thức bị ảnh hưởng mà không tính được, ứng dụng dừng và báo địa chỉ ô; không xuất kết quả với tổng cũ. Công thức động, hàm tự định nghĩa, tham chiếu ngoài, một số shared/array formula, VBA khác và các tính năng bảng tính phức tạp chưa được bảo đảm tương đương Excel.
- Ngày dạng số Excel, `dd/mm/yyyy`, `yyyy-mm-dd`, `yyyymmdd` được nhận diện. Nên dùng những dạng này thay vì ngày viết bằng chữ phụ thuộc ngôn ngữ Windows.
- Mục tiêu là giữ nguyên quy tắc nghiệp vụ của nút Chạy trên mẫu V71. Không tuyên bố đã chứng minh tương đương 100% trên mọi workbook Excel; xem báo cáo kiểm thử đi kèm.

## Mã nguồn và đóng gói lại

Python 3.13, Windows 64 bit. Cài các gói trong `requirements.txt`, sau đó chạy `build.ps1`. `openpyxl` chỉ dùng cho kiểm thử độc lập, không nằm trong `.exe`.

Các tệp chính: `app.py` (giao diện), `engine.py` (nghiệp vụ), `ooxml.py` (đọc/ghi tệp và công thức). VBA gốc nằm trong `reference/` để đối chiếu và bảo trì.

Chạy kiểm thử: `python -m unittest -v test_engine`.

Có thể chạy không giao diện:

```powershell
.\BangKeRunner.exe --input "D:\DuLieu\BangKe.xlsm" --output "D:\DuLieu\BangKe_KET_QUA.xlsm" --report "D:\DuLieu\bao_cao.json"
```

Chế độ dòng lệnh trả mã 0 khi xử lý xong (có thể kèm cảnh báo), mã 1 khi lỗi. Xem trường `status` trong báo cáo JSON và các sheet nhật ký.
