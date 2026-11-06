/**
 * TEX-GIANG RECRUITMENT — TIỆN ÍCH GIAO DIỆN DÙNG CHUNG
 * 1. Thanh thông báo: đóng và ghi nhớ trong phiên
 * 2. Nav: hiện đường kẻ khi đã cuộn + đo chiều cao header vào biến CSS
 * 3. Cột bộ lọc: đồng bộ khoảng cách sticky theo chiều cao header thực tế
 * 4. Khối [data-reveal]: hiện dần khi cuộn tới, tắt khi người dùng giảm chuyển động
 */
(function () {
  'use strict';

  var root = document.documentElement;
  var nav = document.getElementById('site-nav');

  // 1. Thanh thông báo
  var strip = document.getElementById('announce-strip');
  var closeBtn = document.getElementById('announce-close');

  if (strip && closeBtn) {
    if (sessionStorage.getItem('texgiang_announce') === '1') {
      strip.remove();
    } else {
      closeBtn.addEventListener('click', function () {
        strip.remove();
        strip = null;
        syncHeaderHeight();
        sessionStorage.setItem('texgiang_announce', '1');
      });
    }
  }

  /**
   * Ghi chiều cao header đang sticky vào biến CSS --tg-header-h.
   * Các phần tử sticky khác (bộ lọc việc làm, aside chi tiết…) dùng biến này để
   * không bị header che mất một phần khi cuộn.
   */
  function syncHeaderHeight() {
    if (!nav) return;
    var h = Math.round(nav.getBoundingClientRect().height);
    if (h > 0) root.style.setProperty('--tg-header-h', h + 'px');
  }

  if (nav) {
    var onScroll = function () {
      nav.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    syncHeaderHeight();
    window.addEventListener('resize', syncHeaderHeight);
    window.addEventListener('load', syncHeaderHeight);
    if (window.ResizeObserver) {
      new ResizeObserver(syncHeaderHeight).observe(nav);
    }
  }

  /**
   * 3. Bộ lọc việc làm (desktop): khi cuộn tới cuối danh sách, phần tử sticky
   * bị đẩy lên và sẽ bị header che mất một nửa. Ẩn hẳn lúc đó để không lộ
   * "bảng bộ lọc bị cắt", cuộn lên lại thì hiện lại.
   */
  var filterDrawer = document.querySelector('.filters-drawer');
  if (filterDrawer) {
    var mqDesktop = window.matchMedia('(min-width: 1024px)');

    var syncFilterSticky = function () {
      if (!mqDesktop.matches) {
        filterDrawer.classList.remove('is-unpinned');
        return;
      }
      var stickyTop = parseFloat(window.getComputedStyle(filterDrawer).top) || 95;
      var rect = filterDrawer.getBoundingClientRect();
      // rect.top < stickyTop nghĩa là phần tử đã bị cạnh dưới khối cha đẩy lên.
      filterDrawer.classList.toggle('is-unpinned', rect.top < stickyTop - 2);
    };

    window.addEventListener('scroll', syncFilterSticky, { passive: true });
    window.addEventListener('resize', syncFilterSticky);
    if (mqDesktop.addEventListener) mqDesktop.addEventListener('change', syncFilterSticky);
    syncFilterSticky();
  }

  // 4. Hiện dần khi cuộn
  var items = document.querySelectorAll('[data-reveal]');
  if (!items.length) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced || !('IntersectionObserver' in window)) return;

  var show = function (el) {
    el.style.transition = 'opacity .5s ease-out, transform .5s ease-out';
    el.style.opacity = '1';
    el.style.transform = 'none';
  };

  items.forEach(function (el) {
    el.style.opacity = '0';
    el.style.transform = 'translateY(16px)';
  });

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      show(entry.target);
      io.unobserve(entry.target);
    });
  }, { rootMargin: '0px 0px -10% 0px' });

  items.forEach(function (el) { io.observe(el); });

  // Lưới an toàn: tab chạy nền hoặc trình duyệt không bắn IntersectionObserver
  setTimeout(function () {
    items.forEach(function (el) {
      if (el.style.opacity === '0') show(el);
    });
  }, 1500);
})();
