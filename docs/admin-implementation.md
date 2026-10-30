# Hoàn thiện frontend quản trị tuyển dụng — 30/10/2026

Đã triển khai 9 trang admin theo [kế hoạch](admin-redesign-plan.md), thêm đường vào `/admin/` và bộ công cụ build/preview/kiểm tra. Đây là frontend có tương tác demo; chưa triển khai backend hoặc xuất bản website.

## Xem và chạy

```powershell
npm ci
npm run build
npm run preview
```

Mở [khu quản trị local](http://127.0.0.1:4173/admin/) rồi chọn **Vào bản demo**. Server preview chỉ bind 127.0.0.1. [Ảnh xem trước desktop/mobile](admin-ui-preview.jpg).

```powershell
npm run audit:admin
```

Lệnh audit tự mở server tạm, dùng Microsoft Edge headless, chụp ảnh và lưu kết quả vào `.taste-skill/admin-final/report.json`. Không cần mở server preview trước khi chạy audit.

## Kết quả theo trang

| Trang | Hành vi mới |
|---|---|
| Dashboard | Hàng chờ hồ sơ là trọng tâm; phạm vi đơn vị lọc thật; KPI tính từ dữ liệu; lịch hẹn mẫu, tin sắp hết hạn và phân bố hồ sơ/nguồn có số liệu/đơn vị. Mobile KPI 2×2, không tràn ngang |
| Hồ sơ ứng viên | Bộ lọc tên/SĐT + trạng thái + vị trí + đơn vị kết hợp; count, tabs, empty state và phân trang thật; xuất CSV UTF-8 theo kết quả lọc; cập nhật nhanh đúng người |
| Chi tiết hồ sơ | Tên, thông tin và lịch sử theo đúng ID; cập nhật trạng thái/ghi chú phản ánh qua trang danh sách; quay lại giữ bộ lọc; tải văn bản hồ sơ mẫu, không giả tải CV thật |
| Tin tuyển dụng | Search mã/tên, trạng thái và đơn vị; số hồ sơ mở đúng vị trí; phân trang thật; menu xem trước/đóng/mở/xoá trong demo. Tin có hồ sơ được giữ liên kết và hướng dẫn đóng thay vì xoá |
| Soạn tin | Tạo mới có form trống; sửa nạp đúng ID; lương min/max, số lượng và hạn được kiểm tra; nháp khác đang tuyển/đóng; nhãn tuyển gấp riêng; preview từ nội dung đang nhập, cảnh báo rời trang khi chưa lưu |
| Nội dung | Nhóm Trang chủ / Giới thiệu–Phúc lợi / Tin tức / Liên hệ; preview chữ trực tiếp; soạn bài, trạng thái nháp/đã đăng trong demo; không diễn giải HTML nhập vào như markup |
| Banner | Chọn ảnh JPG/PNG/WebP, kiểm dung lượng/khả năng giải mã; raster lại WebP; xem desktop/mobile; alt, focal point, hoàn tác; preview và bật/tắt dải thông báo; metadata lấy từ ảnh đã chọn |
| Cài đặt | Phân nhóm website/pháp nhân, SEO và tiếp nhận; đếm ký tự/preview SEO; email và số dòng có validation; contact dẫn về một nơi sở hữu tại CMS |
| Đăng nhập | Tách “Vào bản demo” khỏi đăng nhập thật; hiện/ẩn mật khẩu có trạng thái accessible; nhập tài khoản không tạo thông báo đã xác thực khi chưa có dịch vụ |

## Thiết kế và nguồn

Style **Framed sheet**, seed **1066333000** từ skill design-taste-frontend; giữ Be Vietnam Pro và xanh TEX-GIANG. Một khung nền hơi xanh bao sidebar/topbar, vùng làm việc trắng inset, không shadow trang trí. Sidebar có chữ, main co được, bảng mobile chuyển thành hàng tóm tắt; icon và hành động không làm mất tên accessible.

Dấu hiệu riêng của sản phẩm là khối “Hồ sơ cần xử lý”: tên người, vị trí, đơn vị, trạng thái và hành động tiếp theo. Màu sắc tập trung ở số liệu/trạng thái; các panel không dùng ảnh trang trí.

Logo, banner WebP và bộ SVG Bootstrap Icons đã có trong dự án được tái sử dụng. Không thêm ảnh stock hoặc ảnh cá nhân mới. Giấy phép icon: `assets/images/icons/LICENSE.txt`. Ảnh banner gốc và nguồn ảnh doanh nghiệp vẫn theo [danh sách nguồn](image-sources.json).

### Nguồn mã chính

- `tools/build-admin.mjs`: nguồn duy nhất sinh shell và 9 file HTML tĩnh; chạy `npm run build:admin` sau khi sửa. HTML sinh ra có comment chỉ rõ nguồn. Không fetch shell runtime.
- `assets/css/admin.css`: bố cục, bảng/list responsive, form, dialog và các token admin riêng.
- `assets/js/admin-data.js`: fixtures lấy cùng nguồn `jobs-data.js`, repository phiên, schema và ghi state trước khi công bố thành công.
- `assets/js/admin.js`: render, bộ lọc, ID routing, editor, validation, preview, CSV, chọn ảnh, dirty state và bàn phím.
- `tailwind.config.cjs`: quét cả `admin/**/*.html` và nguồn generator. `assets/css/utilities.css` là output build local; admin bỏ Play CDN và icon font.
- `tools/audit-admin.mjs`: kiểm tra render/workflow; `tools/serve.mjs`: server preview local.

Không sửa `assets/js/jobs.js` đang có thay đổi phân trang từ trước công việc này. Các thay đổi public/print khác có sẵn trong working tree không bị ghi đè bởi phần triển khai admin.

## Lưu phiên và dữ liệu

Fixtures ban đầu có **8 tin và 4 hồ sơ**, không gõ riêng con số 24/142. Tổng, tỷ lệ, tab và số hồ sơ theo tin được tính từ repository trong phạm vi đang chọn. Ngày dùng múi giờ Việt Nam; lịch hẹn ghi rõ là minh hoạ.

Thay đổi admin được giữ trong **sessionStorage của tab**, qua các trang/reload trong cùng phiên. Dùng **Đặt lại dữ liệu demo** để quay về fixtures. Nếu browser chặn ghi/quota đầy, form giữ nguyên và báo chưa lưu; dữ liệu phiên sai schema được bỏ để phục hồi mẫu và hiện thông báo.

**Các nút lưu admin không cập nhật trang public hoặc tạo dữ liệu production.** Bản xem trước trong admin dùng dữ liệu đang nhập; “Xem website công khai” mở các file public hiện có. Việc hợp nhất dữ liệu đã xuất bản và API là phần tích hợp backend tiếp theo.

Không lưu mật khẩu, CV hoặc CCCD thật. Tệp hồ sơ mẫu là `.txt` được tạo local. CSV là file thật để mở/import bằng Excel; không báo đã xuất `.xlsx` khi không có file đó. Ảnh banner thay thế được xử lý local, lưu WebP giới hạn trong phiên demo, không upload ra ngoài.

## Kiểm tra đã thực hiện

**45/45 lượt render đạt**: 9 trang × 1536, 1440, 1024, 768, 390 px. Không tràn document, chữ/hành động kiểm tra bị cắt khỏi viewport, ảnh hỏng, ID trùng, control hiện hữu thiếu nhãn hoặc lỗi runtime trong ma trận. Trang chi tiết được render bằng ID thực, không chỉ trạng thái thiếu ID.

Kiểm tra bổ sung cuối: cả 9 trang ở **360 px** không tràn ngang và không hiện khối lỗi rỗng. `/admin/` chuyển đúng tới màn đăng nhập; server preview từ chối truy cập package.json, thư mục .git, node_modules và tools.

**16/16 nhóm thao tác đạt**:

1. Filter kết hợp, điều kiện mâu thuẫn ra 0, reset và CSV đúng scope.
2. Modal nạp đúng trạng thái người được chọn; lưu và timeline qua trang.
3. ID không tồn tại không trả bản ghi đầu tiên.
4. Editor đúng tin, preview an toàn, validation lương và đóng/mở.
5. Tạo mới sạch và lưu bản nháp qua trang.
6. Cài đặt, preview SEO, validation và lưu/reload.
7. CMS preview không chạy markup; bài viết được lưu đúng bản ghi.
8. Ảnh sai giữ ảnh cũ; chọn ảnh thật, preview và alt lưu lại được.
9. Drawer mobile và dialog đóng Escape/trả focus.
10. Cảnh báo rời form chưa lưu; lối vào demo và thông báo chưa kết nối xác thực.
11. Lỗi quota giữ dữ liệu form, không báo thành công giả.
12. Hồi quy index/jobs/job-detail/apply/news; giữ phân trang public 6 tin.
13. Kiểm tra cả 4 candidate ID và 8 job ID ban đầu.
14. Sidebar đóng trên mobile bị loại khỏi thứ tự Tab bằng `inert`.
15. Session không hợp lệ được phục hồi, payload markup không chạy.
16. Mở trực tiếp bằng file URL và reduced motion.

`npm run build`, `node --check` trên các JS mới/sửa và kiểm tra whitespace diff đã chạy. Gate `inspect.mjs --app` ở 1440/768/390 đạt **0 FAIL trên 9 trang** sau ngoại lệ sau:

- Chi tiết hồ sơ dùng `--allow placeholder-data`: email `example.com` là địa chỉ mẫu được giữ có chủ ý, tránh gán thông tin mẫu cho một hộp thư thật.
- WARN còn lại: khoảng trống cuối danh sách ngắn trên desktop; công cụ nhận timeline lịch sử như chart thiếu hue; cột số trên mobile đã đổi thành trường nhãn/giá trị nên căn trái; không gian hai bên card đăng nhập. Đã xem ảnh và giữ các trường hợp này theo ngữ cảnh sử dụng.

Ảnh/gate chi tiết nằm ở `.taste-skill/admin-gates/` và `.taste-skill/admin-final/`. Đây là artefact QA, không đưa lên hosting. Xem thêm [review an toàn frontend](admin-security-review.md); không suy kết quả này thành kiểm chứng xác thực backend.

## Nhận xét qua ảnh

ROUND 1

Seen in one second: khung sáng, tên trang rõ, hàng chờ và các hành động theo hồ sơ. Mobile đã nhìn thấy thông tin và nút thao tác thay vì bị mất cột.

Question: HR cần xử lý ai, tin nào tiếp theo?

Identity: TEX-GIANG/TG APPAREL, hồ sơ may/QC/bảo trì, trạng thái tuyển dụng và đơn vị.

Top three fixes: (1) toolbar jobs có inline grid thắng breakpoint mobile, chuyển sang class responsive; (2) disabled pagination chưa đủ tương phản, tăng độ đậm; (3) formatter HTML tạo whitespace trong error summary, làm xuất hiện dải rỗng, giữ các container lỗi trống thực sự.

ROUND 2

Seen in one second: bảng hồ sơ dễ quét, trạng thái rõ, editor có panel lưu/preview riêng. Không dùng hình ảnh không phục vụ công việc.

Scores: design **7,5** · usability **8,0** · identity/creativity **6,5** · content **7,0** → overall **7,4/10**.

Caps: đánh giá theo app screen, không áp tiêu chuẩn mật độ ảnh/motion của landing page. Nội dung nghiệp vụ vẫn là fixtures; chưa chấm điểm như sản phẩm đã tích hợp dữ liệu thật.

Remaining: bảng ở cỡ tablet vẫn ưu tiên mật độ nên một vài nhãn xuống dòng; biểu mẫu dài cần cuộn trên điện thoại; chưa có hệ thống tiếp nhận/xác thực/upload server. Các giới hạn này không được che bằng toast thành công giả.

## Bàn giao và giới hạn

Chưa commit hoặc deploy. Khi triển khai static chỉ đưa file trang và assets cần thiết; không đưa node_modules, tools, `.taste-skill`, `.git` hoặc zip nguồn lên web server. Đăng nhập/SSO, phân quyền, API/DB, tải CV có kiểm soát, kiểm tra tệp server, email/Zalo và audit log thật cần phần backend riêng như kế hoạch đã tách.
