<?php
$message = $message ?? 'Trang bạn tìm không tồn tại hoặc đã được di chuyển.';
$page['robots'] = 'noindex, follow';
?>
<section class="tg-container py-20 text-center lg:py-28" data-reveal>
  <p class="text-[14px] font-semibold uppercase tracking-[0.14em] text-accent">Lỗi 404</p>
  <h1 class="mt-3 text-[clamp(2rem,4vw,3rem)] font-medium leading-[1.15] text-ink">Không tìm thấy trang</h1>
  <p class="mx-auto mt-4 max-w-[52ch] text-[18px] leading-[1.6] text-muted"><?= e($message) ?></p>
  <div class="mt-8 flex flex-wrap justify-center gap-3">
    <a href="<?= url('') ?>" class="tg-btn tg-btn--primary">Về trang chủ</a>
    <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--ghost">Xem việc làm đang tuyển</a>
  </div>
</section>
