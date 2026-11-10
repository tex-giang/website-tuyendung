<?php
/**
 * Trang about — port tự động từ E:/websitetuyendung/about.html.
 * Chạy: node tools/port-static-page.mjs about
 * Nội dung tĩnh; phần cần động (số liệu, timeline, form) chỉnh tay sau khi port.
 */
?>
<section class="tg-container pt-14 lg:pt-20" data-reveal>
      <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
        <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="text-body">Về chúng tôi</span>
      </nav>

      <h1 class="mt-4 max-w-[720px] text-[clamp(2rem,5vw,3.25rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        <?= e(settings('content_about_title', 'May xuất khẩu tại Tân Hương từ năm 2007')) ?>
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        <?= e(settings('content_about_lead', 'Công ty Cổ phần TEX-GIANG (TGI) thành lập tháng 3/2007, vận hành 3 khu sản xuất tại Tân Hương, Cai Lậy và Chợ Gạo.')) ?>
      </p>

      <figure class="mt-12 lg:mt-16">
        <img src="<?= asset('images/banner-texgiang.webp') ?>" srcset="<?= asset('images/banner-texgiang-800.webp') ?> 800w, <?= asset('images/banner-texgiang.webp') ?> 2048w" sizes="(max-width: 768px) 100vw, 1200px"
             alt="Cổng và khu nhà xưởng Nhà máy Tân Hương 2 — Công ty Cổ phần TEX-GIANG"
             width="2048" height="768" class="w-full rounded-media">
      </figure>
    </section>

    <section class="mt-16 bg-surface py-16 lg:mt-20 lg:py-20">
      <div class="tg-container grid grid-cols-2 gap-x-5 gap-y-7 md:gap-x-8 lg:grid-cols-4">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="stat" data-reveal>
          <span class="block text-[clamp(1.75rem,3vw,3rem)] font-medium leading-none tracking-[-0.02em] text-accent"><?= e(settings('stat_' . $i . '_value')) ?></span>
          <span class="mt-2 block text-[14px] leading-snug text-muted"><?= e(settings('stat_' . $i . '_label')) ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </section>

    <section class="tg-container py-20 lg:py-28">
      <div class="grid items-start gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">
        <div data-reveal>
          <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] tracking-[-0.015em] text-ink">
            Chúng tôi làm gì
          </h2>
          <p class="mt-5 max-w-[62ch] text-[16px] leading-[1.7] text-muted">
            Trụ sở và Nhà máy 1 đặt tại Lô BII-8, đường D3, KCN Tân Hương, xã Tân Hương, tỉnh Đồng Tháp. Tháng 8/2024 công ty nhận thêm <strong class="font-medium text-body">Nhà máy Tân Hương 2</strong> rộng 4,8 ha (lô BI-5,6,7,8) trong cùng khu công nghiệp; hai khu còn lại ở Cai Lậy và Chợ Gạo. Hiện công ty có 3 khu sản xuất và khoảng 3.500 lao động.
          </p>
          <p class="mt-4 max-w-[62ch] text-[16px] leading-[1.7] text-muted">
            Sản phẩm chính là áo khoác down, insulated, workwear bảo hộ, đồ outdoor và denim. Khách hàng gồm Kathmandu, Dickies, Helly Hansen, Wilson, Haglofs, First Tactical, Didriksons… tại châu Âu, Mỹ, Úc và New Zealand. Công suất khoảng 8 triệu sản phẩm/năm (TGI).
          </p>

          <figure class="mt-8"><img src="<?= asset('images/company/tan-huong-1.webp') ?>" width="360" height="300" loading="lazy" alt="Nhà máy Tân Hương 1 với hệ thống điện mặt trời trên mái" class="w-full max-w-[480px] rounded-media"><figcaption class="photo-credit">Nhà máy Tân Hương 1 · Hệ thống TEX-GIANG</figcaption></figure>

                    <h2 class="mt-12 text-[20px] font-medium text-ink">Mốc phát triển</h2>
          <ol class="about-timeline">
            <?php foreach ($milestones as $milestone): ?>
            <li class="about-timeline__item<?= (int) $milestone['is_highlight'] === 1 ? ' about-timeline__item--now' : '' ?>">
              <span class="about-timeline__year"><?= e($milestone['year_label']) ?></span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text"><?= e($milestone['text']) ?><?php if ((int) $milestone['is_highlight'] === 1): ?> <span class="about-timeline__badge">Đang vận hành</span><?php endif; ?></p>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>

        <aside class="space-y-6" data-reveal>
          <div class="rounded-card bg-surface p-6">
            <h2 class="text-[16px] font-medium text-ink">Chứng nhận đang áp dụng</h2>
            <ul class="mt-4 space-y-3 text-[16px] leading-[1.6] text-muted">
              <?php foreach ($certifications as $certification): ?>
              <li><?= e($certification['label']) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="rounded-card bg-surface p-6">
            <h2 class="text-[16px] font-medium text-ink">Thông tin doanh nghiệp</h2>
            <p class="mt-4 text-[16px] leading-[1.7] text-muted">
              <strong class="font-medium text-body">Công ty Cổ phần TEX-GIANG (TGI)</strong><br>
              Lô BII-8, đường D3, KCN Tân Hương, xã Tân Hương, tỉnh Đồng Tháp<br>
              Liên hệ người phụ trách theo nhà máy tại <a href="<?= url('contact') ?>" class="text-body hover:text-accent">trang Liên hệ</a>
            </p>
            <a href="<?= url('contact') ?>" class="tg-link mt-5">Xem bản đồ và liên hệ <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]"></a>
          </div>
        </aside>
      </div>
    </section>

    <section class="tg-container pb-20 lg:pb-28">
      <div class="grid gap-10 md:grid-cols-3 md:gap-12" data-reveal>
        <?php
        $valueGroups = [];
        foreach ($values as $valueItem) {
            $valueGroups[$valueItem['title']][] = $valueItem['body'];
        }
        foreach ($valueGroups as $title => $paragraphs):
        ?>
        <div>
          <h2 class="text-[20px] font-medium text-ink"><?= e($title) ?></h2>
          <?php foreach ($paragraphs as $paragraph): ?>
          <p class="mt-3 max-w-[46ch] text-[16px] leading-[1.7] text-muted"><?= e($paragraph) ?></p>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="tg-container pb-20 lg:pb-28">
      <div class="flex flex-col items-start justify-between gap-10 rounded-media bg-accent p-8 lg:flex-row lg:items-center lg:p-14" data-reveal>
        <div class="max-w-[560px]">
          <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] text-white">Cùng làm việc tại Tân Hương 2</h2>
          <p class="mt-4 text-[16px] leading-[1.7] text-white/85"><?= (int) $jobCount ?> vị trí đang mở, từ công nhân may chưa có kinh nghiệm đến kỹ sư IE và QC.</p>
        </div>
        <div class="flex w-full flex-col items-start gap-5 sm:flex-row sm:items-center lg:w-auto">
          <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--white w-full sm:w-auto">Xem việc làm</a>
          <a href="<?= url('apply') ?>" class="inline-flex items-center gap-2 whitespace-nowrap text-[16px] font-medium text-white/90 transition-colors hover:text-white">
            Nộp hồ sơ <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
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
