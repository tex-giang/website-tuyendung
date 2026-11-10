<?php
$compactPager = !empty($listPager);
$current = (int) $result['page'];
$last = (int) $result['pages'];
$total = (int) $result['total'];
$perPage = (int) $result['per_page'];
$firstRow = ($current - 1) * $perPage + 1;
$lastRow = min($current * $perPage, $total);
$query = array_filter($filters, static fn($value): bool => $value !== '' && $value !== null);
$pageHref = static fn(int $number): string => $listUrl . '?' . http_build_query(array_merge($query, ['page' => $number]));
?>
<div class="admin-pager<?= $compactPager ? ' admin-pager--compact' : '' ?>">
  <p class="admin-muted"><?= $compactPager ? '' : 'Hiển thị ' ?><?= $firstRow ?>–<?= $lastRow ?> / <?= $total ?> <?= $compactPager ? 'bản ghi' : 'kết quả' ?></p>
  <?php if ($last > 1 || $compactPager): ?>
    <nav class="admin-pager__links" aria-label="Phân trang kết quả">
      <?php if ($current > 1): ?>
      <a class="admin-pager__item" href="<?= e($pageHref($current - 1)) ?>" rel="prev" aria-label="Trang trước"><?= $compactPager ? '<i class="bi bi-chevron-left" aria-hidden="true"></i>' : 'Trang trước' ?></a>
      <?php elseif ($compactPager): ?>
      <span class="admin-pager__item is-disabled" aria-label="Trang trước" aria-disabled="true"><i class="bi bi-chevron-left" aria-hidden="true"></i></span>
      <?php endif; ?>
      <?php $previous = 0; ?>
      <?php foreach (array_unique([1, ...range(max(1, $current - 1), min($last, $current + 1)), $last]) as $number): ?>
        <?php if ($previous && $number > $previous + 1): ?><span class="admin-pager__gap" aria-hidden="true">…</span><?php endif; ?>
        <?php if ($number === $current): ?>
        <span class="admin-pager__item is-current" aria-current="page" aria-label="Trang <?= $number ?>"><?= $number ?></span>
        <?php else: ?>
        <a class="admin-pager__item" href="<?= e($pageHref($number)) ?>" aria-label="Trang <?= $number ?>"><?= $number ?></a>
        <?php endif; ?>
        <?php $previous = $number; ?>
      <?php endforeach; ?>
      <?php if ($current < $last): ?>
      <a class="admin-pager__item" href="<?= e($pageHref($current + 1)) ?>" rel="next" aria-label="Trang sau"><?= $compactPager ? '<i class="bi bi-chevron-right" aria-hidden="true"></i>' : 'Trang sau' ?></a>
      <?php elseif ($compactPager): ?>
      <span class="admin-pager__item is-disabled" aria-label="Trang sau" aria-disabled="true"><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
