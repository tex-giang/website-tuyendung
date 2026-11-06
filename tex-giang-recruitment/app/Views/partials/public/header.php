<?php
/** Header công khai + dải thông báo lấy từ bảng banners (type = notice). */
// Hiển thị trên mọi trang: trang nào không truyền $notice thì tự lấy banner đang bật.
$notice = $notice ?? \App\Models\Banner::firstActive('notice');
$nav = $page['nav'] ?? '';
$hotline = settings('hotline', '02733937274');
$hotlineDisplay = settings('hotline_display', '0273 393 7274');
$navItems = [
    'home'         => ['Trang chủ', url('')],
    'jobs'         => ['Việc làm', url('jobs')],
    'about'        => ['Về chúng tôi', url('about')],
    'why-tex-giang' => ['Phúc lợi', url('why-tex-giang')],
    'news'         => ['Tin tức', url('news')],
    'contact'      => ['Liên hệ', url('contact')],
];
?>
<?php if ($notice !== null): ?>
<div id="announce-strip" class="bg-accent-soft no-print">
  <div class="tg-container flex min-h-[44px] items-center justify-between gap-4 py-2.5">
    <p class="text-[14px] leading-snug text-body"><?= e($notice['title'] ?? '') ?></p>
    <div class="flex shrink-0 items-center gap-3">
      <?php if (!empty($notice['link_url'])): ?>
      <a href="<?= e(url((string) $notice['link_url'])) ?>" class="tg-link hidden text-[14px] sm:inline-flex"><?= e($notice['link_label'] ?: 'Xem chi tiết') ?>
        <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[13px]"></a>
      <?php endif; ?>
      <button id="announce-close" type="button" aria-label="Đóng thông báo"
              class="grid h-9 w-9 place-items-center rounded-md text-muted transition-colors hover:bg-black/5 hover:text-ink">
        <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[13px]">
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<header id="site-nav" class="sticky top-0 z-40 border-b border-transparent bg-white transition-colors no-print">
  <div class="tg-container flex h-16 items-center justify-between gap-6 lg:h-[72px]">
    <a href="<?= url('') ?>" class="flex shrink-0 items-center gap-2.5" aria-label="TEX-GIANG — trang chủ">
      <img src="<?= asset('images/logo-tgi-mark.webp') ?>" alt="TEX-GIANG (TGI)" width="80" height="52" class="site-logo">
    </a>

    <nav class="hidden items-center gap-7 lg:flex xl:gap-8" aria-label="Điều hướng chính">
      <?php foreach ($navItems as $key => [$label, $href]): ?>
      <a href="<?= $href ?>" class="nav-link <?= nav_active($key, $nav) ?>"<?= $key === $nav ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="flex items-center gap-3.5">
      <a href="tel:<?= e($hotline) ?>" class="hidden text-[14px] text-muted xl:inline-flex">Hotline
        <strong class="ml-1 font-medium text-body"><?= e($hotlineDisplay) ?></strong></a>
      <a href="<?= url('apply') ?>" class="tg-btn tg-btn--primary h-10 px-4 text-[16px]">Ứng tuyển</a>
      <button id="mobile-menu-btn" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="mobile-menu"
              class="grid h-10 w-10 place-items-center rounded-lg border border-line text-body transition-colors hover:border-accent hover:text-accent lg:hidden">
        <img src="<?= asset('images/icons/list.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[20px]">
      </button>
    </div>
  </div>

  <div id="mobile-menu" class="nav-mobile hidden border-t border-line bg-white px-6 pb-6 pt-2">
    <?php foreach ($navItems as $key => [$label, $href]): ?>
    <a href="<?= $href ?>" class="block border-b border-line py-3 text-[16px] <?= $key === $nav ? 'font-medium text-accent' : 'text-body' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= url('admin/login') ?>" class="block border-b border-line py-3 text-[16px] text-body">Cổng quản trị HR</a>
    <a href="tel:<?= e($hotline) ?>" class="block py-3 text-[16px] text-body">Hotline <?= e($hotlineDisplay) ?></a>
  </div>
</header>
