<?php
/**
 * Trang tạm cho các trang chưa port xong — hiện không còn dùng cho tuyển dụng
 * công khai (đã port đủ), giữ lại cho các trang mở rộng sau này.
 */
$heading = $heading ?? 'Trang này';
?>
<section class="tg-container py-20 text-center lg:py-28" data-reveal>
  <p class="text-[14px] font-semibold uppercase tracking-[0.14em] text-accent">Đang hoàn thiện</p>
  <h1 class="mt-3 text-[clamp(2rem,4vw,3rem)] font-medium leading-[1.15] text-ink"><?= e($heading) ?> đang được xây dựng</h1>
  <p class="mx-auto mt-4 max-w-[56ch] text-[18px] leading-[1.6] text-muted">
    Nội dung trang này sẽ sớm được cập nhật. Trong thời gian đó, bạn vẫn xem được danh sách việc làm và nộp hồ sơ trực tuyến.
  </p>
  <div class="mt-8 flex flex-wrap justify-center gap-3">
    <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--primary">Xem việc làm đang tuyển</a>
    <a href="<?= url('') ?>" class="tg-btn tg-btn--ghost">Về trang chủ</a>
  </div>
</section>
