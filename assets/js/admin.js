/* Session-only HR prototype. All user text is escaped before HTML rendering. */
(() => {
  "use strict";
  const D = window.AdminData,
    page = document.body.dataset.adminPage,
    $ = (id) => document.getElementById(id);
  const esc = (v) =>
    String(v ?? "").replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  const icon = (n) =>
    `<img class="admin-icon" src="../assets/images/icons/${n}.svg" alt="" width="18" height="18" aria-hidden="true">`;
  const fmt = (n) => new Intl.NumberFormat("vi-VN").format(Number(n) || 0);
  const day = (v) =>
    v
      ? new Intl.DateTimeFormat("vi-VN", {
          day: "2-digit",
          month: "2-digit",
          year: "numeric",
          timeZone: "Asia/Ho_Chi_Minh",
        }).format(new Date(v.length === 10 ? v + "T12:00:00+07:00" : v))
      : "—";
  const stamp = (v) =>
    new Intl.DateTimeFormat("vi-VN", {
      dateStyle: "short",
      timeStyle: "short",
      timeZone: "Asia/Ho_Chi_Minh",
    }).format(new Date(v));
  const jobLabel = (j) => j?.title || "Tin không còn trong demo",
    company = (code) => (code === "tg-apparel" ? "TG APPAREL" : "TEX-GIANG");
  const labels = {
    ...D.states,
    published: "Đang tuyển",
    draft: "Bản nháp",
    closed: "Đã đóng",
    expired: "Hết hạn",
  };
  const badge = (s) =>
    `<span class="admin-badge is-${esc(s)}">${esc(labels[s] || s)}</span>`;
  const avatar = (c) =>
    `<span class="candidate-initials initial-${esc(c.id)}" aria-hidden="true">${esc(
      c.name
        .trim()
        .split(/\s+/)
        .slice(-2)
        .map((w) => w[0])
        .join(""),
    )}</span>`;
  const val = (id) => $(id)?.value.trim() || "",
    set = (id, v) => {
      if ($(id)) $(id).value = v ?? "";
    };
  const empty = (title, text, link = "") =>
    `<div class="admin-empty"><h2>${esc(title)}</h2><p>${esc(text)}</p>${link}</div>`;
  const notify = (message, type = "success") => showToast(message, type);
  let dirty = false,
    watched = null,
    baseline = "",
    confirmCallback = null,
    openers = new WeakMap(),
    activeCandidate = "",
    listPage = 1,
    media = null,
    mediaEpoch = 0;
  function errors(form, messages) {
    form
      .querySelectorAll("[aria-invalid]")
      .forEach((e) => e.removeAttribute("aria-invalid"));
    form.querySelectorAll(".field-error").forEach((e) => (e.textContent = ""));
    for (const [id, msg] of Object.entries(messages)) {
      if ($(id)) {
        $(id).setAttribute("aria-invalid", "true");
        $(id).setAttribute("aria-describedby", id + "-error");
      }
      if ($(id + "-error")) $(id + "-error").textContent = msg;
    }
    const summary = form.querySelector(".form-errors");
    if (summary)
      summary.textContent = Object.keys(messages).length
        ? "Kiểm tra các mục được đánh dấu trước khi lưu."
        : "";
    $(Object.keys(messages)[0])?.focus();
    return !Object.keys(messages).length;
  }
  function validate(form) {
    const messages = {};
    for (const e of form.querySelectorAll("input,select,textarea")) {
      if (e.disabled || ["hidden", "file", "checkbox"].includes(e.type))
        continue;
      if (!e.validity.valid)
        messages[e.id] = e.validity.valueMissing
          ? "Vui lòng nhập thông tin này."
          : e.type === "email"
            ? "Địa chỉ email chưa hợp lệ."
            : e.type === "url"
              ? "Nhập đường dẫn https:// hợp lệ."
              : "Giá trị chưa nằm trong giới hạn cho phép.";
      if (e.required && !e.value.trim())
        messages[e.id] = "Thông tin này không được để trống.";
    }
    return messages;
  }
  const snapshot = (form) =>
    JSON.stringify(
      [...form.querySelectorAll("input:not([type=file]),select,textarea")]
        .map((e) => [e.id, e.type === "checkbox" ? e.checked : e.value])
        .concat(form.id === "banner-form" ? [["media", media]] : []),
    );
  function syncDirty() {
    if (!watched) return;
    dirty = snapshot(watched) !== baseline;
    document
      .querySelectorAll("[data-save],[data-save-draft]")
      .forEach((b) => (b.disabled = !dirty));
    document
      .querySelectorAll("[data-save-state]")
      .forEach(
        (e) =>
          (e.textContent = dirty
            ? "Có thay đổi chưa lưu"
            : "Đã đồng bộ với phiên demo"),
      );
  }
  function watch(form) {
    if (!form) return;
    watched = form;
    baseline = snapshot(form);
    dirty = false;
    form.addEventListener("input", syncDirty);
    form.addEventListener("change", syncDirty);
    syncDirty();
  }
  function navCount() {
    document
      .querySelectorAll("[data-new-count]")
      .forEach(
        (e) =>
          (e.textContent = D.snapshot().candidates.filter(
            (c) => c.status === "new",
          ).length),
      );
  }
  function saved() {
    if (watched) baseline = snapshot(watched);
    syncDirty();
    navCount();
  }
  function open(id) {
    const dialog = $(id);
    openers.set(dialog, document.activeElement);
    dialog.showModal();
  }
  function close(dialog) {
    dialog.close();
    openers.get(dialog)?.focus();
  }
  function confirm(message, callback) {
    confirmCallback = callback;
    $("confirm-message").textContent = message;
    open("admin-confirm");
  }
  async function commit(form, action) {
    if (form?.getAttribute("aria-busy") === "true") return;
    form?.setAttribute("aria-busy", "true");
    const buttons = [
      ...document.querySelectorAll("button[type=submit]"),
    ].filter((b) => b.form === form);
    buttons.forEach((b) => {
      b.disabled = true;
      b.setAttribute("aria-busy", "true");
    });
    try {
      await new Promise((r) => setTimeout(r, 120));
      action();
    } catch (_) {
      const summary = form?.querySelector(".form-errors");
      if (summary)
        summary.textContent =
          "Chưa lưu được phiên demo. Trình duyệt có thể đầy bộ nhớ hoặc chặn lưu trữ. Thông tin đang nhập vẫn còn.";
      notify("Chưa lưu được dữ liệu. Vui lòng thử lại.", "error");
    } finally {
      form?.removeAttribute("aria-busy");
      buttons.forEach((b) => {
        b.disabled = false;
        b.removeAttribute("aria-busy");
      });
      if (form === watched) syncDirty();
    }
  }
  function title(t) {
    $("admin-page-title").textContent = t;
    document.title = t + " | TEX-GIANG HR";
  }
  function query(values) {
    const q = new URLSearchParams();
    for (const [k, v] of Object.entries(values)) if (v) q.set(k, v);
    try {
      history.replaceState(
        null,
        "",
        location.pathname + (q.size ? "?" + q : ""),
      );
    } catch (_) {}
  }
  function filters(kind) {
    return kind === "candidate"
      ? {
          keyword: val("admin-candidate-search"),
          status: val("admin-status-filter"),
          job: val("admin-position-filter"),
          company: val("admin-company-filter"),
        }
      : {
          keyword: val("admin-job-search"),
          status: val("admin-job-status"),
          company: val("admin-job-company"),
        };
  }
  function candidateRows(ignoreStatus = false) {
    const f = filters("candidate"),
      s = D.snapshot();
    return s.candidates.filter((c) => {
      const j = s.jobs.find((j) => j.id === c.jobId);
      return (
        (!f.keyword ||
          (c.name + " " + c.phone)
            .toLocaleLowerCase("vi")
            .includes(f.keyword.toLocaleLowerCase("vi"))) &&
        (!f.status || ignoreStatus || c.status === f.status) &&
        (!f.job || c.jobId === f.job) &&
        (!f.company || j?.companyCode === f.company)
      );
    });
  }
  function jobRows(ignoreStatus = false) {
    const f = filters("job");
    return D.snapshot().jobs.filter(
      (j) =>
        (!f.keyword ||
          (j.title + " " + j.id)
            .toLocaleLowerCase("vi")
            .includes(f.keyword.toLocaleLowerCase("vi"))) &&
        (!f.status || ignoreStatus || D.jobStatus(j) === f.status) &&
        (!f.company || j.companyCode === f.company),
    );
  }
  function tabs(id, states, rows, statusOf, selected, kind) {
    $(id).innerHTML = [["", "Tất cả"], ...states]
      .map(
        ([key, label]) =>
          `<button type="button" aria-pressed="${key === selected}" data-filter-tab="${key}" data-filter-kind="${kind}">${label}<span class="tab-count">${rows.filter((r) => !key || statusOf(r) === key).length}</span></button>`,
      )
      .join("");
  }
  function pager(id, total) {
    const size = Number(D.snapshot().settings.pageSize) || 6,
      pages = Math.max(1, Math.ceil(total / size));
    listPage = Math.min(listPage, pages);
    const start = (listPage - 1) * size;
    $(id).innerHTML =
      `<p role="status">${total ? `Hiển thị ${start + 1}–${Math.min(start + size, total)} trong ${total}` : "0"} ${id.startsWith("candidate") ? "hồ sơ" : "tin tuyển dụng"}</p>${pages > 1 ? `<nav class="pagination" aria-label="Phân trang"><button type="button" data-page="${listPage - 1}" ${listPage === 1 ? "disabled" : ""}>Trước</button>${Array.from({ length: pages }, (_, i) => `<button type="button" data-page="${i + 1}" ${listPage === i + 1 ? 'aria-current="page"' : ""}>${i + 1}</button>`).join("")}<button type="button" data-page="${listPage + 1}" ${listPage === pages ? "disabled" : ""}>Sau</button></nav>` : ""}`;
    return [start, start + size];
  }
  const candidateLink = (c) =>
    "application-detail.html?" +
    new URLSearchParams({
      id: c.id,
      return: "applications.html" + location.search,
    });
  function renderCandidates() {
    const rows = candidateRows(),
      s = D.snapshot(),
      f = filters("candidate");
    query(f);
    tabs(
      "candidate-tabs",
      [
        ["new", "Mới nhận"],
        ["interview", "Hẹn phỏng vấn"],
        ["hired", "Trúng tuyển"],
      ],
      candidateRows(true),
      (c) => c.status,
      f.status,
      "candidate",
    );
    const [a, b] = pager("candidate-pagination", rows.length);
    $("candidate-list").innerHTML = rows.length
      ? `<div class="table-wrap"><table class="admin-table responsive-table"><thead><tr><th>Ứng viên</th><th>Vị trí ứng tuyển</th><th>Liên hệ</th><th>Trạng thái</th><th class="num">Thao tác</th></tr></thead><tbody id="candidate-table-body">${rows
          .slice(a, b)
          .map((c) => {
            const j = s.jobs.find((j) => j.id === c.jobId);
            return `<tr data-candidate-id="${c.id}"><td><div class="identity">${avatar(c)}<div><a class="row-title" href="${esc(candidateLink(c))}">${esc(c.name)}</a><small>Nộp ${day(c.submitted)}</small></div></div></td><td data-label="Vị trí">${esc(jobLabel(j))}<span class="cell-sub">${esc(j?.company || "—")}</span></td><td data-label="Điện thoại"><a class="row-title nowrap" href="tel:${esc(c.phone)}">${esc(c.phone.replace(/(\d{4})(\d{3})(\d{3})/, "$1 $2 $3"))}</a><span class="cell-sub">${esc(c.source)}</span></td><td data-label="Quy trình">${badge(c.status)}</td><td><div class="row-actions"><a class="tg-link" href="${esc(candidateLink(c))}">Chi tiết</a><button type="button" class="tg-btn tg-btn--ghost" data-status-candidate="${c.id}">Cập nhật</button></div></td></tr>`;
          })
          .join("")}</tbody></table></div>`
      : empty(
          "Không có hồ sơ phù hợp",
          "Thử thay đổi tên, trạng thái, vị trí hoặc đơn vị.",
          '<button class="tg-btn tg-btn--ghost" type="button" data-reset-filters>Xoá bộ lọc</button>',
        );
  }
  const salary = (j) =>
    j.salaryMin
      ? `${fmt(j.salaryMin)} – ${fmt(j.salaryMax || j.salaryMin)} đ`
      : j.salary || "Thoả thuận";
  function renderJobs() {
    const rows = jobRows(),
      s = D.snapshot(),
      f = filters("job");
    query(f);
    tabs(
      "job-tabs",
      [
        ["published", "Đang tuyển"],
        ["draft", "Bản nháp"],
        ["expired", "Hết hạn"],
        ["closed", "Đã đóng"],
      ],
      jobRows(true),
      D.jobStatus,
      f.status,
      "job",
    );
    const [a, b] = pager("job-pagination", rows.length);
    $("job-list").innerHTML = rows.length
      ? `<div class="table-wrap"><table class="admin-table responsive-table"><thead><tr><th>Vị trí tuyển dụng</th><th>Thu nhập / tháng</th><th>Hạn nộp</th><th class="num">Hồ sơ</th><th>Trạng thái</th><th class="num">Thao tác</th></tr></thead><tbody>${rows
          .slice(a, b)
          .map(
            (j) =>
              `<tr data-job-id="${esc(j.id)}"><td><a class="row-title" href="job-form.html?id=${encodeURIComponent(j.id)}">${esc(j.title)}</a><span class="cell-sub">${esc(j.id)} · ${esc(j.company)}${j.urgent ? " · Ưu tiên tuyển gấp" : ""}</span></td><td data-label="Thu nhập">${esc(salary(j))}</td><td data-label="Hạn nộp" class="nowrap">${day(j.deadlineISO)}</td><td class="num" data-label="Hồ sơ"><a class="row-title" href="applications.html?job=${encodeURIComponent(j.id)}">${s.candidates.filter((c) => c.jobId === j.id).length}</a></td><td data-label="Vòng đời">${badge(D.jobStatus(j))}</td><td><div class="row-actions"><a class="tg-link" href="job-form.html?id=${encodeURIComponent(j.id)}">Sửa tin</a><details class="row-menu"><summary aria-label="Thao tác ${esc(j.title)}">⋯</summary><div><button type="button" data-view-job="${esc(j.id)}">Xem trước bản demo</button><button type="button" data-toggle-job="${esc(j.id)}">${j.state === "closed" ? "Mở lại tin" : "Đóng tuyển dụng"}</button><button class="danger" type="button" data-delete-job="${esc(j.id)}">Xoá tin demo</button></div></details></div></td></tr>`,
          )
          .join("")}</tbody></table></div>`
      : empty(
          "Chưa có tin phù hợp",
          "Thay đổi điều kiện lọc hoặc tạo tin tuyển dụng mới.",
          '<a class="tg-btn tg-btn--primary" href="job-form.html">Đăng tin mới</a>',
        );
  }
  function initList(kind) {
    const s = D.snapshot(),
      params = new URLSearchParams(location.search),
      map =
        kind === "candidate"
          ? {
              keyword: "admin-candidate-search",
              status: "admin-status-filter",
              job: "admin-position-filter",
              company: "admin-company-filter",
            }
          : {
              keyword: "admin-job-search",
              status: "admin-job-status",
              company: "admin-job-company",
            };
    if (kind === "candidate")
      $("admin-position-filter").innerHTML =
        '<option value="">Tất cả vị trí</option>' +
        s.jobs
          .map((j) => `<option value="${esc(j.id)}">${esc(j.title)}</option>`)
          .join("");
    for (const [k, id] of Object.entries(map)) {
      const v = params.get(k) || "";
      if (
        $(id).tagName === "INPUT" ||
        [...$(id).options].some((o) => o.value === v)
      )
        set(id, v);
    }
    document
      .querySelectorAll(".admin-toolbar input,.admin-toolbar select")
      .forEach((e) =>
        e.addEventListener(e.tagName === "INPUT" ? "input" : "change", () => {
          listPage = 1;
          kind === "candidate" ? renderCandidates() : renderJobs();
        }),
      );
    kind === "candidate" ? renderCandidates() : renderJobs();
  }
  function bar(label, n, total) {
    return `<div><div class="bar-label"><span>${esc(label)}</span><span class="num">${n} · ${total ? Math.round((n / total) * 100) : 0}%</span></div><div class="bar-track" role="img" aria-label="${esc(label)}: ${n} trong ${total} hồ sơ"><span style="width:${total ? (n / total) * 100 : 0}%"></span></div></div>`;
  }
  function renderDashboard() {
    const s = D.snapshot(),
      scope = val("dashboard-company"),
      jobs = s.jobs.filter((j) => !scope || j.companyCode === scope),
      cs = s.candidates.filter((c) => jobs.some((j) => j.id === c.jobId)),
      queue = cs
        .filter((c) => ["new", "contacted", "interview"].includes(c.status))
        .sort(
          (a, b) =>
            ({ new: 0, interview: 1, contacted: 2 })[a.status] -
            { new: 0, interview: 1, contacted: 2 }[b.status],
        ),
      active = jobs.filter((j) => D.jobStatus(j) === "published"),
      hired = cs.filter((c) => c.status === "hired").length;
    $("dashboard-kpis").innerHTML = [
      ["Cần xử lý", queue.length, "hồ sơ đang trong quy trình"],
      ["Tin đang tuyển", active.length, `trong ${jobs.length} tin demo`],
      ["Hồ sơ tiếp nhận", cs.length, "theo đơn vị đang chọn"],
      [
        "Trúng tuyển",
        hired,
        cs.length
          ? `${((hired / cs.length) * 100).toLocaleString("vi-VN", { maximumFractionDigits: 1 })}% tổng hồ sơ`
          : "Chưa có hồ sơ",
      ],
    ]
      .map(
        ([label, n, note]) =>
          `<div><span>${label}</span><strong>${n}</strong><small>${note}</small></div>`,
      )
      .join("");
    $("queue-subtitle").textContent =
      `${queue.length} hồ sơ cần tiếp tục xử lý · dữ liệu demo`;
    $("dashboard-queue").innerHTML = queue.length
      ? queue
          .map(
            (c) =>
              `<li>${avatar(c)}<div><a class="row-title" href="application-detail.html?id=${c.id}">${esc(c.name)}</a><p>${esc(jobLabel(jobs.find((j) => j.id === c.jobId)))}</p>${badge(c.status)}</div><a class="tg-link" href="application-detail.html?id=${c.id}">${c.status === "new" ? "Xem hồ sơ" : "Tiếp tục"} ${icon("arrow-right")}</a></li>`,
          )
          .join("")
      : "<li>Không có hồ sơ chờ xử lý trong phạm vi này.</li>";
    const until = new Date(
        new Date(D.date() + "T12:00:00+07:00").getTime() + 30 * 86400000,
      )
        .toISOString()
        .slice(0, 10),
      expiring = active
        .filter((j) => j.deadlineISO <= until)
        .sort((a, b) => a.deadlineISO.localeCompare(b.deadlineISO))
        .slice(0, 3);
    $("dashboard-expiring").innerHTML = expiring.length
      ? expiring
          .map(
            (j) =>
              `<li><div><a class="row-title" href="job-form.html?id=${j.id}">${esc(j.title)}</a><p>Hạn ${day(j.deadlineISO)}</p></div><a class="tg-link" href="job-form.html?id=${j.id}">Sửa</a></li>`,
          )
          .join("")
      : "<li>Không có tin hết hạn trong 30 ngày tới.</li>";
    const appointments = cs.filter(
      (c) =>
        c.appointment &&
        c.status === "interview" &&
        c.appointment.slice(0, 10) >= D.date(),
    );
    $("dashboard-appointments").innerHTML = appointments.length
      ? appointments
          .map(
            (c) =>
              `<li><div><a class="row-title" href="application-detail.html?id=${c.id}">${esc(c.name)}</a><p>${stamp(c.appointment)}</p><small>Lịch hẹn minh hoạ</small></div></li>`,
          )
          .join("")
      : "<li>Chưa có lịch hẹn sắp tới.</li>";
    $("dashboard-status-bars").innerHTML = Object.entries(D.states)
      .map(([k, v]) =>
        bar(v, cs.filter((c) => c.status === k).length, cs.length),
      )
      .join("");
    $("dashboard-sources").innerHTML = ["Website", "Zalo / QR", "Giới thiệu"]
      .map((v) => bar(v, cs.filter((c) => c.source === v).length, cs.length))
      .join("");
  }
  function renderDetail() {
    const params = new URLSearchParams(location.search),
      c = D.candidate(params.get("id")),
      back = params.get("return");
    if (back && /^applications\.html(?:\?[^#]{0,1000})?$/.test(back))
      $("back-to-candidates").href = back;
    if (!c) {
      title("Không tìm thấy hồ sơ");
      $("candidate-detail").innerHTML = empty(
        "Hồ sơ không có trong phiên demo",
        "Quay lại danh sách để chọn hồ sơ còn tồn tại.",
        '<a class="tg-btn tg-btn--primary" href="applications.html">Danh sách hồ sơ</a>',
      );
      return;
    }
    const j = D.job(c.jobId);
    title(c.name);
    $("candidate-detail").innerHTML =
      `<div class="detail-grid"><div class="form-stack"><section class="admin-panel"><div class="person-head">${avatar(c)}<div><h2>${esc(jobLabel(j))}</h2><p class="form-hint">${esc(j?.company || "—")} · Nộp ${stamp(c.submitted)}</p></div>${badge(c.status)}</div><dl class="key-values"><div><dt>Điện thoại</dt><dd><a class="row-title" href="tel:${esc(c.phone)}">${esc(c.phone)}</a></dd></div><div><dt>Email</dt><dd>${c.email ? `<a class="row-title" href="mailto:${esc(c.email)}">${esc(c.email)}</a>` : "—"}</dd></div><div><dt>Năm sinh</dt><dd>${c.birth}</dd></div><div><dt>Nguồn hồ sơ</dt><dd>${esc(c.source)}</dd></div><div class="span-all"><dt>Địa chỉ</dt><dd>${esc(c.address)}</dd></div></dl><h3 style="margin-top:24px">Lời nhắn ứng viên</h3><p style="margin-top:8px">${esc(c.message)}</p><button type="button" class="tg-btn tg-btn--ghost" data-download-candidate="${c.id}" style="margin-top:18px">${icon("file-earmark-text")}Tải hồ sơ mẫu (.txt)</button><p class="form-hint">Tệp văn bản minh hoạ, không phải CV hoặc CCCD thật.</p></section><section class="admin-panel"><div class="admin-panel-head"><h2>Lịch sử xử lý</h2><span class="admin-badge">${c.history.length} cập nhật</span></div><ol class="timeline">${[
        ...c.history,
      ]
        .reverse()
        .map(
          (h) =>
            `<li><strong>${esc(D.states[h.status] || h.status)}</strong><p>${esc(h.note)}</p><time>${stamp(h.at)}</time></li>`,
        )
        .join(
          "",
        )}</ol></section></div><aside class="form-stack"><section class="admin-panel"><h2>Bước tiếp theo</h2><form id="candidate-detail-form" novalidate style="margin-top:20px"><div class="form-errors" role="alert"></div><label class="form-label" for="detail-status">Trạng thái xử lý</label><select id="detail-status" class="form-input">${Object.entries(
        D.states,
      )
        .map(
          ([k, v]) =>
            `<option value="${k}" ${k === c.status ? "selected" : ""}>${v}</option>`,
        )
        .join(
          "",
        )}</select><label class="form-label" for="detail-note" style="margin-top:18px">Ghi chú nội bộ</label><textarea class="form-input" id="detail-note" rows="5" maxlength="2000">${esc(c.note)}</textarea><p class="field-error" id="detail-note-error"></p><button class="tg-btn tg-btn--primary" type="submit" data-save disabled style="margin-top:18px;width:100%">Lưu thay đổi</button><p class="save-state" data-save-state></p></form></section><section class="admin-panel"><h2>Liên hệ nhanh</h2><a class="tg-btn tg-btn--ghost" href="tel:${esc(c.phone)}" style="margin-top:18px;width:100%">${icon("telephone")}Gọi ứng viên</a><p class="form-hint" style="margin-top:12px">Số điện thoại trong dữ liệu demo.</p></section></aside></div>`;
    watch($("candidate-detail-form"));
  }
  function openStatus(id) {
    const c = D.candidate(id);
    if (!c) return;
    activeCandidate = c.id;
    set("modal-candidate-id", c.id);
    $("modal-candidate-name").textContent = c.name;
    set("modal-status-select", c.status);
    set("modal-note", c.note);
    $("candidate-status-form").querySelector(".form-errors").textContent = "";
    open("status-update-modal");
  }
  function updateCandidate(id, status, note) {
    if (!D.states[status] || note.length > 2000) throw Error("Invalid");
    D.update((s) => {
      const c = s.candidates.find((c) => c.id === id);
      if (!c) throw Error("Missing");
      if (c.status === status && c.note === note) return;
      c.status = status;
      c.note = note;
      c.history.push({
        at: new Date().toISOString(),
        status,
        note: note || "Cập nhật trạng thái trong phiên demo.",
      });
      c.history = c.history.slice(-100);
    });
  }
  const jobFields = {
    "job-title": "title",
    "job-company": "companyCode",
    "job-department": "departmentCode",
    "job-salary-min": "salaryMin",
    "job-salary-max": "salaryMax",
    "job-quantity": "quantity",
    "job-deadline": "deadlineISO",
    "job-location": "location",
    "job-description": "description",
    "job-requirements": "requirements",
    "job-benefits": "benefits",
    "job-status": "state",
  };
  function initJob() {
    const id = new URLSearchParams(location.search).get("id"),
      j = id ? D.job(id) : null;
    if (id && !j) {
      title("Không tìm thấy tin");
      $("job-editor-form").outerHTML = empty(
        "Tin không có trong phiên demo",
        "Tin có thể đã bị xoá.",
        '<a class="tg-btn tg-btn--primary" href="jobs.html">Quản lý tin</a>',
      );
      return;
    }
    if (j) {
      title("Chỉnh sửa tin tuyển dụng");
      for (const [id, key] of Object.entries(jobFields)) set(id, j[key]);
      $("job-urgent").checked = j.urgent;
      $("job-public-link").href =
        "../job-detail.html?id=" + encodeURIComponent(j.id);
    } else {
      set("job-company", "tex-giang");
      set("job-quantity", 1);
      set("job-status", "draft");
    }
    watch($("job-editor-form"));
  }
  function readJob() {
    const old = D.job(new URLSearchParams(location.search).get("id")),
      j = { ...old };
    for (const [id, key] of Object.entries(jobFields)) j[key] = val(id);
    for (const key of ["salaryMin", "salaryMax", "quantity"])
      j[key] = Number(j[key]) || 0;
    j.urgent = $("job-urgent").checked;
    j.company = company(j.companyCode);
    j.department = $("job-department").selectedOptions[0].textContent;
    j.locationCode = old?.locationCode || "tan-huong";
    j.date = old?.date || day(D.date());
    j.status = j.state === "closed" ? "closed" : j.urgent ? "urgent" : "active";
    j.salary = j.salaryMin
      ? `${fmt(j.salaryMin)} - ${fmt(j.salaryMax || j.salaryMin)} VNĐ`
      : "Thoả thuận";
    j.deadline = j.deadlineISO ? day(j.deadlineISO) : "";
    j.tags = [
      j.state === "closed"
        ? "Đã đóng tuyển"
        : j.urgent
          ? "Tuyển gấp"
          : "Đang tuyển",
    ];
    return j;
  }
  const jobPreview = (j) =>
    `<p class="form-hint">Bản xem trước · chưa xuất bản lên website</p><h2 style="font-size:26px;margin-top:12px">${esc(j.title || "Chức danh tuyển dụng")}</h2><p>${esc(j.company)} · ${esc(j.location)}</p><p><strong>${esc(salary(j))}</strong> / tháng · ${j.quantity || "—"} người</p><p>Hạn nộp: ${j.deadlineISO ? day(j.deadlineISO) : "Chưa chọn"}</p>${[
      ["Mô tả công việc", j.description],
      ["Yêu cầu", j.requirements],
      ["Quyền lợi", j.benefits],
    ]
      .map(([t, v]) => `<h3>${t}</h3><p>${esc(v || "Chưa có nội dung")}</p>`)
      .join("")}`;
  function preview(t, body) {
    $("preview-title").textContent = t;
    $("admin-preview-body").innerHTML = body;
    open("admin-preview");
  }
  async function saveJob(draft = false) {
    const form = $("job-editor-form"),
      j = readJob(),
      messages = draft ? {} : validate(form);
    if (!j.title) messages["job-title"] = "Nhập chức danh trước khi lưu.";
    if (!Number.isInteger(j.quantity) || j.quantity < 1 || j.quantity > 10000)
      messages["job-quantity"] = "Số lượng cần là số nguyên từ 1 đến 10.000.";
    for (const [key, id] of [["salaryMin", "job-salary-min"], ["salaryMax", "job-salary-max"]])
      if (j[key] < 0 || j[key] > 1000000000)
        messages[id] = "Thu nhập phải từ 0 đến 1.000.000.000 đồng.";
    if (j.salaryMax && j.salaryMax < j.salaryMin)
      messages["job-salary-max"] =
        "Mức đến phải lớn hơn hoặc bằng mức bắt đầu.";
    if (j.state === "published" && !draft && j.deadlineISO < D.date())
      messages["job-deadline"] = "Chọn hạn từ hôm nay để mở tuyển dụng.";
    if (!errors(form, messages)) return;
    await commit(form, () => {
      if (draft) j.state = "draft";
      D.update((s) => {
        if (!j.id) {
          let n = s.jobs.length + 1;
          while (
            s.jobs.some((x) => x.id === `TG-${String(n).padStart(2, "0")}`)
          )
            n++;
          j.id = `TG-${String(n).padStart(2, "0")}`;
        }
        const i = s.jobs.findIndex((x) => x.id === j.id);
        i < 0 ? s.jobs.push(j) : s.jobs.splice(i, 1, j);
      });
      set("job-status", j.state);
      try {
        history.replaceState(null, "", "?id=" + encodeURIComponent(j.id));
      } catch (_) {}
      saved();
      title("Chỉnh sửa tin tuyển dụng");
      notify(
        draft
          ? "Đã lưu bản nháp trong phiên demo."
          : "Đã lưu tin trong phiên demo.",
      );
    });
  }
  const contentFields = {
      "cms-slogan": "slogan",
      "cms-philosophy": "philosophy",
      "cms-hero": "hero",
      "cms-about": "about",
      "cms-benefits": "benefits",
      "cms-hotline": "hotline",
      "cms-zalo": "zalo",
      "cms-email": "email",
      "cms-fanpage": "fanpage",
    },
    settingsFields = {
      "set-site-name": "siteName",
      "set-legal-name": "legalName",
      "set-address": "address",
      "set-seo-title": "seoTitle",
      "set-seo-desc": "seoDescription",
      "set-page-size": "pageSize",
      "set-apply-email": "applyEmail",
    };
  const readMapped = (map) =>
    Object.fromEntries(Object.entries(map).map(([id, key]) => [key, val(id)]));
  const fillMapped = (map, source) => {
    for (const [id, key] of Object.entries(map)) set(id, source[key]);
  };
  function setContentTab(key) {
    if (!["home", "about", "posts", "contact"].includes(key)) key = "home";
    document
      .querySelectorAll("[data-content-tab]")
      .forEach((b) =>
        b.setAttribute("aria-pressed", String(b.dataset.contentTab === key)),
      );
    document
      .querySelectorAll("[data-content-panel]")
      .forEach((e) => (e.hidden = e.dataset.contentPanel !== key));
    $("content-preview").dataset.tab = key;
    contentPreview();
  }
  function contentPreview() {
    const c = readMapped(contentFields),
      tab = $("content-preview").dataset.tab,
      parts =
        tab === "about"
          ? [
              ["Giới thiệu", c.about],
              ["Phúc lợi", c.benefits],
            ]
          : tab === "contact"
            ? [
                ["Hotline", c.hotline],
                ["Zalo", c.zalo],
                ["Email", c.email],
              ]
            : tab === "posts"
              ? [["Tin tức", "Chọn bài viết để xem trước nội dung đang soạn."]]
              : [
                  [c.slogan, c.hero],
                  ["Triết lý", c.philosophy],
                ];
    $("content-preview").innerHTML = parts
      .map(([t, v]) => `<h3>${esc(t)}</h3><p>${esc(v)}</p>`)
      .join("");
  }
  function renderPosts() {
    $("cms-post-list").innerHTML =
      `<table class="admin-table responsive-table"><thead><tr><th>Bài viết</th><th>Trạng thái</th><th class="num">Thao tác</th></tr></thead><tbody>${D.snapshot()
        .posts.map(
          (p) =>
            `<tr><td><strong style="font-weight:500">${esc(p.title)}</strong><span class="cell-sub">${esc(p.category)}</span></td><td data-label="Xuất bản">${badge(p.state)}</td><td><button type="button" class="tg-link" data-edit-post="${esc(p.id)}">Soạn bài</button></td></tr>`,
        )
        .join("")}</tbody></table>`;
  }
  function editPost(id) {
    const p = D.snapshot().posts.find((p) => p.id === id);
    set("post-id", p?.id || "");
    set("post-title", p?.title || "");
    set("post-category", p?.category || "Tuyển dụng");
    set("post-body", p?.body || "");
    set("post-state", p?.state || "draft");
    $("post-editor-form").querySelector(".form-errors").textContent = "";
    open("post-editor-dialog");
  }
  function renderSEO() {
    const c = readMapped(settingsFields);
    $("seo-title-count").textContent =
      `${c.seoTitle.length} ký tự · nên ngắn gọn và rõ vị trí tuyển dụng`;
    $("seo-description-count").textContent = `${c.seoDescription.length} ký tự`;
    $("seo-preview").innerHTML =
      `<span>tex-giang.com.vn</span><strong>${esc(c.seoTitle || "Tiêu đề trang")}</strong><p>${esc(c.seoDescription || "Mô tả website tuyển dụng")}</p>`;
  }
  function initBanner() {
    media = structuredClone(D.snapshot().banner);
    set("banner-alt", media.alt);
    set("banner-focal", media.focal);
    set("banner-notice-text", media.notice);
    set("banner-notice-link", media.link);
    set("banner-notice-state", media.enabled ? "on" : "off");
    renderBanner();
    $("banner-form").addEventListener("input", renderBanner);
    watch($("banner-form"));
  }
  function renderBanner() {
    const src =
      /^(?:\.\.\/assets\/images\/[\w/.-]+|data:image\/webp;base64,[A-Za-z0-9+/=]+)$/.test(
        media.src,
      )
        ? media.src
        : "../assets/images/banner-texgiang.webp";
    $("banner-preview-image").src = src;
    $("banner-preview-image").alt = val("banner-alt");
    $("banner-preview-image").style.objectPosition =
      val("banner-focal") + "% center";
    $("banner-metadata").textContent =
      `${media.width} × ${media.height} px · ${Math.round(media.bytes / 1024)} KB · WebP`;
    $("notice-preview").textContent =
      val("banner-notice-state") === "on"
        ? val("banner-notice-text")
        : "Thông báo đang tắt";
  }
  async function loadBanner(file) {
    const epoch = ++mediaEpoch;
    $("banner-file-error").textContent = "";
    if (!file) return;
    try {
      if (
        !["image/jpeg", "image/png", "image/webp"].includes(file.type) ||
        file.size > 5 * 1024 * 1024
      )
        throw Error("Chọn JPG, PNG hoặc WebP tối đa 5 MB.");
      const bitmap = await createImageBitmap(file);
      try {
        if (
          bitmap.width * bitmap.height > 20000000 ||
          bitmap.width < 320 ||
          bitmap.height < 120
        )
          throw Error(
            "Ảnh cần rộng ít nhất 320 px và không vượt quá 20 triệu pixel.",
          );
        const scale = Math.min(1, 2048 / bitmap.width),
          canvas = document.createElement("canvas");
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas
          .getContext("2d")
          .drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        const src = canvas.toDataURL("image/webp", 0.86);
        if (src.length > 1200000)
          throw Error("Ảnh còn quá lớn cho phiên demo. Chọn ảnh nhẹ hơn.");
        if (epoch !== mediaEpoch) return;
        media = {
          ...media,
          src,
          width: canvas.width,
          height: canvas.height,
          bytes: Math.floor((src.length - src.indexOf(",") - 1) * 0.75),
        };
        renderBanner();
        syncDirty();
      } finally {
        bitmap.close();
      }
    } catch (error) {
      if (epoch === mediaEpoch)
        $("banner-file-error").textContent = error.message;
    } finally {
      if (epoch === mediaEpoch) $("banner-file").value = "";
    }
  }
  function download(name, type, text) {
    const url = URL.createObjectURL(new Blob([text], { type })),
      a = document.createElement("a");
    a.href = url;
    a.download = name;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  function exportCSV() {
    const s = D.snapshot(),
      cs = page === "applications" ? candidateRows() : s.candidates,
      cell = (value) => {
        let v = String(value ?? "");
        if (/^[\s\u0000-\u001f]*[=+@-]/.test(v)) v = "'" + v;
        return '"' + v.replaceAll('"', '""') + '"';
      };
    const rows = [
      ["Ứng viên", "Điện thoại", "Vị trí", "Đơn vị", "Trạng thái", "Ngày nộp"],
      ...cs.map((c) => {
        const j = s.jobs.find((j) => j.id === c.jobId);
        return [
          c.name,
          c.phone,
          jobLabel(j),
          j?.company || "",
          D.states[c.status],
          day(c.submitted),
        ];
      }),
    ];
    download(
      "ho-so-demo.csv",
      "text/csv;charset=utf-8",
      "\uFEFF" + rows.map((row) => row.map(cell).join(",")).join("\r\n"),
    );
    notify(`Đã tạo CSV gồm ${cs.length} hồ sơ trong bộ lọc.`);
  }
  document.addEventListener("DOMContentLoaded", () => {
    navCount();
    if (D.recovered && $("admin-recovery")) {
      $("admin-recovery").hidden = false;
      $("admin-recovery").textContent =
        "Dữ liệu phiên cũ không hợp lệ. Đang hiển thị dữ liệu demo ban đầu.";
    }
    const side = $("admin-sidebar"),
      toggle = $("toggle-sidebar-btn");
    let prior;
    const syncSide = () => {
      if (side) {
        const hidden = innerWidth < 1024 && !side.classList.contains("is-open");
        side.inert = hidden;
        side.setAttribute("aria-hidden", String(hidden));
      }
    };
    const closeSide = () => {
      side?.classList.remove("is-open");
      if ($("sidebar-overlay")) $("sidebar-overlay").hidden = true;
      toggle?.setAttribute("aria-expanded", "false");
      document.body.style.overflow = "";
      syncSide();
      prior?.focus();
    };
    syncSide();
    toggle?.addEventListener("click", () => {
      prior = document.activeElement;
      side.classList.add("is-open");
      syncSide();
      $("sidebar-overlay").hidden = false;
      toggle.setAttribute("aria-expanded", "true");
      document.body.style.overflow = "hidden";
      $("close-sidebar-btn").focus();
    });
    $("close-sidebar-btn")?.addEventListener("click", closeSide);
    $("sidebar-overlay")?.addEventListener("click", closeSide);
    window.addEventListener("resize", () => {
      if (innerWidth >= 1024 && side?.classList.contains("is-open"))
        closeSide();
      syncSide();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        document
          .querySelectorAll("details[open]")
          .forEach((d) => (d.open = false));
        if (side?.classList.contains("is-open")) closeSide();
      }
      if (e.key === "Tab" && side?.classList.contains("is-open")) {
        const items = [...side.querySelectorAll("a,button")].filter(
            (x) => x.getClientRects().length,
          ),
          first = items[0],
          last = items.at(-1);
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    });
    document.querySelectorAll("dialog").forEach((d) => {
      d.addEventListener("close", () => openers.get(d)?.focus());
      d.addEventListener("click", (e) => {
        if (e.target === d) {
          const r = d.getBoundingClientRect();
          if (
            e.clientX < r.left ||
            e.clientX > r.right ||
            e.clientY < r.top ||
            e.clientY > r.bottom
          )
            close(d);
        }
      });
    });
    if (page === "dashboard") {
      renderDashboard();
      $("dashboard-company").addEventListener("change", renderDashboard);
    }
    if (page === "applications") initList("candidate");
    if (page === "jobs") initList("job");
    if (page === "application-detail") renderDetail();
    if (page === "job-form") initJob();
    if (page === "content") {
      fillMapped(contentFields, D.snapshot().content);
      renderPosts();
      setContentTab(new URLSearchParams(location.search).get("tab") || "home");
      $("content-editor-form").addEventListener("input", contentPreview);
      watch($("content-editor-form"));
    }
    if (page === "settings") {
      fillMapped(settingsFields, D.snapshot().settings);
      renderSEO();
      $("settings-form").addEventListener("input", renderSEO);
      watch($("settings-form"));
    }
    if (page === "banners") initBanner();
    if (page === "login") {
      $("toggle-password").addEventListener("click", () => {
        const show = $("login-password").type === "password";
        $("login-password").type = show ? "text" : "password";
        $("toggle-password").textContent = show ? "Ẩn" : "Hiện";
        $("toggle-password").setAttribute(
          "aria-label",
          show ? "Ẩn mật khẩu" : "Hiện mật khẩu",
        );
        $("toggle-password").setAttribute("aria-pressed", String(show));
      });
      $("enter-demo").addEventListener(
        "click",
        () => (location.href = "dashboard.html"),
      );
    }
  });
  document.addEventListener("click", (e) => {
    const b = e.target.closest("button,a");
    if (!b) return;
    if (
      b.matches("a[href]") &&
      dirty &&
      !b.target &&
      !b.href.startsWith("tel:") &&
      !b.href.startsWith("mailto:")
    ) {
      e.preventDefault();
      confirm(
        "Bạn có thay đổi chưa lưu. Rời trang và bỏ các thay đổi này?",
        () => {
          dirty = false;
          location.href = b.href;
        },
      );
      return;
    }
    if (b.hasAttribute("data-close-dialog")) {
      close(b.closest("dialog"));
      return;
    }
    if (b.id === "confirm-action") {
      const action = confirmCallback;
      confirmCallback = null;
      close($("admin-confirm"));
      try {
        action?.();
      } catch (_) {
        notify("Chưa thực hiện được thao tác trong phiên demo.", "error");
      }
      return;
    }
    if (b.hasAttribute("data-reset-demo")) {
      confirm(
        "Đặt lại mọi thay đổi trong phiên demo về dữ liệu ban đầu?",
        () => {
          D.reset();
          dirty = false;
          location.href = "dashboard.html";
        },
      );
      return;
    }
    if (b.hasAttribute("data-export")) {
      exportCSV();
      return;
    }
    if (b.hasAttribute("data-reset-filters")) {
      document
        .querySelectorAll(".admin-toolbar input,.admin-toolbar select")
        .forEach((e) => (e.value = ""));
      listPage = 1;
      page === "applications" ? renderCandidates() : renderJobs();
      return;
    }
    if (b.dataset.filterKind) {
      set(
        b.dataset.filterKind === "candidate"
          ? "admin-status-filter"
          : "admin-job-status",
        b.dataset.filterTab,
      );
      listPage = 1;
      page === "applications" ? renderCandidates() : renderJobs();
      return;
    }
    if (b.dataset.page) {
      listPage = Number(b.dataset.page);
      page === "applications" ? renderCandidates() : renderJobs();
      return;
    }
    if (b.dataset.statusCandidate) {
      openStatus(b.dataset.statusCandidate);
      return;
    }
    if (b.dataset.viewJob) {
      const j = D.job(b.dataset.viewJob);
      if (j) preview("Xem trước tin tuyển dụng", jobPreview(j));
      return;
    }
    if (b.hasAttribute("data-preview-job")) {
      preview("Xem trước nội dung đang soạn", jobPreview(readJob()));
      return;
    }
    if (b.hasAttribute("data-save-draft")) {
      saveJob(true);
      return;
    }
    if (b.dataset.toggleJob) {
      const j = D.job(b.dataset.toggleJob);
      confirm(
        `${j.state === "closed" ? "Mở lại" : "Đóng"} tin “${j.title}” trong phiên demo?`,
        () => {
          D.update((s) => {
            const r = s.jobs.find((x) => x.id === j.id);
            r.state = r.state === "closed" ? "published" : "closed";
          });
          renderJobs();
          notify("Đã cập nhật trạng thái tin trong demo.");
        },
      );
      return;
    }
    if (b.dataset.deleteJob) {
      const j = D.job(b.dataset.deleteJob);
      if (D.snapshot().candidates.some((c) => c.jobId === j.id)) {
        notify(
          "Tin đang có hồ sơ. Bạn có thể đóng tuyển dụng để giữ liên kết hồ sơ.",
          "error",
        );
        return;
      }
      confirm(`Xoá tin “${j.title}” khỏi phiên demo?`, () => {
        D.update((s) => (s.jobs = s.jobs.filter((x) => x.id !== j.id)));
        renderJobs();
        notify("Đã xoá tin khỏi phiên demo.");
      });
      return;
    }
    if (b.dataset.downloadCandidate) {
      const c = D.candidate(b.dataset.downloadCandidate);
      download(
        "ho-so-mau-" + c.id + ".txt",
        "text/plain;charset=utf-8",
        `HỒ SƠ MINH HOẠ — KHÔNG PHẢI CV THẬT\n${c.name}\nVị trí: ${jobLabel(D.job(c.jobId))}\nNguồn: ${c.source}\n${c.message}`,
      );
      return;
    }
    if (b.dataset.contentTab) {
      setContentTab(b.dataset.contentTab);
      return;
    }
    if (b.dataset.editPost || b.hasAttribute("data-new-post")) {
      editPost(b.dataset.editPost);
      return;
    }
    if (b.hasAttribute("data-preview-post")) {
      preview(
        "Xem trước bài viết",
        `<p class="form-hint">Bản demo · ${esc(val("post-category"))}</p><h2>${esc(val("post-title") || "Tiêu đề bài viết")}</h2><p>${esc(val("post-body"))}</p>`,
      );
      return;
    }
    if (b.dataset.mediaMode) {
      $("banner-preview-wrap").classList.toggle(
        "is-phone",
        b.dataset.mediaMode === "phone",
      );
      document
        .querySelectorAll("[data-media-mode]")
        .forEach((e) => e.setAttribute("aria-pressed", String(e === b)));
      return;
    }
    if (b.hasAttribute("data-revert-banner")) {
      mediaEpoch++;
      media = structuredClone(D.snapshot().banner);
      set("banner-alt", media.alt);
      set("banner-focal", media.focal);
      $("banner-file-error").textContent = "";
      renderBanner();
      syncDirty();
    }
  });
  document.addEventListener("change", (e) => {
    if (e.target.id === "banner-file") loadBanner(e.target.files[0]);
  });
  document.addEventListener("submit", async (e) => {
    const form = e.target;
    if (!form.closest(".admin-app")) return;
    e.preventDefault();
    if (form.id === "job-editor-form") {
      saveJob();
      return;
    }
    if (form.id === "admin-login-form") {
      if (errors(form, validate(form)))
        form.querySelector(".form-errors").textContent =
          "Chưa kết nối dịch vụ xác thực. Dùng “Vào bản demo” để xem giao diện; không cần nhập mật khẩu thật.";
      return;
    }
    if (form.id === "candidate-status-form") {
      await commit(form, () => {
        updateCandidate(
          activeCandidate,
          val("modal-status-select"),
          val("modal-note"),
        );
        close($("status-update-modal"));
        renderCandidates();
        notify("Đã cập nhật đúng hồ sơ trong phiên demo.");
      });
      return;
    }
    if (form.id === "candidate-detail-form") {
      if (!errors(form, validate(form))) return;
      await commit(form, () => {
        updateCandidate(
          new URLSearchParams(location.search).get("id"),
          val("detail-status"),
          val("detail-note"),
        );
        saved();
        renderDetail();
        notify("Đã lưu trạng thái và ghi chú trong demo.");
      });
      return;
    }
    if (!errors(form, validate(form))) return;
    if (form.id === "content-editor-form") {
      const c = readMapped(contentFields),
        messages = {};
      for (const key of ["hotline", "zalo"])
        if (c[key] && !/^[+\d\s().-]{8,24}$/.test(c[key]))
          messages["cms-" + key] = "Kiểm tra lại số điện thoại.";
      if (c.fanpage && !D.safeLink(c.fanpage))
        messages["cms-fanpage"] = "Chỉ nhận đường dẫn http hoặc https.";
      if (!errors(form, messages)) return;
      await commit(form, () => {
        D.update((s) => (s.content = c));
        saved();
        notify("Đã lưu nội dung trong phiên demo.");
      });
    }
    if (form.id === "settings-form") {
      const s = readMapped(settingsFields);
      s.pageSize = Number(s.pageSize);
      await commit(form, () => {
        D.update((state) => (state.settings = s));
        saved();
        notify("Đã lưu cài đặt trong phiên demo.");
      });
    }
    if (form.id === "banner-form") {
      const link = val("banner-notice-link");
      if (!/^(?:[a-z0-9-]+\.html(?:\?[^\s]*)?|https:\/\/[^\s]+)$/i.test(link)) {
        errors(form, {
          "banner-notice-link":
            "Dùng đường dẫn trang .html hoặc https:// hợp lệ.",
        });
        return;
      }
      await commit(form, () => {
        D.update(
          (s) =>
            (s.banner = {
              ...media,
              alt: val("banner-alt"),
              focal: Number(val("banner-focal")),
              notice: val("banner-notice-text"),
              link,
              enabled: val("banner-notice-state") === "on",
            }),
        );
        media = structuredClone(D.snapshot().banner);
        saved();
        notify("Đã lưu ảnh và thông báo trong phiên demo.");
      });
    }
    if (form.id === "post-editor-form") {
      await commit(form, () => {
        const p = {
          id: val("post-id") || "post-" + Date.now(),
          title: val("post-title"),
          category: val("post-category"),
          body: val("post-body"),
          state: val("post-state"),
        };
        D.update((s) => {
          const i = s.posts.findIndex((x) => x.id === p.id);
          i < 0 ? s.posts.push(p) : s.posts.splice(i, 1, p);
        });
        close($("post-editor-dialog"));
        renderPosts();
        notify("Đã lưu bài viết trong phiên demo.");
      });
    }
  });
  window.addEventListener("beforeunload", (e) => {
    if (dirty) {
      e.preventDefault();
      e.returnValue = "";
    }
  });
  window.addEventListener("admin-data-change", navCount);
})();
