<?php
$page['ajax_fragment'] = true;
$isEdit = $role !== null;
$action = $isEdit ? url('admin/roles/' . (int) $role['id'] . '/edit') : url('admin/roles/create');
$back = url('admin/roles');
$heading = $isEdit ? 'Sửa vai trò' : 'Tạo vai trò';
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$permChecked = static function (string $module, string $action) use ($values): bool {
    return isset($values['perms'][$module][$action]) && (string) $values['perms'][$module][$action] === '1';
};
$selectedBranches = is_array($values['branch_ids'] ?? null) ? array_map('intval', $values['branch_ids']) : [];
$allBranches = (int) ($values['all_branches'] ?? 1) === 1;
?>
<div class="admin-role-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <a href="<?= $back ?>" class="admin-editor-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách vai trò</a>
      <h1 class="admin-h1"><?= e($heading) ?></h1>
      <p class="admin-sub">Đặt tên, chọn quyền cho từng menu và giới hạn nhà máy được xem dữ liệu.</p>
    </div>
  </div>

  <?php if ($errors !== []): ?>
  <div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
  <?php endif; ?>

  <form data-ajax method="POST" action="<?= $action ?>" class="admin-form admin-cms-card admin-role-form">
    <?= csrf_field() ?>

    <section class="admin-role-section" aria-labelledby="role-basic-title">
      <h2 class="admin-cms-section-intro" id="role-basic-title">Thông tin vai trò</h2>
      <div class="field-grid admin-cms-fields">
        <label class="field">
          <span class="field__label">Tên vai trò <em>*</em></span>
          <input class="field__input<?= !empty($errors['description']) ? ' is-invalid' : '' ?>" type="text" name="description"
                 value="<?= e($val('description')) ?>" maxlength="255" required
                 placeholder="Ví dụ: Nhà máy Tân Hương">
          <?php if (!empty($errors['description'])): ?><span class="field__error"><?= e($errors['description']) ?></span><?php endif; ?>
        </label>
        <?php if ($isEdit): ?>
        <label class="field">
          <span class="field__label">Mã vai trò</span>
          <input class="field__input" type="text" value="<?= e($val('name')) ?>" readonly>
        </label>
        <?php endif; ?>
      </div>
    </section>

    <section class="admin-role-section" aria-labelledby="role-scope-title">
      <h2 class="admin-cms-section-intro" id="role-scope-title">Phạm vi nhà máy</h2>
      <p class="admin-muted admin-role-section__hint">Giới hạn dữ liệu tin tuyển dụng / hồ sơ ứng tuyển mà vai trò này được xem. Tài khoản thuộc vai trò sẽ chỉ thấy dữ liệu các nhà máy được chọn.</p>
      <label class="admin-cms-visibility admin-editor-visibility">
        <span>Tất cả nhà máy<span class="admin-muted">Bật để vai trò xem dữ liệu của mọi nhà máy.</span></span>
        <input class="admin-cms-switch" type="checkbox" role="switch" name="all_branches" value="1"
               data-role-branch-all<?= $allBranches ? ' checked' : '' ?>>
      </label>
      <div class="admin-user-branches" data-role-branch-list<?= $allBranches ? ' hidden' : '' ?>>
        <?php foreach ($branches as $branchId => $branchName): ?>
        <label class="admin-user-branch">
          <input type="checkbox" name="branch_ids[]" value="<?= (int) $branchId ?>"
                 <?= in_array((int) $branchId, $selectedBranches, true) ? 'checked' : '' ?>>
          <span><?= e($branchName) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($errors['branch_ids'])): ?><span class="field__error"><?= e($errors['branch_ids']) ?></span><?php endif; ?>
    </section>

    <section class="admin-role-section" aria-labelledby="role-perm-title">
      <h2 class="admin-cms-section-intro" id="role-perm-title">Quyền theo menu</h2>
      <div class="admin-role-matrix-head" aria-hidden="true">
        <span>Menu quản trị</span>
        <?php foreach ($actions as $actionLabel): ?>
        <span class="admin-role-matrix-head__col"><?= e($actionLabel) ?></span>
        <?php endforeach; ?>
        <span class="admin-role-matrix-head__col">Tất cả</span>
      </div>

      <?php foreach ($modules as $moduleKey => $module): ?>
      <fieldset class="admin-role-row">
        <legend class="visually-hidden"><?= e($module['label']) ?></legend>
        <span class="admin-role-row__label"><?= e($module['label']) ?></span>
        <?php foreach ($actions as $actionKey => $actionLabel): ?>
        <label class="admin-role-cell">
          <input class="admin-role-check" type="checkbox" name="perm[<?= e($moduleKey) ?>][<?= e($actionKey) ?>]" value="1"
                 aria-label="<?= e($actionLabel . ' — ' . $module['label']) ?>"<?= $permChecked($moduleKey, $actionKey) ? ' checked' : '' ?>>
        </label>
        <?php endforeach; ?>
        <label class="admin-role-cell admin-role-cell--all">
          <input class="admin-role-check" type="checkbox" data-role-row-all
                 aria-label="<?= e('Chọn tất cả quyền — ' . $module['label']) ?>">
        </label>
      </fieldset>
      <?php endforeach; ?>
    </section>

    <?php if ($assignableUsers !== []): ?>
    <section class="admin-role-section" aria-labelledby="role-users-title">
      <h2 class="admin-cms-section-intro" id="role-users-title">Gán tài khoản vào vai trò</h2>
      <p class="admin-muted admin-role-section__hint">
        Tick tài khoản để gán vào vai trò này. Bỏ tick tài khoản đang thuộc vai trò sẽ chuyển tài khoản đó về
        vai trò mặc định <strong>Nhân viên tuyển dụng</strong>. Tài khoản Quản trị hệ thống không gán được.
      </p>
      <div class="admin-role-users">
        <?php foreach ($assignableUsers as $account):
            $accountId = (int) $account['id'];
            $checked = in_array($accountId, $selectedUsers, true);
        ?>
        <label class="admin-role-user<?= (int) $account['is_active'] === 1 ? '' : ' is-inactive' ?>">
          <input type="checkbox" name="user_ids[]" value="<?= $accountId ?>"<?= $checked ? ' checked' : '' ?>>
          <span class="admin-role-user__name"><?= e($account['full_name']) ?><?= (int) $account['is_active'] === 1 ? '' : ' (đã khoá)' ?></span>
          <span class="admin-role-user__meta"><?= e($account['username']) ?> · <?= e($account['role_label'] ?: $account['role_name']) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <div class="admin-cms-actions admin-compact-actions">
      <a href="<?= $back ?>" class="btn btn-outline">Hủy</a>
      <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu vai trò' : 'Tạo vai trò' ?></button>
    </div>
  </form>
</div>

<script>
(function () {
  'use strict';
  // Bật/tắt danh sách nhà máy
  var all = document.querySelector('[data-role-branch-all]');
  var list = document.querySelector('[data-role-branch-list]');
  if (all && list) {
    var sync = function () { list.hidden = all.checked; };
    all.addEventListener('change', sync);
    sync();
  }
  // "Tất cả" theo từng dòng: tick/bỏ tick 4 ô quyền của dòng
  document.querySelectorAll('[data-role-row-all]').forEach(function (master) {
    var row = master.closest('.admin-role-row');
    if (!row) return;
    var boxes = Array.prototype.slice.call(row.querySelectorAll('input[name^="perm["]'));
    master.addEventListener('change', function () {
      boxes.forEach(function (box) { box.checked = master.checked; });
    });
    boxes.forEach(function (box) {
      box.addEventListener('change', function () {
        master.checked = boxes.every(function (b) { return b.checked; });
      });
    });
    master.checked = boxes.length > 0 && boxes.every(function (b) { return b.checked; });
  });
})();
</script>
