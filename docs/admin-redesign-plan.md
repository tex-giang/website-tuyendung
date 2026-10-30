# Kế hoạch audit và thiết kế lại khu quản trị tuyển dụng

Ngày lập: 30/10/2026. Phạm vi: `D:\Workspace\HTML\website-tuyendung\admin\*`.

**Cập nhật triển khai:** Đã thực hiện phần frontend/demo theo kế hoạch. Xem [báo cáo bàn giao](admin-implementation.md) và [ảnh giao diện](admin-ui-preview.jpg). Phần dưới giữ nội dung/lịch sử của lượt lập kế hoạch trước khi triển khai.

Mục tiêu là một khu làm việc rõ ràng cho HR: biết hồ sơ nào cần xử lý, thao tác đúng ứng viên/tin tuyển dụng, soạn và xem trước nội dung thuận tiện. Giữ nhận diện TEX-GIANG và nền HTML/Tailwind/JavaScript hiện có; không dùng bố cục marketing của trang public cho admin.

**Lượt này chỉ audit và lập kế hoạch, chưa sửa giao diện hay hành vi admin.** Khối admin đã có một đợt chỉnh giao diện trước lượt lập kế hoạch này. Tài liệu này là kế hoạch hoàn thiện tiếp theo, không ghi đè lịch sử Sprint 4 trong `REDESIGN-PLAN.md`.

## 1. Phạm vi và nguyên tắc

- 9 trang: dashboard, tin tuyển dụng, soạn tin, hồ sơ ứng viên, chi tiết hồ sơ, nội dung, banner, cài đặt và đăng nhập.
- Giữ các đường dẫn hiện có, tham số `id` và hook JavaScript; kiểm tra cả `admin.html` đang redirect đến dashboard. Khi triển khai thống nhất đường vào `/admin/`, không để hosting tĩnh hiện danh sách thư mục.
- Làm UI và các tương tác prototype nhất quán bằng dữ liệu demo dùng chung. Không đồng nghĩa đã có đăng nhập, lưu DB, upload, phân quyền hoặc gửi email thật.
- Nội dung và hình ảnh trên public đang dùng là căn cứ đối chiếu cho preview; tách CSS admin để tránh làm thay đổi public.
- Chưa thêm Kanban, chấm điểm AI, lịch phỏng vấn đầy đủ, trình quản lý tài khoản hay báo cáo nâng cao. Đây là các tính năng riêng nếu cần sau này.

## 2. Kết quả audit hiện trạng

Đã mở 9 trang bằng Microsoft Edge headless tại **1440, 1024, 390 px**, tổng 27 lượt render, chụp ảnh và kiểm tra DOM/runtime. Không ghi nhận lỗi JavaScript runtime trong các lượt mở trang. Có một lượt tràn ngang ở dashboard 390 px. Kiểm tra thiết kế bổ sung `inspect.mjs --app` trên dashboard tại 1440/768/390 báo 0 FAIL và 1 WARN về độ cao panel; kết quả đó không thay thế kiểm tra tràn ngang và thao tác thực tế.

Ảnh hiện trạng: [bảng điều khiển và luồng hồ sơ](admin-audit-current.jpg). Bản máy đầy đủ và ảnh từng trang ở `.taste-skill/admin-plan-audit/` (artefact local, không xuất bản).

| Ưu tiên | Phát hiện có bằng chứng | Hướng xử lý |
|---|---|---|
| P0 | Dashboard 390 px tràn ngang; bảng ép tên/chức danh xuống nhiều dòng; phần cần xử lý nằm sau 4 KPI và biểu đồ | Xử lý min-width của grid/panel; KPI mobile 2×2; đưa hàng chờ xử lý lên trước biểu đồ; danh sách mobile riêng |
| P0 | Tìm “Mai” rồi chọn “Đã liên hệ” lại hiện Trần Quốc Bảo, dù tên không khớp | Một hàm lọc kết hợp keyword + trạng thái + vị trí, cùng cập nhật count/empty state |
| P0 | Modal mở cho Trần Quốc Bảo “Đã liên hệ” nhưng select vẫn ở `new` | Truyền candidate ID, nạp trạng thái/ghi chú hiện tại; lưu xong phản ánh đúng đối tượng |
| P0 | `application-detail.html?id=2` vẫn hiển thị Lê Thị Mai | Dùng cùng nguồn dữ liệu với danh sách, giải quyết đúng ID, có trạng thái không tìm thấy |
| P0 | `job-form.html?id=TG-02` vẫn nạp chức danh công nhân may | Tách chế độ tạo/sửa; đúng ID và giá trị form, preview đúng tin |
| P0 | Nút đóng/mở tin suy hành động từ chữ trong nút, trong khi nút ban đầu chỉ có icon; lần bấm đầu không chuyển badge “Đang tuyển” | Hành động dựa trên trạng thái dữ liệu; cập nhật badge, menu và count cùng lúc |
| P1 | Nút xuất Excel báo tải hoàn tất nhưng không tạo tệp; nhiều nút lưu/xoá/xuất bản chỉ toast | Prototype ghi rõ giới hạn. Chỉ báo thành công sau thao tác đã thực hiện; xuất có file thật hoặc nêu chưa hỗ trợ, không báo tải giả |
| P1 | Bảng jobs/applications bị cắt cột trên điện thoại; bảng content có tiêu đề xuống từng từ | Mobile dùng hàng hồ sơ/tin tóm tắt; giữ tên, trạng thái và hành động ngay trên màn hình |
| P1 | Hai select ở jobs và hai select ở applications thiếu nhãn liên kết rõ ràng | Thêm label/accessible name, focus rõ, kiểm tra bàn phím và dialog |
| P1 | Search jobs và một số filter/pagination mới có hình thức, chưa có xử lý tương ứng | Hoặc làm chạy trên fixtures, hoặc bỏ/disable kèm giải thích ngắn; không để control giả |
| P1 | Shell chép lại ở 8 trang; admin còn dùng Tailwind CDN và icon font, khác public đã build CSS | Một nguồn shell có công cụ đồng bộ, CSS build cục bộ và icon SVG local dùng chung |
| P2 | Panel dashboard chênh đáy 123/88 px theo công cụ đo; biểu đồ không có thang/giá trị, tháng T11 chưa nêu dự báo | Sắp lại nội dung theo câu hỏi nghiệp vụ; biểu đồ có đơn vị, số liệu và kỳ; không kéo panel rỗng để bằng chiều cao |
| P2 | Số 24 tin/142 hồ sơ được gõ nhiều chỗ; public có bộ demo khác; 86/142 làm tròn một chữ số là 60,6%, đang ghi 60,5% | Dữ liệu chung; tổng/tỷ lệ/ngày tương đối tính từ dữ liệu, ghi rõ kỳ và mẫu số |
| P2 | Preview banner admin vẫn là PNG 1,9 MB trong khi public dùng WebP | Preview đúng asset đang dùng; dung lượng, tỷ lệ và alt lấy từ metadata thật |

Nguồn kiểm tra chính: `assets/js/admin.js` (lọc, modal, đóng/mở tin và xuất); `admin/jobs.html`; `admin/applications.html`; `admin/application-detail.html`; `admin/job-form.html`; `admin/dashboard.html`. Các lỗi trên là trạng thái prototype hiện tại, không phải đánh giá một hệ thống backend đã triển khai.

## 3. Hướng thiết kế chung

**Đề xuất: “Bàn làm việc tuyển dụng TEX-GIANG”**, nền sáng, mật độ vừa, sidebar có chữ, màu xanh tập trung vào hành động và dữ liệu cần chú ý. Khung quản trị hiện có dùng được; tái tổ chức thứ bậc và hành vi thay vì thay toàn bộ phong cách.

| Thành phần | Quy định đề xuất |
|---|---|
| Sidebar | 232–240 px, logo + “Quản trị tuyển dụng”; nhóm Tuyển dụng / Nội dung / Hệ thống; đưa “Đăng tin mới” thành hành động trong trang jobs, không lặp như một mục điều hướng chính |
| Phạm vi dữ liệu | Hiển thị “Tất cả đơn vị” / TEX-GIANG / TG APPAREL. Chỉ dựng bộ chọn nếu lọc thật các màn liên quan; không tạo switcher trang trí |
| Thanh đầu | Cao khoảng 64 px; một H1 22–24 px, mô tả 14 px khi cần, một hành động chính; thông tin người dùng và Xem website ở vị trí ổn định |
| Canvas | Padding desktop 24–32 px, mobile 16 px; gap 20–24 px; content bên phải sidebar co được bằng `minmax(0,1fr)`/`min-width:0` |
| Màu và mặt phẳng | Giữ xanh `#087A55`, nền trung tính hơi xanh; panel trắng, viền mảnh hoặc một bước màu nền; không gradient, ảnh nền, glass hay shadow trang trí |
| Chữ | Be Vietnam Pro đồng bộ public; nội dung bảng 14 px, form 15–16 px, phụ 12–13 px; số dùng tabular, căn phải ở cột so sánh; không thu nhỏ chữ để nhét bảng |
| Nút | Một kiểu cao 36–40 px ở desktop; mục tiêu chạm quan trọng 44 px trên mobile. Primary một nút/trang; thao tác hàng dùng link hoặc menu có nhãn |
| Trạng thái | Từ điển thống nhất, có chữ đi cùng màu. Trạng thái hồ sơ và trạng thái tin là hai bộ riêng; “Tuyển gấp” là mức ưu tiên, không trộn với vòng đời nháp/đang tuyển/đóng |
| Bảng | Tên là cột rộng nhất; số điện thoại không ngắt giữa nhóm; ngày/giờ nhất quán; hành động ở một vị trí; không dàn 4 icon trên mỗi hàng |
| Form | Nhóm theo nghiệp vụ; label rõ, lỗi inline, summary khi nhiều lỗi; nút Lưu chỉ bật khi có thay đổi hợp lệ; có pending/error và cảnh báo rời trang khi chưa lưu |
| Mobile | Drawer điều hướng có Escape, focus trap/trả focus; list dạng hàng tóm tắt; panel chi tiết xếp một cột; hành động lưu có thể sticky nhưng không che trường hoặc bàn phím |

**Ảnh/icon:** dùng lại logo, icon SVG local và avatar bằng chữ cái đầu ổn định. Ảnh thật chỉ cần ở quản lý banner/media và preview nội dung. Không bổ sung ảnh xưởng hoặc ảnh trang trí vào dashboard, bảng ứng viên, form cài đặt. Không dùng ảnh chụp CCCD hoặc CV như thumbnail mặc định.

**Dấu hiệu riêng của sản phẩm:** khối “Hồ sơ cần xử lý” hiển thị tên, vị trí, đơn vị, lần liên hệ gần nhất và hành động tiếp theo; đây là trọng tâm của dashboard, thay vì một biểu đồ trang trí.

## 4. Kế hoạch từng trang

| Trang | Câu hỏi chính | Bố cục và thay đổi | Hoàn thành khi | Ước lượng |
|---|---|---|---|---|
| `dashboard.html` | Hôm nay HR cần xử lý việc gì trước? | Hàng chờ hồ sơ + lịch hẹn sắp tới nếu có dữ liệu; KPI gọn; tin sắp hết hạn; xu hướng và nguồn ứng viên ở phần phụ | Tác vụ ưu tiên thấy ngay; tổng tính từ dữ liệu; biểu đồ có thang/đơn vị/kỳ; mobile không tràn | 1,5–2,5h |
| `applications.html` | Hồ sơ nào phù hợp bộ lọc và cần xử lý? | Tabs trạng thái; toolbar tìm tên/SĐT + vị trí/đơn vị; bảng desktop, list mobile; số kết quả và empty state; modal cập nhật nhanh | Kết hợp mọi filter đúng; count đúng; modal nạp/lưu đúng ID; xuất/phân trang chỉ hiện khi chạy được | 3–4h |
| `application-detail.html` | Ứng viên là ai và bước tiếp theo là gì? | Tên ứng viên là H1; thông tin + hồ sơ đính kèm bên trái; trạng thái/ghi chú và hành động bên phải; timeline xử lý; quay lại giữ bộ lọc | `?id=` đúng người; lưu cập nhật timeline và danh sách; có missing/error; file và số điện thoại thao tác đúng | 2,5–3,5h |
| `jobs.html` | Tin nào đang tuyển, cần gia hạn hoặc đóng? | Tabs vòng đời; search mã/tên + đơn vị; tiêu đề và lương dễ đọc; số hồ sơ bấm mở danh sách lọc đúng tin; “Sửa” rõ, thao tác khác trong menu | Search/filter chạy; public preview đúng ID; đóng/mở không suy từ text; mobile thấy tên/trạng thái/hạn/hành động | 2–3h |
| `job-form.html` | Tin đã đủ thông tin để đăng chưa? | 2 cột desktop: nội dung chính và panel xuất bản 280–320 px; lương min/max/đơn vị, số lượng dạng số, hạn và địa điểm; preview; nháp/lưu/xuất bản phân biệt | Tạo mới không nạp sẵn tin cũ; sửa đúng ID; validation ngày/lương/số lượng; cảnh báo chưa lưu; preview dùng dữ liệu đang soạn | 2,5–3,5h |
| `content.html` | Nội dung công khai sẽ thay đổi ở đâu? | Tabs hoặc nhóm Trang chủ / Giới thiệu–Phúc lợi / Tin tức / Liên hệ; editor và preview bên cạnh trên desktop; bảng bài viết dễ đọc; phân biệt nháp/đã đăng | Mỗi nhóm chỉ một nơi sở hữu dữ liệu; preview đúng trang/bài; lưu nháp khác xuất bản; ghi rõ phạm vi khi editor chưa hỗ trợ nội dung nào | 1,5–2h |
| `banners.html` | Ảnh và thông báo đang hiển thị thế nào? | Ảnh hiện tại + upload/preview/crop hoặc focal point; metadata thật; alt; preview desktop/mobile; dải thông báo có nội dung/link/bật tắt; có hoàn tác thay ảnh trước lưu | Chọn tệp chạy local, lỗi file rõ; giữ ảnh cũ khi lỗi; đúng tỷ lệ/asset public; bật tắt có preview; không báo đã upload lên server trong demo | 1,5–2h |
| `settings.html` | Cấu hình nào thay đổi hành vi hệ thống? | Tách nhận diện/pháp nhân, SEO, tiếp nhận hồ sơ và vận hành; preview kết quả SEO; dirty state; bỏ cấu hình liên hệ trùng content hoặc dẫn link tới nơi sở hữu | Không trùng nguồn dữ liệu; đếm ký tự thật; email/URL/giới hạn hợp lệ; lưu chưa thành công không mất dữ liệu | 1–1,5h |
| `login.html` | Tôi vào đúng khu quản trị bằng cách nào? | Card 400–440 px, tên sản phẩm rõ; label/lỗi inline/loading; hiện mật khẩu có tên trạng thái; tách nút “Vào bản demo” khỏi thông điệp đăng nhập thật | Không dùng thông báo demo như bằng chứng xác thực; lỗi/focus rõ; thông báo hỗ trợ khôi phục có nội dung thực tế; không thêm ảnh trang trí | 0,75–1,25h |

Hồ sơ: **Mới nhận → Đã liên hệ → Hẹn phỏng vấn → Trúng tuyển / Không phù hợp**. Bước đầu giữ quy trình này, không tự thêm điểm đánh giá hoặc quy tắc tuyển dụng mới.

Tin: **Nháp / Đang tuyển / Đã đóng**, kèm trạng thái hết hạn suy từ ngày và nhãn ưu tiên tuyển gấp riêng. Quy tắc cho phép nộp hồ sơ sau hạn phải dùng cùng một hàm ở public và admin trong giai đoạn triển khai.

## 5. Thứ tự thực hiện và điểm kiểm tra

| Đợt | Phạm vi | Sản phẩm bàn giao | Ước lượng |
|---|---|---|---|
| A — Nền chung | Chụp baseline, nguồn shell, CSS/icon local, fixture/schema chung, trạng thái và định tuyến ID | Shell desktop/mobile; component bảng/form/dialog; bộ dữ liệu demo có tổng đúng, không lệch public/admin | 3–4h |
| B — Xử lý hồ sơ | applications → application-detail | Luồng tìm → mở đúng ứng viên → đổi trạng thái/ghi chú → trở lại danh sách; đủ empty/error/pending | 5,5–7,5h |
| C — Quản lý tin | jobs → job-form | Tạo/sửa/preview/đóng/mở/nháp; liên kết hồ sơ theo tin; validation và dirty state | 4,5–6,5h |
| D — Tổng quan và quản trị nội dung | dashboard → content → banners → settings → login | Dashboard ưu tiên công việc; preview nội dung/media; cài đặt phân nhóm; login/demo rõ ràng | 6,25–9,25h |
| E — QA và chỉnh hình | 9 trang admin + hồi quy public | Ma trận kích thước, click/keyboard, đối chiếu số liệu, ảnh trước/sau và biên bản giới hạn demo | 3–4h |

**Tổng dự kiến: khoảng 22–32 giờ làm việc**, tương đương khoảng 3–4 ngày nếu tập trung. Đây là ước lượng cho thiết kế/frontend và tương tác prototype, không phải cam kết thời gian tích hợp backend. Đợt A có thể điều chỉnh ước lượng khi thấy rõ nguồn dữ liệu cần hợp nhất.

Mỗi đợt chốt bằng ảnh và một luồng thao tác chạy được trước khi chuyển đợt tiếp theo. Không thay tất cả trang rồi mới kiểm tra dữ liệu/ID ở cuối.

## 6. Các quyết định triển khai đề xuất

1. **Giữ stack hiện tại.** Không chuyển React/Next hoặc đổi thư viện UI cho lượt này. Tạo `assets/css/admin.css`; mở rộng build Tailwind quét `admin/**/*.html`, không chỉ các trang public. Các trang public không đổi hình sau khi mở rộng build.
2. **Một nguồn shell.** Dùng template nguồn + script đồng bộ cho HTML tĩnh; không phụ thuộc fetch runtime để sidebar xuất hiện. Không dùng component rời đang cũ làm nguồn nếu chưa đối chiếu markup/hook hiện hành.
3. **Một nguồn fixtures.** Jobs, candidates, states, chart series và media metadata dùng ID ổn định. Counter tính từ dữ liệu trong scope. Ưu tiên bộ demo nhỏ nhưng đầy đủ; không dựng con số 142/24 riêng với 4 hàng và pagination không chạy.
4. **Trạng thái lưu minh bạch.** Prototype lưu trong state phiên và thông báo rõ “trong bản demo”; reload có hành vi xác định. Không lưu CCCD/CV hoặc thông tin nhạy cảm vào localStorage để giả backend. Thay bằng repository/API adapter khi có backend.
5. **Chưa thêm lịch/AI.** Dashboard chỉ hiện lịch hẹn nếu fixtures đã có ngày giờ. Lịch phỏng vấn tương tác, nhắc việc và lịch sử truy cập thật cần kế hoạch chức năng riêng.
6. **Không chia quyền bằng cách ẩn nút rồi gọi là bảo mật.** Login/session, phân quyền, quyền tải CV và audit log thật cần backend; thiết kế chỉ thể hiện rõ các trạng thái được phép/không được phép theo dữ liệu cung cấp.

## 7. Điều kiện nghiệm thu

- 9 trang × **1536, 1440, 1024, 768, 390 px**; bổ sung kiểm nhanh 360 px cho header, toolbar và dialog. Không tràn document hoặc cắt hành động chính; không coi `overflow-x:hidden` là cách sửa nội dung bị mất.
- Trên 1440×900, dashboard thấy hàng chờ và hành động chính; trên mobile thấy việc cần làm trước biểu đồ. Các bảng quản trị có thể cuộn cục bộ trên tablet khi cần, nhưng mobile có phương án hiển thị thông tin/hành động cốt lõi.
- Không lỗi runtime, ID trùng, ảnh hỏng. Input/select có nhãn; icon-only button có tên; menu/dialog đóng Escape, giữ và trả focus, không lọt Tab ra nền.
- Search + trạng thái + vị trí/đơn vị kết hợp đúng; reset/empty/count/paging nhất quán. Chọn hai điều kiện mâu thuẫn phải ra 0 kết quả.
- Mỗi candidate/job ID có cùng tên, trạng thái và nội dung giữa list/detail/editor/preview. ID không tồn tại có empty/error rõ, không âm thầm trả bản ghi đầu.
- Đổi trạng thái cập nhật đúng một đối tượng, timeline/count theo cùng dữ liệu; bấm lặp khi pending không tạo thao tác lặp.
- Tạo/sửa có validation và trạng thái chưa lưu; lưu lỗi không xoá dữ liệu đã nhập; file sai không thay thế file đang có.
- Counter, tỷ lệ, thời gian tương đối và biểu đồ tính được từ fixtures/kỳ được chọn. `0`, chưa có dữ liệu và lỗi tải được phân biệt; tháng tương lai không trình bày như số đã ghi nhận.
- Không thông báo tải/lưu/xuất bản thật khi chỉ demo. Nếu xuất được file thì kiểm tra file và phạm vi hàng sau lọc; backend chưa có phải được ghi rõ.
- `inspect.mjs --app` và xem ảnh thủ công từng trang; chấp nhận ngoại lệ có giải thích cụ thể, không dùng số 0 FAIL để kết luận mọi workflow đã đúng.
- Build CSS và kiểm tra hồi quy các trang public chính: index, jobs, job-detail, apply, news. Không khởi chạy tài khoản hoặc dịch vụ thật trong QA frontend.

## 8. Nhận xét thiết kế baseline

**Nhìn đầu tiên:** giao diện quản trị đã gọn và có nhận diện TEX-GIANG; độ ưu tiên công việc chưa rõ. Dashboard dẫn bằng tổng số, còn trên mobile bốn thẻ số chiếm gần hết màn đầu. Bảng danh sách có nhiều hàng xuống dòng và hành động nằm ngoài phần nhìn đầu tiên.

**Điểm tốt nên giữ:** sidebar có nhãn; nền sáng; bảng/form dùng cùng kiểu; trang chi tiết hai cột; đăng nhập không dùng ảnh nền hoặc gradient.

**Ba việc tạo khác biệt lớn nhất:** (1) đưa hàng chờ xử lý lên trước và sửa mobile; (2) thống nhất dữ liệu/ID/trạng thái để thao tác đúng; (3) sửa mật độ bảng và toolbar, giảm icon-action lặp.

Đánh giá baseline: design **6,5/10**, usability **5/10**, identity/creativity **6/10**, content/data consistency **4,5/10**; điểm tổng tham khảo **5,8/10**. Đây là đánh giá qua ảnh và các luồng demo đã kiểm tra, không phải điểm bảo mật hoặc chất lượng backend.

## 9. Ngoài phạm vi ước lượng

Đăng nhập thật/SSO, phân quyền server, DB/API, lưu và quét tệp, tải CV có kiểm soát, gửi mail/Zalo, audit log thật và triển khai production. Khi bắt đầu tích hợp cần đối chiếu schema và hợp đồng API thực tế để lập phần việc riêng. Không cần chờ các tích hợp này mới sửa được giao diện và prototype.
