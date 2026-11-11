<?php
/** Layout riêng cho trang đăng nhập (không sidebar). */
$page = $page ?? [];
?>
<!DOCTYPE html>
<html lang="vi" translate="no" class="notranslate">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="google" content="notranslate">
  <meta name="googlebot" content="notranslate">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($page['title'] ?? 'Đăng nhập quản trị') ?></title>
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin-app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin-workspace.css') ?>">
  <link rel="icon" href="<?= url('favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('images/favicons/favicon-32x32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('images/favicons/apple-touch-icon.png') ?>">
</head>
<body class="admin-body admin-body--auth" data-admin-base="<?= e(url('admin')) ?>">
  <main class="auth-wrap">
    <?= $content ?>
  </main>
  <script src="<?= asset('js/admin-ajax.js') ?>"></script>
</body>
</html>
