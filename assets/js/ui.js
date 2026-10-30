/**
 * TEX-GIANG RECRUITMENT — TIỆN ÍCH GIAO DIỆN DÙNG CHUNG
 * 1. Thanh thông báo: đóng và ghi nhớ trong phiên
 * 2. Nav: hiện đường kẻ khi đã cuộn
 * 3. Khối [data-reveal]: hiện dần khi cuộn tới, tắt khi người dùng giảm chuyển động
 */
(function () {
  'use strict';

  // 1. Thanh thông báo
  var strip = document.getElementById('announce-strip');
  var closeBtn = document.getElementById('announce-close');

  if (strip && closeBtn) {
    if (sessionStorage.getItem('texgiang_announce') === '1') {
      strip.remove();
    } else {
      closeBtn.addEventListener('click', function () {
        strip.remove();
        sessionStorage.setItem('texgiang_announce', '1');
      });
    }
  }

  // 2. Nav
  var nav = document.getElementById('site-nav');

  if (nav) {
    var onScroll = function () {
      nav.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // 3. Hiện dần khi cuộn
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
