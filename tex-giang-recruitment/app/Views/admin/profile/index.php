<?php
$page['ajax_fragment'] = true;
$role = $roles[$user['role_name']] ?? $user['role_name'];
$profileFields = [
    'Tên đăng nhập' => $user['username'],
    'Họ và tên' => $user['full_name'],
    'Email' => $user['email'],
    'Số điện thoại' => $user['phone'],
    'Vai trò' => $role,
    'Đăng nhập gần nhất' => vn_date($user['last_login_at'], 'H:i d/m/Y') ?: '—',
];
?>
<div class="admin-content-editor admin-profile-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <h1 class="admin-h1">Hồ sơ cá nhân</h1>
      <p class="admin-sub">Thông tin tài khoản và quyền truy cập hệ thống quản trị.</p>
    </div>
    <form method="POST" action="<?= url('admin/logout') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Đăng xuất</button>
    </form>
  </div>

  <div class="admin-cms-form">
    <section class="admin-cms-card admin-cms-fields" aria-label="Thông tin tài khoản">
      <dl class="admin-information admin-information--profile">
        <?php foreach ($profileFields as $label => $value): ?>
        <div><dt><?= e($label) ?></dt><dd><?= e($value ?: '—') ?></dd></div>
        <?php endforeach; ?>
      </dl>
    </section>

    <section class="admin-cms-card admin-cms-fields" id="doi-mat-khau">
      <header class="admin-cms-section-intro">
        <h2>Đổi mật khẩu</h2>
        <p>Mật khẩu mới có ít nhất 8 ký tự và khác mật khẩu hiện tại.</p>
      </header>
      <form class="admin-cms-fields" data-ajax method="POST" action="<?= url('admin/profile/password') ?>">
        <?= csrf_field() ?>
        <div class="field-grid admin-cms-fields">
          <?php foreach (['current_password' => 'Mật khẩu hiện tại', 'password' => 'Mật khẩu mới', 'password_confirm' => 'Nhập lại mật khẩu mới'] as $name => $label): ?>
          <label class="field<?= $name === 'current_password' ? ' field--span2' : '' ?>">
            <span class="field__label"><?= e($label) ?> <em>*</em></span>
            <input class="field__input<?= !empty($errors[$name]) ? ' is-invalid' : '' ?>" type="password" name="<?= e($name) ?>"
                   autocomplete="<?= $name === 'current_password' ? 'current-password' : 'new-password' ?>"<?= $name !== 'current_password' ? ' minlength="8"' : '' ?> required>
            <?php if (!empty($errors[$name])): ?><span class="field__error"><?= e($errors[$name]) ?></span><?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="admin-cms-actions">
          <button type="submit" class="btn btn-primary"><i class="bi bi-key" aria-hidden="true"></i> Đổi mật khẩu</button>
        </div>
      </form>
    </section>
  </div>
</div>
