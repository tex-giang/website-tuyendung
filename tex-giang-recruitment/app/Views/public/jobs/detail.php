<?php
/** Chi tiết tin tuyển dụng — port từ job-detail.html, dữ liệu MySQL. */
$page['main_class'] = 'pb-24 lg:pb-0';
$chip = job_chip($job);
$applyUrl = url('apply?job=' . urlencode((string) $job['slug']));
$hotline = settings('hotline', '02733937274');
$hotlineDisplay = settings('hotline_display', '0273 393 7274');
$descriptionLines = text_lines($job['description'] ?? '');
$requirementLines = text_lines($job['requirements'] ?? '');
$benefitCards = benefit_items($job['benefits'] ?? '');
$summary = ($job['meta_description'] ?? '') !== '' ? (string) $job['meta_description'] : excerpt((string) $job['description'], 220);
?>
<div class="tg-container pt-8 lg:pt-10">
  <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
    <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
    <span class="mx-1.5" aria-hidden="true">/</span>
    <a href="<?= url('jobs') ?>" class="transition-colors hover:text-accent">Việc làm</a>
    <span class="mx-1.5" aria-hidden="true">/</span>
    <span class="text-body"><?= e(preg_replace('/^\d+\s+/', '', (string) $job['title'])) ?></span>
  </nav>
</div>

<section class="tg-container pt-6 lg:pt-8" data-reveal>
  <h1 class="max-w-[720px] text-[clamp(2rem,3.2vw,2.5rem)] font-medium leading-[1.2] tracking-[-0.02em] text-ink">
    <?= e(preg_replace('/^\d+\s+/', '', (string) $job['title'])) ?>
  </h1>

  <div class="mt-4 flex flex-wrap items-center gap-2">
    <span class="tg-chip<?= $chip['muted'] ? ' tg-chip--muted' : '' ?>"><?= e($chip['label']) ?></span>
    <span class="text-[14px] text-muted">TEX-GIANG · <?= e($job['category_name'] ?? 'Tuyển dụng') ?>
      · Đăng ngày <?= e(vn_date($job['posted_at'] ?: $job['created_at'])) ?>
      · Hạn nộp <?= e(vn_date($job['deadline']) ?: 'đến khi tuyển đủ') ?></span>
  </div>

  <p class="mt-6 max-w-[60ch] text-[18px] leading-[1.6] text-muted"><?= e($summary) ?></p>

  <dl class="mt-8 grid grid-cols-2 gap-6 md:grid-cols-4">
    <div>
      <dt class="text-[14px] text-muted">Thu nhập</dt>
      <dd class="mt-1 text-[20px] font-medium text-accent"><?= e(salary_short((string) $job['salary_text']) ?: 'Thỏa thuận') ?></dd>
    </div>
    <div>
      <dt class="text-[14px] text-muted">Số lượng</dt>
      <dd class="mt-1 text-[16px] text-body"><?= (int) $job['quantity'] ?> người (nam/nữ)</dd>
    </div>
    <div>
      <dt class="text-[14px] text-muted">Kinh nghiệm</dt>
      <dd class="mt-1 text-[16px] text-body"><?= e($job['experience'] ?: 'Trao đổi khi phỏng vấn') ?></dd>
    </div>
    <div>
      <dt class="text-[14px] text-muted">Hình thức</dt>
      <dd class="mt-1 text-[16px] text-body"><?= e($job['employment_type'] ?: 'Toàn thời gian') ?></dd>
    </div>
  </dl>
</section>

<section class="tg-container mt-12 pb-20 lg:mt-16 lg:pb-28">
  <div class="grid items-start gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">

    <div class="space-y-12">
      <?php if ($descriptionLines !== []): ?>
      <div>
        <h2 class="text-[clamp(1.375rem,2.4vw,1.75rem)] font-medium leading-[1.25] text-ink" data-reveal>Mô tả công việc</h2>
        <ul class="mt-5 space-y-3 text-[16px] leading-[1.7] text-muted">
          <?php foreach ($descriptionLines as $line): ?>
          <li class="flex gap-3"><span aria-hidden="true" class="mt-2 block h-1.5 w-1.5 shrink-0 rounded-full bg-accent"></span> <?= e($line) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($requirementLines !== []): ?>
      <div>
        <h2 class="text-[clamp(1.375rem,2.4vw,1.75rem)] font-medium leading-[1.25] text-ink" data-reveal>Yêu cầu ứng viên</h2>
        <ul class="mt-5 space-y-3 text-[16px] leading-[1.7] text-muted">
          <?php foreach ($requirementLines as $line): ?>
          <li class="flex gap-3"><span aria-hidden="true" class="mt-2 block h-1.5 w-1.5 shrink-0 rounded-full bg-accent"></span> <?= e($line) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($benefitCards !== []): ?>
      <div>
        <h2 class="text-[clamp(1.375rem,2.4vw,1.75rem)] font-medium leading-[1.25] text-ink" data-reveal>Quyền lợi</h2>
        <ul class="mt-5 grid gap-3 sm:grid-cols-2" data-reveal>
          <?php foreach ($benefitCards as $benefit): ?>
          <li class="rounded-card bg-surface p-4">
            <strong class="block text-[16px] font-medium text-ink"><?= e($benefit['title']) ?></strong>
            <?php if ($benefit['desc'] !== ''): ?>
            <span class="mt-1 block text-[14px] leading-[1.6] text-muted"><?= e($benefit['desc']) ?></span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div>
        <h2 class="text-[clamp(1.375rem,2.4vw,1.75rem)] font-medium leading-[1.25] text-ink" data-reveal>Địa điểm làm việc</h2>
        <p class="mt-4 max-w-[62ch] text-[16px] leading-[1.7] text-muted">
          <?= e($job['location'] ?: ($job['branch_name'] ?? '')) ?>
        </p>
        <a href="<?= url('contact') ?>" class="tg-link mt-4">Xem bản đồ và tuyến xe
          <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]"></a>
      </div>

      <div class="rounded-media bg-accent p-8 lg:p-10" data-reveal>
        <h2 class="text-[clamp(1.375rem,2.4vw,1.75rem)] font-medium leading-[1.25] text-white">Nộp hồ sơ cho vị trí này</h2>
        <p class="mt-3 max-w-[52ch] text-[16px] leading-[1.7] text-white/85">Điền 2 phút trên điện thoại, bộ phận tuyển dụng gọi lại trong 24 giờ làm việc.</p>
        <a href="<?= $applyUrl ?>" class="tg-btn tg-btn--white mt-6">Ứng tuyển ngay</a>
      </div>
    </div>

    <aside class="space-y-6 lg:sticky lg:top-24">
      <div class="rounded-card bg-surface p-6" data-reveal>
        <p class="text-[14px] text-muted">Thu nhập</p>
        <p class="mt-1 text-[24px] font-medium text-accent"><?= e(salary_short((string) $job['salary_text']) ?: 'Thỏa thuận') ?></p>
        <p class="mt-4 text-[14px] leading-[1.6] text-muted">Hạn nộp hồ sơ</p>
        <p class="mt-1 text-[16px] font-medium text-ink"><?= e(vn_date($job['deadline']) ?: 'Đến khi tuyển đủ') ?></p>
        <a href="<?= $applyUrl ?>" class="tg-btn tg-btn--primary mt-6 w-full">Ứng tuyển vị trí này</a>
        <a href="tel:<?= e($hotline) ?>" class="tg-btn tg-btn--ghost mt-3 w-full">Gọi <?= e($hotlineDisplay) ?></a>
        <div class="share-row mt-5 flex flex-wrap gap-4">
          <button type="button" class="open-qr-modal tg-link text-[15px]">Mã QR</button>
          <button type="button" onclick="shareFacebook()" class="tg-link text-[15px]">Facebook</button>
          <button type="button" onclick="shareZalo()" class="tg-link text-[15px]">Zalo</button>
          <button type="button" onclick="copyLink()" class="tg-link text-[15px]">Sao chép liên kết</button>
          <button type="button" onclick="window.print()" class="tg-link text-[15px]">In tin</button>
        </div>
      </div>

      <div class="rounded-card bg-surface p-6" data-reveal>
        <h2 class="text-[16px] font-medium text-ink">Liên hệ ban tuyển dụng</h2>
        <p class="mt-3 text-[14px] leading-[1.7] text-muted">
          Phòng Nhân sự TEX-GIANG<br>
          <?= e(settings('address')) ?>
        </p>
        <p class="mt-3 text-[14px] leading-[1.7] text-muted">
          Hotline: <a href="tel:<?= e($hotline) ?>" class="text-body hover:text-accent"><?= e($hotlineDisplay) ?></a><br>
          Zalo tuyển dụng: <a href="https://zalo.me/<?= e(settings('zalo')) ?>" target="_blank" rel="noopener" class="text-body hover:text-accent">chat ngay</a>
        </p>
      </div>
    </aside>
  </div>

  <?php if ($related !== []): ?>
  <div class="mt-16 border-t border-line pt-10">
    <h2 class="text-[clamp(1.375rem,2.4vw,1.75rem)] font-medium text-ink">Vị trí tương tự</h2>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
      <?php foreach ($related as $rel): ?>
      <a href="<?= url('jobs/' . $rel['slug']) ?>" class="rounded-card border border-line p-5 transition-colors hover:border-accent">
        <p class="text-[14px] text-muted">TEX-GIANG · <?= e($rel['category_name'] ?? 'Tuyển dụng') ?></p>
        <h3 class="mt-2 text-[16px] font-medium leading-[1.4] text-ink"><?= e($rel['title']) ?></h3>
        <p class="mt-3 text-[14px] font-semibold text-accent"><?= e(salary_short((string) $rel['salary_text']) ?: 'Thỏa thuận') ?></p>
        <p class="mt-1 text-[13px] text-muted">Hạn nộp <?= e(vn_date($rel['deadline']) ?: '—') ?></p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<div class="bottom-bar lg:hidden">
  <a href="tel:<?= e($hotline) ?>" class="tg-btn tg-btn--ghost h-12 px-4" aria-label="Gọi hotline">
    <img src="<?= asset('images/icons/telephone.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[18px]">
  </a>
  <a href="<?= $applyUrl ?>" class="tg-btn tg-btn--primary flex-1">Ứng tuyển vị trí này</a>
</div>
