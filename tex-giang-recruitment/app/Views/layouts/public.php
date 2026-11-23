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
  <meta property="og:type" content="<?= e($page['og_type'] ?? 'website') ?>">
  <meta property="og:locale" content="vi_VN">
  <?php if (!empty($page['robots'])): ?>
  <meta name="robots" content="<?= e($page['robots']) ?>">
  <?php else: ?>
  <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
  <?php endif; ?>
  <?php
  // Mã xác minh chủ sở hữu website — nhập ở Quản trị > Cài đặt > SEO.
  $verifyTags = [
      'google-site-verification'         => settings('seo_google_verification'),
      'msvalidate.01'                    => settings('seo_bing_verification'),
      'facebook-domain-verification'     => settings('seo_facebook_domain_verification'),
      'zalo-platform-site-verification'  => settings('seo_zalo_verification'),
  ];
  foreach ($verifyTags as $verifyName => $verifyValue):
      if (trim($verifyValue) === '') { continue; } ?>
  <meta name="<?= e($verifyName) ?>" content="<?= e(trim($verifyValue)) ?>">
  <?php endforeach; ?>

  <?php
  // Canonical bỏ toàn bộ query string (?page=, ?chuyen-muc=, utm_*) để Google
  // gộp mọi biến thể của cùng một trang về một URL duy nhất.
  $canonicalRaw = trim((string) ($page['canonical'] ?? ''));
  $canonicalUrl = $canonicalRaw === '' ? \App\Core\Seo::canonical() : \App\Core\Seo::absolute($canonicalRaw);

  // og:image BẮT BUỘC là URL tuyệt đối, nếu không Facebook/Zalo không tải được ảnh.
  $ogImageRaw = trim((string) ($page['og_image'] ?? ''));
  // Ảnh mặc định 1200x630 (tỉ lệ 1.91:1 chuẩn Facebook/Zalo); ảnh bài viết tự có kích thước riêng.
  $ogImageUrl = $ogImageRaw === ''
      ? \App\Core\Seo::absolute('assets/images/og-default.jpg')
      : \App\Core\Seo::absolute(asset($ogImageRaw));
  $ogIsDefault = $ogImageRaw === '';
  $ogTitle = $page['og_title'] ?? ($page['title'] ?? settings('seo_title', ''));
  $ogDesc = $page['description'] ?? settings('seo_description', '');
  ?>
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:site_name" content="<?= e(settings('site_name')) ?>">
  <meta property="og:image" content="<?= e($ogImageUrl) ?>">
  <meta property="og:image:alt" content="<?= e($ogTitle) ?>">
  <?php if ($ogIsDefault): ?>
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <?php endif; ?>
  <?php if (settings('seo_facebook_app_id') !== ''): ?>
  <meta property="fb:app_id" content="<?= e(settings('seo_facebook_app_id')) ?>">
  <?php endif; ?>
  <?php if (!empty($page['article'])): ?>
  <meta property="article:published_time" content="<?= e((string) ($page['article']['published'] ?? '')) ?>">
  <meta property="article:modified_time" content="<?= e((string) ($page['article']['modified'] ?? '')) ?>">
  <meta property="article:section" content="<?= e((string) ($page['article']['section'] ?? '')) ?>">
  <?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($ogTitle) ?>">
  <meta name="twitter:description" content="<?= e($ogDesc) ?>">
  <meta name="twitter:image" content="<?= e($ogImageUrl) ?>">
  <meta name="theme-color" content="#087A55">

  <!-- PWA: thêm vào màn hình chính mở dạng app, không thanh địa chỉ -->
  <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="TEX-GIANG">
  <meta name="application-name" content="TEX-GIANG">

  <link rel="icon" href="<?= url('favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('images/favicons/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('images/favicons/favicon-16x16.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('images/favicons/apple-touch-icon.png') ?>">

  <script src="<?= asset('js/header-loading.js') ?>"></script>
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/public.css') ?>">

  <?php if (!empty($page['jsonld']) && is_array($page['jsonld'])): ?>
  <?= \App\Core\Seo::tag($page['jsonld']) ?>
  <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?> bg-white font-sans text-body antialiased selection:bg-accent selection:text-white">

<div class="header-loading no-print" aria-hidden="true"></div>
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
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register(<?= json_encode(url('sw.js')) ?>, { scope: <?= json_encode(url('')) ?> }).catch(function () {});
  });
}
</script>
</body>
</html>
