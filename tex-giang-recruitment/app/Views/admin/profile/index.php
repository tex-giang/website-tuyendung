<?php
/** Hồ sơ cá nhân: thông tin tài khoản + đổi mật khẩu. */
$user = $user;
$errors = $errors ?? [];
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$roleLabels = $roles ?? [];
$flashSuccess = \App\Core\Session::pull('_success');
$flashError = \App\Core\Session::pull('_error');
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Hồ sơ cá nhân</h1>
  </div>
</div>

<?php if ($flashSuccess): ?><div class="admin-alert admin-alert--success" role="status"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="admin-alert admin-alert--error" role="alert"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="admin-grid-2">
  <section class="admin-card">
    <h2 class="admin-h2">Thông tin tài khoản</h2>
    <table class="admin-table admin-table--kv">
      <tbody>
        <tr><th>Tên đăng nhập</th><td class="admin-mono"><?= e($user['username']) ?></td></tr>
        <tr><th>Họ và tên</th><td><?= e($user['full_name']) ?></td></tr>
        <tr><th>Vai trò</th><td><?= e($roleLabels[$user['role_name']] ?? $user['role_name']) ?></td></tr>
        <tr><th>Email</th><td><?= e($user['email'] ?? '—') ?></td></tr>
        <tr><th>Điện thoại</th><td><?= e($user['phone'] ?? '—') ?></td></tr>
        <tr><th>Đăng nhập gần nhất</th><td><?= e(vn_date($user['last_login_at'], 'd/m/Y H:i') ?: '—') ?></td></tr>
      </tbody>
    </table>
  </section>

  <section class="admin-card" id="doi-mat-khau">
    <h2 class="admin-h2">Đổi mật khẩu</h2>
    <form method="POST" action="<?= url('admin/profile/password') ?>">
      <?= csrf_field() ?>
      <div class="field-grid">
        <label class="field field--span2">
          <span class="field__label">Mật khẩu hiện tại</span>
          <input class="field__input<?= $err('current_password') ? ' is-invalid' : '' ?>" type="password"
                 name="current_password" required autocomplete="current-password">
          <?php if ($err('current_password')): ?><span class="field__error"><?= e($err('current_password')) ?></span><?php endif; ?>
        </label>
        <label class="field">
          <span class="field__label">Mật khẩu mới</span>
          <input class="field__input<?= $err('password') ? ' is-invalid' : '' ?>" type="password"
                 name="password" required autocomplete="new-password">
          <?php if ($err('password')): ?><span class="field__error"><?= e($err('password')) ?></span><?php endif; ?>
        </label>
        <label class="field">
          <span class="field__label">Nhập lại mật khẩu mới</span>
          <input class="field__input<?= $err('password_confirm') ? ' is-invalid' : '' ?>" type="password"
                 name="password_confirm" required autocomplete="new-password">
          <?php if ($err('password_confirm')): ?><span class="field__error"><?= e($err('password_confirm')) ?></span><?php endif; ?>
        </label>
      </div>
      <button type="submit" class="btn btn-primary mt-4"><i class="bi bi-key me-1" aria-hidden="true"></i> Đổi mật khẩu</button>
    </form>
  </section>
</div>
