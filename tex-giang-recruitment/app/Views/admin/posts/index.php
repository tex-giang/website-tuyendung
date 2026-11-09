<?php
/** Danh sách bài viết (admin). */
$statusBadge = ['published' => 'admin-badge--published', 'draft' => 'admin-badge--draft'];
$start = $result['total'] === 0 ? 0 : (($result['page'] - 1) * $result['per_page']) + 1;
$end = min($result['page'] * $result['per_page'], $result['total']);
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Tin tức &amp; bài viết</h1>
  </div>
  <a href="<?= url('admin/posts/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm bài viết</a>
</div>

<form class="admin-filters" method="GET" action="<?= url('admin/posts') ?>">
  <input type="search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo tiêu đề hoặc slug…">
  <select name="status" class="field__input" data-submit-onchange>
    <option value="">Tất cả trạng thái</option>
    <?php foreach ($statuses as $key => $label): ?>
    <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="category_id" class="field__input" data-submit-onchange>
    <option value="">Tất cả chuyên mục</option>
    <?php foreach ($categories as $category): ?>
    <option value="<?= (int) $category['id'] ?>"<?= (string) $filters['category_id'] === (string) $category['id'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline"><i class="bi bi-funnel me-1" aria-hidden="true"></i> Lọc</button>
  <a href="<?= url('admin/posts') ?>" class="btn btn-ghost"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Xoá lọc</a>
</form>

<div class="admin-card">
  <?php if ($result['items'] === []): ?>
  <p class="admin-empty">Chưa có bài viết nào khớp bộ lọc. <a href="<?= url('admin/posts/create') ?>">Tạo bài đầu tiên</a>.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable">
      <thead>
        <tr><th>Bài viết</th><th>Chuyên mục</th><th>Trạng thái</th><th>Đăng lúc</th><th>Cập nhật</th><th class="is-actions">Thao tác</th></tr>
      </thead>
      <tbody>
        <?php foreach ($result['items'] as $item): ?>
        <tr>
          <td>
            <div class="admin-media">
              <?php $thumb = ($item['image_path'] ?? '') !== '' ? asset((string) $item['image_path']) : ''; ?>
              <?php if ($thumb !== ''): ?>
              <img class="admin-thumb" src="<?= e($thumb) ?>" alt="" loading="lazy">
              <?php else: ?>
              <span class="admin-thumb admin-thumb--empty">ảnh</span>
              <?php endif; ?>
              <div>
                <a class="admin-link" href="<?= url('admin/posts/' . $item['id'] . '/edit') ?>"><?= e($item['title']) ?></a>
                <span class="admin-muted admin-mono">/<?= e($item['slug']) ?></span>
              </div>
            </div>
          </td>
          <td class="admin-muted"><?= e($item['category_name'] ?? '—') ?></td>
          <td>
            <form method="POST" action="<?= url('admin/posts/' . $item['id'] . '/status') ?>" class="admin-inline" data-ajax data-ajax-reload>
              <?= csrf_field() ?>
              <select name="status" class="admin-select-sm" data-submit-onchange aria-label="Đổi trạng thái">
                <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $item['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <span class="admin-badge <?= $statusBadge[$item['status']] ?? '' ?>"><?= e($statuses[$item['status']] ?? $item['status']) ?></span>
          </td>
          <td class="admin-muted"><?= e(vn_date($item['published_at'], 'd/m/Y H:i') ?: '—') ?></td>
          <td class="admin-muted"><?= e(vn_date($item['updated_at'], 'd/m/Y H:i')) ?></td>
          <td class="is-actions">
            <div class="admin-actions">
              <?php if ($item['status'] === 'published'): ?>
              <a href="<?= url('news/' . $item['slug']) ?>" target="_blank" rel="noopener" class="btn btn-icon" title="Xem trên website" aria-label="Xem trên website"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
              <?php endif; ?>
              <a href="<?= url('admin/posts/' . $item['id'] . '/edit') ?>" class="btn btn-outline btn-icon" title="Sửa" aria-label="Sửa"><?= admin_icon_svg('pencil') ?></a>
              <form method="POST" action="<?= url('admin/posts/' . $item['id'] . '/delete') ?>"
                    data-ajax data-confirm="Xoá bài &quot;<?= e($item['title']) ?>&quot;? Bài sẽ gỡ khỏi website.">
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
        <?php $qs = http_build_query(array_filter(['q' => $filters['q'], 'status' => $filters['status'], 'category_id' => $filters['category_id'], 'page' => $p])); ?>
        <?php if ($p === $result['page']): ?>
        <span class="admin-pager__item is-current"><?= $p ?></span>
        <?php else: ?>
        <a class="admin-pager__item" href="<?= url('admin/posts') ?>?<?= e($qs) ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
