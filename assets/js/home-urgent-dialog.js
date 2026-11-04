/**
 * TEX-GIANG — DIALOG "TUYỂN GẤP" Ở TRANG CHỦ
 * Tự mở khi vào trang chủ, liệt kê các vị trí đang ưu tiên tuyển theo status
 * (urgent / hot / expiring). Bấm "Ứng tuyển ngay" sẽ mở dialog ứng tuyển có
 * điền sẵn vị trí. Giao diện dựng bằng Tailwind.
 */
(function () {
  'use strict';

  if (!document.body.classList.contains('page-index')) return;
  // jobs-data.js khai báo `const MOCK_JOBS` ở phạm vi global (không nằm trên window).
  if (typeof MOCK_JOBS === 'undefined' || !Array.isArray(MOCK_JOBS)) return;

  var STATUS = {
    urgent: { order: 0, label: 'Tuyển gấp', classes: 'bg-[#FEE4E2] text-[#B42318]' },
    hot: { order: 1, label: 'Nổi bật', classes: 'bg-[#FEF0C7] text-[#B54708]' },
    expiring: { order: 2, label: 'Sắp hết hạn', classes: 'bg-[#F2F4F7] text-[#475467]' }
  };

  var jobs = MOCK_JOBS
    .filter(function (job) { return job && STATUS[job.status]; })
    .sort(function (a, b) {
      var byStatus = STATUS[a.status].order - STATUS[b.status].order;
      return byStatus !== 0 ? byStatus : String(a.deadline).localeCompare(String(b.deadline));
    });
  if (!jobs.length) return;

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function rowMarkup(job) {
    var status = STATUS[job.status];
    return [
      '<article class="rounded-xl border border-line bg-white p-4 transition-shadow hover:shadow-float">',
      '  <div class="flex flex-wrap items-center gap-2">',
      '    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[12px] font-semibold ' + status.classes + '">' + status.label + '</span>',
      '    <span class="text-[13px] text-muted">Hạn nộp: <strong class="font-medium text-body">' + esc(job.deadline) + '</strong></span>',
      '  </div>',
      '  <h3 class="mt-2 text-[17px] font-medium leading-snug text-ink">' + esc(job.title) + '</h3>',
      '  <p class="mt-1 text-[14px] leading-relaxed text-muted">' + esc(job.salary) + ' · ' + esc(job.location) + '</p>',
      '  <div class="mt-3 flex flex-wrap gap-2">',
      '    <button type="button" class="tg-btn tg-btn--primary h-10 px-4 text-[15px]" data-urgent-apply data-job-title="' + esc(job.title) + '">Ứng tuyển ngay</button>',
      '    <a href="job-detail.html?id=' + esc(job.id) + '" class="tg-btn tg-btn--ghost h-10 px-4 text-[15px]">Xem chi tiết</a>',
      '  </div>',
      '</article>'
    ].join('\n');
  }

  var dialogMarkup = [
    '<div class="flex max-h-[min(88vh,780px)] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">',
    '  <header class="relative shrink-0 overflow-hidden bg-gradient-to-br from-[#087A55] via-[#08674A] to-[#0B223D] px-5 py-5 text-white sm:px-7 sm:py-6">',
    '    <span class="pointer-events-none absolute -right-10 -top-16 h-44 w-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>',
    '    <div class="relative flex items-start justify-between gap-4">',
    '      <div class="min-w-0">',
    '        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-white ring-1 ring-inset ring-white/20">',
    '          <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>Tuyển gấp',
    '        </span>',
    '        <h2 id="urgent-title" class="mt-3 text-[22px] font-medium leading-tight sm:text-[26px]">Vị trí đang cần người ngay</h2>',
    '        <p class="mt-1.5 max-w-[46ch] text-[14px] leading-relaxed text-white/80">' + jobs.length + ' vị trí đang được ưu tiên tuyển. Xem nhanh và ứng tuyển trong 2 phút.</p>',
    '      </div>',
    '      <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/10 text-white transition-colors hover:bg-white/20" data-urgent-close aria-label="Đóng thông báo tuyển gấp">',
    '        <img src="assets/images/icons/x-lg.svg" alt="" aria-hidden="true" width="18" height="18" class="invert">',
    '      </button>',
    '    </div>',
    '  </header>',
    '  <div class="flex-1 space-y-3 overflow-y-auto px-4 py-4 sm:px-6">' + jobs.map(rowMarkup).join('') + '</div>',
    '  <footer class="flex shrink-0 items-center justify-between gap-3 border-t border-line bg-surface/70 px-4 py-3 sm:px-6">',
    '    <a href="jobs.html" class="text-[15px] font-medium text-body transition-colors hover:text-accent">Xem tất cả vị trí →</a>',
    '    <button type="button" class="tg-btn tg-btn--ghost h-10 px-4 text-[15px]" data-urgent-close>Để sau</button>',
    '  </footer>',
    '</div>'
  ].join('\n');

  var dialog = document.createElement('dialog');
  dialog.id = 'urgent-dialog';
  dialog.className = 'urgent-dialog';
  dialog.setAttribute('aria-labelledby', 'urgent-title');
  dialog.innerHTML = dialogMarkup;
  document.body.appendChild(dialog);

  function open() {
    if (dialog.open) return;
    document.body.classList.add('has-dialog');
    dialog.showModal();
    var closeButton = dialog.querySelector('[data-urgent-close]');
    if (closeButton) closeButton.focus();
  }

  function close() {
    document.body.classList.remove('has-dialog');
    if (dialog.open) dialog.close();
  }

  dialog.addEventListener('click', function (event) {
    if (event.target.closest('[data-urgent-close]')) { close(); return; }
    var applyButton = event.target.closest('[data-urgent-apply]');
    if (applyButton) {
      var jobTitle = applyButton.getAttribute('data-job-title') || '';
      close();
      window.setTimeout(function () {
        if (window.TGApplyDialog) {
          window.TGApplyDialog.open({ job: jobTitle });
        } else {
          window.location.href = 'apply.html?job=' + encodeURIComponent(jobTitle);
        }
      }, 150);
      return;
    }
    if (event.target === dialog) close();
  });

  dialog.addEventListener('close', function () {
    document.body.classList.remove('has-dialog');
  });

  // Tự mở khi vào trang chủ (đợi giao diện ổn định một nhịp).
  window.setTimeout(open, 700);

  window.TGUrgentDialog = { open: open, close: close };
})();
