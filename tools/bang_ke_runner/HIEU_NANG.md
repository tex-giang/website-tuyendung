# Tối ưu pandas 1.1.0 — 19/11/2026

Ghi chú phiên bản 1.2: giữ các tối ưu pandas bên dưới và đổi đầu ra thành tệp .xlsx chỉ có KHAI THUE. Bảng thời gian sau là phép đo lịch sử của bản 1.1 còn xuất toàn bộ workbook.

Đo trên cùng máy Windows và cùng một bản cố định của tệp Bang_Ke_FINAL_V71.xlsm (khoảng 18 MB). Bản cũ và bản mới được chạy lần lượt trong tiến trình Python riêng, có cProfile để xác định điểm chậm.

| Giai đoạn | Bản 1.0 | Bản pandas 1.1 |
|---|---:|---:|
| Đọc Excel | 31,76 giây | 9,74 giây |
| Xử lý nghiệp vụ | 4,16 giây | 2,65 giây |
| Lưu và kiểm tra tệp | 3,13 giây | 3,41 giây |
| Tổng | **39,06 giây** | **15,80 giây** |

Trên phép đo này, tổng thời gian giảm khoảng **60%**, tương đương **nhanh hơn 2,47 lần**. Số liệu không bao gồm thời gian mở/giải nén ứng dụng `.exe`; thời gian thực tế phụ thuộc máy, ổ đĩa và nội dung tệp.

## Thay đổi

- Dùng pandas DataFrame với dữ liệu gốc kiểu object để giữ riêng chuỗi, số và giá trị thiếu; không làm mất số 0 đầu của mã hóa đơn.
- Chuẩn hóa mỗi giá trị khóa lặp lại một lần, dùng groupby và bảng tổng hợp để tra cứu nhóm, ứng viên và tổng tiền/thuế.
- Giữ thứ tự nhóm và cách cộng số theo bản xử lý cũ để tránh thay đổi quyết định ở ngưỡng sai số.
- Lọc ô trống chỉ có định dạng khỏi bản XML dùng để đọc; phần ZIP gốc vẫn được dùng khi xuất các sheet không thay đổi. XML có CDATA/comment được xử lý bằng nhánh đọc đầy đủ.
- Không quét mọi ô của các sheet không có công thức khi tách dòng; chỉ cập nhật các phần tử XML có tham chiếu dòng.

## Kiểm chứng kết quả

27 kiểm thử đạt. Trên tệp thật đã cố định đầu vào, XML của KHAI THUE giống hệt bản cũ; kiểm tra độc lập toàn bộ ô/công thức cũng giống nhau. Thống kê khớp/không khớp và các tổng tiền giống nhau. Hai sheet nguồn và mã VBA không bị thay đổi.

Chính tệp thực thi BangKeRunner_Pandas.exe cũng đã được chạy trên đầu vào cố định và cho cùng thống kê, cùng XML KHAI THUE như bản cũ. Một bộ dữ liệu riêng có 120 hóa đơn được tách thành 600 dòng cũng khớp mọi ô/công thức với bản cũ.

Đây là đối chiếu với bản Python trước, không phải một lần chạy VBA mới trong Excel. Các giới hạn công thức của bản trước vẫn được nêu trong HUONG_DAN.md.
