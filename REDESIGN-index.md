# REDESIGN `index.html` — hướng tối giản kiểu zalo.me

> Trạng thái: **đã dựng bản mới** (2026-10-30). Tài liệu này giữ lại làm blueprint cho 19 trang còn lại.
> Quyết định áp dụng: accent `#087A55`, Be Vietnam Pro weight 500, strip thay pop-up.
> Tham chiếu: https://zalo.me/vi/ (đo bằng DevTools @1440×900, 2026-10-30)
> File liên quan: `index.html`, `assets/css/custom.css`, `assets/js/main.js`

---

## 0. Một câu chốt hướng đi

Bỏ "trang trí" để lộ "nội dung": **1 màu nhấn duy nhất, chữ weight 500 thay vì 900, card viền 1px không bóng đổ, nền chỉ có trắng + #F6F7F9, khoảng thở 96px giữa các khối.**

Trang hiện tại không thiếu thông tin — nó thiếu **thứ tự ưu tiên**. Mỗi khối đều đang hét to nhất có thể (gradient, bóng đổ, badge đỏ, chữ in hoa, icon nhiều màu, popup tự bật), nên mắt người lao động không biết bấm vào đâu.

---

## 1. Zalo làm gì để "đơn giản" (số đo thật)

| Thuộc tính | zalo.me/vi | Ghi chú |
|---|---|---|
| Font | `Gilroy` | weight **400 / 500** — không hề dùng 700–900 |
| H1 hero | 48px / **500** / line-height 1.15 | 2 dòng, canh giữa |
| H2 khối | 40px / 500 / 1.15 | canh giữa + dòng phụ 16px #666 |
| Lead | 20px / 400 | tối đa 2 dòng |
| Số liệu lớn | 64px / **500** / màu nhấn | "2.2B+", "81.3M+" |
| Màu nhấn | `#0068FF` — **duy nhất 1 màu** | chỉ dùng cho số, chữ nhấn, link |
| Nền | `#FFFFFF` + `#F6F7F9` | đúng 2 nền, không gradient |
| Chữ | `#292929` (chính) / `#666` (phụ) | không dùng màu thương hiệu cho body |
| Card | nền trắng, viền `1px #EAEAEA`, **không shadow**, radius nhỏ | không "nổi" bằng bóng, nổi bằng khoảng trắng |
| Container | 1232px nội dung | lề ngoài rất rộng |
| Nhịp dọc | ~96–120px mỗi khối | gấp ~2 lần trang hiện tại |
| Nav | 1 dòng, link chữ thường, **không pill, không nền, không icon** | chỉ logo + 5 link + icon ngôn ngữ |
| Footer | nền xám nhạt `#F6F7F9`, cột link chữ | bỏ hẳn footer navy |

**Bốn đòn quyết định:** (1) một màu nhấn, (2) chữ vừa (500) chứ không đậm, (3) card chỉ có đường kẻ, (4) khoảng trắng là thành phần thiết kế chứ không phải chỗ trống.

---

## 2. Chẩn đoán `index.html` hiện tại

| # | Vấn đề | Bằng chứng trong file | Hệ quả |
|---|---|---|---|
| 1 | 5 màu tranh nhau | green `#087A55` + navy `#12345A` + cam `#F26522` + amber + rose `#F43F5E` | mắt không xác định được CTA chính |
| 2 | Mọi khối cùng "nặng" | `rounded-3xl` + `shadow-2xl` + `bg-gradient-to-br` ở hero, banner, card, popup | không có phân cấp thị giác |
| 3 | Chữ đậm tràn lan | `font-black` (900) xuất hiện ~25 lần | không còn chỗ để nhấn; tiếng Việt có dấu ở 900 rất bí |
| 4 | In hoa nhỏ rải rác | `uppercase tracking-widest` ở ~12 nhãn | đọc mỏi, giống tờ rơi |
| 5 | 4 tầng chrome trước nội dung | top bar navy → header → banner ảnh có overlay → badge + popup 1.5s | nội dung thật bị đẩy xuống gần 1 màn hình |
| 6 | Chữ chồng lên chữ | `banner-texgiang.png` (đã có slogan in sẵn) + overlay gradient + tiêu đề + badge + nút cam | rối, giảm uy tín doanh nghiệp |
| 7 | Lặp nội dung | teaser "Việc làm gấp tuần này" (3 job) đứng ngay trên 3 job card y hệt | người đọc thấy trùng, trang dài vô ích |
| 8 | Hiệu ứng nhấp nháy | `animate-ping`, `animate-pulse`, 2 nút floating `pulse-*` | gây lo, không phải ngôn ngữ của web tuyển dụng nghiêm túc |
| 9 | Ảnh 2 lần | cùng `banner-texgiang.png` dùng ở hero + about | phí băng thông, nhàm |
| 10 | Nút nổi 4 cái + popup | QR, Zalo, điện thoại, back-to-top | che nội dung trên mobile |

Giữ nguyên **nội dung và chức năng**, chỉ thay cách trình bày.

---

## 3. Design token mới (dùng cho index, sau đó nhân ra 19 trang còn lại)

```css
:root {
  /* Màu — đúng 1 màu nhấn */
  --accent:        #087A55;  /* chữ nhấn, nút chính, số liệu */
  --accent-strong: #06603f;  /* hover / active */
  --accent-soft:   #F1F7F4;  /* chip, nền nhấn rất nhẹ */

  /* Mực */
  --ink:    #161616;  /* heading */
  --ink-2:  #2B2B2B;  /* body chính */
  --muted:  #6B6B6B;  /* phụ, meta */
  --line:   #E8E8E8;  /* hairline 1px */
  --surface:#F6F7F9;  /* nền khối phụ */
  --white:  #FFFFFF;

  /* Bo góc */
  --r-sm: 8px;   /* chip, input nhỏ */
  --r-md: 12px;  /* card */
  --r-lg: 20px;  /* ảnh lớn */

  /* Nhịp */
  --gap-container: 1200px;
  --pad-x: 24px;
  --section-y: 96px;   /* mobile: 64px */

  /* Chuyển động — chỉ 1 kiểu */
  --t: 180ms cubic-bezier(.2,.6,.2,1);
}
```

**Bị loại bỏ khỏi UI:** navy `#12345A`/`#0B223D`, cam `#F26522`, amber, rose, mọi gradient, `rounded-3xl`, `shadow-xl` trên card, animation lặp vô hạn.
Navy giữ lại **chỉ trong file ảnh banner/logo**, không dùng làm màu nền khối nữa.

### Thang chữ

| Vai trò | Desktop | Mobile | Weight | Màu / ghi chú |
|---|---|---|---|---|
| Hero H1 | 56 / 1.1 | 34 / 1.15 | 500 | `--ink`, tối đa 2 dòng |
| H2 khối | 40 / 1.2 | 28 / 1.25 | 500 | `--ink` |
| H3 card | 20 / 1.4 | 18 | 500 | `--ink` |
| Lead | 18 / 1.6 | 17 | 400 | `--ink-2`, tối đa 2 dòng |
| Body | 16 / 1.65 | 16 | 400 | `--muted` |
| Meta | 14 / 1.5 | 13 | 400 | `--muted` |
| Nhãn nhỏ (chỉ 1 chỗ/khối) | 12 / 1.4, ls .06em | 12 | 600 | `--accent` hoặc `--muted` |
| Số liệu | 48 / 1.05 | 32 | 500 | `--accent` |

Quy ước: **mỗi khối chỉ được 1 phần tử "nổi"** (hoặc 1 số liệu accent, hoặc 1 nút solid). Còn lại là chữ thường.

---

## 4. Blueprint 9 khối của index mới

```
 ┌ nav: logo · Việc làm · Về chúng tôi · Phúc lợi · Tin tức · [Hotline] [Ứng tuyển] ┐ 72px, hairline dưới
 ├──────────────────────────────────────────────────────────────────────────────────┤
 │            CỔNG TUYỂN DỤNG CHÍNH THỨC            (nhãn 12/600, accent)            │
 │   Gia nhập TEX-GIANG.                                                            │
 │   Kiến tạo giá trị, cùng phát triển.     ← H1 56/500, "cùng phát triển" = accent  │
 │   Môi trường may mặc xuất khẩu tại KCN Tân Hương...   ← lead 18, tối đa 2 dòng   │
 │        [ Xem vị trí đang tuyển ]      Nộp hồ sơ →                                │
 │   ┌────────────────────────────────────────────────┐                             │
 │   │  ảnh banner-texgiang.png, radius 20, 16/7      │  ← panel sạch, KHÔNG overlay │
 │   └────────────────────────────────────────────────┘                             │
 ├──────────────────────────────────────────────────────────────────────────────────┤
 │  [ Tìm theo vị trí ] | [ Đơn vị ▾ ] | [ Địa điểm ▾ ] |  ( Tìm việc )  ← pill 56px │
 ├──────────────────────────────────────────────────────────────────────────────────┤
 │  nền #F6F7F9 — 4 số liệu, không card, chia bằng hairline dọc                       │
 │   3.500+        8–18tr         100%            15+                                │
 │   CBCNV         thu nhập       BHXH + cơm ca   năm xuất khẩu                      │
 ├──────────────────────────────────────────────────────────────────────────────────┤
 │  Vị trí đang tuyển                        Xem tất cả 24 vị trí →                  │
 │  ┌──────────┐ ┌──────────┐ ┌──────────┐   ← card viền 1px, radius 12, no shadow   │
 │  │          │ │          │ │          │                                           │
 │  └──────────┘ └──────────┘ └──────────┘                                           │
 ├──────────────────────────────────────────────────────────────────────────────────┤
 │  nền #F6F7F9 — "Thưởng nóng 2.000.000đ..."   [Ứng tuyển ngay]  Chat Zalo →         │
 ├──────────────────────────────────────────────────────────────────────────────────┤
 │  Về TEX-GIANG — 2 cột: ảnh (radius 20) | H2 + 2 đoạn + 2 số + link                 │
 ├──────────────────────────────────────────────────────────────────────────────────┤
 │  Footer #F6F7F9 — 4 cột link chữ + 1 hàng pháp nhân                               │
 └──────────────────────────────────────────────────────────────────────────────────┘
```

### 4.1 Nav (thay top-bar + header cũ)

- Cao 72px, nền trắng, `border-bottom: 1px solid var(--line)`. Sticky chỉ khi cuộn (`backdrop-filter` nhẹ, **không** `shadow`).
- Trái: `logo-tgi.png` cao 32px + wordmark "TEX-GIANG" 16/600, dòng phụ "Tuyển dụng" 12 muted (bỏ khối chữ 2 tầng to như hiện tại).
- Giữa: 5 link 15/400 `--ink`, `hover: color accent`. Trang hiện tại = chữ accent + gạch chân 2px (không pill nền xanh như bây giờ).
- Phải: "Hotline **0277 388 9999**" 14 muted (chỉ hiện ≥lg) + nút solid accent "Ứng tuyển" (cao 40px, radius 8px, weight 500).
- Mobile: nút menu 24px + nút "Ứng tuyển" nhỏ; menu xổ full-width, link 16px, chia hairline.
- **Bỏ khỏi nav:** nút QR, 2 link hệ sinh thái, link "Cổng Quản Trị" → chuyển xuống footer cột cuối (chữ 13 muted).

### 4.2 Hero (thay hero 2 cột + teaser card)

- Bố cục **1 cột canh giữa**, max-width 820px cho phần chữ; ảnh nằm dưới full container 1200px.
- Nhãn 12/600 accent: "CỔNG TUYỂN DỤNG CHÍNH THỨC".
- H1 56/500, 2 dòng. Phương án copy:
  - A. "Gia nhập TEX-GIANG. **Kiến tạo giá trị, cùng phát triển.**"
  - B. "Một công việc ổn định. **Một tương lai bền vững.**"
- Lead 18: "May mặc xuất khẩu tại KCN Tân Hương, Đồng Tháp. Thu nhập 8–18 triệu/tháng, đào tạo nghề miễn phí có lương, xe đưa rước và ăn ca."
- Hàng CTA: nút solid accent "Xem vị trí đang tuyển" + text-link "Nộp hồ sơ trong 2 phút →" (link chữ, không viền).
- Ảnh: `banner-texgiang.png`, `aspect-ratio: 16/7`, `object-fit: cover`, radius 20, **không** gradient overlay, **không** chữ đè lên. Ảnh này đã có slogan in sẵn — để nó tự nói.
- Bỏ teaser card "Việc làm gấp tuần này" (trùng khối 5) → đổi thành dòng 14 muted dưới ảnh: "Đang tuyển 24 vị trí · Cập nhật 30/10/2026".

### 4.3 Thanh tìm kiếm

- Một khối liền: nền trắng, viền 1px `--line`, radius **999px**, cao 56px, chia 3 phần bằng hairline dọc, nút "Tìm việc" solid accent bên trong (bo tròn theo khối).
- Bỏ 3 nhãn `uppercase` phía trên field. Chỉ dùng placeholder + icon 16px muted.
- Mobile: xếp dọc, mỗi field 1 dòng ngăn cách hairline, nút full-width.
- **Giữ nguyên** `form action="jobs.html" method="GET"` và 3 `name="keyword|company|location"`.

### 4.4 Dải số liệu (thay 3 số trong hero)

- Nền `--surface`, padding dọc 64px, không card, không bóng. 4 cột chia bằng `border-left: 1px solid var(--line)` (cột đầu không viền).
- Số 48/500 accent + nhãn 14 muted. Nội dung: `3.500+` CBCNV · `8–18tr` thu nhập/tháng · `100%` BHXH, BHYT, cơm ca · `15+` năm xuất khẩu EU–US.
- Mobile: 2×2.

### 4.5 Việc làm nổi bật

- Header khối canh trái (không canh giữa — đây là danh sách, cần điểm neo đọc): H2 40 + link "Xem tất cả 24 vị trí →" cùng hàng, link màu accent 15/500.
- 3 card, grid 3 cột (tablet 2, mobile 1), `gap: 24px`.
- Cấu trúc card (viền 1px `--line`, radius 12, nền trắng, padding 24, **không shadow**; hover: `border-color: var(--accent)`, `translateY(-2px)`, 180ms):

```
TEX-GIANG · Khối sản xuất                    [Mới]
Công nhân may công nghiệp (áo khoác, thể thao)      ← 20/500, 2 dòng clamp
Vận hành máy may 1 kim, 2 kim theo công đoạn.       ← 14 muted, 2 dòng clamp
8 – 16 triệu/tháng                                  ← 16/600 accent
Tân Hương 2, Đồng Tháp · Hạn 30/11                  ← 13 muted, 1 dòng icon 16px
─────────────────────────────────────────────
Xem chi tiết                    Ứng tuyển →         ← text-link + nút ghost
```

- Chip trạng thái: **chỉ 1 kiểu** — chữ 12/600 accent trên nền `--accent-soft`, radius 6, không viền. Bỏ "Tuyển Gấp" đỏ góc chéo và "Hot" navy.
- Icon: Bootstrap Icons cỡ 16px, **cùng 1 màu muted**. Bỏ `bi-cash-stack` xanh, `bi-geo-alt-fill` xám đậm… đang lẫn 3 màu.

### 4.6 Dải CTA (thay banner gradient)

- Nền `--surface`, radius 20 (hoặc không bo), padding 56px, 2 cột: trái H2 32 + 1 đoạn 16 muted; phải 2 nút (solid accent + link chữ "Chat Zalo tuyển dụng →").
- Nội dung: "Thưởng nóng 2.000.000đ cho công nhân may mới" + 1 dòng phụ (xe đưa rước, ăn ca, đào tạo có lương).
- Bỏ chữ in hoa 12/700 nền gradient, bỏ `border-emerald-500/30`.

### 4.7 Về TEX-GIANG

- 2 cột 6/6, ảnh trái radius 20 (`max-height: 420px`, `object-fit: cover`), chữ phải.
- H2 40 + 2 đoạn 16 muted (giữ nguyên nội dung hiện tại, bỏ `<strong>` màu xanh giữa câu) + 1 hàng 2 số liệu (15+ năm, WRAP & ISO) dạng chữ thường: số 24/500 ink + nhãn 13 muted, **không** hộp nền emerald bo góc.
- 2 link: "Về chúng tôi →" (accent, 15/500) và "Chế độ phúc lợi →".

### 4.8 Footer

- Nền `--surface`, chữ `--ink`, không gradient/không chữ xanh nhạt.
- 4 cột: (1) logo + 1 đoạn mô tả 14 muted + địa chỉ/điện thoại/email dạng 3 dòng có icon 16px; (2) Việc làm; (3) Doanh nghiệp; (4) Kết nối (Zalo, Facebook, QR, sao chép link) — link chữ, không nút vuông màu.
- Hàng dưới: `border-top: 1px solid var(--line)`, 13 muted: © 2026 + MST + "Cổng quản trị HR" (link nhỏ, không nổi).
- **Bỏ** "Thiết kế & Vận hành: IT-P.KT-NS" khỏi dòng bản quyền? → giữ, nhưng 12px muted cuối hàng.

### 4.9 Nổi & modal

- Nút nổi: **2 cái** (Zalo, Gọi) + back-to-top khi cuộn. Cao 48px, radius 999, nền trắng viền hairline, icon accent — hoặc solid accent cho nút chính. **Bỏ `pulse-*` và `animate-ping`.**
- Popup "Thông báo tuyển dụng" sau 1.5s → **thay bằng strip 1 dòng** ngay dưới nav (nền `--accent-soft`, chữ 14, nút ×, nhớ `sessionStorage` như cũ). Lý do: popup chặn nội dung đúng lúc người ta vừa vào, và Zalo không dùng popup.
- QR modal: giữ (chức năng thật), style lại — nền trắng, radius 16, nút solid accent, bỏ viền `border-2` xanh.

---

## 5. Mapping nội dung cũ → mới

| Khối hiện tại | Xử lý | Lý do |
|---|---|---|
| Top utility bar navy (hotline, email, địa chỉ, 2 link hệ sinh thái, nút admin) | **Bỏ hẳn** → hotline vào nav, còn lại vào footer | 1 tầng chrome thừa, navy không còn trong palette |
| Header cao 80px, logo + 2 tầng chữ + 6 link pill + nút QR + CTA gradient | **Nav 72px**, 5 link chữ, 1 CTA solid | giảm mật độ, tăng khoảng trắng |
| Hero banner ảnh + overlay + badge + nút cam | **Panel ảnh sạch** dưới H1 | ảnh đã có chữ sẵn, chồng chữ = rối |
| Hero 2 cột + 3 metric + teaser job card | **Hero 1 cột + dải số liệu riêng**; bỏ teaser | teaser trùng khối job card |
| Search bar 3 field + label uppercase + nút | **Pill liền 56px** | ít nhãn hơn, một khối liền mạch |
| Banner "Thưởng nóng" gradient + 2 nút | **Dải CTA nền xám** | bỏ gradient, giữ thông điệp |
| 3 job card `rounded-3xl` + badge chéo + shadow | **Card hairline 12px**, 1 kiểu chip | phân cấp bằng đường kẻ |
| About 2 cột + 2 ô emerald + 2 nút | Giữ cấu trúc, đổi sang số liệu chữ thường | bớt khối màu |
| Footer navy 4 cột | **Footer xám nhạt** | Zalo-style, nhẹ hơn |
| 3 nút nổi + pulse + popup 1.5s | **2 nút + strip thông báo** | không nhấp nháy, không chặn nội dung |

---

## 6. Ràng buộc kỹ thuật khi code (không được vỡ)

**Hook JS phải giữ nguyên** (`assets/js/main.js` query theo id/class):
`#mobile-menu-btn`, `#mobile-menu`, `#back-to-top`, `#recruitment-popup`, `#close-popup-btn`, `#popup-dont-show-again`, `#qr-modal`, `#close-qr-btn`, `.open-qr-modal`, `#qr-code-img`, `#qr-target-url`, `#toast-container`; hàm `shareFacebook()`, `shareZalo()`, `copyLink()`, `showToast()`.

**Giữ nguyên:** `form action="jobs.html" method="GET"` + 3 input `name`; mọi `href` (`jobs.html`, `job-detail.html`, `apply.html`, `about.html`, `why-tex-giang.html`, `news.html`, `contact.html`, `admin/login.html`, `apply.html?job=...`); `assets/js/main.js`; 2 ảnh trong `assets/images/`.

**Cách ly để không vỡ 19 trang khác:** không sửa `tailwind.config` cũ và không đổi tên class đang dùng ở trang khác. Thêm bộ class mới vào `assets/css/custom.css` (`tg-nav`, `tg-btn`, `tg-btn--ghost`, `tg-card`, `tg-chip`, `tg-section`, `tg-h1/h2`, `tg-lead`, `tg-meta`, `tg-stats`) rồi index dùng class mới. Khi chốt xong mới nhân ra các trang còn lại.

**Font:** Be Vietnam Pro, chỉ tải weight **400/500/600** (bỏ 800/900) — file CSS nhỏ hơn, chữ tiếng Việt sạch hơn.
**A11y:** `:focus-visible` 2px accent (đã có trong custom.css), tương phản chữ ≥ 4.5:1 (accent trên trắng ≈ 5.6:1 ✓), `prefers-reduced-motion: reduce` tắt hover transform và mọi transition.
**Không thêm thư viện mới.** Giữ Tailwind CDN + Bootstrap Icons.

---

## 7. Kế hoạch thực thi

| Bước | Việc | Kết quả |
|---|---|---|
| 1 | ~~Thêm token + bộ class `tg-*` vào `custom.css`~~ → đã chuyển sang **Tailwind**: config token trong `index.html` + `<style type="text/tailwindcss">` cho component (`tg-btn`, `tg-field`, `job-card`…) | nền thiết kế dùng chung |
| 2 | Dựng lại `index.html` theo 9 khối trên, giữ nguyên hook JS/href | trang chạy được |
| 3 | QA: screenshot 1440 / 1536 / 1024 / 390; bấm thử menu mobile, strip thông báo, QR modal, form search, nút Zalo/gọi | checklist pass |
| 4 | (sau khi duyệt) nhân token sang `jobs.html`, `job-detail.html`, `apply.html`… | toàn site đồng bộ |

**Nhịp dọc đã dùng (sau khi soi bằng `inspect.mjs`):** hero `pt 96/112/128` → H1 → lead `24` → CTA `40` → **ảnh `80/96`** → ghi chú `20` → khối tìm kiếm `64/80` → dải số liệu `py 64/80` → section `80/128`. Container `1200px` (tới 1535px) và **`1360px` từ 1536px** (mốc giống zalo.me), padding ngang `24 → 40`.
**Cỡ chữ:** 79 (H1, clamp 5.5vw) · 44/43 (H2) · 24 · 20 · 18 · 16 (body) · 14 (nhỏ nhất).

**Checklist QA:** nav không nhảy khi sticky; ảnh hero không méo ở 390px; card 3 cột → 1 cột; chip chỉ 1 style; không còn gradient/`rounded-3xl`/`font-black` trong `index.html`; tab-focus nhìn thấy rõ; tốc độ tải không tăng (không thêm CDN).

---

## 8. Ba điểm cần chốt trước khi code

1. **Màu nhấn:** giữ xanh nhận diện `#087A55` (khuyến nghị) hay đổi sang "xanh công nghệ" kiểu Zalo `#0068FF`?
2. **Chữ tiêu đề:** Be Vietnam Pro 500 (khuyến nghị, có sẵn) hay thay font hình học giống Gilroy (Poppins / SVN-Gilroy, phải tải thêm)?
3. **Thông báo thưởng 2.000.000đ:** strip dưới nav (khuyến nghị) / bỏ hẳn / vẫn popup nhưng chỉ hiện sau khi cuộn 70%?
