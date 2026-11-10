<?php
$page['ajax_fragment'] = true;
$partials = BASE_PATH . '/app/Views/partials/admin/';
$section = $types[$type];
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1">Danh mục dùng chung</h1>
    <p class="admin-sub"><?= e($section['hint']) ?></p>
  </div>
  <a href="<?= url('admin/categories/' . $type . '/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm <?= e($section['singular']) ?></a>
</div>

<nav class="admin-period-tabs admin-taxonomy-tabs" aria-label="Loại danh mục">
  <?php foreach ($types as $key => $item): ?>
  <a href="<?= e(url('admin/categories') . '?' . http_build_query(['type' => $key])) ?>"<?= $type === $key ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
  <?php endforeach; ?>
</nav>

<section class="admin-list-panel">
  <?php if ($rows === []): ?>
  <p class="admin-list-empty">Chưa có dữ liệu.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-directory-table admin-directory-table--taxonomy" aria-label="<?= e($section['label']) ?>">
      <thead><tr><th scope="col">Tên</th><th scope="col">Đường dẫn (slug)</th><th scope="col">Thông tin</th><th scope="col">Đang sử dụng</th><th scope="col">Hiển thị</th><th scope="col" class="is-actions"><span class="visually-hidden">Thao tác</span></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row):
            $id = (int) $row['id'];
            $base = 'admin/categories/' . $type . '/' . $id;
            $itemName = (string) $row['name'];
            $toggleUrl = url($base . '/toggle');
            $editUrl = url($base . '/edit');
            $deleteUrl = url($base . '/delete');
            $deletePrompt = 'Xóa “' . $itemName . '”?';
            $active = (int) $row['is_active'] === 1;
        ?>
        <tr>
          <td class="admin-directory-name"><a class="admin-link" href="<?= $editUrl ?>"><?= e($itemName) ?></a></td>
          <td class="admin-mono" data-label="Đường dẫn"><?= e($row['slug']) ?></td>
          <td data-label="Thông tin">
            <?php if ($type === 'branch'): ?>
            <span><?= e($row['address'] ?: '—') ?></span>
            <?php $contactCount = count($branchContacts[$id] ?? []); ?>
            <span class="admin-muted"><?= e($row['phone'] ? $row['phone'] . ' · ' : '') ?><?= $contactCount ?> đầu mối</span>
            <?php else: ?>
            <?= e(($row['description'] ?? '') ?: '—') ?>
            <?php endif; ?>
          </td>
          <td class="admin-mono" data-label="Đang sử dụng"><?= (int) $usage[$id] ?></td>
          <td data-label="Hiển thị"><?php require $partials . 'list-switch.php'; ?></td>
          <td class="is-actions"><?php require $partials . 'list-edit-delete.php'; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
