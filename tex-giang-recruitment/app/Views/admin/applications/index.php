<?php
$page['ajax_fragment'] = true;
/** Danh sách hồ sơ ứng tuyển (admin). */
$statusBadge = [
    'new' => 'admin-badge--new', 'contacted' => 'admin-badge--contacted',
    'interview' => 'admin-badge--interview', 'hired' => 'admin-badge--hired',
    'rejected' => 'admin-badge--rejected',
];
$listUrl = url('admin/applications');
$listPager = true;
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="admin-list-title">Hồ sơ ứng tuyển</h1>
    <p class="admin-sub">Theo dõi, liên hệ và xử lý hồ sơ của ứng viên.</p>
  </div>
</div>

<nav class="admin-application-counts" aria-label="Lọc hồ sơ theo trạng thái">
  <?php foreach ($statuses as $key => $label): ?>
  <?php
  $active = $filters['status'] === $key;
  $query = array_filter(array_replace($filters, ['status' => $active ? '' : $key]), static fn($value): bool => $value !== '');
  $href = $listUrl . ($query === [] ? '' : '?' . http_build_query($query));
  ?>
  <a href="<?= e($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
    <span><?= e($label) ?></span>
    <strong><?= (int) ($counts[$key] ?? 0) ?></strong>
  </a>
  <?php endforeach; ?>
</nav>

<div class="admin-list-panel">
  <form class="admin-filters admin-list-toolbar" data-admin-live-filter method="GET" role="search" aria-label="Lọc danh sách hồ sơ" action="<?= $listUrl ?>">
    <label class="admin-list-search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <span class="visually-hidden">Tìm theo tên, số điện thoại hoặc email</span>
      <input id="application-filter-q" type="search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo tên, số điện thoại hoặc email">
    </label>
    <select id="application-filter-status" name="status" class="field__input admin-list-select admin-list-select--status" data-submit-onchange aria-label="Lọc theo trạng thái">
      <option value="">Tất cả trạng thái</option>
      <?php foreach ($statuses as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="application-filter-job" name="job_id" class="field__input admin-list-select admin-list-select--job" data-submit-onchange aria-label="Lọc theo vị trí">
      <option value="">Tất cả vị trí</option>
      <?php foreach ($jobs as $jobId => $jobTitle): ?>
      <option value="<?= (int) $jobId ?>"<?= (string) $filters['job_id'] === (string) $jobId ? ' selected' : '' ?>><?= e($jobTitle) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-outline">Áp dụng</button></noscript>
  </form>

  <?php if ($result['items'] === []): ?>
  <p class="admin-list-empty">Chưa có hồ sơ ứng tuyển nào khớp bộ lọc.</p>
  <?php else: ?>
  <div class="admin-table-wrap admin-application-results">
    <table class="admin-table admin-table--applications table table-hover align-middle" role="table" aria-labelledby="admin-list-title">
      <thead role="rowgroup">
        <tr role="row"><th scope="col" role="columnheader">Ứng viên</th><th scope="col" role="columnheader">Vị trí ứng tuyển</th><th scope="col" role="columnheader">Nguồn</th><th scope="col" role="columnheader">Trạng thái</th><th scope="col" role="columnheader">Thời gian nhận</th><th scope="col" role="columnheader" class="is-actions" aria-label="Thao tác"></th></tr>
      </thead>
      <tbody role="rowgroup">
        <?php foreach ($result['items'] as $app): ?>
        <tr role="row">
          <td role="cell" class="admin-application-results__person">
            <a class="admin-link" href="<?= url('admin/applications/' . $app['id']) ?>"><strong><?= e($app['full_name']) ?></strong></a>
            <span class="admin-muted"><?= e($app['phone']) ?><?= $app['email'] ? ' · ' . e($app['email']) : '' ?></span>
          </td>
          <td role="cell" class="admin-application-results__job" title="<?= e($app['job_title'] ?? 'CV tự do') ?>">
            <?= e($app['job_title'] ?? 'CV tự do') ?>
          </td>
          <td role="cell" class="admin-muted admin-application-results__source"><?= e($app['source'] ?? 'Website') ?></td>
          <td role="cell" class="admin-application-results__status"><span class="admin-badge <?= $statusBadge[$app['status']] ?? '' ?>"><?= e($statuses[$app['status']] ?? $app['status']) ?></span></td>
          <td role="cell" class="admin-muted admin-application-results__date"><?= e(vn_date($app['created_at'], 'H:i d/m/Y')) ?></td>
          <td role="cell" class="is-actions admin-application-results__actions">
            <div class="admin-actions">
              <a href="<?= url('admin/applications/' . $app['id']) ?>" class="btn btn-outline btn-sm"><i class="bi bi-eye me-1" aria-hidden="true"></i> Xem hồ sơ</a>
              <?php if (!empty($app['cv_stored_name'])): ?>
              <a href="<?= url('admin/applications/' . $app['id'] . '/cv') ?>" class="btn btn-ghost btn-sm admin-application-results__cv" title="Tải CV" aria-label="Tải CV"><i class="bi bi-download" aria-hidden="true"></i></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php require BASE_PATH . '/app/Views/partials/admin/pager.php'; ?>
  <?php endif; ?>
</div>
