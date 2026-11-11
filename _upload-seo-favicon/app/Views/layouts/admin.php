<?php
/** Layout quản trị: sidebar + topbar + vùng nội dung. */
$page = $page ?? [];
$user = \App\Core\Session::user() ?? ['full_name' => '', 'role' => ''];
$success = \App\Core\Session::pull('_success');
$error = \App\Core\Session::pull('_error');
$title = $page['title'] ?? 'Quản trị | TEX-GIANG';
$nav = $page['nav'] ?? '';
$counts = [
    'applications' => \App\Core\Permissions::currentCan('applications') ? \App\Models\Application::newCount() : 0,
    'contact' => \App\Core\Permissions::currentCan('contact') ? \App\Models\ContactMessage::newCount() : 0,
];
$canRefresh = !empty($page['ajax_fragment']);

ob_start();
if ($success): ?>
  <div class="admin-alert admin-alert--success" role="status"><?= e((string) $success) ?></div>
<?php endif;
if ($error): ?>
  <div class="admin-alert admin-alert--error" role="alert"><?= e((string) $error) ?></div>
<?php endif;
echo $content;
$mainContent = (string) ob_get_clean();

if (\App\Core\Response::wantsAdminFragment()) {
    \App\Core\Response::json($canRefresh
        ? ['fragment' => true, 'content' => $mainContent, 'title' => $title, 'nav' => $nav, 'counts' => $counts]
        : ['fragment' => false]);
}

$roleLabels = \App\Core\Permissions::roleLabels();
$roleLabel = $roleLabels[$user['role']] ?? $user['role'];
$words = preg_split('/\s+/u', trim((string) $user['full_name'])) ?: [];
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= mb_substr($word, 0, 1, 'UTF-8');
}
$initials = mb_strtoupper($initials !== '' ? $initials : 'TG', 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi" translate="no" class="notranslate">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="google" content="notranslate">
  <meta name="googlebot" content="notranslate">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?></title>
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/datatables/dataTables.bootstrap5.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin-app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin-workspace.css') ?>">
  <link rel="icon" href="<?= url('favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('images/favicons/favicon-32x32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('images/favicons/apple-touch-icon.png') ?>">
</head>
<body class="admin-body" data-admin-base="<?= e(url('admin')) ?>">
<a class="admin-skip" href="#admin-content">Đến nội dung chính</a>
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
              aria-controls="admin-sidebar" aria-expanded="false" aria-label="Mở menu quản trị">
        <i class="bi bi-list" aria-hidden="true"></i>
      </button>
      <nav class="admin-topbar__breadcrumb" aria-label="Vị trí trong trang quản trị">
        <span data-admin-crumb-group><?= e($activeNavGroup) ?></span>
        <span aria-hidden="true">/</span>
        <span class="admin-topbar__current" aria-current="page" data-admin-crumb-title><?= e(explode(' | ', $title, 2)[0]) ?></span>
      </nav>
    </div>

    <div class="admin-topbar__actions">
      <div class="admin-avatar-wrap" id="admin-avatar">
        <button type="button" class="admin-avatar-btn" id="admin-avatar-btn"
                aria-label="Tài khoản <?= e($user['full_name']) ?>" aria-expanded="false" aria-controls="admin-avatar-menu">
          <span class="admin-avatar" aria-hidden="true"><?= e($initials) ?></span>
          <span class="admin-avatar__name"><?= e($user['full_name']) ?></span>
          <i class="bi bi-chevron-down admin-avatar__chevron" aria-hidden="true"></i>
        </button>

        <div class="admin-avatar-menu" id="admin-avatar-menu" hidden>
          <div class="admin-avatar-menu__role"><?= e($roleLabel) ?></div>
          <a href="<?= url('admin/profile') ?>" class="admin-avatar-menu__item">
            <i class="bi bi-person admin-avatar-menu__icon" aria-hidden="true"></i> Hồ sơ cá nhân
          </a>
          <a href="<?= url('admin/profile') ?>#doi-mat-khau" class="admin-avatar-menu__item">
            <i class="bi bi-key admin-avatar-menu__icon" aria-hidden="true"></i> Đổi mật khẩu
          </a>
          <div class="admin-avatar-menu__divider" role="separator"></div>
          <form method="POST" action="<?= url('admin/logout') ?>" data-ajax data-ajax-navigate>
            <?= csrf_field() ?>
            <button type="submit" class="admin-avatar-menu__item is-danger">
              <i class="bi bi-power admin-avatar-menu__icon" aria-hidden="true"></i> Đăng xuất
            </button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <p id="admin-ajax-announcement" class="visually-hidden" role="status"></p>
  <main class="admin-content" id="admin-content" tabindex="-1"<?= $canRefresh ? ' data-admin-fragment' : '' ?>>
    <?= $mainContent ?>
  </main>

  <footer class="admin-footer"><span translate="no">TEX-GIANG</span> · Cổng quản trị tuyển dụng</footer>
</div>

<script src="<?= asset('vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= asset('vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= asset('js/admin-tables.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= asset('js/admin-ajax.js') ?>"></script>
<script src="<?= asset('js/admin-shell.js') ?>"></script>
<script src="<?= asset('js/admin-repeaters.js') ?>"></script>
</body>
</html>
