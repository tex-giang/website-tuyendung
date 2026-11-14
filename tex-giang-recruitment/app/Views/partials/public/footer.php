<?php
/** Footer công khai + nút nổi + thẻ popup + modal QR — theo design gốc. */
$popup = $popup ?? null;
$email = settings('email', 'nhansu@tex-giang.com.vn');
$zalo = settings('zalo');

// Menu chân trang lấy từ bảng menus (chỉ mục đang bật); bảng trống thì dùng mặc định.
$footerMenuItems = [];
foreach (\App\Models\Menu::active() as $menuRow) {
    $footerMenuItems[] = ['label' => (string) $menuRow['label'], 'href' => url((string) $menuRow['url'])];
}
if ($footerMenuItems === []) {
    $footerMenuItems = [
        ['label' => 'Trang chủ', 'href' => url('')],
        ['label' => 'Việc làm', 'href' => url('jobs')],
        ['label' => 'Về chúng tôi', 'href' => url('about')],
        ['label' => 'Phúc lợi', 'href' => url('why-tex-giang')],
        ['label' => 'Tin tức', 'href' => url('news')],
        ['label' => 'Liên hệ', 'href' => url('contact')],
    ];
}
$branches = \App\Models\Branch::active();
$branchContacts = \App\Models\BranchContact::activeGrouped();
?>
<footer class="no-print border-t border-line bg-surface">
  <div class="tg-container py-12 lg:py-16">
    <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr] lg:gap-12">

      <div>
        <a href="<?= url('') ?>" class="inline-flex items-center gap-2.5" aria-label="TEX-GIANG — trang chủ">
          <img src="<?= asset('images/logo-tgi-mark.webp') ?>" alt="TEX-GIANG (TGI)" width="62" height="40" class="footer-logo">
        </a>
        <p class="mt-4 max-w-[320px] text-[15px] leading-[1.7] text-muted"><?= e(settings('content_footer_intro')) ?></p>
        <ul class="mt-4 space-y-2 text-[15px] text-muted">
          <li class="flex items-center gap-2.5">
            <img src="<?= asset('images/icons/envelope.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
            <a href="mailto:<?= e($email) ?>" class="transition-colors hover:text-accent"><?= e($email) ?></a>
          </li>
        </ul>
        <?php if ($branches !== []): ?>
        <div class="mt-4 space-y-3 text-[13px] leading-[1.6] text-muted">
          <?php foreach ($branches as $branch): ?>
          <?php $branchPhoneList = $branchContacts[(int) $branch['id']] ?? []; ?>
          <?php if ($branchPhoneList === []) continue; ?>
          <div>
            <p class="font-medium text-ink">Nhà máy <?= e($branch['name']) ?></p>
            <ul class="mt-0.5 flex flex-wrap items-baseline gap-x-4 gap-y-1">
              <?php foreach ($branchPhoneList as $branchContact): ?>
              <li class="flex items-baseline gap-1.5 whitespace-nowrap">
                <img src="<?= asset('images/icons/telephone.svg') ?>" alt="" aria-hidden="true" width="14" height="14" class="tg-icon">
                <span><?= e($branchContact['full_name']) ?></span>
                <a href="tel:<?= e(\App\Models\BranchContact::telHref((string) $branchContact['phone'])) ?>" class="font-medium text-ink transition-colors hover:text-accent"><?= e($branchContact['phone']) ?></a>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <nav aria-label="Menu chân trang">
        <h2 class="text-[13px] font-semibold uppercase tracking-[0.12em] text-ink">Menu</h2>
        <ul class="mt-4 space-y-2.5 text-[15px]">
          <?php foreach ($footerMenuItems as $menuItem): ?>
          <li><a href="<?= e($menuItem['href']) ?>" class="text-muted transition-colors hover:text-accent"><?= e($menuItem['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div>
        <?php $footerStats = \App\Models\VisitStat::footer(); ?>
        <h2 class="text-[13px] font-semibold uppercase tracking-[0.12em] text-ink">Thống kê truy cập</h2>
        <ul class="mt-4 space-y-2.5 text-[15px]">
          <li class="text-muted">Lượt hôm nay: <span class="font-semibold text-ink"><?= number_format((int) $footerStats['today'], 0, ',', '.') ?></span></li>
          <li class="text-muted">Lượt truy cập tháng: <span class="font-semibold text-ink"><?= number_format((int) $footerStats['month'], 0, ',', '.') ?></span></li>
          <li class="text-muted">Tổng truy cập: <span class="font-semibold text-ink"><?= number_format((int) $footerStats['total'], 0, ',', '.') ?></span></li>
        </ul>
        <!-- Fanpage Facebook — plugin chính thức (data-href: fanpage TEX-GIANG) -->
      <div class="footer-fanpage mt-5">
        <div class="fb-page"
             data-href="https://www.facebook.com/congtycophantexgiang"
             data-width="500"
             data-small-header="false"
             data-adapt-container-width="true"
             data-hide-cover="false"
             data-show-facepile="false">
          <blockquote cite="https://www.facebook.com/congtycophantexgiang" class="fb-xfbml-parse-ignore">
            <a href="https://www.facebook.com/congtycophantexgiang" target="_blank" rel="noopener">TEX-GIANG trên Facebook</a>
          </blockquote>
        </div>
      </div>
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
  <?php if ($branches !== []): ?>
  <button type="button" class="float-btn float-btn--primary" data-open-contact-dialog
          aria-label="Liên hệ chi nhánh tuyển dụng" title="Liên hệ chi nhánh">
    <img src="<?= asset('images/icons/telephone.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[16px]">
  </button>
  <?php endif; ?>
  <a href="<?= e(settings('facebook', 'https://www.facebook.com/')) ?>" target="_blank" rel="noopener"
     class="float-btn float-btn--facebook" aria-label="Fanpage Facebook TEX-GIANG" title="Fanpage Facebook">
    <img src="<?= asset('images/icons/facebook.svg') ?>" alt="" aria-hidden="true" width="24" height="24" class="tg-icon float-btn__icon">
  </a>
  <?php $messengerUrl = messenger_url(); ?>
  <?php if ($messengerUrl !== ''): ?>
  <a href="<?= e($messengerUrl) ?>" target="_blank" rel="noopener" class="float-btn float-btn--messenger" aria-label="Chat Messenger fanpage TEX-GIANG" title="Chat Messenger">
    <svg class="float-btn__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="26" height="26"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.974 12-11.111S18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8.6l3.131 3.26L19.752 8.6l-6.561 6.363z"/></svg>
  </a>
  <?php endif; ?>
  <?php if ($zalo !== ''): ?>
  <a href="<?= e(zalo_url()) ?>" target="_blank" rel="noopener" class="float-btn float-btn--zalo" aria-label="Chat Zalo tuyển dụng" title="Chat Zalo">
    <img src="<?= asset('images/icons/zalo.svg') ?>" alt="" aria-hidden="true" width="24" height="24" class="tg-icon float-btn__icon">
  </a>
  <?php endif; ?>
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

<?php if ($branches !== []): ?>
<div id="contact-dialog" class="tg-modal hidden" role="dialog" aria-modal="true" aria-labelledby="contact-dialog-title" data-contact-dialog>
  <div class="relative flex max-h-[min(90vh,640px)] w-full max-w-[420px] flex-col overflow-hidden rounded-2xl bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
      <h2 id="contact-dialog-title" class="text-[17px] font-medium leading-tight text-ink">Liên hệ tuyển dụng</h2>
      <button type="button" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-muted transition-colors hover:bg-surface hover:text-ink" data-close-contact-dialog aria-label="Đóng hộp thoại liên hệ">
        <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="18" height="18" class="tg-icon">
      </button>
    </div>

    <div class="flex-1 space-y-2 overflow-y-auto p-3">
      <?php foreach ($branches as $branch): ?>
      <?php $contacts = $branchContacts[(int) $branch['id']] ?? []; ?>
      <section class="rounded-card border border-line px-3.5 py-2.5">
        <p class="text-[13px] font-medium leading-snug text-ink">Nhà máy <?= e($branch['name']) ?></p>
        <?php if ($contacts !== []): ?>
        <ul class="mt-1.5 space-y-1">
          <?php foreach ($contacts as $contact): ?>
          <li class="flex items-baseline justify-between gap-3">
            <span class="min-w-0 truncate text-[13px] text-muted">
              <?= e($contact['full_name']) ?><?php if (!empty($contact['title'])): ?><span class="text-[12px] text-muted"> — <?= e((string) $contact['title']) ?></span><?php endif; ?>
            </span>
            <a href="tel:<?= e(\App\Models\BranchContact::telHref((string) $contact['phone'])) ?>"
               class="shrink-0 whitespace-nowrap text-[14px] font-semibold text-accent transition-colors hover:text-accent-strong"><?= e($contact['phone']) ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="mt-1 text-[13px] text-muted">Đang cập nhật</p>
        <?php endif; ?>
      </section>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  var dialog = document.querySelector('[data-contact-dialog]');
  if (!dialog) return;

  var closeEls = dialog.querySelectorAll('[data-close-contact-dialog]');
  var opener = null;

  function open() {
    dialog.classList.remove('hidden');
    document.body.classList.add('has-dialog');
    var first = dialog.querySelector('[data-close-contact-dialog]');
    if (first) first.focus();
  }
  function close() {
    dialog.classList.add('hidden');
    document.body.classList.remove('has-dialog');
    if (opener) opener.focus();
  }

  // Uỷ quyền click: nút có thể nằm sau script (nút cố định ở float-stack) vẫn bắt được.
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-open-contact-dialog]');
    if (!trigger) return;
    if (!dialog.classList.contains('hidden')) return;
    opener = trigger;
    open();
  });
  closeEls.forEach(function (button) { button.addEventListener('click', close); });
  dialog.addEventListener('click', function (event) {
    if (event.target === dialog) close();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !dialog.classList.contains('hidden')) close();
  });
})();
</script>

<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v21.0"></script>