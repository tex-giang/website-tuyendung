# Audit và hoàn thiện giao diện tuyển dụng — 30/10/2026

Phạm vi: 12 trang public/thông báo trong danh sách yêu cầu, cùng đường dẫn cũ `search-results.html`. Tiếp tục trên working tree đang có thay đổi; không commit, không deploy. Các file admin có thay đổi từ trước lượt audit này; không redesign thêm admin.

## Hướng thiết kế

Giữ nhận diện xanh TEX-GIANG, font Be Vietnam Pro, khung 1200/1360 px và cấu trúc nội dung đang có. Dùng ảnh nhà máy làm điểm tựa cho phần giới thiệu; danh sách việc và biểu mẫu là trọng tâm ở các trang thao tác.

Direction tham khảo từ design-taste-frontend: **The split stage**, seed **1207904907**, giữ palette/type của thương hiệu. Áp dụng bố cục chữ–ảnh ở phần giới thiệu; không giữ ảnh toàn chiều cao suốt trang vì yêu cầu bộ lọc ngang, job-row và form hai cột của người dùng cần diện tích thao tác. Không dùng bộ ảnh cây cối trong direction vì không đúng chủ đề.

Ý tưởng: hình ảnh nhà máy Tân Hương và con người TG APPAREL trở thành phần dẫn đường để ứng viên chuyển từ tìm hiểu nơi làm việc sang chọn vị trí và nộp hồ sơ. Tương tác chính là lọc việc thật trên dữ liệu demo và chuyển đúng vị trí/công ty sang biểu mẫu.

## Phát hiện và phần đã sửa

| Mức | Phát hiện trước audit | Kết quả |
|---|---|---|
| Cao | Bộ lọc vẫn nằm ở cột trái, trái với yêu cầu thanh ngang | Thanh lọc sticky dưới nav trên desktop; drawer có overlay trên mobile |
| Cao | `?department=` ở các link footer chưa được đọc | Đọc đủ các bộ lọc hợp lệ, đồng bộ query khi lọc/xoá/sắp xếp |
| Cao | Từ khoá người dùng được chèn vào HTML của chip | Dùng `textContent`, tạo nút xoá bằng DOM, không thực thi markup |
| Cao | Các mã tin đều mở cùng nội dung công nhân may | Dùng chung dữ liệu demo cho danh sách/chi tiết; cập nhật tiêu đề, mô tả, lương, hạn và link ứng tuyển theo mã tin |
| Cao | Form thiếu lỗi inline, chưa kiểm tra địa chỉ, số điện thoại dùng regex quá rộng | Thêm lỗi từng field, summary, focus lỗi đầu tiên, kiểm tra phone/email/địa chỉ/năm sinh/đồng ý liên hệ |
| Vừa | Thông báo upload thành công dù chỉ là tệp local | Hiển thị “Đã chọn trên máy”, kiểm tra định dạng/dung lượng, gỡ tệp và bỏ qua CV không hợp lệ; không gửi dữ liệu ra ngoài |
| Vừa | Thiếu Escape và quản lý focus ở QR/filter drawer | Escape, trap focus ở dialog, trả focus về nút mở, khoá cuộn nền |
| Vừa | Phụ thuộc Tailwind Play CDN và icon font bên ngoài | CSS utility build sẵn khoảng 25 KB, 22 icon SVG local dùng qua `<img>` |
| Vừa | Banner PNG khoảng 1,9 MB, lặp ảnh và thiếu ngữ cảnh | WebP khoảng 226 KB, biến thể 800 px và `srcset`; bổ sung 5 ảnh doanh nghiệp có ghi nguồn |
| Vừa | Thông báo lỗi khẳng định dữ liệu còn lưu dù chưa có cơ chế lưu | Bỏ khẳng định sai; trang thành công nêu rõ hoàn tất gửi hồ sơ mẫu |
| Nhẹ | Hotline hiển thị chưa đồng nhất, số 24 vị trí không khớp dữ liệu | Đồng nhất cách hiển thị hotline hiện có; trang chủ dùng 7 vị trí đang mở trong bộ 8 tin demo |

## Thay đổi theo trang

| Trang | Hoàn thiện |
|---|---|
| `index.html` | Banner responsive WebP, icon local, thêm dải ảnh hoạt động cộng đồng/điện mặt trời, số vị trí khớp bộ demo |
| `jobs.html` | Bộ lọc ngang sticky, lọc trực tiếp trên desktop, drawer mobile, job-row thoáng, empty state, nhãn dữ liệu demo |
| `job-detail.html` | Đúng tin theo `id`, giữ hai cột/card sticky/CTA mobile; ứng tuyển chuyển đúng công ty và vị trí |
| `apply.html` | Vị trí ở hàng đầu; thông tin cá nhân và CV/nguyện vọng ở hai cột; mobile một cột; lỗi inline, dropzone bàn phím, xem/gỡ tệp |
| `application-success.html` | Minh hoạ SVG hồ sơ đã hoàn tất, bố cục thông báo chung, nêu rõ luồng demo |
| `application-error.html` | Minh hoạ SVG cảnh báo tệp, hướng dẫn thử lại, không hứa giữ dữ liệu chưa được lưu |
| `404.html` | Minh hoạ thư mục/tìm kiếm, CTA rõ về danh sách việc và nộp hồ sơ |
| `about.html` | Ảnh banner tối ưu, thêm ảnh Tân Hương 1 và dải ảnh cộng đồng/phát triển bền vững; giữ stats và nội dung doanh nghiệp |
| `why-tex-giang.html` | Tiêu đề nổi bật hơn, ảnh nhà máy, icon đi cùng tiêu đề phúc lợi, giữ bảng so sánh và CTA xanh |
| `contact.html` | Chữ–ảnh giới thiệu, icon thông tin liên hệ, giữ map 16:9, form và tuyến xe |
| `news.html` | Giữ bài lớn + danh sách; thêm thumbnail cho bài phụ; alt không mô tả ảnh nhà máy như ảnh chụp sự kiện |
| `news-detail.html` | Giữ cột đọc khoảng 64–68ch, thêm ảnh minh hoạ các công đoạn làm việc và chú thích |
| `search-results.html` | Giữ redirect sang jobs, xác minh bảo toàn giá trị query, kể cả khi thứ tự tham số được chuẩn hoá |

Giữ các ID tích hợp của `jobs.js` và `apply.js`, cùng các ID menu/QR/toast đang dùng. Các icon trang public dùng `alt=""` và `aria-hidden="true"` khi chỉ trang trí; tên nút vẫn là chữ hoặc `aria-label`.

## Ảnh và icon

Nguồn ảnh doanh nghiệp: [TG APPAREL](https://tg-apparel.vn/), tải về local, chuyển WebP, không hotlink ảnh. File nguồn chi tiết, kích thước và dung lượng nằm tại [image-sources.json](image-sources.json).

| File local | Nội dung nguồn | Vị trí sử dụng |
|---|---|---|
| `company/tan-huong-2.webp` | FACTORY TAN HUONG 2 | Giới thiệu jobs/apply/contact/phúc lợi |
| `company/tan-huong-1.webp` | FACTORY TAN HUONG 1 | Giới thiệu doanh nghiệp |
| `company/social.webp` | SOCIAL | Dải ảnh index/about/phúc lợi và thumbnail tin |
| `company/environment.webp` | ENVIRONMENTAL | Dải ảnh index/about/phúc lợi |
| `company/workshop.webp` | ABOUT US | Thumbnail cẩm nang và ảnh minh hoạ bài viết |

Nguồn không nêu tên nhiếp ảnh gia hoặc giấy phép mở. Quyền ảnh thuộc chủ sở hữu nguồn; cần xác nhận quyền xuất bản với doanh nghiệp khi đưa bản thiết kế lên website chính thức. Ghi nguồn TG APPAREL ở footer các trang dùng ảnh và chú thích ảnh trong bài. Không dùng ảnh để làm bằng chứng cho sự kiện demo.

`banner-texgiang.png` và logo là tài sản đã có trong dự án. Giữ bản PNG gốc; các trang public dùng bản WebP. Ba minh hoạ thông báo là SVG vẽ riêng bằng code. Icon Bootstrap Icons 1.11.3: giấy phép MIT giữ tại `assets/images/icons/LICENSE.txt`.

## Kiểm tra

Kết quả cuối: **60/60 lượt render và 8/8 nhóm kiểm tra tương tác đạt**, không tràn ngang, ảnh hỏng, ID trùng hoặc lỗi JavaScript runtime trong ma trận. Đã xem các ảnh desktop/tablet/mobile của cả 12 trang. Gate thiết kế trả **0 FAIL cho 12/12 trang sau khi áp dụng các ngoại lệ công khai bên dưới**; còn 7 WARN thuộc phân bố mép chữ, scrollbar và vùng dropzone.

Xem [ảnh xem trước desktop/mobile](public-ui-preview.jpg). Kết quả máy chi tiết: `.taste-skill/public-audit/report.json`; các ảnh kiểm tra là artefact local, đã được thêm vào `.gitignore`.

Chạy `npm run build:css` để build utility CSS, `node --check` cho JavaScript đã sửa. `npm run audit:public` kiểm tra bằng Microsoft Edge headless, lưu ảnh và báo cáo tại `.taste-skill/public-audit/`.

Ma trận gồm 12 trang × 5 kích thước **1536, 1440, 1024, 768, 390 px**: kiểm tra tràn ngang, chữ vượt khung, một H1/trang, H1 tối đa hai dòng, ảnh hỏng, ID trùng và lỗi JavaScript runtime. Các tương tác được kiểm tra riêng: lọc/query/reset/sort/empty state; chuỗi nhập giống HTML không thực thi; drawer/menu/Escape; QR/focus; đúng vị trí/công ty sang apply; lỗi form/CV/gỡ tệp/gửi demo; redirect cũ; file URL và reduced motion.

Gate thiết kế `inspect.mjs` chạy ở 1440/768/390, ảnh và log tại `.taste-skill/final-<tên-trang>/`. Các ngoại lệ được khai báo rõ khi chạy:

- `few-pictures`: trang tìm việc, biểu mẫu, thông báo, liên hệ hoặc bài viết không cần ép đủ bốn ảnh. Index/about có đủ ảnh để không cần ngoại lệ này.
- `focal-point`: các trang thao tác/nội dung như jobs/apply/job-detail/contact/news-detail dùng tiêu đề vừa phải để giữ diện tích đọc và thao tác.
- `still-page`, `no-footage`: không có video quy trình phù hợp được cấp quyền; giữ chuyển động nhẹ sẵn có, hover/press và reduced motion, không thêm chuyển động liên tục vào form/thông báo.
- Warning dropzone nét đứt là chủ ý vì đây là vùng chọn tệp thật. Một số cảnh báo lệch 17–23 px ở desktop đến từ cách đo scrollbar/nội dung chữ không đầy dòng; kiểm tra ảnh và ma trận overflow riêng.

Lint theo house-style vẫn đánh dấu các đường phân cách nav, viền control và câu có dấu gạch dài trong nội dung cũ. Đây không phải chứng nhận “mọi quy tắc house-style đều sạch”; giữ các quy ước giao diện hiện có khi chúng phục vụ điều hướng hoặc biểu mẫu. Không đánh đồng số FAIL thẩm mỹ được miễn với kiểm tra lỗi chạy.

## Nhận xét qua ảnh

ROUND 1

Seen in one second: danh sách việc và form đã rõ, nhưng một selector ảnh của dải story làm icon mũi tên phóng lớn trên mobile.

Idea: nhà máy và con người là bối cảnh; chọn việc và ứng tuyển là hành động chính.

Live thing: bộ lọc việc làm và chọn CV local.

Layout words by section: intro=split / jobs=list / form=two-column / company=photo-story / footer=columns.

Top three problems: (1) mũi tên trong story phóng lớn, sửa selector chỉ áp dụng ảnh trong figure; (2) header jobs/apply chiếm nhiều chiều cao mobile, giảm ảnh xuống 130 px; (3) icon cặp từng job-row gây lặp, bỏ icon ở từng hàng, giữ icon ở control và thông tin chức năng.

ROUND 2

Seen in one second: tên trang, ảnh nhà máy và hành động ứng tuyển; danh sách việc thoáng hơn, mobile có bố cục riêng.

Idea: giữ đặc trưng Tân Hương trong ảnh, đưa ứng viên đến đúng vị trí và biểu mẫu.

Live thing: lọc, lưu trạng thái trong URL và chuyển đúng vị trí/công ty sang form.

Layout words by section: intro=split / jobs=list / company=editorial-photos / benefits=two-column-list / article=reading-column / notice=illustration-split.

Scores (nhận xét thiết kế, không phải điểm công cụ): design 7.0 · usability 8.0 · creativity 6.5 · content 6.5 → overall 7.2.

Caps applied: nội dung mẫu và ảnh nguồn có độ phân giải giới hạn khiến chất lượng nội dung chưa thể coi là bản xuất bản cuối. Trang form/list/notice được đánh giá theo mục tiêu thao tác thay vì ép theo mật độ ảnh của landing page.

Top three remaining problems: (1) ảnh nhà máy gốc 360×300 cần ảnh độ phân giải cao hơn để sắc nét trên màn lớn; (2) nhiều số liệu/chính sách/tin vẫn là nội dung mẫu; (3) Google Fonts, QR và bản đồ còn dùng dịch vụ bên ngoài.

## Nội dung còn cần xác nhận trước xuất bản

Ứng tuyển, liên hệ và đăng ký nhận tin vẫn là demo, chưa lưu hồ sơ hoặc gửi cho HR. Không có backend được triển khai trong phạm vi audit giao diện.

HR cần xác nhận hotline/email/Zalo, địa chỉ/marker bản đồ, ba tuyến xe và giờ đón, số lao động/diện tích/năm thành lập/chứng nhận, mức lương/thưởng/đào tạo/bảo hiểm, hạn tuyển và bài sự kiện. Link Zalo hiện vẫn là link chung có sẵn; chưa có tài khoản tuyển dụng đã xác nhận. Không dùng các số liệu trong giao diện như bằng chứng đã được kiểm chứng.

## Chạy lại

```powershell
npm ci
npm run build:css
python -m http.server 4173 --bind 127.0.0.1
```

Ở terminal thứ hai trong cùng thư mục:

```powershell
npm run audit:public
```

Trang public là HTML/CSS/JS tĩnh. Khi xuất bản chỉ cần file trang và assets; không đưa `.git`, `.taste-skill`, `node_modules`, zip nguồn hoặc công cụ audit lên web server.
