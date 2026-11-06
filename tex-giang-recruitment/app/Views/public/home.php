<?php
/** Trang chủ — port từ index.html tĩnh, dữ liệu lấy từ MySQL. */
$hotline = settings('hotline', '02733937274');
$hotlineDisplay = settings('hotline_display', '0273 393 7274');
$branches = \App\Models\Branch::options();
$stats = [];
for ($i = 1; $i <= 4; $i++) {
    $stats[] = [settings('stat_' . $i . '_value'), settings('stat_' . $i . '_label')];
}
?>
<section>
  <div class="tg-container">
    <div class="hero-slider" data-hero-slider aria-roledescription="carousel" aria-label="Hình ảnh nhà máy và môi trường làm việc TEX-GIANG">
      <div class="hero-slider__viewport">
        <div class="hero-slider__track" data-slider-track>
          <?php if ($heroBanners === []): ?>
          <div class="hero-slider__slide" data-slide>
            <img class="hero-slider__img hero-slider__img--banner" src="<?= asset('images/banner-texgiang.webp') ?>"
                 alt="Cổng Nhà máy Tân Hương 2 — TEX-GIANG" width="2048" height="768" fetchpriority="high">
          </div>
          <?php endif; ?>
          <?php foreach ($heroBanners as $index => $banner): ?>
          <div class="hero-slider__slide" data-slide>
            <img class="hero-slider__img<?= $index === 0 ? ' hero-slider__img--banner' : '' ?>"
                 src="<?= asset((string) $banner['image_path']) ?>"
                 <?php if (!empty($banner['image_path_small'])): ?>srcset="<?= asset((string) $banner['image_path_small']) ?> 800w, <?= asset((string) $banner['image_path']) ?> 2048w"
                 sizes="(max-width: 768px) 100vw, 1200px"<?php endif; ?>
                 alt="<?= e($banner['image_alt'] ?? '') ?>" width="2048" height="768"
                 <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
            <?php if (!empty($banner['title'])): ?>
            <span class="hero-slider__shade" aria-hidden="true"></span>
            <div class="hero-slider__caption">
              <?php if (!empty($banner['subtitle'])): ?><p class="hero-slider__eyebrow"><?= e($banner['subtitle']) ?></p><?php endif; ?>
              <p class="hero-slider__title"><?= e($banner['title']) ?></p>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" class="hero-slider__btn hero-slider__btn--prev" data-slider-prev aria-label="Ảnh trước">
          <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20">
        </button>
        <button type="button" class="hero-slider__btn hero-slider__btn--next" data-slider-next aria-label="Ảnh tiếp theo">
          <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20">
        </button>
        <div class="hero-slider__dots" data-slider-dots role="group" aria-label="Chọn ảnh"></div>
      </div>
      <p class="sr-only" data-slider-live aria-live="polite"></p>
    </div>

    <div class="mt-10 flex flex-col items-center text-center lg:mt-14">
      <h1 class="max-w-[1000px] text-[clamp(2rem,5.5vw,5rem)] font-medium leading-[1.08] tracking-[-0.025em] text-ink">
        <?= e(settings('content_hero_title_1', 'Một công việc ổn định.')) ?><br><span class="text-accent"><?= e(settings('content_hero_title_2', 'Thu nhập xứng đáng.')) ?></span>
      </h1>

      <p class="mt-6 max-w-[660px] text-[18px] leading-[1.6] text-muted">
        <?= e(settings('content_hero_subtitle')) ?>
      </p>

      <div class="mt-10 flex flex-wrap items-center justify-center gap-x-7 gap-y-3">
        <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--primary">Xem vị trí đang tuyển</a>
        <a href="<?= url('apply') ?>" class="tg-link">Nộp hồ sơ trong 2 phút
          <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]"></a>
      </div>

      <p class="mt-5 text-[14px] text-muted">
        Đang tuyển <strong class="font-medium text-body"><span data-open-job-count><?= (int) $jobCount ?></span> vị trí</strong><span class="max-md:hidden"> · Cập nhật <?= date('d/m/Y') ?></span>
      </p>
    </div>
  </div>
</section>

<section class="pt-16 lg:pt-20">
  <div class="tg-container">
    <form action="<?= url('jobs') ?>" method="GET" role="search"
          class="search-pill mx-auto flex max-w-[1000px] flex-col gap-2 2xl:max-w-[1080px] md:flex-row md:items-center md:gap-0
                 md:rounded-full md:border md:border-line md:bg-white md:p-1.5 md:transition-colors md:focus-within:border-accent">
      <label class="field max-md:rounded-xl max-md:bg-surface">
        <img src="<?= asset('images/icons/search.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
        <input type="text" name="q" placeholder="Vị trí, từ khoá — ví dụ: công nhân may, QC, IE" aria-label="Từ khoá tìm kiếm">
      </label>
      <label class="field max-md:rounded-xl max-md:bg-surface">
        <img src="<?= asset('images/icons/geo-alt.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
        <select name="location" aria-label="Địa điểm làm việc">
          <option value="">Tất cả địa điểm</option>
          <?php foreach ($branches as $slug => $name): ?>
          <option value="<?= e($slug) ?>">Khu <?= e($name) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit" class="tg-btn tg-btn--primary h-11 rounded-full px-7">Tìm việc</button>
    </form>
  </div>
</section>

<section class="mt-16 bg-surface py-16 lg:mt-20 lg:py-20">
  <div class="tg-container grid grid-cols-2 gap-x-5 gap-y-7 md:gap-x-8 lg:grid-cols-4">
    <?php foreach ($stats as [$value, $label]): ?>
    <div class="stat" data-reveal>
      <span class="block text-[clamp(1.75rem,3vw,3rem)] font-medium leading-none tracking-[-0.02em] text-accent"><?= e($value) ?></span>
      <span class="mt-2 block text-[14px] leading-snug text-muted"><?= e($label) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="py-20 lg:py-32">
  <div class="tg-container">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between" data-reveal>
      <div>
        <h2 class="text-[clamp(1.75rem,3.4vw,2.75rem)] font-medium leading-[1.2] tracking-[-0.015em] text-ink">Vị trí đang tuyển</h2>
        <p class="mt-2.5 text-[16px] text-muted">Bổ sung nhân sự cho khu Tân Hương.</p>
      </div>
      <a href="<?= url('jobs') ?>" class="tg-link">Xem tất cả <span data-open-job-count><?= (int) $jobCount ?></span> vị trí
        <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]"></a>
    </div>

    <div class="mt-10" data-job-slider>
      <?php if ($jobs === []): ?>
      <p class="rounded-card bg-surface p-8 text-center text-[16px] text-muted">Hiện chưa có vị trí nào đang mở. Vui lòng quay lại sau.</p>
      <?php else: ?>
      <div class="hidden" data-job-source>
        <?php foreach ($jobs as $job): ?>
          <?php require BASE_PATH . '/app/Views/partials/public/job-card.php'; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($ad !== null): ?>
<section class="tg-container pb-20 lg:pb-32">
  <div class="flex flex-col items-start justify-between gap-10 rounded-media bg-accent p-8 lg:flex-row lg:items-center lg:p-14 2xl:p-16" data-reveal>
    <div class="max-w-[560px] xl:max-w-[620px]">
      <h2 class="text-[clamp(1.75rem,3.4vw,2.75rem)] font-medium leading-[1.2] text-white"><?= e($ad['title'] ?? '') ?></h2>
      <p class="mt-4 max-w-[52ch] text-[16px] leading-[1.7] text-white/85"><?= e($ad['description'] ?? '') ?></p>
    </div>
    <div class="flex w-full flex-col items-start gap-5 sm:flex-row sm:items-center lg:w-auto">
      <a href="<?= e(url((string) ($ad['link_url'] ?: 'apply'))) ?>" class="tg-btn tg-btn--white w-full sm:w-auto"><?= e($ad['cta_label'] ?: 'Đăng ký ngay') ?></a>
      <a href="https://zalo.me/<?= e(settings('zalo', $hotline)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 whitespace-nowrap text-[16px] font-medium text-white/90 transition-colors hover:text-white">
        Chat Zalo tuyển dụng <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="tg-container grid items-center gap-10 pb-20 lg:grid-cols-2 lg:gap-16 lg:pb-32">
  <figure data-reveal>
    <img src="<?= asset('images/banner-texgiang.webp') ?>" srcset="<?= asset('images/banner-texgiang-800.webp') ?> 800w, <?= asset('images/banner-texgiang.webp') ?> 2048w"
         sizes="(max-width: 768px) 100vw, 1200px"
         alt="Khu nhà xưởng và cổng Nhà máy Tân Hương 2" width="2048" height="768" loading="lazy"
         class="aspect-[4/3] w-full rounded-media object-cover object-right">
  </figure>

  <div data-reveal>
    <h2 class="text-[clamp(1.75rem,3.4vw,2.75rem)] font-medium leading-[1.2] tracking-[-0.015em] text-ink">Về TEX-GIANG</h2>
    <p class="mt-4 max-w-[62ch] text-[16px] leading-[1.7] text-muted"><?= e(settings('content_about_1')) ?></p>
    <p class="mt-4 max-w-[62ch] text-[16px] leading-[1.7] text-muted"><?= e(settings('content_about_2')) ?></p>

    <div class="mt-8 flex flex-wrap gap-10">
      <div>
        <span class="block text-[24px] font-medium leading-tight text-ink">2007</span>
        <span class="mt-1 block text-[14px] text-muted">Thành lập, may xuất khẩu Âu – Mỹ</span>
      </div>
      <div>
        <span class="block text-[24px] font-medium leading-tight text-ink">SA8000 · ISO 9001</span>
        <span class="mt-1 block text-[14px] text-muted">Chứng nhận đang áp dụng</span>
      </div>
    </div>

    <div class="mt-8 flex flex-wrap gap-7">
      <a href="<?= url('about') ?>" class="tg-link">Về chúng tôi
        <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]"></a>
      <a href="<?= url('why-tex-giang') ?>" class="tg-link">Chế độ phúc lợi
        <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]"></a>
    </div>
  </div>
</section>

<section class="tg-container pb-20 lg:pb-28" aria-labelledby="company-scenes">
  <div class="image-story">
    <div>
      <h2 id="company-scenes">Con người và nơi làm việc</h2>
      <p>Khám phá không gian nhà máy, hoạt động cộng đồng và định hướng phát triển bền vững trong hệ thống TEX-GIANG.</p>
      <a href="<?= url('about') ?>" class="tg-link mt-5">Tìm hiểu doanh nghiệp
        <img class="tg-icon" src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" width="20" height="20"></a>
    </div>
    <figure><img src="<?= asset('images/company/social.webp') ?>" width="600" height="450" loading="lazy" alt="Đại diện doanh nghiệp trao quà trong hoạt động cộng đồng"><figcaption>Gắn kết cùng cộng đồng</figcaption></figure>
    <figure><img src="<?= asset('images/company/environment.webp') ?>" width="600" height="450" loading="lazy" alt="Hệ thống điện mặt trời trên mái nhà xưởng TEX-GIANG"><figcaption>Hướng tới sản xuất bền vững</figcaption></figure>
  </div>
</section>

<?php
/* Dữ liệu cho dialog "Tuyển gấp" — lấy từ DB, render sẵn để JS chỉ việc dựng dialog. */
$urgentJson = [];
foreach (($urgentJobs ?? []) as $uJob) {
    $urgentJson[] = [
        'slug'       => (string) $uJob['slug'],
        'title'      => preg_replace('/^\d+\s+/', '', (string) $uJob['title']),
        'salary'     => salary_short((string) ($uJob['salary_text'] ?? '')) ?: 'Thỏa thuận',
        'location'   => (string) ($uJob['branch_name'] ?? ''),
        'deadline'   => vn_date($uJob['deadline'] ?? null) ?: '—',
        'status'     => (int) ($uJob['is_urgent'] ?? 0) === 1 ? 'urgent' : 'expiring',
        'detail_url' => url('jobs/' . $uJob['slug']),
    ];
}
?>
<script type="application/json" id="tg-urgent-jobs"
        data-icon-close="<?= e(asset('images/icons/x-lg.svg')) ?>"
        data-jobs-url="<?= e(url('jobs')) ?>"
        data-apply-url="<?= e(url('apply')) ?>"><?= json_encode($urgentJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>