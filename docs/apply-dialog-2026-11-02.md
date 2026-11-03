# Dialog ứng tuyển dùng chung — 02/11/2026

Chuyển luồng nộp hồ sơ từ "mở trang `apply.html`" sang **dialog mở tại chỗ** trên mọi trang public. Vẫn giữ `apply.html` làm bản dự phòng dạng trang đầy đủ.

## Hành vi

- Bấm bất kỳ link **Ứng tuyển / Nộp hồ sơ** nào (header, thẻ việc làm, CTA, footer, trang thông báo) → mở `<dialog>` ngay trên trang hiện tại, **không điều hướng**, không mất vị trí đọc. Trang ứng tuyển đầy đủ là `apply.html`; link **"Cần bản đầy đủ? Mở trang ứng tuyển"** bên trong dialog điều hướng thật tới `apply.html` (kèm tham số đang chọn).
- Dialog tự điền sẵn khu sản xuất + vị trí khi link có tham số: `?job=` khớp mềm tiêu đề tin, `?vai-tri=` chọn đúng mã vị trí, `?khu=`/`?company=` chọn đúng khu.
- Submit hợp lệ → hiện trạng thái **“Đã gửi hồ sơ (bản demo)”** ngay trong dialog; có nút **Nộp hồ sơ khác** (làm mới form, giữ dialog mở).
- **Chỉ nút × ở góc trên bên phải mới đóng được dialog.** Phím Escape và bấm nền ngoài không đóng; khi cố đóng bằng hai cách đó, focus được đưa về nút × để gợi ý. Footer không còn nút “Đóng”.
- Đóng giữa chừng rồi mở lại: dữ liệu đang nhập được giữ; nếu chưa nhập gì thì lần mở sau vẫn prefill theo link mới.
- Trên trang `apply.html`, link ứng tuyển **cũng mở dialog**: dialog dùng chính form đang có trên trang (chuyển form vào dialog khi mở, trả về chỗ cũ khi đóng) nên không trùng ID và giữ nguyên dữ liệu đang nhập; gửi thành công vẫn chuyển tới `application-success.html` như luồng trang độc lập.
- Nếu trình duyệt không hỗ trợ `<dialog>` hoặc JS lỗi, link gốc vẫn điều hướng tới trang tương ứng như cũ.

## Kiến trúc

| File | Vai trò |
|---|---|
| `assets/js/apply-form.js` | Một nguồn markup form + validation + drag-drop CV + prefill. `TGApplyForm.mount(root, options)` dùng cho cả trang lẫn dialog. |
| `assets/js/apply-dialog.js` | Tạo `<dialog>` kiểu Bootstrap (header/body/footer, backdrop), chặn click link `apply.html` bằng event delegation nên bắt cả link do `jobs.js`/`job-detail.js` render sau. |
| `assets/css/public.css` | Khối `/* DIALOG ỨNG TUYỂN */`: layout dialog, form 1 cột trong dialog, footer sticky, trạng thái thành công, mobile toàn màn hình, tắt animation khi `prefers-reduced-motion`. |
| `apply.html` | Giữ header/footer/giới thiệu; form mount qua `<div id="apply-inline-mount" data-tg-apply-inline>`. Trang này cần JS để dựng form (giống dialog), nhưng vẫn là đích dự phòng khi trình duyệt không hỗ trợ `<dialog>` hoặc cần link trực tiếp. |
| `assets/js/apply.js` | Đã xoá; logic chuyển vào `apply-form.js`. |
| `tools/audit-public.mjs` | Thêm kiểm tra: mở dialog từ job-detail/index, prefill, validation, success trong dialog, không đổi URL, Escape/bấm nền không đóng, chỉ nút × đóng và trả focus, và mở dialog trên `file://`. |

Không thêm thư viện: dialog dùng phần tử `<dialog>` gốc (top layer, focus trap, `::backdrop`) và CSS/JS nội bộ, nên chạy offline và trên `file://` giống mọi trang khác.

## Kiểm tra

```powershell
npm ci
npm run build            # build:admin + build:css (Tailwind quét cả assets/js/*.js)
npm run preview          # server local 127.0.0.1:4173
npm run audit:public     # cần Microsoft Edge; kiểm tra 12 trang × 5 viewport + tương tác
```

Kết quả audit ghi tại `.taste-skill/public-audit/report.json` (gitignored).

## Giới hạn

- Vẫn là demo: không gửi request, không lưu DB, file CV chỉ nằm trên máy người dùng.
- `application-success.html` vẫn tồn tại cho luồng mở `apply.html` trực tiếp; từ dialog thì không điều hướng sang trang đó.
- Nếu sau này chuyển sang PHP include, chỉ cần nạp `apply-form.js` + `apply-dialog.js` trong footer chung.
