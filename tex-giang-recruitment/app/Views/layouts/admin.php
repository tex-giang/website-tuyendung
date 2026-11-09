<?php
/** Layout quản trị: sidebar + topbar + vùng nội dung. */
$page = $page ?? [];
$nav = $page['nav'] ?? '';
$user = \App\Core\Session::user() ?? ['full_name' => '', 'role' => ''];
$success = \App\Core\Session::pull('_success');
$error = \App\Core\Session::pull('_error');
$roleLabels = ['super_admin' => 'Quản trị hệ thống', 'hr_admin' => 'Quản trị nhân sự', 'recruiter' => 'Tuyển dụng'];
$roleLabel = $roleLabels[$user['role']] ?? $user['role'];
$words = preg_split('/\s+/u', trim((string) $user['full_name'])) ?: [];
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= mb_substr($word, 0, 1, 'UTF-8');
}
$initials = mb_strtoupper($initials !== '' ? $initials : 'TG', 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($page['title'] ?? 'Quản trị | TEX-GIANG') ?></title>
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/datatables/dataTables.bootstrap5.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin-app.css') ?>">
  <link rel="icon" href="<?= asset('images/logo-tgi.png') ?>" type="image/png">
</head>
<body class="admin-body">
<script>
(function () {
  try {
    if (window.localStorage.getItem('texgiang_admin_sidebar') === 'collapsed') {
      document.body.classList.add('is-sidebar-collapsed');
    }
  } catch (_) {}
})();
</script>

<?php require BASE_PATH . '/app/Views/partials/admin/sidebar.php'; ?>

<div class="admin-main">
  <header class="admin-topbar">
    <div class="admin-topbar__lead">
      <button type="button" class="admin-sidebar-toggle" id="admin-sidebar-toggle"
              aria-controls="admin-sidebar" aria-expanded="true" aria-label="Ẩn/hiện menu quản trị" title="Ẩn/hiện menu (Ctrl+B)">
        <span></span><span></span><span></span>
      </button>
      <div class="admin-topbar__info">
        <p class="admin-topbar__eyebrow">Cổng quản trị HR</p>
        <p class="admin-topbar__user"><span class="admin-badge admin-badge--soft"><?= e($roleLabel) ?></span></p>
      </div>
    </div>

    <div class="admin-topbar__actions">
      <div class="admin-avatar-wrap" id="admin-avatar">
        <button type="button" class="admin-avatar-btn" id="admin-avatar-btn"
                aria-haspopup="menu" aria-expanded="false" aria-controls="admin-avatar-menu">
          <span class="admin-avatar" aria-hidden="true"><?= e($initials) ?></span>
          <span class="admin-avatar__name"><?= e($user['full_name']) ?></span>
          <span class="admin-avatar__chevron" aria-hidden="true">▾</span>
        </button>

        <div class="admin-avatar-menu" id="admin-avatar-menu" role="menu" aria-labelledby="admin-avatar-btn">
          <a role="menuitem" href="<?= url('admin/profile') ?>" class="admin-avatar-menu__item">
            <span class="admin-avatar-menu__icon" aria-hidden="true">👤</span> Hồ sơ cá nhân
          </a>
          <a role="menuitem" href="<?= url('admin/profile') ?>#doi-mat-khau" class="admin-avatar-menu__item">
            <span class="admin-avatar-menu__icon" aria-hidden="true">🔑</span> Đổi mật khẩu
          </a>
          <div class="admin-avatar-menu__divider" role="separator"></div>
          <form method="POST" action="<?= url('admin/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" role="menuitem" class="admin-avatar-menu__item is-danger">
              <span class="admin-avatar-menu__icon" aria-hidden="true">⏻</span> Đăng xuất
            </button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <div class="admin-content">
    <?php if ($success): ?>
    <div class="admin-alert admin-alert--success" role="status"><?= e((string) $success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="admin-alert admin-alert--error" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <?= $content ?>
  </div>

  <footer class="admin-footer">TEX-GIANG Recruitment · PHP <?= e(PHP_VERSION) ?> · <a href="<?= url('') ?>">về website</a></footer>
</div>

<script src="<?= asset('vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= asset('vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= asset('js/admin-tables.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= asset('js/admin-ajax.js') ?>"></script>
<script>
document.querySelectorAll('[data-submit-onchange]').forEach(function (select) {
  var form = select.form;
  if (form && form.hasAttribute('data-ajax')) return; // admin-ajax.js xử lý
  select.addEventListener('change', function () { form.submit(); });
});
</script>
<script>

// Menu avatar ở appbar: mở/đóng, click ngoài, phím Escape.
(function () {
  var wrap = document.getElementById('admin-avatar');
  var btn = document.getElementById('admin-avatar-btn');
  var menu = document.getElementById('admin-avatar-menu');
  if (!wrap || !btn || !menu) return;

  function setOpen(open) {
    wrap.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  btn.addEventListener('click', function (event) {
    event.stopPropagation();
    setOpen(!wrap.classList.contains('is-open'));
  });
  document.addEventListener('click', function (event) {
    if (!wrap.contains(event.target)) setOpen(false);
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && wrap.classList.contains('is-open')) {
      setOpen(false);
      btn.focus();
    }
  });
  // Nút thu gọn/mở sidebar trên appbar (nhớ trạng thái; Ctrl+B).
  var sidebarToggle = document.getElementById('admin-sidebar-toggle');
  if (sidebarToggle) {
    function syncSidebarToggle() {
      var collapsed = document.body.classList.contains('is-sidebar-collapsed');
      sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      sidebarToggle.setAttribute('aria-label', collapsed ? 'Mở menu quản trị' : 'Ẩn menu quản trị');
      sidebarToggle.setAttribute('title', collapsed ? 'Mở menu (Ctrl+B)' : 'Ẩn menu (Ctrl+B)');
    }
    function toggleSidebar() {
      document.body.classList.toggle('is-sidebar-collapsed');
      try {
        window.localStorage.setItem('texgiang_admin_sidebar',
          document.body.classList.contains('is-sidebar-collapsed') ? 'collapsed' : 'open');
      } catch (_) {}
      syncSidebarToggle();
    }
    sidebarToggle.addEventListener('click', toggleSidebar);
    document.addEventListener('keydown', function (event) {
      if ((event.ctrlKey || event.metaKey) && (event.key === 'b' || event.key === 'B')) {
        event.preventDefault();
        toggleSidebar();
      }
    });
    syncSidebarToggle();
  }
  menu.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () { setOpen(false); });
  });
})();
</script>
</body>
</html>
