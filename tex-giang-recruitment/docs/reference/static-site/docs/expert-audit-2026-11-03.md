# Audit chuyên gia — website tuyển dụng TEX-GIANG

> Đây là snapshot trước khi sửa. Xem [kết quả triển khai sau audit](audit-fixes-2026-11-03.md) cho trạng thái mới.

Ngày kiểm tra: **03/11/2026**. Kiểm tra checkout hiện tại bằng Edge/Playwright trên HTTP local. Đây là lượt review; không sửa giao diện, dữ liệu nguồn hoặc luồng nghiệp vụ.

## Kết luận

**Giao diện có nền tảng tốt để trình bày và tiếp tục phát triển, nhưng chưa nên chốt là đã hoàn thiện hoặc dùng tiếp nhận hồ sơ thật.** Admin có hệ thống rõ ràng hơn phần public. Những điểm cản trở lớn nhất hiện tại là tính đúng của luồng chọn việc → ứng tuyển → xử lý hồ sơ, sau đó mới đến ảnh và bố cục.

Không cần thiết kế lại toàn bộ. Nên giữ nhận diện xanh, font Be Vietnam Pro, cấu trúc admin, job-row, form có lỗi inline và CTA ứng tuyển trên mobile; sửa các điểm dưới đây theo thứ tự.

## Phạm vi và bằng chứng

- Chạy lại `npm run audit:public`: **60 lượt render / 9 nhóm tương tác, không có failure**. 12 trang, các chiều rộng 1536/1440/1024/768/390 px.
- Chạy lại `npm run audit:admin`: **45 lượt render / 16 nhóm tương tác, không có failure**. 9 trang, cùng ma trận chiều rộng.
- Kiểm tra bổ sung **23 màn hình × 2 chiều rộng 390/1440 = 46 lượt**: bao gồm `ung-tuyen.html`, `index2.html`, trang chi tiết đúng ID. Không thấy tràn ngang, ảnh local hỏng, control hiện ra thiếu label hoặc lỗi JS runtime trong ma trận này.
- Đối chiếu ảnh desktop/mobile của cả 23 màn hình; kiểm tra thêm ảnh tablet và toàn trang từ bộ inspector cho index/jobs/news/ung-tuyen và admin dashboard/applications. Chụp riêng dialog ứng tuyển ở phần đầu và phần cuối.
- Kiểm tra local target của các link được render trong ma trận không thấy file đích bị thiếu. **Link tồn tại không đồng nghĩa link mở đúng nội dung**: lỗi card việc và bài tin bên dưới là ví dụ.
- Inspector chạy trực tiếp, không thêm ngoại lệ mới, trên 6 trang đại diện: index **0 FAIL / 2 WARN**, jobs **2 / 2**, ung-tuyen **4 / 3**, news **1 / 4**, admin applications **0 / 1**, admin dashboard **0 / 0**.
- Artefact tại `.taste-skill/expert-2026-11-03/`: `report.json`, ảnh từng màn hình, `dialog-390.png`, `dialog-lower-390.png`, `review-folds-*.jpg` và `gates/`.

Các bài kiểm tra cũ chủ yếu xác nhận render, lọc, form và CRUD mẫu. Chúng **chưa kiểm tra toàn bộ liên kết giữa các luồng mới**. Vì vậy, kết quả pass không phủ định các lỗi tái hiện dưới đây.

## Các lỗi cần sửa trước khi chốt frontend

P1: làm mất dữ liệu hoặc đưa người dùng/hồ sơ sang nội dung sai. P2: gây hiểu nhầm, tăng thao tác hoặc giảm hiệu quả tuyển dụng.

### F01 — P1: Hồ sơ mới làm phiên admin bị khôi phục về dữ liệu gốc sau reload

Tái hiện: mở `job-detail.html?id=TG-02` → ứng tuyển QC bằng tên/số điện thoại/địa chỉ hợp lệ → sang admin trong cùng tab → hồ sơ xuất hiện → reload admin → hồ sơ biến mất, `AdminData.recovered === true`.

Nguyên nhân tại `assets/js/admin-data.js:223`: kiểm tra số ID khác nhau bằng hằng số **4**, thay vì số hồ sơ đang lưu. Hồ sơ thứ năm hợp lệ làm cả snapshot bị coi là lỗi. Nhánh đọc dữ liệu quay về seed, có thể kéo theo các chỉnh sửa khác trong cùng snapshot không còn xuất hiện.

Đề xuất: kiểm tra ID không trùng so với độ dài danh sách; giữ schema/giới hạn hợp lý; chỉ xóa hàng chờ sau khi ghi state thành công. Tiêu chí nghiệm thu: thêm nhiều hồ sơ, cập nhật trạng thái và sửa tin; reload vẫn giữ đủ dữ liệu trong phạm vi demo đã công bố.

### F02 — P1: Ứng tuyển QC bị gán thành Công nhân may; thông tin trống bị tự điền

Tái hiện cùng F01: form hiển thị `qc`, hàng chờ ghi `Giám sát QC — kiểm phẩm (12 – 18 triệu)` nhưng admin nhập thành `jobId: TG-01` — Công nhân may chuyền. Không nhập năm sinh thì admin hiện **2000**.

Nguồn: `assets/js/apply-form.js:348` gửi chữ của option thay vì ID tin; `assets/js/admin-data.js:310` so sánh chuỗi tiêu đề đúng tuyệt đối, rồi fallback sang tin đầu tiên tại dòng 320. Năm sinh mặc định tại dòng 318. Luồng này cũng cắt số điện thoại về 10 chữ số mà chưa chuẩn hóa tiền tố quốc gia.

Đã tái hiện thêm: số hợp lệ `+84918123456` được form chấp nhận nhưng vào admin thành `8491812345`. Chọn Cái Lậy và nhập kinh nghiệm QC trên form chung thì cả khu mong muốn và lời nhắn đều rỗng trong queue/admin. Đây là mất/sai thông tin tiếp nhận, không chỉ là khác biệt trình bày.

Đề xuất: chuyển `jobId` và mã khu sản xuất xuyên suốt danh sách/chi tiết/form/queue/admin; không suy quan hệ từ tiêu đề; thiếu năm sinh giữ null và hiển thị `—`; chuẩn hóa đầu số trước validation. Khi không khớp vị trí, giữ trạng thái chưa xác định để HR xử lý.

### F03 — P1: Card QC và IE trên trang chủ mở chi tiết Công nhân may

Tái hiện: tại `index.html`, bấm tiêu đề QC hoặc IE → đều đến `job-detail.html` không có ID → H1 “Công nhân may công nghiệp”. Các link “Xem chi tiết” cũng có cùng vấn đề.

Nguồn: `index.html:189`, `index.html:205`; `assets/js/job-detail.js:3` mặc định TG-01 khi không có ID.

Đề xuất: render card từ cùng nguồn dữ liệu và gắn ID đúng; tiêu đề card, chi tiết và CTA phải giữ cùng một vị trí.

### F04 — P1: Danh sách tin tức có nhiều bài nhưng trang chi tiết luôn là một bài

Tái hiện `news-detail.html?id=tuyen-300-cong-nhan`, `?id=khach-hang-chau-au-kiem-tra` và cả `?id=does-not-exist`: đều hiện “Ngày hội việc làm Châu Thành 2026”.

Nguồn: `assets/js/news.js` sinh URL có ID; `news-detail.html` là nội dung tĩnh và chưa đọc ID để nạp bài. Tiêu đề, ngày, nội dung và metadata không đi theo lựa chọn.

Đề xuất: dùng chung kho bài viết cho list/detail; ID không tồn tại phải có trạng thái không tìm thấy. Bài chưa có nội dung không nên có link mở một bài không liên quan.

### F05 — P1: Ghi hàng chờ thất bại vẫn thông báo gửi thành công

Tái hiện bằng cách mô phỏng `sessionStorage.setItem` ném `QuotaExceededError`: điền form hợp lệ → vẫn chuyển sang `application-success.html`; hàng chờ không có hồ sơ.

Nguồn: `assets/js/demo-store.js:16` nuốt lỗi ghi; `assets/js/apply-form.js:362` tiếp tục nhánh thành công. Trang xác nhận vẫn có câu HR sẽ xem và gọi lại dù là demo.

Đề xuất: API ghi trả kết quả hoặc ném lỗi; chỉ xác nhận khi thao tác lưu theo phạm vi demo đã thành công, giữ dữ liệu nhập và cho thử lại khi lỗi. Nội dung xác nhận phải phân biệt rõ mô phỏng với việc gửi HR thật.

### F06 — P2: Ba bề mặt ứng tuyển chưa giữ cùng ngữ cảnh và quy tắc

Hiện có dialog, `apply.html` và `ung-tuyen.html`. Từ tin Quản lý đơn hàng TG-04, mở dialog rồi bấm “Mở trang ứng tuyển”: trang mới có câu “Bạn đang ứng tuyển…” nhưng dropdown vị trí vẫn trống, khu làm việc chưa chọn.

Nguồn: `assets/js/apply-dialog.js:171–175` chờ `position/location`, trong khi nhánh mở từ link tại dòng 229 chỉ truyền `job/company`. `assets/js/ung-tuyen.js:95` lại yêu cầu tiêu đề khớp hoàn toàn. Khu sản xuất được nhập trên form chung cũng chưa được ghi vào payload (`note: ''`). Ô giới thiệu thực tế là `#apply-note`, nhưng nhánh submit tìm `#apply-message`, nên lời nhắn không được chuyển đúng sang queue.

Các quy tắc khác nhau: form chung bắt buộc địa chỉ và đồng ý liên hệ; trang độc lập không có cùng yêu cầu, lại thêm số/ảnh CCCD. Không mặc định coi trường nào phải bắt buộc: cần thống nhất theo nhu cầu HR.

Đề xuất: một schema/payload/validator và một cơ chế prefill ID cho cả ba bề mặt. Có thể giữ dialog và trang đầy đủ nếu hai nơi cùng dùng một biểu mẫu. Chỉ thu thông tin cần cho bước tiếp nhận; cân nhắc chuyển CCCD sang giai đoạn hoàn thiện hồ sơ nhận việc.

### F07 — P2: Trạng thái hết hạn không đồng nhất

Tại ngày 03/11/2026, TG-06 có hạn 31/10/2026. Public vẫn hiện “Sắp Hết Hạn” và “Ứng tuyển”; admin xác định `expired`. Trang chủ đếm cố định 7 vị trí, admin còn 6 tin đang tuyển; danh sách public gồm 8 tin và có chú thích gồm cả tin đóng.

Đề xuất: dùng cùng hàm tính trạng thái theo ngày Việt Nam và cùng nguồn đếm. Mặc định ưu tiên tin đang mở; tin hết hạn/đã đóng ghi rõ có nhận hồ sơ dự phòng hay không.

### F08 — P2: Kênh liên hệ và lời hứa cần hoàn thiện

Nhiều nút “Chat Zalo” vẫn dẫn `https://zalo.me`, chưa chỉ đích tuyển dụng. Một số nơi dùng `https://zalo.me/02733937274`, cần kiểm tra đây có thực sự là kênh tuyển dụng. Footer lặp cùng một hotline hai lần; có đoạn lặp tên/link TEX-GIANG do nội dung chưa được biên tập hết.

Biểu mẫu liên hệ và newsletter ghi là demo nhưng toast vẫn hứa HR gọi lại/gửi tin. Thời gian phản hồi ở các nơi khác nhau: 24 giờ và 24–48 giờ.

Đề xuất: xác nhận một nguồn hotline/email/Zalo/giờ tiếp nhận, bỏ mục trùng, dùng thông điệp phản hồi thống nhất. Không coi các số liệu/chính sách/chứng nhận trong fixture là thông tin đã xác minh.

## Nhận xét thiết kế và trải nghiệm

| Khu vực | Nhận xét qua ảnh hiện tại | Hướng cải thiện |
|---|---|---|
| Trang chủ | Chữ lớn, CTA rõ, màu thương hiệu nhất quán. Slogan chiếm phần lớn màn hình đầu; ảnh là poster có sẵn nhiều chữ, xuống mobile chữ trên ảnh rất nhỏ | Đưa “việc gì / thu nhập / ở đâu” lên sớm hơn; rút khoảng trống trước ảnh; dùng ảnh thật về chuyền may/con người làm điểm tựa |
| Danh sách việc | Job-row dễ quét trên desktop. Ở 390×844, phần giới thiệu + ảnh + điều khiển chiếm gần hết màn hình đầu, chưa đọc được một tin đầy đủ | Mobile dùng heading ngắn, số việc và tìm/lọc ngay sau đó; thu nhỏ hoặc chuyển ảnh giới thiệu xuống dưới danh sách |
| Chi tiết việc | Lương, số lượng và CTA đáy mobile tốt. Nhiều vị trí ngoài TG-01 chỉ có một câu mô tả, yêu cầu được ghi “trao đổi khi phỏng vấn” | Bổ sung công việc, yêu cầu, ca/giờ làm, thành phần lương, địa điểm đúng vị trí; tránh bắt ứng viên liên hệ chỉ để biết điều kiện cơ bản |
| Ứng tuyển | Dialog có footer gửi cố định, focus rõ, field đủ lớn; trang độc lập ít nhiễu nhưng khá dài | Giữ cảm giác gọn; thống nhất nội dung/nhãn/field, prefill đúng, giảm thông tin chưa cần ở bước đầu |
| About/phúc lợi | Nội dung có cấu trúc, bảng so sánh có thể đọc. Ảnh nhà máy lặp qua jobs/apply/contact/phúc lợi | Mỗi trang dùng hình có nhiệm vụ riêng: không gian làm việc, lớp đào tạo, bữa ăn, xe đưa rước. Dùng ảnh đúng chủ đề và xác nhận số liệu/chính sách |
| News | Bố cục 1 bài lớn + bài nhỏ có phân cấp; nhưng ảnh nhiều bài là các crop của cùng cổng nhà máy, có bài khác nhau lặp lại ngay trong phần tất cả | Thay bằng ảnh theo bài; không dùng ảnh cổng/bếp để minh chứng sự kiện/đào tạo không liên quan; cân nhắc bỏ lặp bài nổi bật trong danh sách đầu |
| Liên hệ | Khối thông tin, chỉ đường, tuyến xe và form là nền tảng phù hợp | Đưa địa chỉ cổng tiếp nhận, giờ làm và gọi/chỉ đường gần nhau; xác minh marker bản đồ, có link mở bản đồ độc lập |
| Thông báo | 404/success/error cùng ngôn ngữ thị giác, hành động quay lại rõ | Giảm phần ảnh trang trí trên mobile; ưu tiên trạng thái và hành động; sửa câu xác nhận theo trạng thái lưu thực tế |
| Admin desktop | Sidebar sáng, phân nhóm rõ, bảng sạch, dữ liệu KPI tính từ danh sách, trạng thái có nghĩa; nên giữ hệ thống này | Ưu tiên sửa liên kết dữ liệu. Tăng độ dễ đọc cho meta nhỏ; không thêm ảnh hoặc biểu đồ trang trí |
| Admin tablet/mobile | Bảng đã chuyển thành danh sách, không tràn ngang. Bộ lọc/tab chiếm nhiều chiều cao; select trạng thái bị cắt chữ. Ở chi tiết/sửa tin, hành động quan trọng xuống sau khối thông tin dài | Cho chọn nhanh bộ lọc thường dùng; control cần đủ chỗ cho giá trị; đưa bước xử lý/lưu thành thanh hành động phù hợp trên mobile |

### Logo mobile: chưa đúng yêu cầu 60×60 trong checkout hiện tại

Đo ở 390 px: public index/jobs/apply khoảng **61,66×40 px**; trang `ung-tuyen.html` khoảng **49,33×32 px**; logo topbar admin khoảng **30×30 px**. Chữ thương hiệu rời đã được bỏ ở mobile, nhưng kích thước chưa thống nhất theo yêu cầu trước đó.

Nguồn: `.site-logo` trong `assets/css/public.css:151`, `.gf-brand img` và quy tắc topbar admin. Nên dùng cùng khung logo 60×60, `object-fit: contain`, cùng khoảng đệm header; không kéo méo tỷ lệ logo.

### Khả năng tiếp cận và chi tiết

- Form chung có lỗi inline/`aria-invalid`; trang độc lập mới chỉ đổi class lỗi, chưa nối thông báo lỗi với field bằng `aria-describedby`/trạng thái tương ứng.
- Mục nhỏ trong admin có cỡ 11–12 px; ưu tiên tên người/vị trí/điện thoại đủ rõ, meta thấp hơn một bậc nhưng không quá nhỏ trên mobile.
- Trang tin đang dùng khoảng 10 cỡ chữ; nên gom thành nhóm tiêu đề/bài chính/bài phụ/body/meta rõ ràng hơn.
- Việc dialog không đóng bằng Escape đã có chủ ý trong mã và test hiện tại. Không tính đây là regression; nút đóng phải luôn nhìn thấy và dùng được bằng bàn phím.
- Không thấy ảnh local hỏng/overflow trong ma trận không đồng nghĩa đã đạt WCAG. Chưa chạy đánh giá screen reader, axe toàn diện hoặc bàn phím iOS/Android thật.

## SEO, nội dung và trạng thái triển khai

- `job-detail.html` đổi title theo ID nhưng meta description vẫn mô tả 300 công nhân may, kể cả khi đang xem QC/kế toán. Cần tạo metadata từ cùng tin, và bổ sung ảnh chia sẻ phù hợp khi có domain xuất bản.
- Tin tuyển dụng và bài viết vẫn chủ yếu là fixture; số lượng/thu nhập/hạn tuyển/chứng nhận/tuyến xe/thưởng cần HR duyệt trước khi công bố.
- Hiện vẫn là frontend demo/sessionStorage. Đăng nhập thật, lưu hồ sơ/CV trên máy chủ, quyền truy cập HR, thông báo tiếp nhận và xuất bản nội dung chưa được chứng minh hoặc triển khai trong lượt audit này. Đây là phần cần nối để vận hành, không đánh đồng với lỗi bố cục.
- Chưa kiểm chứng Lighthouse/Core Web Vitals production, SEO index, máy chủ thật, dịch vụ QR/bản đồ/Zalo hoặc thiết bị di động thật. Không có số điểm hiệu năng production được suy ra từ kết quả local.

## Critique thị giác theo skill

ROUND 1 — review, không sửa nguồn

Seen in one second: trang chủ là lời hứa công việc ổn định và poster cổng nhà máy; admin là danh sách hồ sơ cần xử lý.

Idea (one sentence): nơi làm việc TEX-GIANG dẫn ứng viên từ tìm hiểu nhà máy sang chọn vị trí và nộp hồ sơ.

Live thing: lọc việc, chọn vị trí, form ứng tuyển và các thao tác HR trong phiên demo.

Layout words by section: home hero=centered / jobs=cards / company=split / scenes=photo-list / jobs page=filter-list / admin=sidebar-table / apply=form.

Scores: không dùng một điểm chung để che lỗi vận hành. **Riêng trang chủ**: design 7 · usability 7 · creativity 6 · content 6 → arithmetic 6,7; cap house-style **6,5** do nhấn màu một phần headline và nhịp card quen thuộc. Admin dashboard: design 7,5 · usability 8 · creativity 6,5 · content 6,5 → **7,4** trong phạm vi prototype. Điểm chủ quan, không phải chứng nhận từ công cụ.

Caps applied: nếu giữ đúng rubric máy cho từng trang, các trang còn FAIL không vượt 6,0. Một số FAIL đòi bốn ảnh, tiêu đề cực lớn hoặc chuyển động ở trang lọc/form là không phù hợp mục tiêu thao tác; ghi nhận nguyên trạng để phân biệt với lỗi UX thật. Không đề xuất thêm ảnh/video hoặc animation vào form chỉ để đạt điểm công cụ. `off-centre` trên form độc lập cần xem cùng scrollbar/gutter, không kết luận ngay là lỗi căn lề 18 px.

Top three problems, with screenshot and fix:

1. `gates/index/mobile-fold.jpg`: nội dung chính là slogan, còn việc cụ thể/tìm kiếm nằm thấp. Đưa giá trị cụ thể về việc/lương/khu và lối tìm việc lên sớm.
2. `gates/news/desktop-page-01.jpg`: cổng nhà máy và vài ảnh lặp lại làm nhiều bài trông giống nhau. Dùng ảnh phù hợp từng bài và loại bớt trùng lặp.
3. `gates/jobs/mobile-fold.jpg`: phần dẫn nhập chiếm gần hết màn hình đầu. Thu gọn phần này để ứng viên thấy tin đầu tiên sớm.

## Thứ tự sửa đề xuất

1. **Độ đúng dữ liệu:** F01/F02/F05; giữ hồ sơ, đúng vị trí, không báo thành công khi lưu lỗi. Kiểm thử xuyên suốt public → queue → admin → reload.
2. **Điều hướng và trạng thái:** F03/F04/F06/F07; đúng tin/bài, cùng ngữ cảnh ứng tuyển, thống nhất hết hạn và số đang mở.
3. **Hoàn thiện giao diện:** logo mobile 60×60; thu gọn đầu trang jobs/mobile; ảnh theo nội dung; nâng khả năng đọc/admin mobile; thống nhất câu chữ/liên hệ.
4. **Chuẩn bị vận hành:** HR xác nhận nội dung, nối lưu hồ sơ/CV và đăng nhập/quyền HR; kiểm tra end-to-end trên môi trường triển khai rồi mới chốt dùng thật.

Tiêu chí chốt: ứng viên chọn đúng việc, mở đúng chi tiết, gửi đúng thông tin; lỗi lưu không mất chữ đã nhập; HR nhận đúng hồ sơ, reload vẫn giữ; tin hết hạn và bài không tồn tại được xử lý đúng; mobile nhận diện đúng và không phải cuộn dài chỉ để bắt đầu tìm việc.
