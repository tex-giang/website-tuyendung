<?php
/** Footer công khai + nút nổi + thẻ popup + modal QR — theo design gốc. */
$popup = $popup ?? null;
$hotline = settings('hotline', '02733937274');
$hotlineDisplay = settings('hotline_display', '0273 393 7274');
$email = settings('email', 'nhansu@tex-giang.com.vn');
$branches = \App\Models\Branch::active();
?>
<footer class="no-print border-t border-line bg-surface">
  <div class="tg-container py-12 lg:py-16">
    <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1.3fr_1fr] lg:gap-12">

      <div>
        <a href="<?= url('') ?>" class="inline-flex items-center gap-2.5" aria-label="TEX-GIANG — trang chủ">
          <img src="<?= asset('images/logo-tgi-mark.webp') ?>" alt="TEX-GIANG (TGI)" width="62" height="40" class="footer-logo">
        </a>
        <p class="mt-4 max-w-[300px] text-[15px] leading-[1.7] text-muted"><?= e(settings('content_footer_intro')) ?></p>
        <ul class="mt-4 space-y-2 text-[15px] text-muted">
          <li class="flex items-center gap-2.5">
            <img src="<?= asset('images/icons/telephone.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
            <a href="tel:<?= e($hotline) ?>" class="transition-colors hover:text-accent"><?= e($hotlineDisplay) ?></a>
          </li>
          <li class="flex items-center gap-2.5">
            <img src="<?= asset('images/icons/envelope.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
            <a href="mailto:<?= e($email) ?>" class="transition-colors hover:text-accent"><?= e($email) ?></a>
          </li>
        </ul>
      </div>

      <nav aria-label="Menu chân trang">
        <h2 class="text-[13px] font-semibold uppercase tracking-[0.12em] text-ink">Menu</h2>
        <ul class="mt-4 space-y-2.5 text-[15px]">
          <li><a href="<?= url('') ?>" class="text-muted transition-colors hover:text-accent">Trang chủ</a></li>
          <li><a href="<?= url('jobs') ?>" class="text-muted transition-colors hover:text-accent">Việc làm</a></li>
          <li><a href="<?= url('about') ?>" class="text-muted transition-colors hover:text-accent">Về chúng tôi</a></li>
          <li><a href="<?= url('why-tex-giang') ?>" class="text-muted transition-colors hover:text-accent">Phúc lợi</a></li>
          <li><a href="<?= url('news') ?>" class="text-muted transition-colors hover:text-accent">Tin tức</a></li>
          <li><a href="<?= url('contact') ?>" class="text-muted transition-colors hover:text-accent">Liên hệ</a></li>
        </ul>
      </nav>

      <div>
        <h2 class="text-[13px] font-semibold uppercase tracking-[0.12em] text-ink">Liên hệ chi nhánh</h2>
        <ul class="mt-4 space-y-3 text-[15px] leading-[1.6] text-muted">
          <?php foreach ($branches as $branch): ?>
          <li><span class="block font-medium text-body"><?= e($branch['name']) ?></span><?= e($branch['address'] ?? '') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h2 class="text-[13px] font-semibold uppercase tracking-[0.12em] text-ink">Kết nối</h2>
        <ul class="mt-4 space-y-2.5 text-[15px]">
          <li><a href="https://zalo.me/<?= e(settings('zalo', $hotline)) ?>" target="_blank" rel="noopener" class="text-muted transition-colors hover:text-accent">Zalo tuyển dụng</a></li>
          <li><a href="<?= e(settings('facebook', 'https://www.facebook.com/')) ?>" target="_blank" rel="noopener" class="text-muted transition-colors hover:text-accent">Facebook</a></li>
          <li><a href="<?= e(settings('youtube', 'https://www.youtube.com/')) ?>" target="_blank" rel="noopener" class="text-muted transition-colors hover:text-accent">YouTube</a></li>
          <li><a href="<?= e(settings('website', 'https://tex-giang.com.vn')) ?>" target="_blank" rel="noopener" class="text-muted transition-colors hover:text-accent">Website công ty</a></li>
          <li><button type="button" class="open-qr-modal text-muted transition-colors hover:text-accent">Mã QR tuyển dụng</button></li>
        </ul>
      </div>
    </div>

    <div class="mt-10 flex flex-col gap-3 border-t border-line pt-6 text-[13px] text-muted sm:flex-row sm:items-center sm:justify-between">
      <p>© <?= date('Y') ?> <?= e(settings('legal_name', 'Công ty Cổ phần TEX-GIANG')) ?></p>
      <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <span>Ảnh: TEX-GIANG</span>
        <a href="<?= url('admin/login') ?>" class="transition-colors hover:text-accent">Cổng quản trị HR</a>
      </p>
    </div>
  </div>
</footer>

<div class="float-stack fixed bottom-4 right-3 z-40 flex flex-col items-center gap-2 no-print sm:bottom-6 sm:right-6 sm:gap-2.5">
  <button id="back-to-top" type="button" aria-label="Lên đầu trang" class="float-btn opacity-0 pointer-events-none">
    <img src="<?= asset('images/icons/arrow-up.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
  </button>
  <a href="https://zalo.me/<?= e(settings('zalo', $hotline)) ?>" target="_blank" rel="noopener" class="float-btn" aria-label="Chat Zalo tuyển dụng">Zalo</a>
  <a href="tel:<?= e($hotline) ?>" class="float-btn float-btn--primary" aria-label="Gọi hotline <?= e($hotlineDisplay) ?>">
    <img src="<?= asset('images/icons/telephone.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[16px]">
  </a>
</div>

<?php if ($popup !== null): ?>
<div id="recruitment-popup" class="hidden fixed bottom-20 left-4 right-4 z-40 w-auto max-w-[360px] flex-col rounded-media bg-white p-5 shadow-float sm:right-auto sm:w-[360px] no-print"
     role="dialog" aria-modal="false" aria-labelledby="popup-title">
  <div class="flex items-start justify-between gap-4">
    <p class="text-[14px] font-medium text-accent"><?= e($popup['subtitle'] ?? '') ?></p>
    <button id="close-popup-btn" type="button" aria-label="Đóng thông báo"
            class="grid h-7 w-7 shrink-0 place-items-center rounded-md text-muted transition-colors hover:bg-surface hover:text-ink">
      <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="18" height="18">
    </button>
  </div>
  <h2 id="popup-title" class="mt-2 text-[18px] font-medium leading-[1.35] text-ink"><?= e($popup['title'] ?? '') ?></h2>
  <p class="mt-2 text-[14px] leading-[1.6] text-muted"><?= e($popup['description'] ?? '') ?></p>
  <a href="<?= e(url((string) ($popup['link_url'] ?: 'apply'))) ?>" class="tg-btn tg-btn--primary mt-4 h-10 w-full text-[15px]"><?= e($popup['cta_label'] ?: 'Nộp hồ sơ ngay') ?></a>
  <button id="popup-dont-show-again" type="button" class="mt-2 text-[14px] text-muted underline underline-offset-2 transition-colors hover:text-ink">
    Không hiện lại trong phiên này
  </button>
</div>
<?php endif; ?>

<div id="qr-modal" class="tg-modal hidden" role="dialog" aria-modal="true" aria-labelledby="qr-title">
  <div class="relative w-full max-w-[380px] rounded-2xl bg-white p-7 text-center">
    <button id="close-qr-btn" type="button" aria-label="Đóng"
            class="absolute right-3 top-3 grid h-8 w-8 place-items-center rounded-lg text-muted transition-colors hover:bg-surface hover:text-ink">
      <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
    </button>
    <h3 id="qr-title" class="text-[18px] font-medium text-ink">Mã QR tuyển dụng</h3>
    <p class="mt-1.5 text-[14px] leading-[1.6] text-muted">Quét bằng camera điện thoại hoặc Zalo để mở nhanh trên thiết bị khác.</p>
    <div class="my-5 inline-block rounded-xl border border-line p-3">
      <img id="qr-code-img" src="" alt="Mã QR tuyển dụng" width="180" height="180" class="h-[180px] w-[180px]">
    </div>
    <p id="qr-target-url" class="break-all text-[14px] text-muted"></p>
    <button type="button" onclick="copyLink()" class="tg-btn tg-btn--primary mt-5 h-11 w-full text-[16px]">Sao chép đường dẫn</button>
  </div>
</div>
