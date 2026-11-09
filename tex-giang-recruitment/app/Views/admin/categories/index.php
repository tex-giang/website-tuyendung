<?php
$page['ajax_fragment'] = true;
/** Danh mục dùng chung: khu sản xuất, khối phòng ban, chuyên mục tin tức. */
$sections = [
    'branch' => [
        'title'  => 'Khu sản xuất',
        'hint'   => 'Dùng cho bộ lọc địa điểm, chi nhánh trong tin tuyển dụng và footer.',
        'items'  => $branches,
        'countFn' => static fn(array $row): string => \App\Models\Branch::jobCount((int) $row['id']) . ' tin',
    ],
    'category' => [
        'title'  => 'Khối phòng ban',
        'hint'   => 'Dùng cho bộ lọc khối phòng ban và hiển thị trên thẻ việc làm.',
        'items'  => $categories,
        'countFn' => static fn(array $row): string => \App\Models\Category::jobCount((int) $row['id']) . ' tin',
    ],
    'post-category' => [
        'title'  => 'Chuyên mục tin tức',
        'hint'   => 'Dùng cho bộ lọc chuyên mục ở trang Tin tức và phân loại bài viết.',
        'items'  => $postCategories,
        'countFn' => static fn(array $row): string => \App\Models\PostCategory::postCount((int) $row['id']) . ' bài',
    ],
];
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Danh mục dùng chung</h1>
  </div>
</div>

<?php foreach ($sections as $type => $section): ?>
<section class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-h2"><?= e($section['title']) ?> <span class="admin-badge admin-badge--soft"><?= count($section['items']) ?></span></h2>
    <a href="<?= url('admin/categories/' . $type . '/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm</a>
  </div>

  <?php if ($section['items'] === []): ?>
  <p class="admin-empty">Chưa có dữ liệu.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable" data-dt-compact>
      <thead><tr><th>Tên</th><th>Slug</th><th>Thông tin</th><th>Đang dùng</th><th>Trạng thái</th><th class="is-actions">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($section['items'] as $row): ?>
        <tr>
          <td><strong><?= e($row['name']) ?></strong><span class="admin-muted admin-mono">#<?= (int) $row['id'] ?> · thứ tự <?= (int) $row['sort_order'] ?></span></td>
          <td class="admin-mono"><?= e($row['slug']) ?></td>
          <td class="admin-muted">
            <?php if ($type === 'branch'): ?>
              <?= e($row['address'] ?? '—') ?>
              <?php $contactCount = count($branchContacts[(int) $row['id']] ?? []); ?>
              <span class="admin-badge admin-badge--soft"><?= $contactCount ?> người liên hệ</span>
            <?php elseif ($type === 'category'): ?>
              <?= e($row['description'] ?? '—') ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td class="admin-muted"><?= e($section['countFn']($row)) ?></td>
          <td>
            <span class="admin-badge <?= (int) $row['is_active'] === 1 ? 'admin-badge--published' : 'admin-badge--draft' ?>">
              <?= (int) $row['is_active'] === 1 ? 'Đang bật' : 'Đang tắt' ?>
            </span>
          </td>
          <td class="is-actions">
            <div class="admin-actions">
              <form data-ajax method="POST" action="<?= url('admin/categories/' . $type . '/' . $row['id'] . '/toggle') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-outline btn-icon" type="submit" title="<?= (int) $row['is_active'] === 1 ? 'Tắt' : 'Bật' ?>" aria-label="Bật/Tắt"><?= (int) $row['is_active'] === 1 ? admin_icon_svg('eye-off') : admin_icon_svg('eye') ?></button>
              </form>
              <a href="<?= url('admin/categories/' . $type . '/' . $row['id'] . '/edit') ?>" class="btn btn-outline btn-icon" title="Sửa" aria-label="Sửa"><?= admin_icon_svg('pencil') ?></a>
              <form data-ajax method="POST" action="<?= url('admin/categories/' . $type . '/' . $row['id'] . '/delete') ?>"
                    data-confirm="Xoá &quot;<?= e($row['name']) ?>&quot;?">
                <?= csrf_field() ?><button class="btn btn-danger btn-icon" type="submit" title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>
