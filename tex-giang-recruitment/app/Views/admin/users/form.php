<?php
/** Form thêm/sửa tài khoản quản trị. */
$isEdit = $user !== null;
$action = $isEdit ? url('admin/users/' . $user['id'] . '/edit') : url('admin/users/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= url('admin/users') ?>">Người dùng</a> / <?= $isEdit ? 'Sửa' : 'Thêm' ?></p>
    <h1 class="admin-h1"><?= $isEdit ? 'Sửa tài khoản' : 'Thêm tài khoản' ?></h1>
  </div>
  <a href="<?= url('admin/users') ?>" class="btn btn-ghost">← Danh sách</a>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" class="admin-form">
  <?= csrf_field() ?>
  <section class="admin-card">
    <h2 class="admin-h2">Thông tin tài khoản</h2>
    <div class="field-grid">
      <label class="field">
        <span class="field__label">Tên đăng nhập <em>*</em></span>
        <input class="field__input<?= $err('username') ? ' is-invalid' : '' ?>" type="text" name="username"
               value="<?= e($val('username')) ?>" maxlength="50" <?= $isEdit ? 'readonly' : 'required' ?>>
        <?php if ($err('username')): ?><span class="field__error"><?= e($err('username')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Họ và tên <em>*</em></span>
        <input class="field__input<?= $err('full_name') ? ' is-invalid' : '' ?>" type="text" name="full_name"
               value="<?= e($val('full_name')) ?>" maxlength="150" required>
        <?php if ($err('full_name')): ?><span class="field__error"><?= e($err('full_name')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Email</span>
        <input class="field__input<?= $err('email') ? ' is-invalid' : '' ?>" type="email" name="email"
               value="<?= e($val('email')) ?>" maxlength="190">
        <?php if ($err('email')): ?><span class="field__error"><?= e($err('email')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Điện thoại</span>
        <input class="field__input" type="tel" name="phone" value="<?= e($val('phone')) ?>" maxlength="30">
      </label>
      <label class="field">
        <span class="field__label">Vai trò</span>
        <select class="field__input<?= $err('role_id') ? ' is-invalid' : '' ?>" name="role_id">
          <?php foreach ($roles as $roleName => $roleLabel): ?>
            <?php $roleId = \App\Models\User::roleIdByName($roleName); ?>
            <option value="<?= (int) $roleId ?>"<?= (string) $val('role_id') === (string) $roleId ? ' selected' : '' ?>><?= e($roleLabel) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($err('role_id')): ?><span class="field__error"><?= e($err('role_id')) ?></span><?php endif; ?>
      </label>
      <label class="field field--checkbox">
        <input type="checkbox" name="is_active" value="1"<?= (int) $val('is_active', '1') === 1 ? ' checked' : '' ?>>
        <span>Tài khoản đang hoạt động</span>
        <?php if ($err('is_active')): ?><span class="field__error"><?= e($err('is_active')) ?></span><?php endif; ?>
      </label>
      <?php if (!$isEdit): ?>
      <label class="field">
        <span class="field__label">Mật khẩu <em>*</em> <small>tối thiểu 8 ký tự</small></span>
        <input class="field__input<?= $err('password') ? ' is-invalid' : '' ?>" type="password" name="password" required autocomplete="new-password">
        <?php if ($err('password')): ?><span class="field__error"><?= e($err('password')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Nhập lại mật khẩu <em>*</em></span>
        <input class="field__input<?= $err('password_confirm') ? ' is-invalid' : '' ?>" type="password" name="password_confirm" required autocomplete="new-password">
        <?php if ($err('password_confirm')): ?><span class="field__error"><?= e($err('password_confirm')) ?></span><?php endif; ?>
      </label>
      <?php endif; ?>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></button>
    <a href="<?= url('admin/users') ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>

<?php if ($isEdit): ?>
<form method="POST" action="<?= url('admin/users/' . $user['id'] . '/password') ?>" class="admin-form mt-4">
  <?= csrf_field() ?>
  <section class="admin-card">
    <h2 class="admin-h2">Đặt lại mật khẩu</h2>
    <p class="admin-hint">Dùng khi người dùng quên mật khẩu. Mật khẩu mới tối thiểu 8 ký tự.</p>
    <div class="field-grid">
      <label class="field">
        <span class="field__label">Mật khẩu mới</span>
        <input class="field__input" type="password" name="password" required autocomplete="new-password">
      </label>
      <label class="field">
        <span class="field__label">Nhập lại mật khẩu mới</span>
        <input class="field__input" type="password" name="password_confirm" required autocomplete="new-password">
      </label>
    </div>
    <button type="submit" class="btn btn-outline mt-4">Đặt lại mật khẩu</button>
  </section>
</form>
<?php endif; ?>
