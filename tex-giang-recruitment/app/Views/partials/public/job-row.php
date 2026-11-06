<?php
/** Một dòng việc làm trong danh sách. @var array $job */
$isClosed = ($job['status'] ?? '') === 'closed';
$chip = job_chip($job);
$detailUrl = url('jobs/' . $job['slug']);
$applyUrl = url('apply?job=' . urlencode((string) $job['slug']));
?>
<article class="job-row<?= $isClosed ? ' is-closed' : '' ?>">
  <div class="min-w-0">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[14px] text-muted">
      <span class="font-medium text-body">TEX-GIANG</span>
      <span aria-hidden="true">·</span>
      <span><?= e($job['category_name'] ?? 'Tuyển dụng') ?></span>
      <span class="tg-chip<?= $chip['muted'] ? ' tg-chip--muted' : '' ?>"><?= e($chip['label']) ?></span>
    </div>

    <h3 class="mt-2 text-[20px] font-medium leading-[1.4] text-ink">
      <a href="<?= $detailUrl ?>" class="transition-colors hover:text-accent"><?= e($job['title']) ?></a>
    </h3>

    <p class="mt-2 line-clamp-2 text-[16px] leading-[1.6] text-muted"><?= e(excerpt((string) $job['description'], 180)) ?></p>

    <p class="mt-3 text-[14px] text-muted"><?= e($job['branch_name'] ?? '') ?><?= $job['location'] ? ' — ' . e((string) $job['location']) : '' ?>
      · Hạn nộp <?= e(vn_date($job['deadline'])) ?: '—' ?><?= $job['code'] ? ' · Mã ' . e((string) $job['code']) : '' ?></p>
  </div>

  <div class="job-row__side">
    <span class="text-[16px] font-semibold text-accent"><?= e(salary_short((string) $job['salary_text']) ?: 'Thỏa thuận') ?></span>
    <?php if ($isClosed): ?>
      <span class="text-[14px] text-muted">Đã hết hạn tuyển — vẫn nhận CV cho đợt sau</span>
    <?php else: ?>
      <a href="<?= $applyUrl ?>" class="tg-btn tg-btn--primary h-11 px-5">Ứng tuyển</a>
      <a href="<?= $detailUrl ?>" class="text-[16px] text-muted transition-colors hover:text-accent">Xem chi tiết</a>
    <?php endif; ?>
  </div>
</article>
