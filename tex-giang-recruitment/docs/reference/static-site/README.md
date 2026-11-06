# Tài liệu tham chiếu từ site tĩnh gốc

Thư mục này lưu **bản gốc của site tĩnh** (`E:\websitetuyendung`) để đối chiếu khi cần, sau khi dự án đã chuyển sang PHP.

## Vì sao không copy thẳng vào `public/`

Các file dưới đây **không được nạp ở runtime**; copy vào `public/` sẽ tạo trang/asset trùng lặp, dễ gây nhầm lẫn và làm nặng bản deploy. Vì vậy chúng được archive ở đây.

| Đường dẫn | Nội dung |
|---|---|
| `pages/` | 17 trang HTML tĩnh gốc — đã được port thành PHP view (`app/Views/public/*`) |
| `admin/` | 10 trang quản trị tĩnh gốc — đã được thiết kế lại bằng PHP + phân quyền |
| `components/` | header/footer/job-card/admin-sidebar tĩnh — đã thay bằng `app/Views/partials/*` |
| `assets/css/admin.css` | CSS admin bản tĩnh — thay bằng `public/assets/css/admin-app.css` |
| `assets/js/*` | JS demo của bản tĩnh (jobs-data, news-data, admin-data, demo-store, apply-dialog…).
Thay bằng: `apply-form.js`, `home-jobs.js`, `jobs.js` (bản động, dữ liệu từ MySQL) |
| `docs/` | Tài liệu/audit/preview của bản tĩnh |
| `root/` | README, FACT-SHEET, AUDIT-PDF, REDESIGN-* của bản tĩnh |
| `tools/` | Script build/audit của bản tĩnh (`build-admin.mjs`, `audit-*.mjs`, `serve.mjs`) — dự án PHP dùng `tools/serve.ps1` |

## Asset đã copy sang runtime

Toàn bộ ảnh của bản tĩnh đã có trong `public/assets/images/` (kể cả `images/news/cover-*.webp`, `images/404.svg`,
`images/icons/LICENSE.txt`, các định dạng PNG dự phòng). CSS/JS dùng thật đã nằm trong `public/assets/css|js/`;
Tailwind được build lại bằng pipeline riêng (`npm run build:css`).

## Không thuộc website — không copy

- `.taste-skill/`, `designs/`, `bao-cao/` — tài liệu thiết kế/báo cáo riêng, không phục vụ runtime.
- `node_modules/` — thư viện build của bản tĩnh.
- `tex-giang-recruitment.zip` — gói nén cũ không dùng.
