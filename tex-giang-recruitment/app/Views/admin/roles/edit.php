<?php
$page['ajax_fragment'] = true;
$isSuper = $role['name'] === 'super_admin';
$roleLabel = trim((string) ($role['description'] ?? '')) !== '' ? (string) $role['description'] : (string) $role['name'];
$error = errors('perm');
?>
<div class="admin-compact-editor admin-role-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <a href="<?= url('admin/roles') ?>" class="admin-editor-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách vai trò</a>
      <h1 class="admin-h1">Phân quyền: <?= e($roleLabel) ?></h1>
      <p class="admin-sub">Bật quyền cho từng menu. Có quyền thêm/sửa/xoá thì tự động có quyền xem.</p>
    </div>
  </div>

  <?php if ($error): ?><div class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

  <form data-ajax method="POST" action="<?= url('admin/roles/' . (int) $role['id'] . '/edit') ?>" class="admin-form admin-cms-card admin-role-form">
    <?= csrf_field() ?>

    <div class="admin-role-matrix-head" aria-hidden="true">
      <span>Menu quản trị</span>
      <?php foreach ($actions as $actionKey => $actionLabel): ?>
      <span class="admin-role-matrix-head__col"><?= e($actionLabel) ?></span>
      <?php endforeach; ?>
    </div>

    <?php foreach ($modules as $moduleKey => $module): ?>
    <?php $row = $matrix[$moduleKey] ?? ['view' => 0, 'create' => 0, 'update' => 0, 'delete' => 0]; ?>
    <fieldset class="admin-role-row">
      <legend class="visually-hidden"><?= e($module['label']) ?></legend>
      <span class="admin-role-row__label"><?= e($module['label']) ?></span>
      <?php foreach ($actions as $actionKey => $actionLabel): ?>
      <label class="admin-role-cell">
        <input class="admin-role-check" type="checkbox" name="perm[<?= e($moduleKey) ?>][<?= e($actionKey) ?>]" value="1"
               aria-label="<?= e($actionLabel . ' — ' . $module['label']) ?>"<?= !empty($row[$actionKey]) ? ' checked' : '' ?>>
      </label>
      <?php endforeach; ?>
    </fieldset>
    <?php endforeach; ?>

    <div class="admin-cms-actions admin-compact-actions">
      <a href="<?= url('admin/roles') ?>" class="btn btn-outline">Hủy</a>
      <button type="submit" class="btn btn-primary">Lưu phân quyền</button>
    </div>
  </form>
</div>
