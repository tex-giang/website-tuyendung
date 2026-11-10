<?php
$page['ajax_fragment'] = true;
$listUrl = url('admin/posts');
$listPager = true;
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="admin-list-title">Tin tức và bài viết</h1>
    <p class="admin-sub">Quản lý bài viết hiển thị trên website.</p>
  </div>
  <a href="<?= $listUrl ?>/create" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm bài viết</a>
</div>

<div class="admin-list-panel">
  <form class="admin-filters admin-list-toolbar" data-admin-live-filter method="GET" role="search" aria-label="Lọc danh sách bài viết" action="<?= $listUrl ?>">
    <label class="admin-list-search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <span class="visually-hidden">Tìm theo tiêu đề hoặc slug</span>
      <input id="post-filter-q" type="search" name="q" class="field__input" value="<?= e($filters['q']) ?>" placeholder="Tìm theo tiêu đề hoặc slug…">
    </label>
    <select id="post-filter-category" name="category_id" class="field__input admin-list-select" data-submit-onchange aria-label="Lọc theo chuyên mục">
      <option value="">Tất cả chuyên mục</option>
      <?php foreach ($categories as $category): ?>
      <option value="<?= (int) $category['id'] ?>"<?= (string) $filters['category_id'] === (string) $category['id'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="post-filter-status" name="status" class="field__input admin-list-select admin-list-select--status" data-submit-onchange aria-label="Lọc theo trạng thái">
      <option value="">Mọi trạng thái</option>
      <?php foreach ($statuses as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-outline">Áp dụng</button></noscript>
  </form>

  <?php if ($result['items'] === []): ?>
  <p class="admin-list-empty">Chưa có bài viết nào khớp bộ lọc.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-cms-table table table-hover align-middle" aria-labelledby="admin-list-title">
      <thead><tr>
        <th scope="col">Ảnh</th><th scope="col">Tiêu đề</th><th scope="col">Chuyên mục</th>
        <th scope="col">Trạng thái</th><th scope="col">Ngày đăng</th><th scope="col" class="is-actions">Thao tác</th>
      </tr></thead>
      <tbody>
        <?php foreach ($result['items'] as $item): ?>
        <?php $postUrl = $listUrl . '/' . (int) $item['id']; $published = $item['status'] === 'published'; ?>
        <tr>
          <td>
            <?php if (!empty($item['image_path'])): ?>
            <img class="admin-cms-thumb" src="<?= e(asset($item['image_path'])) ?>" alt="" loading="lazy">
            <?php else: ?>
            <span class="admin-cms-thumb admin-cms-thumb--empty" aria-label="Chưa có ảnh"><i class="bi bi-image" aria-hidden="true"></i></span>
            <?php endif; ?>
          </td>
          <td class="admin-cms-table__title">
            <a href="<?= $postUrl ?>/edit"><?= e($item['title']) ?></a>
            <span class="admin-muted admin-mono">/<?= e($item['slug']) ?></span>
          </td>
          <td><?= e($item['category_name'] ?? '—') ?></td>
          <td><span class="admin-badge <?= $published ? 'admin-badge--published' : 'admin-badge--draft' ?>"><?= e($statuses[$item['status']] ?? $item['status']) ?></span></td>
          <td class="admin-muted admin-nowrap"><?= e(vn_date($item['published_at']) ?: '—') ?></td>
          <td class="is-actions">
            <div class="admin-actions">
              <?php if ($published): ?>
              <a href="<?= url('news/' . $item['slug']) ?>" target="_blank" rel="noopener" class="admin-row-icon" title="Xem trên website" aria-label="Xem trên website"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
              <?php endif; ?>
              <a href="<?= $postUrl ?>/edit" class="admin-row-icon" title="Chỉnh sửa" aria-label="Chỉnh sửa"><i class="bi bi-pencil" aria-hidden="true"></i></a>
              <form method="POST" action="<?= $postUrl ?>/status" data-ajax data-ajax-refresh>
                <?= csrf_field() ?>
                <button type="submit" name="status" value="<?= $published ? 'draft' : 'published' ?>" class="admin-row-icon"
                        title="<?= $published ? 'Chuyển về bản nháp' : 'Đăng bài viết' ?>" aria-label="<?= $published ? 'Chuyển về bản nháp' : 'Đăng bài viết' ?>"><i class="bi <?= $published ? 'bi-eye-slash' : 'bi-globe' ?>" aria-hidden="true"></i></button>
              </form>
              <form method="POST" action="<?= $postUrl ?>/delete" data-ajax data-ajax-refresh data-confirm="Xóa bài &quot;<?= e($item['title']) ?>&quot;? Bài sẽ gỡ khỏi website.">
                <?= csrf_field() ?><button type="submit" class="admin-row-icon admin-row-icon--danger" title="Xóa bài viết" aria-label="Xóa bài viết"><i class="bi bi-trash" aria-hidden="true"></i></button>
              </form>
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
