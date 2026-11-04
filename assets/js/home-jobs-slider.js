/**
 * TEX-GIANG — SLIDER "VỊ TRÍ ĐANG TUYỂN" (dạng product carousel)
 * - Ít hơn 4 vị trí: giữ grid tĩnh có sẵn trong HTML.
 * - Từ 4 vị trí trở lên: cắt danh sách bằng slice thành từng trang
 *   (3 thẻ/trang desktop, 2 thẻ tablet, 1 thẻ mobile) và có nút trước/sau,
 *   chấm chọn trang, vuốt trên mobile, phím ← →.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-job-slider]');
  if (!root) return;
  if (typeof MOCK_JOBS === 'undefined' || !Array.isArray(MOCK_JOBS)) return;

  var jobs = MOCK_JOBS.filter(function (job) { return job && job.status !== 'closed'; });
  if (!jobs.length) return;

  var CHIPS = {
    urgent: 'Tuyển gấp',
    hot: 'Nổi bật',
    active: 'Đang tuyển',
    expiring: 'Sắp hết hạn',
    training: 'Đào tạo có lương'
  };
  var ARROW = '<img src="assets/images/icons/arrow-right.svg" alt="" aria-hidden="true" width="18" height="18" class="tg-icon">';

  var mqSm = window.matchMedia('(min-width: 640px)');
  var mqLg = window.matchMedia('(min-width: 1024px)');
  var page = 0;
  var pages = [];
  var perView = 3;

  function perViewNow() {
    if (mqLg.matches) return 3;
    if (mqSm.matches) return 2;
    return 1;
  }

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function shortTitle(title) {
    return String(title || '').replace(/^\d+\s+/, '').trim();
  }

  function salaryText(salary) {
    // Ví dụ "8.000.000 - 16.000.000 VNĐ" -> "8 – 16 triệu/tháng"
    var found = String(salary || '').match(/\d{1,3}(?:\.\d{3})+/g);
    if (!found || !found.length) return String(salary || '');
    var value = function (raw) {
      var number = parseFloat(raw.replace(/\./g, '')) / 1000000;
      return Number.isInteger(number) ? String(number) : number.toFixed(1).replace('.', ',');
    };
    if (found.length >= 2) return value(found[0]) + ' – ' + value(found[1]) + ' triệu/tháng';
    return value(found[0]) + ' triệu/tháng';
  }

  function cardMarkup(job) {
    var title = shortTitle(job.title);
    var chip = CHIPS[job.status] ? '<span class="tg-chip">' + CHIPS[job.status] + '</span>' : '';
    return [
      '<article class="job-card group flex h-full flex-col">',
      '  <div class="flex items-center justify-between gap-3">',
      '    <span class="text-[14px] text-muted">TEX-GIANG · ' + esc(job.department) + '</span>',
      '    ' + chip,
      '  </div>',
      '  <h3 class="mt-3 text-[20px] font-medium leading-[1.4] text-ink">',
      '    <a href="job-detail.html?id=' + esc(job.id) + '" class="transition-colors group-hover:text-accent">' + esc(title) + '</a>',
      '  </h3>',
      '  <p class="mt-2 line-clamp-2 text-[16px] leading-[1.6] text-muted">' + esc(job.description) + '</p>',
      '  <p class="mt-4 text-[16px] font-semibold text-accent">' + esc(salaryText(job.salary)) + '</p>',
      '  <p class="mt-3 text-[14px] text-muted">' + esc(job.location) + ' · Hạn nộp ' + esc(job.deadline) + '</p>',
      '  <div class="mt-auto flex items-center justify-between gap-3 pt-6">',
      '    <a href="job-detail.html?id=' + esc(job.id) + '" class="text-[16px] text-muted transition-colors hover:text-accent">Xem chi tiết</a>',
      '    <a href="apply.html?job=' + encodeURIComponent(job.title || title) + '" class="tg-btn tg-btn--ghost h-9 border-0 bg-white px-4 text-[16px]">Ứng tuyển</a>',
      '  </div>',
      '</article>'
    ].join('\n');
  }

  function dotsMarkup() {
    if (pages.length <= 1) return '';
    var html = '<div class="job-slider__dots" role="group" aria-label="Chọn trang vị trí">';
    for (var i = 0; i < pages.length; i++) {
      html += '<button type="button" class="job-slider__dot" data-job-dot="' + i + '" aria-label="Trang ' + (i + 1) + ' / ' + pages.length + '"></button>';
    }
    return html + '</div>';
  }

  function navMarkup() {
    if (pages.length <= 1) return '';
    return [
      '<div class="job-slider__nav">',
      '  <button type="button" class="job-slider__btn job-slider__btn--prev" data-job-prev aria-label="Trang vị trí trước">' + ARROW + '</button>',
      '  ' + dotsMarkup(),
      '  <button type="button" class="job-slider__btn" data-job-next aria-label="Trang vị trí tiếp theo">' + ARROW + '</button>',
      '</div>'
    ].join('\n');
  }

  function render(animate) {
    var track = root.querySelector('[data-job-track]');
    if (!track) return;
    if (!animate) {
      track.style.transition = 'none';
      void track.offsetWidth;
      track.style.transition = '';
    }
    track.style.transform = 'translateX(-' + page * 100 + '%)';

    root.querySelectorAll('[data-job-dot]').forEach(function (dot) {
      if (Number(dot.getAttribute('data-job-dot')) === page) dot.setAttribute('aria-current', 'true');
      else dot.removeAttribute('aria-current');
    });
    var prev = root.querySelector('[data-job-prev]');
    var next = root.querySelector('[data-job-next]');
    if (prev) prev.disabled = page <= 0;
    if (next) next.disabled = page >= pages.length - 1;
  }

  function go(target) {
    if (target < 0) target = 0;
    if (target > pages.length - 1) target = pages.length - 1;
    if (target === page) return;
    page = target;
    render(true);
  }

  function bind() {
    var prev = root.querySelector('[data-job-prev]');
    var next = root.querySelector('[data-job-next]');
    if (prev) prev.addEventListener('click', function () { go(page - 1); });
    if (next) next.addEventListener('click', function () { go(page + 1); });
    root.querySelectorAll('[data-job-dot]').forEach(function (dot) {
      dot.addEventListener('click', function () { go(Number(dot.getAttribute('data-job-dot'))); });
    });

    var viewport = root.querySelector('.job-slider__viewport');
    var startX = null;
    var startY = null;
    var dragging = false;
    var dragged = false;

    function onDragMove(event) {
      if (!dragging) return;
      if (Math.abs(event.clientX - startX) > 8) dragged = true;
    }

    function onDragEnd(event) {
      if (!dragging) return;
      dragging = false;
      var deltaX = event.clientX - startX;
      var deltaY = event.clientY - startY;
      startX = null;
      startY = null;
      window.removeEventListener('pointermove', onDragMove);
      window.removeEventListener('pointerup', onDragEnd);
      window.removeEventListener('pointercancel', onDragCancel);
      // Click phát sinh ngay sau pointerup sẽ đọc cờ `dragged`; hạ cờ ở tick kế tiếp.
      window.setTimeout(function () { dragged = false; }, 0);
      // Bỏ qua khi người dùng đang cuộn dọc.
      if (Math.abs(deltaX) < 50 || Math.abs(deltaX) <= Math.abs(deltaY)) return;
      go(deltaX < 0 ? page + 1 : page - 1);
    }

    function onDragCancel() {
      dragging = false;
      startX = null;
      startY = null;
      window.removeEventListener('pointermove', onDragMove);
      window.removeEventListener('pointerup', onDragEnd);
      window.removeEventListener('pointercancel', onDragCancel);
    }

    viewport.addEventListener('pointerdown', function (event) {
      if (event.button !== 0) return; // chỉ chuột trái hoặc chạm
      startX = event.clientX;
      startY = event.clientY;
      dragged = false;
      dragging = true;
      // Nghe ở window để vẫn bắt được khi rê ra ngoài viewport,
      // nhưng KHÔNG dùng pointer capture (capture làm click không tới link).
      window.addEventListener('pointermove', onDragMove, { passive: true });
      window.addEventListener('pointerup', onDragEnd);
      window.addEventListener('pointercancel', onDragCancel);
    });
    // Không để trình duyệt kéo-thả link/ảnh khi đang kéo slider.
    viewport.addEventListener('dragstart', function (event) { event.preventDefault(); });
    // Chặn click vào link/thẻ nếu vừa kéo slider (desktop).
    viewport.addEventListener('click', function (event) {
      if (!dragged) return;
      event.preventDefault();
      event.stopPropagation();
      dragged = false;
    }, true);

    root.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowRight') { event.preventDefault(); go(page + 1); }
      else if (event.key === 'ArrowLeft') { event.preventDefault(); go(page - 1); }
    });
  }

  function build() {
    perView = perViewNow();
    pages = [];
    for (var i = 0; i < jobs.length; i += perView) pages.push(jobs.slice(i, i + perView));
    page = Math.min(page, pages.length - 1);
    if (page < 0) page = 0;

    // Số vị trí hiển thị ở hero và tiêu đề đều lấy từ MOCK_JOBS.
    document.querySelectorAll('[data-open-job-count]').forEach(function (el) {
      el.textContent = jobs.length;
    });

    var pageHtml = pages.map(function (chunk) {
      return '<div class="job-slider__page" style="--per-view:' + perView + '">' + chunk.map(cardMarkup).join('') + '</div>';
    }).join('');

    root.innerHTML = [
      '<div class="job-slider__viewport">',
      '  <div class="job-slider__track" data-job-track>' + pageHtml + '</div>',
      '</div>',
      navMarkup()
    ].join('\n');

    bind();
    render(false);
  }

  var onBreakpoint = function () {
    if (perViewNow() !== perView) {
      page = 0;
      build();
    }
  };
  if (mqSm.addEventListener) {
    mqSm.addEventListener('change', onBreakpoint);
    mqLg.addEventListener('change', onBreakpoint);
  } else if (mqSm.addListener) {
    mqSm.addListener(onBreakpoint);
    mqLg.addListener(onBreakpoint);
  }

  build();
})();
