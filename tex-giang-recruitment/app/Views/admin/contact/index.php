<?php
$page['ajax_fragment'] = true;
/** Hộp thư liên hệ từ website. */
$statusBadge = ['new' => 'admin-badge--new', 'read' => 'admin-badge--draft', 'replied' => 'admin-badge--published'];
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1" id="admin-list-title">Hộp thư liên hệ</h1>
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

<form class="admin-filters" method="GET" role="search" aria-label="Bộ lọc danh sách" action="<?= url('admin/contact') ?>">
  <label class="field">
    <span class="field__label">Trạng thái</span>
    <select name="status" class="field__input">
      <option value="">Tất cả trạng thái</option>
      <?php foreach ($statuses as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?> (<?= (int) ($counts[$key] ?? 0) ?>)</option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="admin-filter-actions">
    <button type="submit" class="btn btn-outline"><i class="bi bi-funnel me-1" aria-hidden="true"></i> Áp dụng</button>
    <a href="<?= url('admin/contact') ?>" class="btn btn-ghost"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Xoá lọc</a>
  </div>
</form>

<div class="admin-card">
  <?php if ($result['items'] === []): ?>
  <p class="admin-empty">Chưa có tin nhắn nào. Câu hỏi gửi từ trang Liên hệ sẽ xuất hiện tại đây.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle" aria-labelledby="admin-list-title">
      <thead><tr><th>Người gửi</th><th>Nội dung</th><th>Trạng thái</th><th>Nhận lúc</th><th class="is-actions">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($result['items'] as $item): ?>
        <tr>
          <td>
            <a class="admin-link" href="<?= url('admin/contact/' . $item['id']) ?>"><strong><?= e($item['full_name']) ?></strong></a>
            <span class="admin-muted"><?= e($item['phone']) ?><?= $item['email'] ? ' · ' . e($item['email']) : '' ?></span>
          </td>
          <td>
            <?php if (!empty($item['subject'])): ?><span class="admin-muted"><?= e($item['subject']) ?></span><?php endif; ?>
            <span class="admin-muted"><?= e(excerpt((string) $item['message'], 110)) ?></span>
          </td>
          <td><span class="admin-badge <?= $statusBadge[$item['status']] ?? '' ?>"><?= e($statuses[$item['status']] ?? $item['status']) ?></span></td>
          <td class="admin-muted"><?= e(vn_date($item['created_at'], 'd/m/Y H:i')) ?></td>
          <td class="is-actions">
            <div class="admin-actions">
              <a href="<?= url('admin/contact/' . $item['id']) ?>" class="btn btn-outline btn-sm"><i class="bi bi-eye me-1" aria-hidden="true"></i> Xem</a>
              <form data-ajax-refresh data-ajax method="POST" action="<?= url('admin/contact/' . $item['id'] . '/delete') ?>"
                    data-confirm="Xoá tin nhắn của &quot;<?= e($item['full_name']) ?>&quot;?">
                <?= csrf_field() ?><button class="btn btn-danger btn-icon" type="submit" title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php $listUrl = url('admin/contact'); require BASE_PATH . '/app/Views/partials/admin/pager.php'; ?>
  <?php endif; ?>
</div>
