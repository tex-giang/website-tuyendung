<?php
/** Trang xác nhận đã gửi hồ sơ (thật, lưu DB). */
$last = $last ?? [];
$hotline = settings('hotline', '02733937274');
$hotlineDisplay = settings('hotline_display', '0273 393 7274');
?>
<div class="tg-container">
  <div class="mx-auto max-w-[640px]">
    <h1 class="mt-3 text-[clamp(2.25rem,5vw,4.5rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
      Hồ sơ đã được gửi
    </h1>

    <p class="mt-4 max-w-[56ch] text-[18px] leading-[1.6] text-muted">
      Cảm ơn <?= e($last['full_name'] ?? 'bạn') ?> đã ứng tuyển tại TEX-GIANG.
      Bộ phận nhân sự xem hồ sơ và gọi lại theo số điện thoại bạn đã để lại.
    </p>

    <?php if (!empty($last['id'])): ?>
    <div class="mt-6 inline-flex flex-wrap items-center gap-x-3 gap-y-1 rounded-card bg-surface px-5 py-4 text-[15px]">
      <span class="text-muted">Mã hồ sơ</span>
      <strong class="text-ink">HS-<?= str_pad((string) (int) $last['id'], 5, '0', STR_PAD_LEFT) ?></strong>
      <?php if (!empty($last['job_title'])): ?>
      <span class="text-muted">·</span>
      <span class="text-muted">Vị trí: <?= e($last['job_title']) ?></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="mt-8 flex flex-wrap gap-3">
      <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--primary">Xem vị trí khác</a>
      <a href="<?= url('') ?>" class="tg-btn tg-btn--ghost">Về trang chủ</a>
    </div>

    <div class="mt-10 rounded-card bg-surface p-6 lg:p-8">
      <h2 class="text-[16px] font-medium text-ink">Các bước tiếp theo</h2>
      <ol class="mt-4 space-y-4 text-[16px] leading-[1.6] text-muted">
        <li><span class="block font-medium text-body">Trong 24 – 48 giờ làm việc</span> Nhân sự gọi xác nhận thông tin và sắp lịch.</li>
        <li><span class="block font-medium text-body">Buổi phỏng vấn tại nhà máy</span> Mang CCCD, gặp trực tiếp tại Nhà máy Tân Hương 2.</li>
        <li><span class="block font-medium text-body">Nhận việc</span> Thử tay nghề hoặc vào lớp đào tạo nghề có lương.</li>
      </ol>
    </div>

    <p class="mt-6 max-w-[62ch] text-[14px] leading-[1.7] text-muted">
      Cần hỏi gấp? Gọi <a href="tel:<?= e($hotline) ?>" class="text-body underline underline-offset-2 hover:text-accent"><?= e($hotlineDisplay) ?></a>
      hoặc chat Zalo <a href="https://zalo.me/<?= e(settings('zalo', $hotline)) ?>" target="_blank" rel="noopener" class="text-body underline underline-offset-2 hover:text-accent"><?= e($hotlineDisplay) ?></a>.
    </p>
  </div>
  <img class="notice-art" src="<?= asset('images/application-success.svg') ?>" width="400" height="330" alt="" aria-hidden="true">
</div>
