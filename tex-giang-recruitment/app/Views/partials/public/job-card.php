<?php
/** Một thẻ việc làm dạng carousel (trang chủ). @var array $job */
$chip = job_chip($job);
$applyUrl = url('apply?job=' . urlencode((string) $job['slug']));
$detailUrl = url('jobs/' . $job['slug']);
$title = preg_replace('/^\d+\s+/', '', (string) $job['title']) ?? (string) $job['title'];
?>
<article class="job-card group flex h-full flex-col">
  <div class="flex items-center justify-between gap-3">
    <span class="text-[14px] text-muted">TEX-GIANG · <?= e($job['category_name'] ?? 'Tuyển dụng') ?></span>
    <span class="tg-chip<?= $chip['muted'] ? ' tg-chip--muted' : '' ?>"><?= e($chip['label']) ?></span>
  </div>
  <h3 class="mt-3 text-[20px] font-medium leading-[1.4] text-ink">
    <a href="<?= $detailUrl ?>" class="transition-colors group-hover:text-accent"><?= e($title) ?></a>
  </h3>
  <p class="mt-2 line-clamp-2 text-[16px] leading-[1.6] text-muted"><?= e(excerpt((string) $job['description'], 150)) ?></p>
  <p class="mt-4 text-[16px] font-semibold text-accent"><?= e(salary_short((string) $job['salary_text']) ?: 'Thỏa thuận') ?></p>
  <p class="mt-3 text-[14px] text-muted"><?= e($job['branch_name'] ?? '') ?> · Hạn nộp <?= e(vn_date($job['deadline'])) ?: '—' ?></p>
  <div class="mt-auto flex items-center justify-between gap-3 pt-6">
    <a href="<?= $detailUrl ?>" class="text-[16px] text-muted transition-colors hover:text-accent">Xem chi tiết</a>
    <a href="<?= $applyUrl ?>" class="tg-btn tg-btn--ghost h-9 border-0 bg-white px-4 text-[16px]">Ứng tuyển</a>
  </div>
</article>
