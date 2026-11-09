<?php
$loginError = errors('login');
$usernameError = errors('username');
$passwordError = errors('password');
?>
<form class="auth-card" method="POST" action="<?= url('admin/login') ?>" autocomplete="on">
  <?= csrf_field() ?>
  <a href="<?= url('') ?>" class="auth-brand">
    <img src="<?= asset('images/logo-tgi-mark.webp') ?>" alt="TEX-GIANG" width="88" height="58">
  </a>
  <h1 class="auth-title">Đăng nhập quản trị</h1>

  <?php if ($loginError): ?>
  <div class="admin-alert admin-alert--error" role="alert"><?= e($loginError) ?></div>
  <?php endif; ?>

  <label class="field">
    <span class="field__label">Tên đăng nhập</span>
    <input class="field__input<?= $usernameError ? ' is-invalid' : '' ?>" type="text" name="username"
           value="<?= e(old('username')) ?>" autocomplete="username" required autofocus>
    <?php if ($usernameError): ?><span class="field__error"><?= e($usernameError) ?></span><?php endif; ?>
  </label>

  <label class="field">
    <span class="field__label">Mật khẩu</span>
    <input class="field__input<?= $passwordError ? ' is-invalid' : '' ?>" type="password" name="password"
           autocomplete="current-password" required>
    <?php if ($passwordError): ?><span class="field__error"><?= e($passwordError) ?></span><?php endif; ?>
  </label>

  <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
</form>
