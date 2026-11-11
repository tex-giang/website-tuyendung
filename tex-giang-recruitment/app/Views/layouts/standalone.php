<?php
/**
 * Layout độc lập — dùng cho trang nộp hồ sơ nhanh (/tuyen-dung).
 * Không header/footer, không nút nổi/dialog: đúng kiểu trang form riêng như
 * bản tĩnh ung-tuyen.html.
 */
$page = $page ?? [];
$bodyClass = $page['body_class'] ?? 'tg-public gf-page';
$scripts = $page['scripts'] ?? [];
?>
<!DOCTYPE html>
<html lang="vi" translate="no" class="notranslate">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="google" content="notranslate">
  <meta name="googlebot" content="notranslate">
  <title><?= e($page['title'] ?? settings('seo_title', 'Tuyển dụng TEX-GIANG')) ?></title>
  <meta name="description" content="<?= e($page['description'] ?? settings('seo_description', '')) ?>">
  <meta name="robots" content="<?= e($page['robots'] ?? 'noindex') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/public.css') ?>">
  <link rel="icon" href="<?= url('favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('images/favicons/favicon-32x32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('images/favicons/apple-touch-icon.png') ?>">
</head>
<body class="<?= e($bodyClass) ?>">

<main id="main-content" class="gf-wrap">
<?= $content ?>
</main>

<script src="<?= asset('js/phone-format.js') ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= e(asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
