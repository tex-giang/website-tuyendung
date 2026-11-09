<?php
/** Danh sách tin tuyển dụng (admin). */
$statusBadge = [
    'published' => 'admin-badge--published', 'draft' => 'admin-badge--draft',
    'paused' => 'admin-badge--paused', 'closed' => 'admin-badge--closed', 'expired' => 'admin-badge--expired',
];
$start = $result['total'] === 0 ? 0 : (($result['page'] - 1) * $result['per_page']) + 1;
$end = min($result['page'] * $result['per_page'], $result['total']);
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Tin tuyển dụng</h1>
  </div>
  <a href="<?= url('admin/jobs/create') ?>" class="btn btn-primary">+ Thêm tin</a>
</div>

<form class="admin-filters" method="GET" action="<?= url('admin/jobs') ?>">
  <input type="search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo tiêu đề hoặc mã tin…">
  <select name="status" class="field__input" data-submit-onchange>
    <option value="">Tất cả trạng thái</option>
    <?php foreach ($statuses as $key => $label): ?>
    <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?> (<?= (int) $counts[$key] ?>)</option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline">Lọc</button>
  <a href="<?= url('admin/jobs') ?>" class="btn btn-ghost">Xoá lọc</a>
</form>

<div class="admin-card">
  <?php if ($result['items'] === []): ?>
  <p class="admin-empty">Chưa có tin tuyển dụng nào khớp bộ lọc. <a href="<?= url('admin/jobs/create') ?>">Tạo tin đầu tiên</a>.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable">
      <thead>
        <tr>
          <th>Mã</th><th>Tiêu đề</th><th>Khu</th><th>Lương</th><th>Hạn nộp</th>
          <th>Trạng thái</th><th>Cập nhật</th><th class="is-actions">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['items'] as $job): ?>
        <tr>
          <td class="admin-mono"><?= e($job['code'] ?: '—') ?></td>
          <td>
            <a class="admin-link" href="<?= url('admin/jobs/' . $job['id'] . '/edit') ?>"><?= e($job['title']) ?></a>
            <span class="admin-muted"><?= e($job['category_name'] ?? '') ?> · <?= (int) $job['quantity'] ?> người</span>
          </td>
          <td><?= e($job['branch_name'] ?? '—') ?></td>
          <td><?= e(salary_short((string) $job['salary_text']) ?: 'Thoả thuận') ?></td>
          <td class="admin-muted admin-nowrap"><?= e(vn_date($job['deadline']) ?: '—') ?></td>
          <td>
            <form method="POST" action="<?= url('admin/jobs/' . $job['id'] . '/status') ?>" class="admin-inline" data-ajax data-ajax-reload>
              <?= csrf_field() ?>
              <select name="status" class="admin-select-sm" data-submit-onchange aria-label="Đổi trạng thái">
                <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $job['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <span class="admin-badge <?= $statusBadge[$job['status']] ?? '' ?>"><?= e($statuses[$job['status']] ?? $job['status']) ?></span>
          </td>
          <td class="admin-muted"><?= e(vn_date($job['updated_at'], 'd/m/Y H:i')) ?></td>
          <td class="is-actions">
            <div class="admin-actions">
              <a href="<?= url('jobs/' . $job['slug']) ?>" target="_blank" rel="noopener" class="btn btn-icon" title="Xem trên website">↗</a>
              <a href="<?= url('admin/jobs/' . $job['id'] . '/edit') ?>" class="btn btn-outline btn-icon" title="Sửa" aria-label="Sửa"><?= admin_icon_svg('pencil') ?></a>
              <form method="POST" action="<?= url('admin/jobs/' . $job['id'] . '/duplicate') ?>" data-ajax>
                <?= csrf_field() ?><button class="btn btn-outline btn-icon" type="submit" title="Nhân bản" aria-label="Nhân bản"><?= admin_icon_svg('copy') ?></button>
              </form>
              <form method="POST" action="<?= url('admin/jobs/' . $job['id'] . '/delete') ?>" data-ajax
                    data-confirm="Xoá tin &quot;<?= e($job['title']) ?>&quot;? Hồ sơ ứng tuyển liên quan vẫn được giữ lại.">
                <?= csrf_field() ?><button class="btn btn-danger btn-icon" type="submit" title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
              </form>
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
        <?php $qs = http_build_query(array_filter(['q' => $filters['q'], 'status' => $filters['status'], 'page' => $p])); ?>
        <?php if ($p === $result['page']): ?>
        <span class="admin-pager__item is-current"><?= $p ?></span>
        <?php else: ?>
        <a class="admin-pager__item" href="<?= url('admin/jobs') ?>?<?= e($qs) ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
