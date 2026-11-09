<?php
$page['ajax_fragment'] = true;
/**
 * Thống kê lượt truy cập — bảng theo ngày + biểu đồ 14/30 ngày + tách thiết bị.
 *
 * @var array<int, array<string, mixed>> $rows
 * @var array<string, int> $summary
 * @var array<string, int> $quick
 * @var array<int, array{date:string,total:int}> $chart
 * @var int $days
 * @var array<int, string> $periods
 */
$maxChart = 1;
foreach ($chart as $point) {
    $maxChart = max($maxChart, (int) $point['total']);
}

$devices = [
    'desktop' => ['label' => 'Máy tính', 'count' => $summary['desktop']],
    'mobile'  => ['label' => 'Điện thoại', 'count' => $summary['mobile']],
    'tablet'  => ['label' => 'Máy tính bảng', 'count' => $summary['tablet']],
];
$deviceTotal = max(1, $summary['total']);
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb">Hệ thống / Thống kê truy cập</p>
    <h1 class="admin-h1">Thống kê lượt truy cập</h1>
  </div>
  <form method="GET" action="<?= url('admin/visits') ?>" class="admin-period-form">
    <label class="field">
      <span class="field__label">Khoảng thời gian</span>
      <select class="field__input" name="days" data-submit-onchange>
        <?php foreach ($periods as $value => $label): ?>
        <option value="<?= (int) $value ?>"<?= (int) $days === (int) $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <noscript><button type="submit" class="btn btn-outline btn-sm">Áp dụng</button></noscript>
  </form>
</div>

<div class="admin-stat-grid">
  <div class="admin-stat-card">
    <span class="admin-stat-card__label">Hôm nay</span>
    <strong class="admin-stat-card__value"><?= number_format((int) $quick['today'], 0, ',', '.') ?></strong>
    <span class="admin-stat-card__foot">Hôm qua: <?= number_format((int) $quick['yesterday'], 0, ',', '.') ?></span>
  </div>
  <div class="admin-stat-card">
    <span class="admin-stat-card__label">7 ngày gần nhất</span>
    <strong class="admin-stat-card__value"><?= number_format((int) $quick['week'], 0, ',', '.') ?></strong>
  </div>
  <div class="admin-stat-card">
    <span class="admin-stat-card__label">30 ngày gần nhất</span>
    <strong class="admin-stat-card__value"><?= number_format((int) $quick['month'], 0, ',', '.') ?></strong>
  </div>
  <div class="admin-stat-card admin-stat-card--accent">
    <span class="admin-stat-card__label">Tổng cộng</span>
    <strong class="admin-stat-card__value"><?= number_format((int) $quick['all'], 0, ',', '.') ?></strong>
  </div>
</div>

<section class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-h2">Biểu đồ <?= count($chart) ?> ngày gần nhất</h2>
    <span class="admin-badge admin-badge--soft"><?= number_format((int) $summary['total'], 0, ',', '.') ?> lượt trong kỳ</span>
  </div>
  <div class="visit-chart" role="img" aria-label="Biểu đồ lượt truy cập theo ngày">
    <?php foreach ($chart as $point): ?>
    <?php $height = (int) round(((int) $point['total'] / $maxChart) * 100); ?>
    <div class="visit-chart__col" title="<?= e(date('d/m/Y', strtotime($point['date']))) ?>: <?= number_format((int) $point['total'], 0, ',', '.') ?> lượt">
      <span class="visit-chart__value"><?= (int) $point['total'] ?></span>
      <div class="visit-chart__bar-wrap">
        <div class="visit-chart__bar" style="height: <?= $height ?>%"></div>
      </div>
      <span class="visit-chart__label"><?= e(date('d/m', strtotime($point['date']))) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-h2">Chi tiết theo ngày</h2>
    <span class="admin-badge admin-badge--soft"><?= count($rows) ?> ngày có lượt · kỳ <?= e($periods[$days] ?? ($days . ' ngày')) ?></span>
  </div>
  <?php if ($rows === []): ?>
  <p class="admin-empty">Chưa ghi nhận lượt truy cập trong khoảng thời gian này.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
  <table class="admin-table table table-hover align-middle js-datatable" data-dt-compact aria-label="Lượt truy cập theo ngày">
    <thead>
      <tr>
        <th>Ngày</th>
        <th>Tổng lượt</th>
        <th>Máy tính</th>
        <th>Điện thoại</th>
        <th>Máy tính bảng</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td data-order="<?= e((string) $row['stat_date']) ?>"><strong><?= e(date('d/m/Y', strtotime((string) $row['stat_date']))) ?></strong></td>
        <td><span class="admin-badge admin-badge--published"><?= number_format((int) $row['total'], 0, ',', '.') ?></span></td>
        <td><?= number_format((int) $row['desktop'], 0, ',', '.') ?></td>
        <td><?= number_format((int) $row['mobile'], 0, ',', '.') ?></td>
        <td><?= number_format((int) $row['tablet'], 0, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>

<section class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-h2">Theo thiết bị (tổng cộng)</h2>
  </div>
  <div class="visit-device-list">
    <?php foreach ($devices as $device): ?>
    <?php $percent = round(((int) $device['count'] / $deviceTotal) * 100); ?>
    <div class="visit-device">
      <span class="visit-device__name"><?= e($device['label']) ?></span>
      <div class="visit-device__track"><span class="visit-device__fill" style="width: <?= $percent ?>%"></span></div>
      <span class="visit-device__num"><?= number_format((int) $device['count'], 0, ',', '.') ?> <em>(<?= $percent ?>%)</em></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>
