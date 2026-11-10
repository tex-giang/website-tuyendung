<?php
$page['ajax_fragment'] = true;
$isEdit = $user !== null;
$action = $isEdit ? url('admin/users/' . (int) $user['id'] . '/edit') : url('admin/users/create');
$back = url('admin/users');
$heading = $isEdit ? 'Chỉnh sửa người dùng' : 'Thêm người dùng';
$val = static fn(string $key): string => (string) ($values[$key] ?? '');
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
      <p class="admin-sub"><?= $isEdit ? 'Cập nhật thông tin và quyền truy cập của tài khoản.' : 'Tạo tài khoản và gán vai trò truy cập hệ thống quản trị.' ?></p>
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
