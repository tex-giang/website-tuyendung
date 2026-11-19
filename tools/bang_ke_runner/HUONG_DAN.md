# Bảng Kê Runner V71 — Pandas 1.6.2 · Template nguyên bản

Ứng dụng Windows 10/11 64 bit chạy độc lập, không cần cài Microsoft Excel, Python hoặc kết nối mạng.

## Sử dụng

1. Chỉ cần mở `BangKeRunner.exe`. **Không cần đặt template.xlsx hoặc thư mục assets cạnh .exe.** Bấm **Tải template** ở góc trên bên phải để chọn nơi lưu mẫu từ trong ứng dụng.
2. Mẫu là bản sao nguyên file `template.xlsx` người dùng cung cấp, không xóa dữ liệu, không chỉnh ô/định dạng/công thức hoặc lưu lại workbook. Nút Tải template sao chép nguyên từng byte. KHAI THUE nhập từ **dòng 8**; hai sheet nguồn từ **dòng 2**. Giữ tên sheet/tiêu đề cột và lưu sau khi nhập. Với dữ liệu mới, nên lưu số hóa đơn/MST dạng văn bản để giữ số 0 đầu.
3. Bấm **Chọn tệp** hoặc kéo thả tệp vào khung, chọn một tệp `.xlsx` hoặc `.xlsm` có đủ:
   - `KHAI THUE`
   - `chi tiet hoa don` (hoặc `hoa don chi tiet`)
   - `tong hop hoa don`
4. Bấm **RUN · Xử lý bảng kê** và chọn tên/nơi lưu kết quả.
5. Đợi trạng thái Hoàn tất ngay trong cửa sổ. Bấm **Mở nơi lưu kết quả** để lấy tệp.

Kết quả luôn là **một tệp .xlsx có duy nhất sheet KHAI THUE**. Không xuất các sheet nguồn, Header, sheet nhật ký hoặc macro. Dù đầu vào là .xlsm, kết quả vẫn là .xlsx. Chọn một tên khác tệp gốc. Nếu chọn tên kết quả đã tồn tại, hộp thoại Lưu sẽ hỏi trước khi thay thế. Không sửa hoặc lưu tệp đầu vào trong lúc ứng dụng đang đọc.

Tệp gốc được giữ nguyên. Các nhóm bị bỏ qua và cảnh báo được hiển thị trong mục **Chi tiết xử lý** (bấm để mở/thu gọn); không tạo thêm tệp nhật ký tự động. Không cần nhấn Run nhiều lần.

Giao diện mặc định 560 × 480, hỗ trợ kéo thả Excel và nhật ký thu gọn. Khi đang chạy, các nút chọn tệp/tải mẫu bị khóa để tránh chạy chồng. Lỗi và kết quả hiển thị ngay trong cửa sổ.

## Thư viện và tốc độ

Giao diện dùng **PySide6 (Qt Widgets)**. Phần tính toán dùng **pandas + NumPy** để lập bảng dữ liệu, gom nhóm hóa đơn, tạo khóa đối chiếu và tổng hợp tiền/thuế. **lxml** đọc/ghi cấu trúc Excel và định dạng bảng khai thuế. **openpyxl chỉ dùng cho kiểm thử**, không nằm trong ứng dụng.

Đã tối ưu bước đọc để không phải dựng hàng triệu ô trống chỉ có định dạng. Định dạng gốc vẫn được giữ trong tệp kết quả. Xem `HIEU_NANG.md` để biết kết quả đo trên cùng tệp với bản cũ.

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
- Gia hạn validation thuế suất, cập nhật SUM tổng đơn giản và kiểm tra tổng; hiển thị tối đa 500 vấn đề mỗi lần chạy như giới hạn mã gốc.
- Không thực thi macro đầu vào. Bản xuất chỉ chứa KHAI THUE, không có mã VBA hoặc nút Chạy macro cũ.

Giữ định dạng số, màu, đường viền, độ rộng cột, chiều cao dòng, ô gộp, hình ảnh và vùng in của bảng. Công thức tham chiếu sheet nguồn được chuyển thành giá trị đã tính để tệp một sheet có thể dùng độc lập. Danh sách chọn phụ thuộc sheet nguồn được nhúng trực tiếp khi có thể; thay đổi phụ trợ được báo trong khung nhật ký. Bản 1.6.2 thay tài nguyên mẫu bằng template.xlsx nguyên bản; không áp dụng bước làm trống mẫu của các bản trước.

## Phạm vi và khác biệt cần biết

Ứng dụng thực hiện luồng của nút **Chạy**, không chạy VBA hoặc cung cấp các nút macro phụ riêng lẻ. Đây là bản chuyển logic, không phải một bộ máy tính Excel đầy đủ.

- Đầu vào hỗ trợ `.xlsx`, `.xlsm` không mã hóa. Không hỗ trợ `.xls`, `.xlsb` và sheet KHAI THUE đang khóa.
- Công thức nội bộ KHAI THUE được giữ và tính lại. Công thức tham chiếu sheet bị loại được xuất thành giá trị đã tính. Bộ xử lý công thức có phạm vi giới hạn: tham chiếu ô/vùng, phép toán số học, SUM, ROUND, ABS, MIN, MAX, COUNT, COUNTA, PRODUCT, AVERAGE, SUBTOTAL thông dụng với dữ liệu không ẩn/lọc.
- Nếu gặp công thức bị ảnh hưởng mà không tính được, ứng dụng dừng và báo địa chỉ ô; không xuất kết quả với tổng cũ. Công thức động, hàm tự định nghĩa, tham chiếu ngoài, một số shared/array formula, VBA khác và các tính năng bảng tính phức tạp chưa được bảo đảm tương đương Excel.
- Ngày dạng số Excel, `dd/mm/yyyy`, `yyyy-mm-dd`, `yyyymmdd` được nhận diện. Nên dùng những dạng này thay vì ngày viết bằng chữ phụ thuộc ngôn ngữ Windows.
- Mục tiêu là giữ nguyên quy tắc nghiệp vụ của nút Chạy trên mẫu V71. Không tuyên bố đã chứng minh tương đương 100% trên mọi workbook Excel; xem báo cáo kiểm thử đi kèm.

## Mã nguồn và đóng gói lại

Python 3.13, Windows 64 bit. Cài các gói trong `requirements.txt`, sau đó chạy `build.ps1`. Script dùng đường tìm DLL của Python/Windows trong lúc build để tránh lấy nhầm DLL từ ứng dụng khác trên máy. `openpyxl` chỉ dùng cho kiểm thử độc lập, không nằm trong `.exe`.

Các tệp chính: `app.py` (CLI/khởi động), `desktop_ui.py` (giao diện PySide6 và luồng nền), `engine.py` (nghiệp vụ), `pandas_index.py` (bảng và tổng hợp pandas), `ooxml.py` (đọc tệp và công thức), `single_sheet.py` (xuất riêng KHAI THUE), `templates.py` (lưu mẫu đính kèm). Tệp nguồn `assets/template.xlsx` cần có khi build, được nhúng bằng `--onefile --add-data` và đọc qua tài nguyên `_MEIPASS` lúc chạy. Khi mang ứng dụng sang máy khác, chỉ cần file .exe. `assets/template_info.json` ghi SHA-256 của mẫu để đối chiếu. VBA gốc trong gói mã nguồn chỉ dùng để đối chiếu và bảo trì.

Chạy kiểm thử: `python -m unittest -v test_engine test_optimization test_single_sheet test_templates test_desktop_ui`.

Có thể chạy không giao diện:

```powershell
.\BangKeRunner.exe --input "D:\DuLieu\BangKe.xlsm" --output "D:\DuLieu\BangKe_KHAI_THUE.xlsx"
```

Chế độ dòng lệnh trả mã 0 khi xử lý xong (có thể kèm cảnh báo), mã 1 khi lỗi. Tham số `--report "đường_dẫn.json"` chỉ dùng khi chủ động cần báo cáo để kiểm thử/tự động hóa; giao diện thông thường chỉ xuất một tệp khai thuế.

Lưu template bằng dòng lệnh: `BangKeRunner.exe --save-template "D:\DuLieu\template.xlsx"`.
