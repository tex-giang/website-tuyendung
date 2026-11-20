<?php
/**
 * VÙNG KẾT QUẢ của trang /jobs — phần được thay tại chỗ khi lọc/sắp xếp/phân trang.
 * Đây là fragment: KHÔNG chứa <html>, header, footer hay form bộ lọc.
 *
 * Đích thay thế phía client: [data-jobs-results] (chính là <div> bao ngoài file này).
 * Dữ liệu vào: $filters, $result, $query, $branches, $blocks, $categories.
 */
$data = require BASE_PATH . '/app/Views/partials/public/jobs-results-data.php';
$chips = $data['chips'];
$pageUrl = $data['page_url'];
$start = $data['start'];
$end = $data['end'];
?>
<div data-jobs-results>
  <div class="flex flex-wrap items-center justify-between gap-3" data-reveal>
    <p class="text-[16px] text-muted">
      <strong id="results-count" aria-live="polite" class="font-semibold text-ink"><?= (int) $result['total'] ?></strong> vị trí phù hợp
    </p>
    <div class="flex items-center gap-3">
      <button type="button" id="toggle-mobile-filters" aria-expanded="false" aria-controls="filters-sidebar"
              class="tg-btn tg-btn--ghost h-10 px-4 text-[15px] lg:hidden">
        <img src="<?= asset('images/icons/sliders.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon"> Bộ lọc
      </button>
      <form method="GET" action="<?= url('jobs') ?>" id="jobs-sort-form" data-jobs-filter>
        <?php foreach ($query as $key => $value): ?>
          <?php if ($key !== 'sort' && $key !== 'page' && $value !== ''): ?>
          <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
          <?php endif; ?>
        <?php endforeach; ?>
        <label for="filter-sort" class="sr-only">Sắp xếp</label>
        <select id="filter-sort" name="sort" class="form-input w-auto py-2 text-[15px]">
          <option value="newest"<?= $filters['sort'] === 'newest' ? ' selected' : '' ?>>Theo nhà máy · tin mới trước</option>
          <option value="urgent"<?= $filters['sort'] === 'urgent' ? ' selected' : '' ?>>Theo nhà máy · tin gấp trước</option>
        </select>
      </form>
    </div>
  </div>

  <?php if ($chips !== []): ?>
  <div id="active-filters-chips" class="flex flex-wrap items-center gap-2 pt-4" aria-live="polite">
    <?php foreach ($chips as $key => $label): ?>
    <span class="tg-chip inline-flex items-center gap-1.5"><?= e($label) ?>
      <a href="<?= e($pageUrl([$key => ''])) ?>" class="filter-chip-remove" data-jobs-link aria-label="Bỏ lọc <?= e($label) ?>">
        <img class="tg-icon" src="<?= asset('images/icons/x-lg.svg') ?>" width="16" height="16" alt="" aria-hidden="true">
      </a>
    </span>
    <?php endforeach; ?>
    <a href="<?= url('jobs') ?>" class="ml-1 text-[14px] font-medium text-accent hover:underline" data-jobs-link>Xoá tất cả bộ lọc</a>
  </div>
  <?php endif; ?>

  <div id="jobs-list-container" class="mt-6 space-y-3" data-jobs-list>
    <?php foreach ($result['items'] as $job): ?>
      <?php require BASE_PATH . '/app/Views/partials/public/job-row.php'; ?>
    <?php endforeach; ?>
  </div>

  <?php if ($result['items'] === []): ?>
  <div id="jobs-empty-state" role="status" class="mt-6 rounded-card bg-surface p-10 text-center">
    <h2 class="text-[20px] font-medium text-ink">Không có vị trí khớp bộ lọc</h2>
    <p class="mx-auto mt-2 max-w-[46ch] text-[16px] leading-[1.6] text-muted">
      Thử bỏ một vài điều kiện, hoặc gửi hồ sơ tự do để được tư vấn khi có đợt tuyển mới.
    </p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--ghost" data-jobs-link>Xoá bộ lọc</a>
      <a href="<?= url('apply') ?>" class="tg-btn tg-btn--primary">Nộp CV tự do</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($result['pages'] > 1): ?>
  <nav id="jobs-pager" class="mt-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center" aria-label="Phân trang danh sách việc làm">
    <p class="text-[14px] text-muted">Hiển thị <?= $start ?>–<?= $end ?> trong <?= (int) $result['total'] ?> vị trí</p>
    <div class="flex items-center gap-2">
      <?php if ($result['page'] > 1): ?>
      <a href="<?= e($pageUrl(['page' => $result['page'] - 1])) ?>" class="grid h-9 min-w-9 place-items-center rounded-lg border border-line px-3 text-[14px] text-body transition-colors hover:border-accent hover:text-accent" data-jobs-link>Trước</a>
      <?php endif; ?>
      <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
        <?php if ($p === $result['page']): ?>
        <span aria-current="page" class="grid h-9 min-w-9 place-items-center rounded-lg bg-accent-soft px-3 text-[14px] font-medium text-accent"><?= $p ?></span>
        <?php else: ?>
        <a href="<?= e($pageUrl(['page' => $p])) ?>" class="grid h-9 min-w-9 place-items-center rounded-lg border border-line px-3 text-[14px] text-body transition-colors hover:border-accent hover:text-accent" data-jobs-link><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($result['page'] < $result['pages']): ?>
      <a href="<?= e($pageUrl(['page' => $result['page'] + 1])) ?>" class="grid h-9 min-w-9 place-items-center rounded-lg border border-line px-3 text-[14px] text-body transition-colors hover:border-accent hover:text-accent" data-jobs-link>Sau</a>
      <?php endif; ?>
    </div>
  </nav>
  <?php endif; ?>
</div>
