<?php
/** Danh sách hồ sơ ứng tuyển (admin). */
$statusBadge = [
    'new' => 'admin-badge--new', 'contacted' => 'admin-badge--contacted',
    'interview' => 'admin-badge--interview', 'hired' => 'admin-badge--hired',
    'rejected' => 'admin-badge--rejected',
];
$start = $result['total'] === 0 ? 0 : (($result['page'] - 1) * $result['per_page']) + 1;
$end = min($result['page'] * $result['per_page'], $result['total']);
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Hồ sơ ứng tuyển</h1>
  </div>
</div>

<div class="admin-kpis admin-kpis--compact">
  <?php foreach ($statuses as $key => $label): ?>
  <div class="admin-kpi admin-kpi--sm">
    <span class="admin-kpi__label"><?= e($label) ?></span>
    <strong class="admin-kpi__value"><?= (int) ($counts[$key] ?? 0) ?></strong>
  </div>
  <?php endforeach; ?>
</div>

<form class="admin-filters" method="GET" action="<?= url('admin/applications') ?>">
  <input type="search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo tên, số điện thoại, email…">
  <select name="status" class="field__input" data-submit-onchange>
    <option value="">Tất cả trạng thái</option>
    <?php foreach ($statuses as $key => $label): ?>
    <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?> (<?= (int) ($counts[$key] ?? 0) ?>)</option>
    <?php endforeach; ?>
  </select>
  <select name="job_id" class="field__input" data-submit-onchange>
    <option value="">Tất cả vị trí</option>
    <?php foreach ($jobs as $jobId => $jobTitle): ?>
    <option value="<?= (int) $jobId ?>"<?= (string) $filters['job_id'] === (string) $jobId ? ' selected' : '' ?>><?= e($jobTitle) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline">Lọc</button>
  <a href="<?= url('admin/applications') ?>" class="btn btn-ghost">Xoá lọc</a>
</form>

<div class="admin-card">
  <?php if ($result['items'] === []): ?>
  <p class="admin-empty">Chưa có hồ sơ nào khớp bộ lọc. Hồ sơ ứng viên gửi từ website sẽ xuất hiện tại đây.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable">
      <thead>
        <tr><th>Ứng viên</th><th>Vị trí</th><th>Khu / nguồn</th><th>Trạng thái</th><th>Nhận lúc</th><th class="is-actions">Thao tác</th></tr>
      </thead>
      <tbody>
        <?php foreach ($result['items'] as $app): ?>
        <tr>
          <td>
            <a class="admin-link" href="<?= url('admin/applications/' . $app['id']) ?>"><strong><?= e($app['full_name']) ?></strong></a>
            <span class="admin-muted"><?= e($app['phone']) ?><?= $app['email'] ? ' · ' . e($app['email']) : '' ?></span>
            <span class="admin-muted"><?= e($app['address'] ?? '') ?></span>
          </td>
          <td>
            <?= e($app['job_title'] ?? 'CV tự do') ?>
            <?php if (!empty($app['job_code'])): ?><span class="admin-muted admin-mono"><?= e($app['job_code']) ?></span><?php endif; ?>
          </td>
          <td class="admin-muted"><?= e($app['source'] ?? 'Website') ?></td>
          <td><span class="admin-badge <?= $statusBadge[$app['status']] ?? '' ?>"><?= e($statuses[$app['status']] ?? $app['status']) ?></span></td>
          <td class="admin-muted"><?= e(vn_date($app['created_at'], 'd/m/Y H:i')) ?></td>
          <td class="is-actions">
            <div class="admin-actions">
              <a href="<?= url('admin/applications/' . $app['id']) ?>" class="btn btn-outline btn-sm">Xem hồ sơ</a>
              <?php if (!empty($app['cv_stored_name'])): ?>
              <a href="<?= url('admin/applications/' . $app['id'] . '/cv') ?>" class="btn btn-ghost btn-sm">Tải CV</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($result['pages'] > 1): ?>
  <div class="admin-pager">
    <p class="admin-muted">Hiển thị <?= $start ?>–<?= $end ?> / <?= (int) $result['total'] ?></p>
    <div class="admin-pager__links">
      <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
        <?php $qs = http_build_query(array_filter(['q' => $filters['q'], 'status' => $filters['status'], 'job_id' => $filters['job_id'], 'page' => $p])); ?>
        <?php if ($p === $result['page']): ?>
        <span class="admin-pager__item is-current"><?= $p ?></span>
        <?php else: ?>
        <a class="admin-pager__item" href="<?= url('admin/applications') ?>?<?= e($qs) ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
