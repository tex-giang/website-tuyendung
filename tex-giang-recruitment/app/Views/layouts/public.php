<?php
/** Layout công khai — mọi trang tuyển dụng dùng chung header/footer theo design gốc. */
$page = $page ?? [];
$bodyClass = $page['body_class'] ?? 'tg-public';
$mainClass = $page['main_class'] ?? '';
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
  <meta property="og:title" content="<?= e($page['og_title'] ?? ($page['title'] ?? settings('seo_title', ''))) ?>">
  <meta property="og:description" content="<?= e($page['description'] ?? settings('seo_description', '')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="vi_VN">
  <?php if (!empty($page['og_image'])): ?>
  <meta property="og:image" content="<?= e(asset($page['og_image'])) ?>">
  <?php endif; ?>
  <?php if (!empty($page['robots'])): ?>
  <meta name="robots" content="<?= e($page['robots']) ?>">
  <?php endif; ?>

  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/public.css') ?>">
  <link rel="icon" href="<?= asset('images/logo-tgi.png') ?>" type="image/png">
</head>
<body class="<?= e($bodyClass) ?> bg-white font-sans text-body antialiased selection:bg-accent selection:text-white">

<?php require BASE_PATH . '/app/Views/partials/public/header.php'; ?>

<a class="skip-link" href="#main-content">Đến nội dung chính</a>
<main id="main-content" class="<?= e($mainClass) ?>">
<?= $content ?>
</main>

<?php require BASE_PATH . '/app/Views/partials/public/footer.php'; ?>

<?php if (!str_starts_with(current_path(), '/apply')): ?>
<?php require BASE_PATH . '/app/Views/partials/public/apply-dialog.php'; ?>
<?php endif; ?>

<div id="toast-container"></div>

<script src="<?= asset('js/phone-format.js') ?>"></script>
      <script src="<?= asset('js/main.js') ?>"></script>
<script src="<?= asset('js/ui.js') ?>"></script>
<script src="<?= asset('js/apply-dialog.js') ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= e(asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
