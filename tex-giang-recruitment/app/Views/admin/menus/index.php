<?php
/**
 * Danh sách menu website (header/footer). Menu bật mới hiện ngoài public.
 *
 * @var array<int, array<string, mixed>> $menus
 */
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Menu website</h1>
  </div>
  <a href="<?= url('admin/menus/create') ?>" class="btn btn-primary">+ Thêm menu</a>
</div>

<?php if ($menus === []): ?>
<section class="admin-card"><p class="admin-empty">Chưa có menu nào. <a href="<?= url('admin/menus/create') ?>">Thêm ngay</a>.</p></section>
<?php else: ?>
<section class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable" data-dt-compact>
      <thead><tr><th>Nhãn</th><th>Đường dẫn</th><th>Thứ tự</th><th>Trạng thái</th><th class="is-actions">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($menus as $menu): ?>
        <tr>
          <td><strong><?= e($menu['label']) ?></strong></td>
          <td class="admin-mono"><?= e($menu['url'] !== '' ? $menu['url'] : '(trang chủ)') ?></td>
          <td class="admin-muted"><?= (int) $menu['sort_order'] ?></td>
          <td>
            <span class="admin-badge <?= (int) $menu['is_active'] === 1 ? 'admin-badge--published' : 'admin-badge--draft' ?>">
              <?= (int) $menu['is_active'] === 1 ? 'Đang hiện' : 'Đang ẩn' ?>
            </span>
          </td>
          <td class="is-actions">
            <div class="admin-actions">
              <form method="POST" action="<?= url('admin/menus/' . $menu['id'] . '/toggle') ?>" data-ajax>
                <?= csrf_field() ?>
                <button class="btn btn-outline btn-icon" type="submit" title="<?= (int) $menu['is_active'] === 1 ? 'Ẩn' : 'Hiện' ?>" aria-label="Ẩn/Hiện"><?= (int) $menu['is_active'] === 1 ? admin_icon_svg('eye-off') : admin_icon_svg('eye') ?></button>
              </form>
              <a href="<?= url('admin/menus/' . $menu['id'] . '/edit') ?>" class="btn btn-outline btn-icon" title="Sửa" aria-label="Sửa"><?= admin_icon_svg('pencil') ?></a>
              <form method="POST" action="<?= url('admin/menus/' . $menu['id'] . '/delete') ?>"
                    data-ajax data-confirm="Xoá menu &quot;<?= e($menu['label']) ?>&quot;?">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-icon" type="submit" title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
