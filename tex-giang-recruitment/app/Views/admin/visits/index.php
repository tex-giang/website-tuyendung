<?php
$page['ajax_fragment'] = true;
$maxChart = max([1, ...array_map(static fn(array $point): int => (int) $point['total'], $chart)]);
$devices = [
    ['Máy tính', 'desktop', (int) $summary['desktop']],
    ['Điện thoại', 'mobile', (int) $summary['mobile']],
    ['Máy tính bảng', 'tablet', (int) $summary['tablet']],
];
$deviceTotal = array_sum(array_column($devices, 2));
$metrics = [
    'Hôm nay' => $quick['today'], 'Hôm qua' => $quick['yesterday'],
    '7 ngày qua' => $quick['week'], '30 ngày qua' => $quick['month'],
    'Tổng lượt truy cập' => $quick['all'],
];
$number = static fn(int $value): string => number_format($value, 0, ',', '.');
?>
<div class="admin-page-head admin-visits-head">
  <div>
    <h1 class="admin-h1">Thống kê truy cập</h1>
    <p class="admin-sub">Lượt truy cập website tuyển dụng theo ngày và theo thiết bị.</p>
  </div>
  <nav class="admin-period-tabs" aria-label="Khoảng thời gian thống kê">
    <?php foreach ($periods as $value => $label): ?>
    <a href="<?= url('admin/visits') ?>?days=<?= (int) $value ?>"<?= $days === $value ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<dl class="admin-visits-metrics" aria-label="Số lượt truy cập">
  <?php foreach ($metrics as $label => $value): ?>
  <div><dt><?= e($label) ?></dt><dd><?= $number((int) $value) ?></dd></div>
  <?php endforeach; ?>
</dl>

<div class="admin-visits-grid">
  <section class="admin-list-panel" aria-labelledby="visit-trend-title">
    <header class="admin-visits-panel-head">
      <h2 id="visit-trend-title">Lượt truy cập theo ngày</h2>
      <p><?= count($chart) ?> ngày gần nhất</p>
    </header>
    <div class="admin-visits-chart">
      <div class="visit-chart" role="img" aria-label="Biểu đồ lượt truy cập theo ngày">
        <?php foreach ($chart as $point): ?>
        <?php $value = (int) $point['total']; ?>
        <div class="visit-chart__col" title="<?= e(vn_date($point['date'])) ?> · <?= $number($value) ?> lượt">
          <div class="visit-chart__bar-wrap"><div class="visit-chart__bar" style="height: <?= round($value / $maxChart * 100, 2) ?>%"></div></div>
          <span class="visit-chart__label"><?= e(vn_date($point['date'], 'd-m')) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="admin-list-panel" aria-labelledby="visit-device-title">
    <header class="admin-visits-panel-head">
      <h2 id="visit-device-title">Thiết bị truy cập</h2>
      <p>Tỷ lệ theo loại thiết bị</p>
    </header>
    <div class="admin-visits-device">
      <svg class="admin-device-ring" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
        <circle class="admin-device-ring__track" cx="50" cy="50" r="40" pathLength="100"/>
        <?php $offset = 0; foreach ($devices as [, $key, $count]): ?>
        <?php $percentage = $deviceTotal > 0 ? $count / $deviceTotal * 100 : 0; ?>
        <?php if ($percentage > 0): ?>
        <circle class="admin-device-color--<?= e($key) ?>" cx="50" cy="50" r="40" pathLength="100"
                stroke-dasharray="<?= $percentage ?> <?= 100 - $percentage ?>" stroke-dashoffset="<?= -$offset ?>"/>
        <?php endif; ?>
        <?php $offset += $percentage; endforeach; ?>
      </svg>
      <ul class="admin-device-legend">
        <?php foreach ($devices as [$label, $key, $count]): ?>
        <li><span class="admin-device-legend__label"><i class="admin-device-color--<?= e($key) ?>" aria-hidden="true"></i><?= e($label) ?></span>
          <span><?= $number($count) ?></span><strong><?= number_format($deviceTotal > 0 ? $count / $deviceTotal * 100 : 0, 1, ',', '.') ?>%</strong></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
</div>

<section class="admin-list-panel admin-visits-table" aria-label="Bảng lượt truy cập theo ngày">
  <?php if ($rows === []): ?>
  <p class="admin-list-empty">Chưa ghi nhận lượt truy cập trong kỳ <?= e($periods[$days]) ?>.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable" data-dt-compact>
      <thead><tr><th scope="col">Thời gian</th><th scope="col">Lượt truy cập</th><th scope="col">Máy tính</th><th scope="col">Điện thoại</th><th scope="col">Máy tính bảng</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr><td><?= e(vn_date($row['stat_date'])) ?></td><td><strong><?= $number((int) $row['total']) ?></strong></td>
          <td><?= $number((int) $row['desktop']) ?></td><td><?= $number((int) $row['mobile']) ?></td><td><?= $number((int) $row['tablet']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
