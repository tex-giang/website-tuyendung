<?php
$page['ajax_fragment'] = true;
$isEdit = $user !== null;
$action = $isEdit ? url('admin/users/' . (int) $user['id'] . '/edit') : url('admin/users/create');
$back = url('admin/users');
$heading = $isEdit ? 'Chỉnh sửa người dùng' : 'Thêm người dùng';
$val = static fn(string $key): string => (string) ($values[$key] ?? '');
$selectedBranches = is_array($values['branch_ids'] ?? null) ? array_map('intval', $values['branch_ids']) : [];
$allBranches = (int) ($values['all_branches'] ?? 1) === 1;
$renderField = static function (string $name, string $label, string $type, bool $required, string $attributes = '') use ($val, $errors): void {
    $error = $errors[$name] ?? '';
    ?>
    <label class="field">
      <span class="field__label"><?= e($label) ?><?= $required ? ' <em>*</em>' : '' ?></span>
      <input class="field__input<?= $error ? ' is-invalid' : '' ?>" type="<?= e($type) ?>" name="<?= e($name) ?>"
        <?= $type !== 'password' ? 'value="' . e($val($name)) . '"' : 'autocomplete="new-password" minlength="8"' ?> <?= $attributes ?><?= $required ? ' required' : '' ?>>
      <?php if ($error): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
    </label>
    <?php
};
?>
<div class="admin-compact-editor admin-user-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <a href="<?= $back ?>" class="admin-editor-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách người dùng</a>
      <h1 class="admin-h1"><?= e($heading) ?></h1>
      <p class="admin-sub"><?= $isEdit ? 'Cập nhật thông tin, quyền truy cập và phạm vi nhà máy của tài khoản.' : 'Tạo tài khoản, gán vai trò và phạm vi nhà máy được phép xem.' ?></p>
    </div>
  </div>
  <form id="user-information" data-ajax data-admin-draft method="POST" action="<?= $action ?>" class="admin-form admin-cms-card admin-cms-fields admin-compact-card">
    <?= csrf_field() ?>
    <div class="field-grid admin-cms-fields">
      <?php
      $renderField('username', 'Tên đăng nhập', 'text', !$isEdit, 'maxlength="50" autocomplete="username" autocapitalize="none" spellcheck="false"' . ($isEdit ? ' readonly' : ''));
      $renderField('full_name', 'Họ và tên', 'text', true, 'maxlength="150" autocomplete="name"');
      $renderField('email', 'Email', 'email', false, 'maxlength="190" autocomplete="email"');
      $renderField('phone', 'Số điện thoại', 'tel', false, 'maxlength="30" autocomplete="tel"');
      ?>
      <label class="field field--span2">
        <span class="field__label">Vai trò <em>*</em></span>
        <select class="field__input admin-user-role<?= !empty($errors['role_id']) ? ' is-invalid' : '' ?>" name="role_id" required>
          <?php foreach ($roles as $roleName => $roleLabel): ?>
          <?php $roleId = (int) \App\Models\User::roleIdByName($roleName); ?>
          <option value="<?= $roleId ?>"<?= (int) $val('role_id') === $roleId ? ' selected' : '' ?>><?= e($roleLabel) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['role_id'])): ?><span class="field__error"><?= e($errors['role_id']) ?></span><?php endif; ?>
      </label>
      <?php if (!$isEdit): ?>
      <?php $renderField('password', 'Mật khẩu', 'password', true); $renderField('password_confirm', 'Nhập lại mật khẩu', 'password', true); ?>
      <?php endif; ?>
    </div>

    <section class="admin-user-scope" aria-labelledby="user-scope-title">
      <h2 class="admin-user-scope__title" id="user-scope-title">Phạm vi nhà máy (tuỳ chọn)</h2>
      <?php
      $roleScopeIds = $roleScope['branch_ids'] ?? null;
      $roleScopeLabel = (string) ($roleScope['label'] ?? '');
      $roleAllBranches = $roleScopeIds === null;
      ?>
      <p class="admin-user-scope__hint">
        <?php if ($roleAllBranches): ?>
        Vai trò <strong><?= e($roleScopeLabel !== '' ? $roleScopeLabel : 'hiện tại') ?></strong> không giới hạn nhà máy.
        Bật "Tất cả nhà máy" để xem mọi nhà máy, hoặc chọn nhà máy cụ thể để giới hạn riêng tài khoản này.
        <?php else: ?>
        Vai trò <strong><?= e($roleScopeLabel) ?></strong> đã giới hạn dữ liệu theo nhà máy của vai trò.
        Tài khoản có thể thu hẹp thêm, nhưng không mở rộng quá phạm vi của vai trò.
        <?php endif; ?>
      </p>
      <label class="admin-cms-visibility admin-editor-visibility">
        <span>Theo phạm vi của vai trò<span class="admin-muted">Tắt để chọn nhà máy riêng cho tài khoản này.</span></span>
        <input class="admin-cms-switch" type="checkbox" role="switch" name="all_branches" value="1"
               data-branch-all<?= $allBranches ? ' checked' : '' ?>>
      </label>
      <div class="admin-user-branches" data-branch-list<?= $allBranches ? ' hidden' : '' ?>>
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

    <div class="field">
      <label class="admin-cms-visibility admin-editor-visibility">
        <span>Tài khoản đang hoạt động<span class="admin-muted">Tài khoản được phép đăng nhập khi bật.</span></span>
        <input class="admin-cms-switch" type="checkbox" role="switch" name="is_active" value="1"<?= $val('is_active') === '1' ? ' checked' : '' ?>>
      </label>
      <?php if (!empty($errors['is_active'])): ?><span class="field__error"><?= e($errors['is_active']) ?></span><?php endif; ?>
    </div>
    <div class="admin-cms-actions admin-compact-actions">
      <a href="<?= $back ?>" class="btn btn-outline">Hủy</a>
      <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm người dùng' ?></button>
    </div>
  </form>

  <?php if ($isEdit): ?>
  <form id="user-password" data-ajax data-admin-draft method="POST" action="<?= url('admin/users/' . (int) $user['id'] . '/password') ?>" class="admin-form admin-cms-card admin-cms-fields admin-compact-card">
    <?= csrf_field() ?>
    <header class="admin-cms-section-intro">
      <h2>Đặt lại mật khẩu</h2>
      <p>Tạo mật khẩu mới có ít nhất 8 ký tự cho tài khoản này.</p>
    </header>
    <div class="field-grid admin-cms-fields">
      <?php $renderField('password', 'Mật khẩu mới', 'password', true); $renderField('password_confirm', 'Nhập lại mật khẩu mới', 'password', true); ?>
    </div>
    <div class="admin-cms-actions">
      <button type="submit" class="btn btn-outline"><i class="bi bi-key" aria-hidden="true"></i> Đặt lại mật khẩu</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<script>
(function () {
  'use strict';
  var toggle = document.querySelector('[data-branch-all]');
  var list = document.querySelector('[data-branch-list]');
  if (!toggle || !list) return;
  function sync() { list.hidden = toggle.checked; }
  toggle.addEventListener('change', sync);
  sync();
})();
</script>
