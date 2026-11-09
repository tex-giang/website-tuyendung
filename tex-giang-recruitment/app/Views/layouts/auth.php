<?php
/** Layout riêng cho trang đăng nhập (không sidebar). */
$page = $page ?? [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($page['title'] ?? 'Đăng nhập quản trị') ?></title>
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin-app.css') ?>">
  <link rel="icon" href="<?= asset('images/logo-tgi.png') ?>" type="image/png">
</head>
<body class="admin-body admin-body--auth" data-admin-base="<?= e(url('admin')) ?>">
  <main class="auth-wrap">
    <?= $content ?>
  </main>
  <script src="<?= asset('js/admin-ajax.js') ?>"></script>
</body>
</html>
