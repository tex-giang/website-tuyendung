/**
 * TEX-GIANG — SLIDER "VỊ TRÍ ĐANG TUYỂN" (dữ liệu render sẵn từ PHP).
 * Đọc các thẻ .job-card do server render trong [data-job-source], chia trang
 * theo breakpoint (3 / 2 / 1 thẻ) rồi dựng carousel giống design gốc.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-job-slider]');
  if (!root) return;
  var source = root.querySelector('[data-job-source]');
  if (!source) return;

  var cards = Array.prototype.slice.call(source.querySelectorAll('.job-card'));
  if (!cards.length) return;

  var ARROW = '<img src="' + document.currentScript.src.replace(/js\/home-jobs\.js.*$/, '') + 'images/icons/arrow-right.svg" alt="" aria-hidden="true" width="18" height="18" class="tg-icon">';

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
    var startX = null, startY = null, dragging = false, dragged = false;

    function onDragMove(event) {
      if (!dragging) return;
      if (Math.abs(event.clientX - startX) > 8) dragged = true;
    }

    function onDragEnd(event) {
      if (!dragging) return;
      dragging = false;
      var deltaX = event.clientX - startX;
      var deltaY = event.clientY - startY;
      startX = null; startY = null;
      window.removeEventListener('pointermove', onDragMove);
      window.removeEventListener('pointerup', onDragEnd);
      window.removeEventListener('pointercancel', onDragCancel);
      window.setTimeout(function () { dragged = false; }, 0);
      if (Math.abs(deltaX) < 50 || Math.abs(deltaX) <= Math.abs(deltaY)) return;
      go(deltaX < 0 ? page + 1 : page - 1);
    }

    function onDragCancel() {
      dragging = false; startX = null; startY = null;
      window.removeEventListener('pointermove', onDragMove);
      window.removeEventListener('pointerup', onDragEnd);
      window.removeEventListener('pointercancel', onDragCancel);
    }

    viewport.addEventListener('pointerdown', function (event) {
      if (event.button !== 0) return;
      startX = event.clientX;
      startY = event.clientY;
      dragged = false;
      dragging = true;
      window.addEventListener('pointermove', onDragMove, { passive: true });
      window.addEventListener('pointerup', onDragEnd);
      window.addEventListener('pointercancel', onDragCancel);
    });
    viewport.addEventListener('dragstart', function (event) { event.preventDefault(); });
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
    for (var i = 0; i < cards.length; i += perView) pages.push(cards.slice(i, i + perView));
    page = Math.min(page, pages.length - 1);
    if (page < 0) page = 0;

    var pageHtml = pages.map(function (chunk) {
      return '<div class="job-slider__page" style="--per-view:' + perView + '">'
        + chunk.map(function (card) { return card.outerHTML; }).join('')
        + '</div>';
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
