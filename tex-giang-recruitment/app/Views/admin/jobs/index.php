<?php
$page['ajax_fragment'] = true;
$statusBadge = [
    'published' => 'admin-badge--published', 'draft' => 'admin-badge--draft',
    'paused' => 'admin-badge--paused', 'closed' => 'admin-badge--closed', 'expired' => 'admin-badge--expired',
];
$statuses = array_replace($statuses, ['published' => 'Đang tuyển']);
$listUrl = url('admin/jobs');
$listPager = true;
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="admin-list-title">Tin tuyển dụng</h1>
    <p class="admin-sub">Quản lý các vị trí đang tuyển tại các khu sản xuất.</p>
  </div>
  <a href="<?= url('admin/jobs/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm tin tuyển dụng</a>
</div>

<div class="admin-list-panel">
  <form class="admin-filters admin-list-toolbar" data-admin-live-filter method="GET" role="search" aria-label="Lọc danh sách tin tuyển dụng" action="<?= $listUrl ?>">
    <label class="admin-list-search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <span class="visually-hidden">Tìm theo tiêu đề hoặc mã tin</span>
      <input id="job-filter-q" type="search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo tiêu đề hoặc mã tin…">
    </label>
    <select id="job-filter-status" name="status" class="field__input admin-list-select" data-submit-onchange aria-label="Lọc theo trạng thái">
      <option value="">Tất cả trạng thái (<?= array_sum($counts) ?>)</option>
      <?php foreach ($statuses as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?> (<?= (int) $counts[$key] ?>)</option>
      <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-outline">Áp dụng</button></noscript>
  </form>

  <?php if ($result['items'] === []): ?>
  <p class="admin-list-empty">Chưa có tin tuyển dụng nào khớp bộ lọc.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-table--jobs table table-hover align-middle" aria-labelledby="admin-list-title">
      <thead><tr>
        <th scope="col">Mã tin</th><th scope="col">Tiêu đề</th><th scope="col">Khu sản xuất</th><th scope="col">Mức lương</th>
        <th scope="col">Hạn nộp</th><th scope="col">Trạng thái</th><th scope="col">Cập nhật</th>
        <th scope="col" class="is-actions" aria-label="Thao tác"></th>
      </tr></thead>
      <tbody>
        <?php foreach ($result['items'] as $job): ?>
        <?php $jobId = (int) $job['id']; $jobUrl = $listUrl . '/' . $jobId; ?>
        <tr>
          <td class="admin-mono admin-nowrap"><?= e($job['code'] ?: '—') ?></td>
          <td class="admin-job-cell-title">
            <a class="admin-link" href="<?= $jobUrl ?>/edit" title="<?= e($job['title']) ?>"><?= e($job['title']) ?></a>
            <span class="admin-muted"><?= e($job['category_name'] ?? 'Chưa phân bộ phận') ?><?= !empty($job['is_urgent']) ? ' · Tuyển gấp' : '' ?></span>
          </td>
          <td><?= e($job['branch_name'] ?? '—') ?></td>
          <td><?= e(salary_short((string) $job['salary_text']) ?: 'Thoả thuận') ?></td>
          <td class="admin-nowrap"><?= e(vn_date($job['deadline']) ?: '—') ?></td>
          <td><span class="admin-badge <?= $statusBadge[$job['status']] ?? '' ?>"><?= e($statuses[$job['status']] ?? $job['status']) ?></span></td>
          <td class="admin-nowrap admin-muted"><?= e(vn_date($job['updated_at'], 'H:i d/m/Y')) ?></td>
          <td class="is-actions">
            <div class="admin-actions">
              <a href="<?= url('jobs/' . $job['slug']) ?>" target="_blank" rel="noopener" class="admin-row-icon" title="Xem trên website" aria-label="Xem trên website: <?= e($job['title']) ?>"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
              <a href="<?= $jobUrl ?>/edit" class="admin-row-icon" title="Chỉnh sửa" aria-label="Chỉnh sửa: <?= e($job['title']) ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a>
              <button type="button" class="admin-row-icon admin-row-menu-trigger" popovertarget="job-status-<?= $jobId ?>" title="Đổi trạng thái" aria-label="Đổi trạng thái: <?= e($job['title']) ?>"><i class="bi bi-list-check" aria-hidden="true"></i></button>
              <div class="admin-row-menu" id="job-status-<?= $jobId ?>" popover data-admin-row-menu>
                <form method="POST" action="<?= $jobUrl ?>/status" data-ajax data-ajax-refresh>
                  <?= csrf_field() ?>
                  <?php foreach ($statuses as $key => $label): ?>
                  <button type="submit" name="status" value="<?= e($key) ?>"<?= $job['status'] === $key ? ' disabled' : '' ?>>
                    <?= e($label) ?><?php if ($job['status'] === $key): ?> <i class="bi bi-check2" aria-hidden="true"></i><?php endif; ?>
                  </button>
                  <?php endforeach; ?>
                </form>
              </div>
              <button type="button" class="admin-row-icon admin-row-menu-trigger" popovertarget="job-actions-<?= $jobId ?>" title="Thao tác khác" aria-label="Thao tác khác: <?= e($job['title']) ?>"><i class="bi bi-three-dots" aria-hidden="true"></i></button>
              <div class="admin-row-menu" id="job-actions-<?= $jobId ?>" popover data-admin-row-menu>
                <form method="POST" action="<?= $jobUrl ?>/duplicate" data-ajax>
                  <?= csrf_field() ?><button type="submit"><i class="bi bi-copy" aria-hidden="true"></i> Nhân bản</button>
                </form>
                <form method="POST" action="<?= $jobUrl ?>/delete" data-ajax data-ajax-refresh
                      data-confirm="Xoá tin &quot;<?= e($job['title']) ?>&quot;? Hồ sơ ứng tuyển liên quan vẫn được giữ lại.">
                  <?= csrf_field() ?><button class="admin-row-menu__danger" type="submit"><i class="bi bi-trash" aria-hidden="true"></i> Xóa tin</button>
                </form>
              </div>
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
