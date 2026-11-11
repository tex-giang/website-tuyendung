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
  <?php if (!empty($page['robots'])): ?>
  <meta name="robots" content="<?= e($page['robots']) ?>">
  <?php endif; ?>

  <?php
  // Canonical bỏ toàn bộ query string (?page=, ?chuyen-muc=, utm_*) để Google
  // gộp mọi biến thể của cùng một trang về một URL duy nhất.
  $canonicalRaw = trim((string) ($page['canonical'] ?? ''));
  $canonicalUrl = $canonicalRaw === '' ? \App\Core\Seo::canonical() : \App\Core\Seo::absolute($canonicalRaw);

  // og:image BẮT BUỘC là URL tuyệt đối, nếu không Facebook/Zalo không tải được ảnh.
  $ogImageRaw = trim((string) ($page['og_image'] ?? ''));
  $ogImageUrl = $ogImageRaw === ''
      ? \App\Core\Seo::absolute('assets/images/favicons/icon-512.png')
      : \App\Core\Seo::absolute(asset($ogImageRaw));
  ?>
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:site_name" content="<?= e(settings('site_name')) ?>">
  <meta property="og:image" content="<?= e($ogImageUrl) ?>">
  <meta property="og:image:width" content="512">
  <meta property="og:image:height" content="512">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($page['og_title'] ?? ($page['title'] ?? settings('seo_title', ''))) ?>">
  <meta name="twitter:image" content="<?= e($ogImageUrl) ?>">
  <meta name="theme-color" content="#087A55">

  <link rel="icon" href="<?= url('favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('images/favicons/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('images/favicons/favicon-16x16.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('images/favicons/apple-touch-icon.png') ?>">

  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/public.css') ?>">

  <?php if (!empty($page['jsonld']) && is_array($page['jsonld'])): ?>
  <?= \App\Core\Seo::tag($page['jsonld']) ?>
  <?php endif; ?>
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
