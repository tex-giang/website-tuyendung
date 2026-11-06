<?php
/** Danh sách việc làm — lọc và phân trang phía server (dữ liệu MySQL). */
$filters = $filters;
$result = $result;
$hasFilter = $filters['q'] !== '' || $filters['location'] !== '' || $filters['department'] !== '' || $filters['salary'] !== '';
$chips = [];
if ($filters['q'] !== '') {
    $chips['q'] = 'Từ khoá: ' . $filters['q'];
}
if ($filters['location'] !== '') {
    $chips['location'] = 'Khu ' . $branches[$filters['location']];
}
if ($filters['department'] !== '') {
    $chips['department'] = $categories[$filters['department']];
}
if ($filters['salary'] !== '') {
    $chips['salary'] = $salaryRanges[$filters['salary']];
}
$start = $result['total'] === 0 ? 0 : (($result['page'] - 1) * $result['per_page']) + 1;
$end = min($result['page'] * $result['per_page'], $result['total']);
?>
<section class="tg-container pt-14 lg:pt-20">
  <div class="page-intro">
    <div>
      <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
        <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="text-body">Việc làm</span>
      </nav>

      <h1 class="mt-4 max-w-[900px] text-[clamp(2rem,4.4vw,3rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        Việc làm tại TEX-GIANG
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        Vị trí đang mở tại Nhà máy Tân Hương 2 và TEX-GIANG. Lọc theo khu sản xuất, khối phòng ban hoặc mức lương.
      </p>
    </div>
    <figure class="intro-photo">
      <img src="<?= asset('images/company/tan-huong-2.webp') ?>" width="360" height="300" alt="Toàn cảnh nhà máy Tân Hương 2 trong hệ thống TEX-GIANG" fetchpriority="high">
      <figcaption>Nhà máy Tân Hương 2 · TEX-GIANG</figcaption>
    </figure>
  </div>
</section>

<section class="tg-container pb-20 pt-10 lg:pb-28">
  <div class="jobs-layout">

    <div id="filter-overlay" class="filter-overlay hidden" aria-hidden="true"></div>
    <aside id="filters-sidebar" class="filters-drawer hidden lg:block" aria-label="Bộ lọc việc làm">
      <form id="jobs-filter-form" class="space-y-5 rounded-card bg-surface p-5 lg:p-6" method="GET" action="<?= url('jobs') ?>">
        <div class="flex items-center justify-between">
          <h2 class="text-[16px] font-semibold text-ink">Bộ lọc</h2>
          <div class="flex items-center gap-2">
            <a href="<?= url('jobs') ?>" class="text-[14px] font-medium text-accent hover:underline">Xoá hết</a>
            <button type="button" id="close-filter-drawer" class="grid h-8 w-8 place-items-center rounded-lg bg-white text-muted lg:hidden" aria-label="Đóng bộ lọc">
              <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
            </button>
          </div>
        </div>

        <div>
          <label class="form-label" for="filter-q">Từ khoá</label>
          <input type="search" id="filter-q" name="q" value="<?= e($filters['q']) ?>" placeholder="May mặc, QC, IE, kế toán…" class="form-input">
        </div>

        <div>
          <label class="form-label" for="filter-location">Địa điểm</label>
          <select id="filter-location" name="location" class="form-input">
            <option value="">Tất cả địa điểm</option>
            <?php foreach ($branches as $slug => $name): ?>
            <option value="<?= e($slug) ?>"<?= $filters['location'] === $slug ? ' selected' : '' ?>>Khu <?= e($name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label" for="filter-department">Khối phòng ban</label>
          <select id="filter-department" name="department" class="form-input">
            <option value="">Tất cả phòng ban</option>
            <?php foreach ($categories as $slug => $name): ?>
            <option value="<?= e($slug) ?>"<?= $filters['department'] === $slug ? ' selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label" for="filter-salary">Mức lương</label>
          <select id="filter-salary" name="salary" class="form-input">
            <option value="">Tất cả mức lương</option>
            <?php foreach ($salaryRanges as $slug => $label): ?>
            <option value="<?= e($slug) ?>"<?= $filters['salary'] === $slug ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="tg-btn tg-btn--primary w-full">Áp dụng bộ lọc</button>
      </form>
    </aside>

    <div>
      <div class="flex flex-wrap items-center justify-between gap-3" data-reveal>
        <p class="text-[16px] text-muted">
          <strong id="results-count" aria-live="polite" class="font-semibold text-ink"><?= (int) $result['total'] ?></strong> vị trí phù hợp
        </p>
        <div class="flex items-center gap-3">
          <button type="button" id="toggle-mobile-filters" aria-expanded="false" aria-controls="filters-sidebar"
                  class="tg-btn tg-btn--ghost h-10 px-4 text-[15px] lg:hidden">
            <img src="<?= asset('images/icons/sliders.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon"> Bộ lọc
          </button>
          <form method="GET" action="<?= url('jobs') ?>" id="jobs-sort-form">
            <?php foreach ($filters as $key => $value): ?>
              <?php if ($key !== 'sort' && $value !== ''): ?>
              <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
              <?php endif; ?>
            <?php endforeach; ?>
            <label for="filter-sort" class="sr-only">Sắp xếp</label>
            <select id="filter-sort" name="sort" class="form-input w-auto py-2 text-[15px]">
              <option value="newest"<?= $filters['sort'] === 'newest' ? ' selected' : '' ?>>Mới đăng trước</option>
              <option value="urgent"<?= $filters['sort'] === 'urgent' ? ' selected' : '' ?>>Ưu tiên tuyển gấp</option>
            </select>
          </form>
        </div>
      </div>

      <?php if ($chips !== []): ?>
      <div id="active-filters-chips" class="flex flex-wrap items-center gap-2 pt-4" aria-live="polite">
        <?php foreach ($chips as $key => $label): ?>
        <span class="tg-chip inline-flex items-center gap-1.5"><?= e($label) ?>
          <a href="<?= e(url_with($filters, [$key => ''])) ?>" class="filter-chip-remove" aria-label="Bỏ lọc <?= e($label) ?>">
            <img class="tg-icon" src="<?= asset('images/icons/x-lg.svg') ?>" width="16" height="16" alt="" aria-hidden="true">
          </a>
        </span>
        <?php endforeach; ?>
        <a href="<?= url('jobs') ?>" class="ml-1 text-[14px] font-medium text-accent hover:underline">Xoá tất cả bộ lọc</a>
      </div>
      <?php endif; ?>

      <div id="jobs-list-container" class="mt-6 space-y-3">
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
          <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--ghost">Xoá bộ lọc</a>
          <a href="<?= url('apply') ?>" class="tg-btn tg-btn--primary">Nộp CV tự do</a>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($result['pages'] > 1): ?>
      <nav id="jobs-pager" class="mt-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center" aria-label="Phân trang danh sách việc làm">
        <p class="text-[14px] text-muted">Hiển thị <?= $start ?>–<?= $end ?> trong <?= (int) $result['total'] ?> vị trí</p>
        <div class="flex items-center gap-2">
          <?php if ($result['page'] > 1): ?>
          <a href="<?= e(url_with($filters, ['page' => $result['page'] - 1])) ?>" class="grid h-9 min-w-9 place-items-center rounded-lg border border-line px-3 text-[14px] text-body transition-colors hover:border-accent hover:text-accent">Trước</a>
          <?php endif; ?>
          <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <?php if ($p === $result['page']): ?>
            <span aria-current="page" class="grid h-9 min-w-9 place-items-center rounded-lg bg-accent-soft px-3 text-[14px] font-medium text-accent"><?= $p ?></span>
            <?php else: ?>
            <a href="<?= e(url_with($filters, ['page' => $p])) ?>" class="grid h-9 min-w-9 place-items-center rounded-lg border border-line px-3 text-[14px] text-body transition-colors hover:border-accent hover:text-accent"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($result['page'] < $result['pages']): ?>
          <a href="<?= e(url_with($filters, ['page' => $result['page'] + 1])) ?>" class="grid h-9 min-w-9 place-items-center rounded-lg border border-line px-3 text-[14px] text-body transition-colors hover:border-accent hover:text-accent">Sau</a>
          <?php endif; ?>
        </div>
      </nav>
      <?php endif; ?>

      <p class="mt-8 text-[14px] leading-[1.6] text-muted">
        Không thấy vị trí phù hợp? Gọi <a href="tel:<?= e(settings('hotline')) ?>" class="text-body underline underline-offset-2 hover:text-accent"><?= e(settings('hotline_display')) ?></a>
        hoặc <a href="https://zalo.me/<?= e(settings('zalo')) ?>" target="_blank" rel="noopener" class="text-body underline underline-offset-2 hover:text-accent">chat Zalo</a> để được tư vấn.
      </p>
    </div>
  </div>
</section>
