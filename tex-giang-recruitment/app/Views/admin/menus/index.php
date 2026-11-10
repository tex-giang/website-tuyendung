<?php
$page['ajax_fragment'] = true;
$partials = BASE_PATH . '/app/Views/partials/admin/';
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1">Menu website</h1>
    <p class="admin-sub">Menu điều hướng dùng chung cho đầu trang và chân trang, sắp xếp theo thứ tự hiển thị.</p>
  </div>
  <a href="<?= url('admin/menus/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm menu</a>
</div>
<section class="admin-list-panel">
  <?php if ($menus === []): ?>
  <p class="admin-list-empty">Chưa có menu nào.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-directory-table admin-directory-table--menus" aria-label="Danh sách menu">
      <thead><tr><th scope="col" class="admin-directory-order">Thứ tự</th><th scope="col">Tên hiển thị</th><th scope="col">Đường dẫn</th><th scope="col">Hiển thị</th><th scope="col" class="is-actions"><span class="visually-hidden">Thao tác</span></th></tr></thead>
      <tbody>
        <?php foreach ($menus as $menu):
            $base = 'admin/menus/' . (int) $menu['id'];
            $itemName = (string) $menu['label'];
            $toggleUrl = url($base . '/toggle');
            $editUrl = url($base . '/edit');
            $deleteUrl = url($base . '/delete');
            $deletePrompt = 'Xóa menu “' . $itemName . '”?';
            $active = (int) $menu['is_active'] === 1;
        ?>
        <tr>
          <td class="admin-directory-order admin-mono" data-label="Thứ tự"><?= (int) $menu['sort_order'] ?></td>
          <td class="admin-directory-name"><a class="admin-link" href="<?= $editUrl ?>"><?= e($itemName) ?></a></td>
          <td class="admin-mono" data-label="Đường dẫn"><?= e($menu['url'] !== '' ? $menu['url'] : '/') ?></td>
          <td data-label="Hiển thị"><?php require $partials . 'list-switch.php'; ?></td>
          <td class="is-actions"><?php require $partials . 'list-edit-delete.php'; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
