<?php
$current = (int) $result['page'];
$last = (int) $result['pages'];
$total = (int) $result['total'];
$perPage = (int) $result['per_page'];
$firstRow = ($current - 1) * $perPage + 1;
$lastRow = min($current * $perPage, $total);
$query = array_filter($filters, static fn($value): bool => $value !== '' && $value !== null);
$pageHref = static fn(int $number): string => $listUrl . '?' . http_build_query(array_merge($query, ['page' => $number]));
?>
<div class="admin-pager">
  <p class="admin-muted">Hiển thị <?= $firstRow ?>–<?= $lastRow ?> / <?= $total ?> kết quả</p>
  <?php if ($last > 1): ?>
    <nav class="admin-pager__links" aria-label="Phân trang kết quả">
      <?php if ($current > 1): ?>
      <a class="admin-pager__item" href="<?= e($pageHref($current - 1)) ?>" rel="prev">Trang trước</a>
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
      <a class="admin-pager__item" href="<?= e($pageHref($current + 1)) ?>" rel="next">Trang sau</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
