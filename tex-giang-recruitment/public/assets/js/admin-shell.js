(function () {
  'use strict';

  const sidebar = document.getElementById('admin-sidebar');
  const toggle = document.getElementById('admin-sidebar-toggle');
  const mobile = window.matchMedia('(max-width: 991.98px)');
  const drawer = window.bootstrap?.Offcanvas.getOrCreateInstance(sidebar);
  let drawerOpen = false;

  function syncToggle() {
    const open = mobile.matches ? drawerOpen : !document.body.classList.contains('is-sidebar-collapsed');
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Ẩn menu quản trị' : 'Mở menu quản trị');
  }

  sidebar.addEventListener('show.bs.offcanvas', () => {
    drawerOpen = true;
    syncToggle();
  });
  sidebar.addEventListener('hidden.bs.offcanvas', () => {
    drawerOpen = false;
    syncToggle();
    if (mobile.matches || sidebar.contains(document.activeElement)) toggle.focus();
  });
  mobile.addEventListener('change', () => {
    if (!mobile.matches && drawerOpen) drawer.hide();
    if (mobile.matches && sidebar.contains(document.activeElement)) toggle.focus();
    syncToggle();
  });
  toggle.addEventListener('click', () => {
    if (mobile.matches) {
      drawer?.toggle(toggle);
      return;
    }
    const collapsed = document.body.classList.toggle('is-sidebar-collapsed');
    try {
      window.localStorage.setItem('texgiang_admin_sidebar', collapsed ? 'collapsed' : 'open');
    } catch (_) {}
    syncToggle();
  });
  syncToggle();

  const account = document.getElementById('admin-avatar');
  const accountButton = document.getElementById('admin-avatar-btn');
  const accountMenu = document.getElementById('admin-avatar-menu');

  function setAccountOpen(open) {
    accountMenu.hidden = !open;
    accountButton.setAttribute('aria-expanded', String(open));
  }

  accountButton.addEventListener('click', () => setAccountOpen(accountMenu.hidden));
  document.addEventListener('click', (event) => {
    if (!account.contains(event.target)) setAccountOpen(false);
  });
  account.addEventListener('focusout', (event) => {
    if (!account.contains(event.relatedTarget)) setAccountOpen(false);
  });
  account.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !accountMenu.hidden) {
      setAccountOpen(false);
      accountButton.focus();
    }
  });

})();
