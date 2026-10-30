import { mkdir, writeFile } from "node:fs/promises";

// Single source for the static admin shell and screens; never fetch the shell at runtime.
const icon = (name) =>
  `<img class="admin-icon" src="../assets/images/icons/${name}.svg" width="18" height="18" alt="" aria-hidden="true">`;
const field = (id, label, type = "text", extra = "") =>
  `<div><label class="form-label" for="${id}">${label}</label><input class="form-input" id="${id}" name="${id}" type="${type}" ${extra}><p id="${id}-error" class="field-error"></p></div>`;
const area = (id, label, rows = 4, extra = "") =>
  `<div><label class="form-label" for="${id}">${label}</label><textarea class="form-input" id="${id}" name="${id}" rows="${rows}" ${extra}></textarea><p id="${id}-error" class="field-error"></p></div>`;
const select = (id, label, options) =>
  `<div><label class="form-label" for="${id}">${label}</label><select class="form-input" id="${id}" name="${id}">${options.map(([v, t]) => `<option value="${v}">${t}</option>`).join("")}</select><p id="${id}-error" class="field-error"></p></div>`;
const companies = [
  ["", "Tất cả đơn vị"],
  ["tex-giang", "TEX-GIANG"],
  ["tg-apparel", "TG APPAREL"],
];
const statuses = [
  ["", "Tất cả trạng thái"],
  ["new", "Mới nhận"],
  ["contacted", "Đã liên hệ"],
  ["interview", "Hẹn phỏng vấn"],
  ["hired", "Trúng tuyển"],
  ["rejected", "Không phù hợp"],
];
const save = (form, label = "Lưu thay đổi") =>
  `<button type="submit" form="${form}" class="tg-btn tg-btn--primary" data-save disabled>${icon("check2")}${label}</button>`;
const panel = (title, body, desc = "") =>
  `<section class="admin-panel"><div class="admin-panel-head"><div><h2>${title}</h2>${desc ? `<p>${desc}</p>` : ""}</div></div>${body}</section>`;
const pages = {
  dashboard: {
    title: "Bảng điều khiển",
    subtitle: "Ưu tiên công việc cần xử lý trong phiên tuyển dụng.",
    actions: `<a class="tg-btn tg-btn--primary" href="job-form.html">${icon("briefcase")}Đăng tin mới</a>`,
    body: `
    <div id="dashboard-scope" class="admin-toolbar" style="max-width:340px;display:block">${select("dashboard-company", "Phạm vi tuyển dụng", companies)}</div>
    <div id="dashboard-kpis" class="dashboard-kpis"></div>
    <div class="dashboard-main">
      <section class="admin-panel"><div class="admin-panel-head"><div><h2>Hồ sơ cần xử lý</h2><p id="queue-subtitle">Ưu tiên hồ sơ mới và lịch hẹn.</p></div><a class="tg-link" href="applications.html">Xem tất cả ${icon("arrow-right")}</a></div><ul class="queue-list" id="dashboard-queue"></ul></section>
      <div class="dashboard-side"><section class="admin-panel"><div class="admin-panel-head"><h2>Tin sắp hết hạn</h2><a class="tg-link" href="jobs.html?status=published">Quản lý</a></div><ul class="queue-list" id="dashboard-expiring"></ul></section><section class="admin-panel"><h2>Lịch hẹn sắp tới</h2><ul class="queue-list" id="dashboard-appointments"></ul></section></div>
    </div>
    <div class="dashboard-side" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:24px;margin-top:24px"><section class="admin-panel"><h2>Phân bố hồ sơ</h2><p class="form-hint">Theo trạng thái xử lý hiện tại · đơn vị: hồ sơ</p><div id="dashboard-status-bars" class="bar-list"></div></section><section class="admin-panel"><h2>Nguồn ứng viên</h2><p class="form-hint">Tính từ các hồ sơ trong phạm vi đang chọn</p><div id="dashboard-sources" class="bar-list"></div></section></div>`,
  },
  applications: {
    title: "Hồ sơ ứng viên",
    subtitle: "Tìm đúng người, theo dõi liên hệ và cập nhật bước tiếp theo.",
    actions: `<button class="tg-btn tg-btn--ghost" type="button" data-export>${icon("file-earmark-text")}Xuất CSV</button>`,
    body: `
    <div class="admin-tabs" id="candidate-tabs" aria-label="Nhóm hồ sơ"></div>
    <div class="admin-toolbar">${field("admin-candidate-search", "Tên hoặc số điện thoại", "search", 'placeholder="Tìm ứng viên…" maxlength="100"')}${select("admin-status-filter", "Trạng thái", statuses)}${select("admin-position-filter", "Vị trí", [["", "Tất cả vị trí"]])}${select("admin-company-filter", "Đơn vị", companies)}<button class="tg-btn tg-btn--ghost" type="button" data-reset-filters>Xoá lọc</button></div>
    <div id="candidate-list"></div><div id="candidate-pagination" class="list-footer"></div>`,
  },
  "application-detail": {
    title: "Hồ sơ ứng viên",
    subtitle: "Thông tin ứng tuyển và lịch sử xử lý.",
    actions: `<a class="tg-btn tg-btn--ghost" id="back-to-candidates" href="applications.html">Danh sách hồ sơ</a>`,
    body: '<div id="candidate-detail"></div>',
  },
  jobs: {
    title: "Tin tuyển dụng",
    subtitle: "Quản lý vị trí, thời hạn và các hồ sơ theo tin.",
    actions: `<a class="tg-btn tg-btn--primary" href="job-form.html">${icon("briefcase")}Đăng tin mới</a>`,
    body: `
    <div class="admin-tabs" id="job-tabs" aria-label="Vòng đời tin"></div>
    <div class="admin-toolbar job-toolbar">${field("admin-job-search", "Chức danh hoặc mã tin", "search", 'placeholder="Ví dụ: QC, TG-02…" maxlength="100"')}${select(
      "admin-job-status",
      "Trạng thái",
      [
        ["", "Tất cả trạng thái"],
        ["published", "Đang tuyển"],
        ["draft", "Bản nháp"],
        ["expired", "Hết hạn"],
        ["closed", "Đã đóng"],
      ],
    )}${select("admin-job-company", "Đơn vị", companies)}<button class="tg-btn tg-btn--ghost" type="button" data-reset-filters>Xoá lọc</button></div>
    <div id="job-list"></div><div id="job-pagination" class="list-footer"></div>`,
  },
  "job-form": {
    title: "Đăng tin tuyển dụng",
    subtitle: "Soạn nội dung, xem trước và lưu trong bản demo.",
    actions: `<a class="tg-btn tg-btn--ghost" href="jobs.html">Huỷ & quay lại</a>`,
    body: `
    <form id="job-editor-form" novalidate><div class="form-errors" id="job-form-errors" role="alert" tabindex="-1"></div><div class="editor-layout"><div class="form-stack">
      ${panel(
        "Thông tin vị trí",
        `<div class="field-grid"><div class="span-all">${field("job-title", "Chức danh tuyển dụng *", "text", 'required maxlength="140"')}</div>${select("job-company", "Đơn vị tuyển dụng *", companies.slice(1))}${select(
          "job-department",
          "Khối phòng ban *",
          [
            ["production", "Sản xuất may mặc"],
            ["qc", "Kiểm soát chất lượng QC"],
            ["ie", "Kỹ thuật công nghiệp IE"],
            ["office", "Văn phòng & kinh doanh"],
            ["maintenance", "Bảo trì cơ điện"],
          ],
        )}${field("job-salary-min", "Thu nhập từ (đ/tháng)", "number", 'min="0" max="1000000000" step="50000"')}${field("job-salary-max", "Thu nhập đến (đ/tháng)", "number", 'min="0" max="1000000000" step="50000"')}${field("job-quantity", "Số lượng cần tuyển *", "number", 'required min="1" max="10000"')}${field("job-deadline", "Hạn nộp hồ sơ *", "date", "required")}<div class="span-all">${field("job-location", "Địa điểm làm việc *", "text", 'required maxlength="240"')}</div></div>`,
      )}
      ${panel("Nội dung tuyển dụng", `<div class="form-stack">${area("job-description", "Mô tả công việc *", 5, 'required maxlength="8000"')}${area("job-requirements", "Yêu cầu ứng viên *", 4, 'required maxlength="8000"')}${area("job-benefits", "Quyền lợi & chế độ *", 4, 'required maxlength="8000"')}</div>`)}
    </div><aside class="editor-side">${panel(
      "Xuất bản",
      `${select("job-status", "Trạng thái tin", [
        ["draft", "Bản nháp"],
        ["published", "Đang tuyển"],
        ["closed", "Đã đóng"],
      ])}<label class="form-label" style="display:flex;align-items:center;gap:10px;margin-top:20px"><input type="checkbox" id="job-urgent" name="job-urgent"> Ưu tiên tuyển gấp</label><p class="form-hint">Nhãn ưu tiên tách riêng khỏi trạng thái tin.</p><div class="form-stack" style="gap:10px;margin-top:24px"><button class="tg-btn tg-btn--ghost" type="button" data-preview-job>${icon("file-earmark-text")}Xem trước</button><button type="button" class="tg-btn tg-btn--ghost" data-save-draft disabled>Lưu bản nháp</button><button class="tg-btn tg-btn--primary" type="submit" data-save disabled>Lưu tin trong demo</button></div><p class="save-state" data-save-state>Chưa có thay đổi</p><p class="form-hint">Lưu trong phiên trình duyệt. Website công khai chưa được cập nhật.</p>`,
    )}<div style="margin-top:18px"><a class="tg-link" id="job-public-link" href="../jobs.html" target="_blank" rel="noopener">Xem website công khai ${icon("arrow-right")}</a></div></aside></div></form>`,
  },
  content: {
    title: "Nội dung website",
    subtitle: "Soạn nội dung và xem trước trước khi lưu phiên demo.",
    actions: save("content-editor-form"),
    body: `
    <div class="admin-tabs" id="content-tabs" aria-label="Nhóm nội dung"><button type="button" data-content-tab="home" aria-pressed="true">Trang chủ</button><button type="button" data-content-tab="about" aria-pressed="false">Giới thiệu & phúc lợi</button><button type="button" data-content-tab="posts" aria-pressed="false">Tin tức</button><button type="button" data-content-tab="contact" aria-pressed="false">Liên hệ</button></div>
    <form id="content-editor-form" novalidate><div class="form-errors" role="alert"></div><div class="editor-layout"><div class="form-stack">
    <section class="admin-panel" data-content-panel="home"><h2>Thông điệp trang chủ</h2><div class="form-stack" style="margin-top:20px">${field("cms-slogan", "Khẩu hiệu *", "text", 'required maxlength="160"')}${field("cms-philosophy", "Triết lý hoạt động", "text", 'maxlength="240"')}${area("cms-hero", "Đoạn giới thiệu *", 4, 'required maxlength="1200"')}</div></section>
    <section class="admin-panel" data-content-panel="about" hidden><h2>Giới thiệu & phúc lợi</h2><div class="form-stack" style="margin-top:20px">${area("cms-about", "Giới thiệu doanh nghiệp", 6, 'maxlength="8000"')}${area("cms-benefits", "Thông điệp phúc lợi", 6, 'maxlength="8000"')}</div></section>
    <section class="admin-panel" data-content-panel="posts" hidden><div class="admin-panel-head"><h2>Bài viết</h2><button type="button" class="tg-btn tg-btn--ghost" data-new-post>Thêm bài viết</button></div><div id="cms-post-list"></div><p class="form-hint">Chọn bài để soạn, xem trước và lưu trạng thái nháp/đã đăng trong demo.</p></section>
    <section class="admin-panel" data-content-panel="contact" hidden><h2>Thông tin liên hệ</h2><div class="field-grid" style="margin-top:20px">${field("cms-hotline", "Hotline *", "tel", 'required maxlength="24"')}${field("cms-zalo", "Số điện thoại Zalo", "tel", 'maxlength="24"')}${field("cms-email", "Email tuyển dụng *", "email", 'required maxlength="160"')}${field("cms-fanpage", "Đường dẫn fanpage", "url", 'maxlength="300"')}</div><p class="form-hint">Thông tin liên hệ được sở hữu tại đây; cài đặt hệ thống không lặp lại các trường này.</p></section>
    </div><aside class="editor-side"><section class="admin-panel"><div class="admin-panel-head"><h2>Xem trước nội dung</h2></div><div id="content-preview" class="preview-body"></div><p class="save-state" data-save-state>Chưa có thay đổi</p></section></aside></div></form>`,
  },
  banners: {
    title: "Banner & thông báo",
    subtitle: "Kiểm tra ảnh và thông điệp trên desktop hoặc điện thoại.",
    actions: save("banner-form"),
    body: `
    <form id="banner-form" novalidate><div class="form-errors" role="alert"></div><div class="editor-layout"><div class="form-stack"><section class="admin-panel"><div class="admin-panel-head"><div><h2>Banner trang chủ</h2><p>Ảnh ngang 8:3 · khuyến nghị 2048 × 768 px</p></div></div><div class="media-controls"><button type="button" class="tg-btn tg-btn--ghost" data-media-mode="desktop" aria-pressed="true">Desktop</button><button type="button" class="tg-btn tg-btn--ghost" data-media-mode="phone" aria-pressed="false">Điện thoại</button></div><div class="media-preview" id="banner-preview-wrap"><img class="preview-image" id="banner-preview-image" src="../assets/images/banner-texgiang.webp" alt="Cổng Nhà máy Tân Hương 2"></div><div class="media-metadata" id="banner-metadata"></div><div class="file-picker"><label class="form-label" for="banner-file">Chọn ảnh thay thế</label><input type="file" id="banner-file" accept="image/jpeg,image/png,image/webp" aria-describedby="banner-file-hint banner-file-error"><p class="form-hint" id="banner-file-hint">JPG, PNG, WebP · tối đa 5 MB · ảnh được xử lý ngay trên máy.</p><p class="field-error" id="banner-file-error" role="alert"></p></div><button type="button" class="tg-link" style="margin-top:12px" data-revert-banner>Hoàn tác thay ảnh</button></section>
    <section class="admin-panel"><h2>Dải thông báo</h2><div class="notice-preview" id="notice-preview" style="margin:20px 0"></div><div class="form-stack">${area("banner-notice-text", "Nội dung thông báo *", 3, 'required maxlength="400"')}${field("banner-notice-link", "Liên kết khi bấm *", "text", 'required maxlength="300"')}${select(
      "banner-notice-state",
      "Hiển thị thông báo",
      [
        ["on", "Bật"],
        ["off", "Tắt"],
      ],
    )}</div></section></div>
    <aside class="editor-side">${panel("Thiết lập ảnh", `${area("banner-alt", "Mô tả ảnh (alt) *", 3, 'required maxlength="240"')}<label class="form-label" for="banner-focal" style="margin-top:20px">Điểm lấy nét ngang</label><input id="banner-focal" type="range" min="0" max="100" value="50" style="width:100%;accent-color:#087a55"><p class="form-hint">Điều chỉnh vùng ảnh được giữ trên điện thoại.</p><p class="save-state" data-save-state>Chưa có thay đổi</p><p class="form-hint">Bản xem trước và thay ảnh chỉ được lưu trong phiên demo.</p>`)} </aside></div></form>`,
  },
  settings: {
    title: "Cài đặt hệ thống",
    subtitle: "Nhận diện, nội dung tìm kiếm và quy tắc tiếp nhận hồ sơ.",
    actions: save("settings-form"),
    body: `
    <form id="settings-form" novalidate><div class="form-errors" role="alert"></div>
    <section class="settings-row"><div><h2>Website & pháp nhân</h2><p>Thông tin nhận diện dùng trong toàn hệ thống.</p></div><div class="settings-fields">${field("set-site-name", "Tên website *", "text", 'required maxlength="120"')}${field("set-legal-name", "Tên pháp nhân *", "text", 'required maxlength="180"')}${field("set-address", "Địa chỉ nhà máy *", "text", 'required maxlength="300"')}<a class="tg-link" href="content.html?tab=contact">Quản lý hotline, email và Zalo ${icon("arrow-right")}</a></div></section>
    <section class="settings-row"><div><h2>Hiển thị tìm kiếm</h2><p>Xem trước nội dung tiêu đề và mô tả. Công cụ tìm kiếm có thể hiển thị khác.</p></div><div class="settings-fields">${field("set-seo-title", "Tiêu đề trang *", "text", 'required maxlength="160"')}<p id="seo-title-count" class="form-hint"></p>${area("set-seo-desc", "Mô tả trang *", 4, 'required maxlength="400"')}<p id="seo-description-count" class="form-hint"></p><div class="seo-preview" id="seo-preview"></div></div></section>
    <section class="settings-row"><div><h2>Tiếp nhận & hiển thị</h2><p>Quy tắc áp dụng trong phiên demo quản trị.</p></div><div class="settings-fields">${field("set-page-size", "Số dòng mỗi trang quản trị *", "number", 'required min="2" max="50"')}${field("set-apply-email", "Email nhận thông báo hồ sơ *", "email", 'required maxlength="160"')}<p class="form-hint">Chưa gửi email thật. Cấu hình này dùng để xem và kiểm tra biểu mẫu.</p></div></section><p class="save-state" data-save-state>Chưa có thay đổi</p></form>`,
  },
  login: {
    title: "Đăng nhập quản trị",
    login: true,
    body: `<main class="login-card"><div class="login-brand"><img src="../assets/images/logo-tgi.png" alt="Logo TEX-GIANG" width="40" height="40"><div><strong>TEX-GIANG</strong><p class="form-hint">Quản trị tuyển dụng</p></div></div><h1>Không gian làm việc của HR</h1><p>Quản lý hồ sơ, tin tuyển dụng và nội dung website.</p><form id="admin-login-form" novalidate><div class="form-errors" role="alert"></div><div class="form-stack" style="gap:18px">${field("login-username", "Tên đăng nhập hoặc email", "text", 'autocomplete="username" maxlength="160" required')}<div><label class="form-label" for="login-password">Mật khẩu</label><div class="password-wrap"><input class="form-input" id="login-password" type="password" autocomplete="current-password" maxlength="200" required><button type="button" id="toggle-password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button></div><p class="field-error" id="login-password-error"></p></div><button type="submit" class="tg-btn tg-btn--ghost">Đăng nhập</button></div><p class="form-hint">Xác thực tài khoản chưa được kết nối trong bản giao diện này.</p></form><section class="demo-access"><h2 style="font-size:15px;margin-bottom:8px">Khám phá bản demo</h2><p>Dữ liệu minh hoạ, không cần mật khẩu thật. Thay đổi chỉ được lưu trong phiên trình duyệt.</p><button type="button" class="tg-btn tg-btn--primary" id="enter-demo" style="width:100%">Vào bản demo ${icon("arrow-right")}</button></section><div class="login-footer"><a class="tg-link" href="../index.html">Về website tuyển dụng</a></div></main>`,
  },
};
const navs = [
  ["dashboard", "Bảng điều khiển", "buildings"],
  ["applications", "Hồ sơ ứng viên", "file-earmark-check"],
  ["jobs", "Tin tuyển dụng", "briefcase"],
  ["content", "Nội dung website", "file-earmark-text"],
  ["banners", "Banner & thông báo", "cloud-arrow-up"],
  ["settings", "Cài đặt hệ thống", "sliders"],
];
const head = `<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><link rel="icon" href="../assets/images/logo-tgi.png"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/utilities.css"><link rel="stylesheet" href="../assets/css/custom.css"><link rel="stylesheet" href="../assets/css/admin.css">`;
const dialogs = `<dialog id="status-update-modal" aria-labelledby="status-dialog-title"><form id="candidate-status-form" novalidate><div class="dialog-head"><div><h2 id="status-dialog-title">Cập nhật hồ sơ</h2><p id="modal-candidate-name" class="form-hint"></p></div><button type="button" class="admin-icon-btn" data-close-dialog aria-label="Đóng">${icon("x-lg")}</button></div><input id="modal-candidate-id" type="hidden">${select("modal-status-select", "Trạng thái xử lý", statuses.slice(1))}<div style="margin-top:18px">${area("modal-note", "Ghi chú nội bộ", 4, 'maxlength="2000"')}</div><div class="form-errors" role="alert"></div><div class="dialog-actions"><button type="button" class="tg-btn tg-btn--ghost" data-close-dialog>Huỷ</button><button type="submit" class="tg-btn tg-btn--primary">Lưu trong demo</button></div></form></dialog>
<dialog id="admin-preview" class="preview-dialog" aria-labelledby="preview-title"><div class="dialog-head"><h2 id="preview-title">Xem trước</h2><button type="button" class="admin-icon-btn" data-close-dialog aria-label="Đóng xem trước">${icon("x-lg")}</button></div><div id="admin-preview-body" class="preview-body"></div></dialog>
<dialog id="admin-confirm" aria-labelledby="confirm-title"><h2 id="confirm-title">Xác nhận thao tác</h2><p id="confirm-message" style="margin-top:12px"></p><div class="dialog-actions"><button class="tg-btn tg-btn--ghost" type="button" data-close-dialog>Huỷ</button><button class="tg-btn tg-btn--primary" type="button" id="confirm-action">Tiếp tục</button></div></dialog>
<dialog id="post-editor-dialog" aria-labelledby="post-dialog-title"><form id="post-editor-form" novalidate><div class="dialog-head"><h2 id="post-dialog-title">Soạn bài viết</h2><button type="button" class="admin-icon-btn" data-close-dialog aria-label="Đóng soạn bài">${icon("x-lg")}</button></div><input type="hidden" id="post-id"><div class="form-stack">${field("post-title", "Tiêu đề *", "text", 'required maxlength="160"')}${select(
  "post-category",
  "Chuyên mục",
  [
    ["Tuyển dụng", "Tuyển dụng"],
    ["Nội bộ", "Nội bộ"],
    ["Cẩm nang", "Cẩm nang"],
  ],
)}${area("post-body", "Nội dung *", 7, 'required maxlength="12000"')}${select(
  "post-state",
  "Trạng thái",
  [
    ["draft", "Bản nháp"],
    ["published", "Đã đăng trong demo"],
  ],
)}</div><div class="form-errors" role="alert"></div><div class="dialog-actions"><button type="button" class="tg-btn tg-btn--ghost" data-preview-post>Xem trước</button><button type="submit" class="tg-btn tg-btn--primary">Lưu bài trong demo</button></div></form></dialog>`;
await mkdir("admin", { recursive: true });
await mkdir("components/admin", { recursive: true });
for (const [key, page] of Object.entries(pages)) {
  const active =
    key === "application-detail"
      ? "applications"
      : key === "job-form"
        ? "jobs"
        : key;
  const sidebar = `<aside id="admin-sidebar" class="admin-sidebar" aria-label="Điều hướng quản trị"><div><div class="admin-brand"><img src="../assets/images/logo-tgi.png" alt="Logo TEX-GIANG" width="34" height="34"><div><strong>TEX-GIANG</strong><span>Quản trị tuyển dụng</span></div><button type="button" class="admin-icon-btn" id="close-sidebar-btn" aria-label="Đóng menu">${icon("x-lg")}</button></div><nav aria-label="Menu chính">${navs.map(([id, label, ic], i) => `${[0, 1, 3, 5].includes(i) ? `<p class="admin-section-label">${i === 0 ? "Không gian làm việc" : i === 1 ? "Tuyển dụng" : i === 3 ? "Nội dung" : "Hệ thống"}</p>` : ""}<a href="${id}.html" class="admin-nav-link ${id === active ? "is-active" : ""}" ${id === active ? 'aria-current="page"' : ""}>${icon(ic)}<span>${label}</span>${id === "applications" ? '<span class="admin-badge" data-new-count aria-label="Số hồ sơ mới" style="margin-left:auto">0</span>' : ""}</a>`).join("")}</nav></div><div class="admin-sidebar-foot"><a href="../index.html" target="_blank" rel="noopener">Xem website công khai ${icon("arrow-right")}</a><a href="login.html">Màn hình đăng nhập</a><button type="button" class="tg-link" data-reset-demo>Đặt lại dữ liệu demo</button></div></aside>`;
  const shell = page.login
    ? page.body
    : `<a class="skip-link" href="#admin-main">Đến nội dung chính</a>${sidebar}<div class="admin-overlay" id="sidebar-overlay" hidden></div><div class="admin-workspace"><header class="admin-topbar"><div class="topbar-context"><button id="toggle-sidebar-btn" class="admin-icon-btn" type="button" aria-label="Mở menu" aria-controls="admin-sidebar" aria-expanded="false">${icon("list")}</button><a class="admin-mobile-logo" href="dashboard.html" aria-label="TEX-GIANG — bảng điều khiển"><img src="../assets/images/logo-tgi.png" alt="Logo TEX-GIANG" width="60" height="60"></a><span>TEX-GIANG / Tuyển dụng</span></div><div class="topbar-right"><span class="admin-badge">Phiên demo</span><a class="tg-link" href="../index.html" target="_blank" rel="noopener">Xem website</a><span class="admin-avatar" aria-label="Nhân sự — bản demo">HR</span></div></header><main id="admin-main" class="admin-sheet"><div class="admin-content-width"><div id="admin-recovery" class="form-errors" role="status" hidden></div><div class="admin-page-head"><div><h1 id="admin-page-title">${page.title}</h1><p>${page.subtitle}</p></div><div class="admin-head-actions">${page.actions || ""}</div></div>${page.body}<div class="admin-demo-note">${icon("info-circle-fill")}<span>Dữ liệu minh hoạ. Thay đổi lưu trong phiên trình duyệt, chưa gửi đến HR hoặc xuất bản lên website.</span></div></div></main></div>${dialogs}`;
  const html = `<!DOCTYPE html>\n<!-- Generated by tools/build-admin.mjs. Edit that source, then npm run build:admin. -->\n<html lang="vi"><head>${head}<title>${page.title} | TEX-GIANG HR</title></head><body class="admin-app ${page.login ? "admin-login" : ""}" data-admin-page="${key}">${shell}<noscript><p>Vui lòng bật JavaScript để sử dụng giao diện quản trị demo.</p></noscript><div id="toast-container"></div><script src="../assets/js/jobs-data.js"></script><script src="../assets/js/admin-data.js"></script><script src="../assets/js/main.js"></script><script src="../assets/js/admin.js"></script></body></html>\n`;
  const formatted = html.replace(/></g, ">\n<").replace(/(<(div|p|span)\b[^>]*>)\n(<\/\2>)/g, "$1$3");
  await writeFile(`admin/${key}.html`, formatted);
  if (key === "dashboard")
    await writeFile("components/admin/shell-reference.html", sidebar);
}
await writeFile(
  "admin/index.html",
  '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="robots" content="noindex"><meta http-equiv="refresh" content="0;url=login.html"><title>Quản trị tuyển dụng</title></head><body><a href="login.html">Mở khu quản trị tuyển dụng</a></body></html>\n',
);
console.log("Generated 9 admin screens and /admin/ entry point.");
