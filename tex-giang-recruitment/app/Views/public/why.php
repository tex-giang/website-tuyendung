<?php
/**
 * Trang why-tex-giang — port tự động từ E:/websitetuyendung/why-tex-giang.html.
 * Chạy: node tools/port-static-page.mjs why-tex-giang
 * Nội dung tĩnh; phần cần động (số liệu, timeline, form) chỉnh tay sau khi port.
 */
?>
<section class="tg-container pt-14 lg:pt-20"><div class="page-intro"><div>
      <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
        <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="text-body">Phúc lợi</span>
      </nav>

      <h1 class="mt-4 max-w-[900px] text-[clamp(2rem,5vw,3.25rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        <?= e(settings('content_why_title', 'Vì sao chọn TEX-GIANG')) ?>
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        <?= e(settings('content_why_lead', 'Bốn điều người lao động hỏi nhiều nhất khi đến nhà máy: môi trường có an toàn không, thu nhập bao nhiêu, có được dạy nghề không, và đi lại thế nào.')) ?>
      </p>
    </div><figure class="intro-photo"><img src="<?= asset('images/company/tan-huong-2.webp') ?>" width="360" height="300" alt="Toàn cảnh nhà máy Tân Hương 2 trong hệ thống TEX-GIANG" fetchpriority="high"><figcaption>Nhà máy Tân Hương 2 · TEX-GIANG</figcaption></figure></div></section>

    <section class="tg-container pb-20 pt-12 lg:pb-24">
      <div class="grid gap-12 md:grid-cols-2 md:gap-x-16 md:gap-y-14">
        <?php foreach ($benefitGroups as $groupItem): ?>
        <div data-reveal>
          <h2 class="text-[20px] font-medium text-ink"><img class="tg-icon" src="<?= asset('images/icons/' . e($groupItem['icon']) . '.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> <?= e($groupItem['title']) ?></h2>
          <?php if (($groupItem['intro'] ?? '') !== ''): ?>
          <p class="mt-3 max-w-[52ch] text-[16px] leading-[1.7] text-muted"><?= e($groupItem['intro']) ?></p>
          <?php endif; ?>
          <ul class="mt-4 space-y-2 text-[16px] leading-[1.6] text-muted">
            <?php foreach ($groupItem['items'] as $item): ?>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> <?= e($item['text']) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="bg-surface py-20 lg:py-24">
      <div class="tg-container">
        <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] tracking-[-0.015em] text-ink" data-reveal>
          So sánh với nơi khác
        </h2>
        <p class="mt-3 max-w-[56ch] text-[16px] leading-[1.7] text-muted" data-reveal>
          Những điểm ứng viên thường hỏi lại sau buổi phỏng vấn.
        </p>

        <div class="mt-8 overflow-x-auto">
          <table class="compare-table w-full border-collapse text-left" data-reveal>
            <thead>
              <tr>
                <th scope="col" class="py-3 pr-3 text-[14px] font-medium text-muted sm:pr-6">Hạng mục</th>
                <th scope="col" class="py-3 pr-3 text-[14px] font-medium text-accent sm:pr-6">Tại TEX-GIANG</th>
                <th scope="col" class="py-3 text-[14px] font-medium text-muted">Nơi khác thường thấy</th>
              </tr>
            </thead>
            <tbody class="text-[15px] leading-[1.6] sm:text-[16px]">
              <?php foreach ($comparisons as $comparison): ?>
              <tr class="border-t border-line">
                <th scope="row" class="py-4 pr-3 text-left font-medium text-ink sm:pr-6"><?= e($comparison['label']) ?></th>
                <td class="py-4 pr-3 text-body sm:pr-6" data-label="TEX-GIANG"><?= e($comparison['tex_giang']) ?></td>
                <td class="py-4 text-muted" data-label="Nơi khác"><?= e($comparison['other_value']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="tg-container py-20 lg:py-28">
      <div class="flex flex-col items-start justify-between gap-10 rounded-media bg-accent p-8 lg:flex-row lg:items-center lg:p-14" data-reveal>
        <div class="max-w-[560px]">
          <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] text-white">Bắt đầu từ một buổi phỏng vấn</h2>
          <p class="mt-4 text-[16px] leading-[1.7] text-white/85">Điền 2 phút, nhân sự gọi lại trong 24 giờ làm việc và hẹn lịch thử tay nghề.</p>
        </div>
        <div class="flex w-full flex-col items-start gap-5 sm:flex-row sm:items-center lg:w-auto">
          <a href="<?= url('apply') ?>" class="tg-btn tg-btn--white w-full sm:w-auto">Nộp hồ sơ</a>
          <a href="<?= url('jobs') ?>" class="inline-flex items-center gap-2 whitespace-nowrap text-[16px] font-medium text-white/90 transition-colors hover:text-white">
            Xem việc làm <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
          </a>
        </div>
      </div>
    </section>

<section class="tg-container pb-20 lg:pb-28" aria-labelledby="company-scenes">
  <div class="image-story">
    <div><h2 id="company-scenes">Con người và nơi làm việc</h2><p>Khám phá không gian nhà máy, hoạt động cộng đồng và định hướng phát triển bền vững trong hệ thống TEX-GIANG.</p><a href="<?= url('about') ?>" class="tg-link mt-5">Tìm hiểu doanh nghiệp <img class="tg-icon" src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" width="20" height="20"></a></div>
    <figure><img src="<?= asset('images/company/social.webp') ?>" width="600" height="450" loading="lazy" alt="Đại diện doanh nghiệp trao quà trong hoạt động cộng đồng"><figcaption>Gắn kết cùng cộng đồng</figcaption></figure>
    <figure><img src="<?= asset('images/company/environment.webp') ?>" width="600" height="450" loading="lazy" alt="Hệ thống điện mặt trời trên mái nhà xưởng TEX-GIANG"><figcaption>Hướng tới sản xuất bền vững</figcaption></figure>
  </div>
</section>
