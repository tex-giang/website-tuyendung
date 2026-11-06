/**
 * TEX-GIANG — trang danh sách việc làm (bản động).
 * Bộ lọc và phân trang do server xử lý bằng GET; file này chỉ lo phần UX:
 *   1. Drawer bộ lọc trên mobile (mở/đóng, Escape, khoá focus).
 *   2. Tự submit khi đổi select trên desktop.
 */
(function () {
  'use strict';

  var drawer = document.getElementById('filters-sidebar');
  var overlay = document.getElementById('filter-overlay');
  var openBtn = document.getElementById('toggle-mobile-filters');
  var closeBtn = document.getElementById('close-filter-drawer');
  var form = document.getElementById('jobs-filter-form');
  var sortForm = document.getElementById('jobs-sort-form');

  function isMobile() { return window.innerWidth < 1024; }

  function openDrawer() {
    if (!drawer) return;
    drawer.classList.remove('hidden');
    overlay?.classList.remove('hidden');
    document.body.classList.add('has-dialog');
    openBtn?.setAttribute('aria-expanded', 'true');
    closeBtn?.focus();
  }

  function closeDrawer(restoreFocus) {
    if (!drawer) return;
    drawer.classList.add('hidden');
    overlay?.classList.add('hidden');
    document.body.classList.remove('has-dialog');
    openBtn?.setAttribute('aria-expanded', 'false');
    if (restoreFocus !== false && isMobile()) openBtn?.focus();
  }

  openBtn?.addEventListener('click', openDrawer);
  closeBtn?.addEventListener('click', function () { closeDrawer(); });
  overlay?.addEventListener('click', function () { closeDrawer(); });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && drawer && !drawer.classList.contains('hidden') && isMobile() && !drawer.classList.contains('lg:block')) {
      closeDrawer();
    }
  });
  window.addEventListener('resize', function () {
    if (!isMobile()) closeDrawer(false);
  });

  // Đổi select (địa điểm / phòng ban / lương) tự lọc ngay trên desktop.
  if (form) {
    form.querySelectorAll('select').forEach(function (select) {
      select.addEventListener('change', function () {
        if (!isMobile()) form.submit();
      });
    });
  }

  sortForm?.querySelector('select')?.addEventListener('change', function () {
    sortForm.submit();
  });
})();
