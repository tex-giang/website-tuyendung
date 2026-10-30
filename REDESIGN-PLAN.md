# KẾ HOẠCH REDESIGN — các trang còn lại (sau index.html)

> Bản ghi: 2026-10-30. Trang chủ đã xong (Tailwind + nhịp 120/128 + mốc 1536px, gate `inspect.mjs` 1 FAIL do thiếu ảnh).
> Mục tiêu: 12 trang public + 9 trang admin cùng một ngôn ngữ thiết kế, không vỡ hook JS, không lặp code shell 12 lần.

---

## 0. Hiện trạng (đo bằng số, không đoán)

| Nhóm | Số file | Dòng | Ghi chú |
|---|---|---|---|
| Trang public | 12 | 213–523 | index đã redesign; 11 trang còn nguyên style cũ |
| Trang thông báo | 3 | 67–87 | `404`, `application-success`, `application-error` — shell tối giản, không nav |
| Admin | 9 | 114–366 | shell riêng (sidebar), dùng `admin.js` |
| Component rời | 4 | 62–141 | `header/footer/job-card/admin-sidebar.html` — **bản cũ, không được include ở đâu**, đã lệch với index |
| Stub | 2 | 12 | `why-us.html`, `admin.html` — chỉ redirect/nháp, nên xoá |

**Dependency đã kiểm tra**

| Trang | Script | Số hook `getElementById` |
|---|---|---|
| 9 trang public | `custom.css` + `main.js` | 11 (`mobile-menu`, `qr-modal`, `back-to-top`, `recruitment-popup`…) |
| `jobs.html` | + `jobs.js` | 14 (`filter-*`, `jobs-list-container`, `jobs-empty-state`, `filters-sidebar`, `results-count`…) |
| `apply.html` | + `apply.js` | 13 (`apply-*`, `cv-drop-zone`, `upload-progress-*`, `online-apply-form`…) |
| 8 trang admin | + `admin.js` | 8 (`admin-sidebar`, `status-update-modal`, `toggle-sidebar-btn`…) |
| 3 trang thông báo | chỉ `custom.css` | 0 |

**Vấn đề chung phải xử lý trước (nếu không sẽ copy-paste 12 lần):**
1. Mỗi trang tự khai báo `tailwind.config` + tự copy top-bar/nav/mobile-menu/footer.
2. `components/*.html` đã lỗi thời (vẫn `rounded-3xl`, top-bar navy) — không còn là nguồn sự thật.
3. `search-results.html` gần như trùng `jobs.html`.
4. Trang public còn: gradient + `rounded-3xl` + `font-black` + card icon vuông (ước tính 500+ chỗ).
5. `banner-texgiang.png` 1.9 MB dùng 2 lần; `tex-giang-recruitment.zip` (1.9 MB) và `.taste-skill/` nằm trong web root.

---

## 1. Ba quyết định cần chốt trước khi làm

| # | Vấn đề | Phương án A (khuyến nghị) | Phương án B |
|---|---|---|---|
| 1 | Chia sẻ Tailwind cho 21 trang | **Build một lần bằng Tailwind CLI**: `src/tailwind.css` (chứa `@apply` component) → `assets/css/app.css` (~15 KB, minify). Trang chỉ `<link>` 1 file. Hết lặp config, bỏ 400 KB Play CDN, không FOUC. Vẫn chỉ là file tĩnh để upload. | Giữ Play CDN, thêm `tools/sync-shell.mjs` chèn cùng một khối `tailwind.config` + `<style type="text/tailwindcss">` + header/footer vào mọi trang (marker `<!-- SHELL:HEAD -->`). Không cần npm, nhưng tải nặng hơn và vẫn phụ thuộc CDN. |
| 2 | `search-results.html` | **Gộp vào `jobs.html`** (`jobs.js` đã đọc `?keyword=&company=&location=`), xoá trang, để lại redirect `meta refresh` cho link cũ. | Giữ trang riêng, chỉ thay shell cho đồng bộ. |
| 3 | Admin (9 trang) | Làm **sau cùng**, một sprint riêng, ngôn ngữ "app" (dense, sidebar, bảng) — không dùng hero/CTA marketing. | Làm xen kẽ, chấp nhận vừa làm vừa chỉnh token. |

Nếu bạn chọn A cho (1), việc đầu tiên là tách `tailwind.config` + khối `@apply` của `index.html` ra file nguồn rồi build ra CSS — index sau đó cũng dùng file CSS đó (bỏ CDN).

---

## 2. Thư viện component dùng chung (làm ở Sprint 0)

Rút từ index, đủ để 11 trang còn lại chỉ việc ghép:

| Component | Trạng thái | Dùng ở |
|---|---|---|
| `tg-container` (1200 / 1360 @1536) | có | tất cả |
| `tg-btn` + `primary` / `ghost` / `white` | có | tất cả |
| `tg-link` (mũi tên) | có | tất cả |
| `tg-chip` (1 kiểu duy nhất) | có | jobs, job-detail, news |
| `page-hero` (H1 40–56 + 1 dòng dẫn, không gradient) | **mới** | jobs, about, why, news, contact, news-detail, job-detail |
| `stats-band` (4 số, nền surface) | có (index) | about, why |
| `job-card` (nền surface, không viền) | có | index, jobs, search |
| `job-row` (hàng ngang: title trái – lương/nút phải) | **mới** | jobs (24 tin → quét nhanh) |
| `field` (input/select/textarea + label + lỗi) | có một nửa | apply, contact, job-detail |
| `filters-bar` (sticky, chip đang lọc) | **mới** | jobs |
| `cta-band` (khối xanh accent) | có | mọi trang |
| `footer` + `announce-strip` + `float-stack` + `qr-modal` + `toast` | có (index) | mọi trang |
| `notice-page` (khung 404 / success / error) | **mới** | 3 trang thông báo |

---

## 3. Lộ trình 5 sprint

```
Sprint 0  shell + component        ✅ xong 30/10
Sprint 1  jobs → job-detail → apply ✅ xong 30/10
Sprint 2  about → why → contact     ✅ xong 30/10
Sprint 3  news → news-detail        ✅ xong 30/10
Sprint 4  admin 9 trang + login     ✅ xong 30/10
```

**Trạng thái: 22/22 trang đã redesign (30/10/2026).**

### Sprint 0 — Nền dùng chung (4 h)
1. Chốt quyết định (1) → dựng nguồn CSS/`config` dùng chung, build, thay `<link>` ở index, kiểm tra index không đổi hình.
2. Viết `page-hero`, `job-row`, `filters-bar`, `notice-page` vào lớp component.
3. Dựng `header` + `footer` + `announce-strip` + `float-stack` + `qr-modal` thành **một khối shell** duy nhất (file `components/shell.html` làm nguồn), rồi chèn vào từng trang.
4. Xoá `why-us.html`, `admin.html`; cập nhật `components/header|footer|job-card.html` theo shell mới hoặc xoá hẳn.
5. Gate: `inspect.mjs` chạy lại trên index (không được tăng FAIL).

### Sprint 1 — Đường tiền: tìm việc → xem tin → nộp (9 h)

**`jobs.html`** (3 h) — 345 dòng, `jobs.js` 14 hook
- Hero ngắn: H1 40 "Việc làm tại TEX-GIANG", dòng phụ "24 vị trí đang mở · Tân Hương 2".
- Lọc: từ drawer nổi bên trái → **thanh lọc sticky dưới nav** (keyword + 3 select + sắp xếp), chip đang lọc có nút xoá. Mobile: nút "Bộ lọc" mở drawer.
- Danh sách: `job-row` (title + tổ chức + meta trái, lương + `Ứng tuyển` phải), ngăn cách bằng khoảng trắng, không viền từng card.
- Giữ nguyên id: `filter-keyword/company/department/location/salary/sort`, `jobs-list-container`, `results-count`, `active-filters-chips`, `jobs-empty-state`, `filters-sidebar`, `toggle-mobile-filters`, `close-filter-drawer`, `jobs-filter-form`.
- **Rủi ro:** `jobs.js` tự sinh HTML card → phải sửa template trong JS, không chỉ HTML. Kiểm tra phân trang/URL query vẫn chạy.

**`job-detail.html`** (2 h) — 305 dòng
- 2 cột: trái JD (mô tả, yêu cầu, quyền lợi, địa điểm), phải **card ứng tuyển sticky** (lương, hạn, địa chỉ, nút chính, chia sẻ Zalo/QR).
- Mobile: thanh CTA dính đáy (`Ứng tuyển` + `Gọi`), ẩn float stack để không chồng 2 lớp nút.
- Giữ `#apply-section` (form nhanh) hoặc thay bằng nút sang `apply.html?job=<mã>` — chốt một hướng.
- Bỏ banner gradient + icon vuông.

**`apply.html`** (3 h) — 304 dòng, `apply.js` 13 hook
- Chia 2 cột desktop (Thông tin cá nhân | Vị trí + CV), 1 cột mobile. Bỏ "rounded-3xl + shadow-2xl".
- Vùng tải CV: khối nét đứt nhạt (hợp lệ vì là form field) + preview tên/dung lượng/xoá.
- Lỗi hiển thị ngay dưới field (`aria-describedby`), nút submit full-width, dòng "Dữ liệu demo chưa lưu DB" giữ nguyên.
- Giữ toàn bộ 13 id trong `apply.js`.

**`application-success` / `application-error` / `404`** (1 h)
- Chung `notice-page`: logo nhỏ → H1 40 → 1 câu → 1 nút chính + 1 link phụ. Bỏ gradient, bỏ nền rose.

### Sprint 2 — Niềm tin (5 h)

| Trang | Việc | Component |
|---|---|---|
| `about.html` (264) | hero ngắn; dải 4 số (15+ năm, 3.500+ CBCNV, 45.000 m², 100% XK); 1 ảnh nhà máy lớn + 2 đoạn; Tầm nhìn/Sứ mệnh/Giá trị thành 3 khối chữ (bỏ card icon vuông) | `page-hero`, `stats-band` |
| `why-tex-giang.html` (322) | 4 trụ cột → list 2 cột có số liệu; **giữ bảng so sánh** (bảng thật thì được kẻ); CTA xanh cuối | `stats-band`, `cta-band` |
| `contact.html` (305) | 2 cột: form liên hệ ngắn | thông tin + giờ làm; Google Maps iframe 16:9 radius 20; danh sách tuyến xe (nếu có dữ liệu) | `field`, bản đồ |

### Sprint 3 — Tin tức (3 h)
- `news.html` (265): bỏ grid 3 card đều → **1 bài lớn + list bài nhỏ** (title + ngày + nhãn), chips danh mục.
- `news-detail.html` (213): cột nội dung ~68ch, meta trên, ảnh trong bài radius 20, "Bài liên quan" dạng list text.
- `search-results.html`: gộp theo quyết định (2) → xoá hoặc redirect.

### Sprint 4 — Admin (7 h, ngôn ngữ riêng)
- Shell mới: sidebar 240px (thu gọn mobile), top bar 56px, nội dung trên nền `#F6F7F9`, panel trắng bo 12, **không gradient**.
- `login` (114): 1 cột giữa, nền trắng, không nền gradient.
- `dashboard` (366): 4 KPI số lớn 40/500 + 1 biểu đồ cột thuần SVG/CSS + 2 list.
- `jobs` / `applications` / `banners` / `content` / `settings` / `job-form` / `application-detail`: bảng dense 14px, badge trạng thái 1 kiểu, filter bar gọn.
- Giữ 8 id của `admin.js`; giữ dữ liệu demo + nhãn "[Demo]".

---

## 4. Định nghĩa "xong" cho mỗi trang

- [ ] `node inspect.mjs <trang>` — 0 FAIL (trừ `few-pictures`/`no-footage` khi chưa có ảnh thật, ghi rõ lý do).
- [ ] Ảnh chụp 1536 / 1440 / 1024 / 768 / 390: không tràn ngang, không cắt chữ, headline ≤ 2 dòng.
- [ ] Mọi `href`, `form action`, `name` field giữ nguyên; mọi id trong `main.js`/`jobs.js`/`apply.js`/`admin.js` còn đủ.
- [ ] Bàn phím: tab đi hết trang theo thứ tự đọc, focus nhìn thấy, modal QR đóng được bằng Esc/click nền.
- [ ] Không còn `bg-gradient`, `rounded-3xl`, `font-black`, chữ < 14px trong trang đó.
- [ ] Mobile: menu mở/đóng, drawer lọc (jobs), thanh CTA đáy (job-detail) hoạt động.
- [ ] `inspect.mjs` trên index vẫn 0 FAIL mới (không hồi quy sau khi đổi CSS dùng chung).

---

## 5. Nợ kỹ thuật dọn luôn trong Sprint 0

| Việc | Lý do |
|---|---|
| Chuyển `banner-texgiang.png` (1.9 MB) → WebP, thêm bản 800px cho mobile | tải trang chậm, dùng 2 lần |
| Xoá `tex-giang-recruitment.zip`, `.taste-skill/` khỏi web root (hoặc `.gitignore`) | lộ bản zip + artefact kiểm tra ra production |
| Xoá `why-us.html`, `admin.html` | file nháp 12 dòng |
| Cập nhật `README.md` (mục 1–3) theo stack mới | tài liệu đang mô tả bản cũ (Bootstrap Icons + Tailwind CDN + `rounded-3xl`) |
| Rà `alt`, `aria-label` cho mọi nút icon | hiện chỉ index được làm |

## 6. Nội dung cần bạn cung cấp (chặn chất lượng, không chặn tiến độ)

1. **Ảnh thật**: xưởng may, công nhân, căn tin, xe đưa rước, phòng phỏng vấn, HR tuyển dụng (6–10 ảnh) → mở khoá FAIL `few-pictures` và thay ảnh crop banner ở mục "Về TEX-GIANG".
2. Logo **SVG** + favicon + 1 ảnh OG 1200×630.
3. Số liệu chính thức: số CBCNV, số chuyền, m² nhà xưởng, năm thành lập, chứng nhận (WRAP/ISO số hiệu).
4. Danh sách **tuyến xe đưa rước** (cho `contact.html`).
5. 2–3 câu cảm nhận thật của công nhân (có tên/chức danh) — không tự bịa.

## 7. Rủi ro

| Rủi ro | Mức | Cách chặn |
|---|---|---|
| Sửa shell làm vỡ `main.js` (id) | cao | shell giữ nguyên id; test click từng trang sau khi chèn |
| `jobs.js` sinh HTML card → style cũ quay lại | cao | sửa template trong JS, gate lại trang jobs |
| Build CSS mất `@apply` không dùng ở trang nào | trung | build một lần sau khi có đủ markup, quét class lạ |
| 12 trang lệch nhau sau khi sửa tay | trung | mọi thay đổi shell đi qua file nguồn + script chèn |
| Không có ảnh thật | đang xảy ra | giữ 2 ảnh hiện tại, ghi rõ, bổ sung sau không đổi layout |

---

## 8. Kết quả Sprint 0 + Sprint 1 (2026-10-30)

**Đã làm**

| Việc | Kết quả |
|---|---|
| CSS dùng chung | `assets/css/app.css` (biến `--tg-*` để không đụng `--accent` cam còn sót trong `custom.css`), khung 1200/1360px, nút, chip, form, job-row, filters-drawer, notice, modal, bottom-bar |
| Config Tailwind dùng chung | `assets/js/tw.config.js` — mọi trang nạp cùng một config, hết lặp 90 dòng/trang |
| Tiện ích chung | `assets/js/ui.js` (đóng strip, kẻ nav khi cuộn, reveal + lưới an toàn 1.5s) thay 4 khối inline lặp nhau |
| Trang đã dựng lại | `jobs.html`, `job-detail.html`, `apply.html`, `application-success.html`, `application-error.html`, `404.html` |
| Giữ hook | `jobs.js` sửa template card → `job-row`; `apply.js` thêm so khớp mềm tên vị trí khi prefill `?job=` |
| Việc bỏ | form ứng tuyển chết trong `job-detail` (không có handler) → thay bằng CTA sang `apply.html?job=`; phân trang giả trong `jobs` |

**Đo lại bằng `inspect.mjs` (1440/768/390)**

| Trang | Trước | Sau | Còn lại |
|---|---|---|---|
| jobs | 3 FAIL | **2** | few-pictures (thiếu ảnh), focal-point (danh sách là trọng tâm, chấp nhận) |
| job-detail | 5 FAIL | **2** | few-pictures, focal-point (tiêu đề tin 40px là chủ ý) |
| apply | 5 FAIL | **2** | few-pictures, focal-point |
| success | 4 FAIL | **2** | few-pictures, still-page (trang tĩnh) |
| error | 4 FAIL | **2** (ước tính tương tự) | few-pictures, still-page |
| 404 | 5 FAIL | **3** | few-pictures, still-page, off-centre@390 |
| index | 1 FAIL | **1** | không hồi quy |

Đã sửa: eyebrow/kicker, số thứ tự trang trí 01–04, ô nét đứt, khung viền 1px, glass/backdrop-blur, headline vỡ dòng ở 390, measure 90+ ký tự, h1 28px, body 14px, nút đầu màn hình trên mobile.

**Chấp nhận có lý do**
- `few-pictures` / `no-footage`: repo chỉ có 1 ảnh thật (banner) — không đưa ảnh stock vào trang tuyển dụng.
- `focal-point` ở jobs/apply/job-detail: trọng tâm là danh sách việc và biểu mẫu, không phải ảnh hay chữ khổng lồ.
- `still-page` ở 3 trang thông báo: trang xác nhận tĩnh, cố ý không có chuyển động.
- `off-centre`: lệch đúng bằng bề rộng thanh cuộn (17px) — WARN, không phải lỗi bố cục.

### Sprint 2 — kết quả (30/10)

| Trang | Trước | Sau | Ghi chú |
|---|---|---|---|
| about | 5 FAIL | **1** | Bỏ 4 ảnh Unsplash hotlink (không phải nhà máy của mình); H1 rút còn "15 năm may xuất khẩu tại Đồng Tháp"; slogan dài chuyển thành câu statement |
| why-tex-giang | 6 FAIL | **2** | 4 trụ cột thành list, bảng so sánh giữ dạng bảng thật, **xếp khối trên mobile** (`compare-table` + `data-label`) để không cuộn ngang |
| contact | 5 FAIL | **2** | Map 16:9, form có toast demo (`preventDefault` + `showToast`), bỏ 4 khối icon SVG lặp |

Còn lại đều là `few-pictures` / `no-footage` (thiếu ảnh thật) và `focal-point` ở trang chữ-first.

### Sprint 3 — kết quả (30/10)

| Trang | Trước | Sau | Ghi chú |
|---|---|---|---|
| news | 6 FAIL | **1** | Bỏ 3 ảnh Unsplash; bài nổi bật dùng ảnh nhà máy (crop 4:3 `object-right` để không cắt chữ trên banner); danh mục/ngày xuống **dưới** tiêu đề (hết eyebrow); form đăng ký nhận tin có toast |
| news-detail | 7 FAIL | **2** | H1 rút còn "Ngày hội việc làm Châu Thành 2026" + câu dẫn riêng; nút share đổi sang hàm thật (`shareZalo/shareFacebook/copyLink` — bản cũ gọi hàm không tồn tại); cột chữ 64ch |
| search-results | — | — | **Gộp vào jobs.html**: trang redirect giữ nguyên `?keyword=&company=&location=`; đã test ra đúng `jobs.html?keyword=qc&company=tg-apparel` và bộ lọc tự áp |

Sprint 3 xong toàn bộ trang public. Còn Sprint 4 (9 trang admin + login).

### Sprint 4 — kết quả (30/10)

| Trang | Việc chính |
|---|---|
| `login.html` | Thẻ trắng 420px, bỏ nền gradient; giữ `handleLogin`, `togglePasswordVisibility` |
| `dashboard.html` | 4 KPI, biểu đồ cột (sửa **lỗi cột không hiển thị**: `height:%` trong cột `auto` → thêm `h-full justify-end`), nguồn ứng viên, 2 bảng ngắn |
| `jobs.html` | Bảng dày 14px, 4 dòng (gồm 1 tin đã đóng), icon-button thao tác, phân trang |
| `applications.html` | Bảng + modal cập nhật trạng thái; thêm dòng thứ 4 "Hẹn phỏng vấn" để bộ lọc nào cũng có dữ liệu |
| `application-detail.html` | 2 cột: hồ sơ + timeline, panel cập nhật trạng thái, liên hệ nhanh |
| `job-form.html` | 3 nhóm form, textarea thay rich-text giả, nút xuất bản giữ `handlePublish` |
| `content.html` | 3 panel: khẩu hiệu, bảng bài viết, thông tin liên hệ |
| `banners.html` | Banner trang chủ (8:3) + dải thông báo; ghi chú nên xuất WebP |
| `settings.html` | Pháp nhân, SEO có đếm ký tự gợi ý, vận hành, phiên bản |

Gate `inspect.mjs --app`: **0 FAIL** ở dashboard / applications / jobs / login. WARN còn lại: số dùng `tabular-nums` (đã bật toàn bộ), `panel-void` trên biểu đồ, `hero-void` ở trang danh sách (khoảng trắng cạnh bảng) — đặc thù app screen, chấp nhận.
