# Hoàn thiện sau audit chuyên gia — 03/11/2026

> Điều chỉnh theo yêu cầu sau đó: các nút ứng tuyển trên website mở dialog; không dẫn sang `ung-tuyen.html`. Trang `ung-tuyen.html` dành riêng cho luồng QR, không sửa trong lượt điều chỉnh này. Bỏ liên kết “Mở trang ứng tuyển” khỏi dialog. Các ghi chú chuyển bản nháp bên dưới là lịch sử triển khai trước điều chỉnh này.

Phạm vi: sửa các lỗi frontend/luồng demo và các điểm giao diện trong [audit chuyên gia](expert-audit-2026-11-03.md). Giữ HTML, Tailwind build local, JavaScript thuần và bộ sinh admin hiện có. Không commit hoặc deploy.

## Kết quả triển khai

| Hạng mục audit | Thay đổi |
|---|---|
| F01 — mất hồ sơ khi reload | Kiểm tra ID duy nhất theo độ dài danh sách, cho phép tối đa 2.000 hồ sơ; ghi state trước khi công bố dữ liệu và xóa hàng chờ. Lỗi nhập queue giữ queue để thử lại, không đưa dữ liệu chưa lưu vào bảng |
| F02 — sai vị trí/thông tin | Dùng `jobId` ổn định từ tin đến form/queue/admin. Không khớp thì giữ vị trí chưa xác định, không gán sang TG-01. Giữ năm sinh null, chuẩn hóa +84, giữ khu mong muốn, lời nhắn, tên tệp và giới tính nếu được nhập |
| F03 — card trang chủ mở sai tin | Gắn ID cho từng card. Tiêu đề, mô tả, thu nhập, địa điểm, hạn và CTA được đồng bộ từ cùng kho tin |
| F04 — bài tin luôn giống nhau | List/detail dùng chung `news-data.js`, đúng ID/nội dung/title/description/ảnh; ID không tồn tại chuyển 404; bài liên quan loại bài hiện tại. Chuyển các câu chuyện sự kiện demo chưa xác nhận thành cẩm nang nghề nghiệp, không giữ số liệu sự kiện bịa |
| F05 — lưu lỗi vẫn thành công | Queue chỉ trả thành công sau `sessionStorage.setItem`. Lỗi giữ nội dung, hiện thông báo, mở lại nút gửi; khóa gửi trùng trong một lượt |
| F06 — ba biểu mẫu khác nhau | Dialog, apply và trang đầy đủ cùng dùng `TGApplyForm`/contract. Giữ ID tin và khu; chuyển chữ đang nhập qua bản nháp khi mở trang đầy đủ; tệp phải chọn lại và có thông báo. Bỏ thu CCCD ở bước đầu |
| F07 — hết hạn/đếm lệch | Một hàm trạng thái theo ngày Việt Nam dùng chung public/admin. Mặc định list chỉ tin đang mở; có lựa chọn tất cả đợt; tin cũ ghi rõ hồ sơ dự phòng; đếm trang chủ theo nguồn tin |
| F08 — liên hệ/lời hứa sai | Bỏ hotline/link doanh nghiệp lặp, bỏ đích Zalo chưa xác nhận và dùng trang liên hệ. Form liên hệ/newsletter nói rõ chưa gửi/lưu thật; xác nhận hồ sơ mô tả đúng phạm vi lưu ở tab |

Không phục hồi được những hồ sơ đã bị bản cũ ghi mất trước khi sửa. Phiên bị lỗi cũ vẫn được báo là dữ liệu không hợp lệ; bản mới không tạo dữ liệu cá nhân thay thế.

## Giao diện

- Mobile dưới 768 px: khung ảnh logo **60×60**, `object-fit: contain`, không thêm chữ thương hiệu rời; áp dụng public, trang form, topbar/sidebar/login admin. Desktop giữ bố cục nhận diện hiện có.
- Mobile jobs: thu gọn heading/lead, bỏ ảnh dẫn nhập ở đầu danh sách để hiện tin đầu sớm; giữ drawer lọc và CTA của tin. Ẩn nút liên hệ nổi che nội dung danh sách.
- Admin mobile: giá trị trạng thái không bị cắt; vị trí/khu sản xuất là bộ lọc mở rộng; Chi tiết/Cập nhật đủ vùng bấm. Form chỉnh sửa có nút lưu ở đáy màn hình; bước xử lý hồ sơ lên trước thông tin dài. Các panel vẫn giữ thứ tự DOM theo cách hoạt động hiện tại.
- Trang chủ dùng ảnh tập thể ở chương trình Mindset Transformation có chú thích lưu trữ; bổ sung ảnh chi nhánh Cai Lậy/Chợ Gạo tại About. Phúc lợi dùng ảnh tập huấn đúng chú thích tháng 02/2020. Trang tin dùng ảnh có alt/chú thích theo nội dung thay cho nhiều crop của cùng poster cổng.
- Giữ cột đọc bài ở khoảng 60ch, phân cấp tiêu đề/lead/body/meta, bài liên quan dạng list. Trang xác nhận tăng chữ phụ lên 16 px.
- Chuyển bảng so sánh suy đoán về nơi tuyển khác thành bảng điều cần xác nhận trực tiếp với tuyển dụng.

## Nguồn và phạm vi xác nhận ảnh

Ảnh mới lấy từ website TEX-GIANG, lưu WebP local và ghi trong `image-sources.json`:

| Tài sản | Nguồn / nội dung | Nơi dùng |
|---|---|---|
| `company/team-event.webp` | [News & Events](https://tex-giang.com.vn/news-events.html), chương trình Mindset Transformation; nguồn không cung cấp ngày trong caption | Trang chủ, cẩm nang |
| `company/training.webp` | [News & Events](https://tex-giang.com.vn/news-events.html), tập huấn phòng chống buôn bán người và xâm hại tình dục tháng 02/2020 | Phúc lợi, bài an toàn |
| `company/cai-lay.webp` | [Website doanh nghiệp](https://tex-giang.com.vn/), chi nhánh Cai Lậy | About, cẩm nang sinh hoạt/đi lại |
| `company/cho-gao.webp` | [Website doanh nghiệp](https://tex-giang.com.vn/), chi nhánh Chợ Gạo | About |
| `company/quality-system.webp` | [QA System](https://tex-giang.com.vn/qa-system.html), sơ đồ QA/QC | Bài nghề nghiệp QC/IE |

Không tạo ảnh công nhân/nhà máy giả và không dùng ảnh 2020 làm bằng chứng cho sự kiện 2026. Nguồn không ghi nhiếp ảnh gia hay giấy phép mở; doanh nghiệp vẫn cần xác nhận quyền xuất bản khi đưa site chính thức lên mạng. Credit nằm ở footer/figcaption. Hotline/email đối chiếu với [website TEX-GIANG](https://tex-giang.com.vn/); chưa có tài khoản Zalo được xác nhận.

## Nguồn mã

- `assets/js/application-data.js`: vị trí/ID/khu, chuẩn hóa điện thoại và schema queue.
- `assets/js/demo-store.js`: queue có giới hạn, ghi lỗi phải trả về caller.
- `assets/js/apply-form.js`, `apply-dialog.js`: một biểu mẫu, lỗi inline, prefill và chuyển bản nháp.
- `assets/js/jobs-data.js`: nguồn tin và quy tắc ngày/trạng thái dùng chung.
- `assets/js/news-data.js`, `news-detail.js`: nội dung, route, metadata và bài liên quan.
- `assets/js/admin-data.js`, `admin.js`: nhập hồ sơ, schema session và hiển thị trường thiếu.
- `tools/build-admin.mjs`: nguồn sinh HTML admin. Chạy `npm run build:admin` khi thay đổi; không sửa riêng output.
- `tools/test-audit-fixes.mjs`: regression cho những lỗi mà kiểm tra giao diện cũ chưa bao phủ. Chạy `npm run test:flows`.

## Kiểm chứng

Chi tiết kết quả sau lượt kiểm cuối được ghi kèm báo cáo trong `.taste-skill/`. Các kiểm tra chạy trên Edge headless tại HTTP local, không phải E2E dịch vụ tuyển dụng đang vận hành. Ma trận public/admin và các ca nhập liệu dùng dữ liệu kiểm thử trong browser context riêng, không sửa phiên trình duyệt của người dùng.

## Giới hạn còn lại

Backend, đăng nhập/quyền HR, lưu CV trên máy chủ, email tiếp nhận và xuất bản nội dung chưa được triển khai trong checkout này. Các thay đổi admin vẫn giữ trong sessionStorage của tab và không cập nhật website public. Hồ sơ demo có đường vào quản trị trong cùng tab; không coi hai tab là cùng một cơ sở dữ liệu.

Mức lương/chính sách/chứng nhận/tuyến xe trong tin mẫu cần HR duyệt. Chưa thể tự xác nhận nội dung đó chỉ bằng sửa frontend. Chưa deploy, chưa đo Core Web Vitals production, chưa kiểm tra trên thiết bị iOS/Android thật.

## Kết quả kiểm tra cuối

- `npm run build`: thành công; cảnh báo cơ sở Browserslist cũ không chặn build.
- `npm run audit:public`: **60 render / 9 nhóm tương tác đạt** ở 1536/1440/1024/768/390 px.
- `npm run audit:admin`: **45 render / 16 nhóm tương tác đạt**, cùng ma trận chiều rộng.
- `npm run test:flows`: **12 nhóm đạt**. Bao gồm đúng ID QC, nullable birth, +84, reload giữ trạng thái/chỉnh sửa, card trang chủ, 15 ID bài tin/404, lỗi quota, nhập queue thất bại, chuyển bản nháp, hồ sơ chưa xác định, import lặp, tin hết hạn, dữ liệu giả mạo, logo 360/390/767, tin đầu mobile và lọc responsive. Logo tất cả bề mặt đã đo đúng 60×60.
- `node --check` cho JavaScript thay đổi và `git diff --check`: đạt. Đã xem các sheet desktop/tablet/mobile của 13 trang public/form và 9 màn hình admin; xem lại ảnh form và bảng hồ sơ sau lần tinh chỉnh cuối.
- Inspector: **0 FAIL ở 13 trang public/form và 9 màn hình admin sau các ngoại lệ đúng vai trò trang**. Dùng lại ngoại lệ `few-pictures`/`focal-point` cho list/form/thông báo, `still-page`/`no-footage` cho trang thao tác không cần cảnh chuyển động; trang chủ chạy không thêm các ngoại lệ đó. Chi tiết hồ sơ admin vẫn cho phép email fixture `example.com` theo ngoại lệ đã công bố trước.
- Những cảnh báo còn lại: nét đứt ở vùng chọn tệp thật; nút gửi form nằm sau các mục bắt buộc; một đoạn giải thích dài trên apply; nhiều cỡ chữ ở About; các mép chữ do bố cục nhiều cột/gutter; khoảng trắng cuối sheet hồ sơ và bố cục login căn giữa. Không coi cảnh báo không có video là lỗi nghiệp vụ và không thêm autoplay để đạt điểm.
- Rà soát đầu vào/queue/draft: [security review](audit-fixes-security-review.md), không phải chứng nhận an toàn production.

Bản preview local: `npm run preview` → `http://127.0.0.1:4173/`. Build và test dùng các browser context riêng; không đăng nhập hoặc thao tác trong phiên trình duyệt của người dùng.

## Nhận xét thị giác sau sửa

ROUND 2

Seen in one second: trang chủ nhấn công việc và ảnh con người; trang jobs mobile đã hiện vị trí đầu thay vì ảnh dẫn nhập; admin ưu tiên hồ sơ và hành động.

Idea (one sentence): nơi làm việc và con người TEX-GIANG dẫn ứng viên đến đúng vị trí, biểu mẫu và hàng chờ HR trong demo.

Live thing: lọc tin đang mở, giữ ngữ cảnh ứng tuyển, xác nhận lưu thật trong phiên và theo dõi đúng hồ sơ qua reload.

Layout words by section: home=type/photo / jobs=list / apply=shared-form / about=photo-story / news=lead/list / article=reading-column / admin=sidebar/table.

Scores: đánh giá chủ quan theo cùng rubric. Trang chủ design 7 · usability 8 · creativity 6 · content 7 → arithmetic 7,1; cap house-style 6,5 vì headline nhấn màu và nhịp card đã giữ từ thiết kế cũ. Admin dashboard design 7,5 · usability 8 · creativity 6,5 · content 7 → 7,4. Điểm này không đo độ sẵn sàng backend hay độ đúng nội dung HR.

Caps applied: giữ nền tảng thương hiệu và bố cục có sẵn, không tuyên bố đã tạo một art direction mới hoặc bản thiết kế đạt mọi quy tắc của gallery. Các lỗi dữ liệu được nghiệm thu bằng regression riêng, không suy từ điểm thẩm mỹ.

Top three remaining problems, with screenshot and future action:

1. `fix-final-index/desktop-fold.jpg`: ảnh tập thể là ảnh lưu trữ và bị crop trên desktop. Thay bằng buổi chụp mới về chuyền may/con người khi doanh nghiệp có tài sản phù hợp.
2. `final-about/desktop-page-01.jpg`: một số số liệu/chứng nhận/chính sách vẫn cần HR xác nhận để thành nội dung xuất bản; không tự chỉnh số liệu theo suy đoán.
3. `fix-final-ung-tuyen/mobile-page-01.jpg`: form đầy đủ vẫn cần cuộn để nhập các phần tùy chọn. Có thể khảo sát tỷ lệ hoàn thành trên thiết bị thật trước khi rút gọn thêm; hiện không thêm wizard hoặc bắt tạo tài khoản.
