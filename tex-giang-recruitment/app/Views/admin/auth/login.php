<?php
$loginError = errors('login');
$usernameError = errors('username');
$passwordError = errors('password');
$notice = \App\Core\Session::pull('_error');
?>
<div class="auth-layout">
  <a href="<?= url('') ?>" class="auth-brand" aria-label="Trang chủ TEX-GIANG">
    <img src="<?= asset('images/logo-tgi.png') ?>" alt="" width="40" height="40">
    <span>TEX-GIANG</span>
  </a>

  <form class="auth-card admin-cms-fields" method="POST" action="<?= url('admin/login') ?>" autocomplete="on" data-ajax data-ajax-navigate>
    <?= csrf_field() ?>
    <div class="auth-heading">
      <h1 class="auth-title">Đăng nhập quản trị</h1>
      <p class="auth-sub">Truy cập hệ thống tuyển dụng TEX-GIANG.</p>
    </div>

    <?php if ($loginError): ?>
    <div class="admin-alert admin-alert--error" role="alert" data-ajax-notice><?= e($loginError) ?></div>
    <?php elseif ($notice): ?>
    <p class="auth-notice" role="status" data-ajax-notice><?= e((string) $notice) ?></p>
    <?php endif; ?>

    <div class="field">
      <label class="field__label" for="login-username">Tên đăng nhập</label>
      <input class="field__input" id="login-username" type="text" name="username"
             value="<?= e(old('username')) ?>" autocomplete="username" autocapitalize="none" spellcheck="false"
             <?= $usernameError ? 'aria-invalid="true" aria-describedby="login-username-error"' : '' ?> required>
      <?php if ($usernameError): ?><span class="field__error" id="login-username-error"><?= e($usernameError) ?></span><?php endif; ?>
    </div>

    <div class="field">
      <label class="field__label" for="login-password">Mật khẩu</label>
      <div class="auth-password">
        <input class="field__input" id="login-password" type="password" name="password"
               autocomplete="current-password" <?= $passwordError ? 'aria-invalid="true" aria-describedby="login-password-error"' : '' ?> required>
        <button type="button" class="btn btn-ghost" id="login-password-toggle" aria-label="Hiện mật khẩu" aria-controls="login-password" hidden>
          <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
      </div>
      <?php if ($passwordError): ?><span class="field__error" id="login-password-error"><?= e($passwordError) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Đăng nhập</button>
  </form>
  <p class="auth-note">Chỉ tài khoản được cấp quyền mới có thể truy cập.</p>
  <a class="auth-note admin-link" href="<?= url('') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Về website tuyển dụng</a>
</div>
<script>
  const password = document.getElementById('login-password');
  const passwordToggle = document.getElementById('login-password-toggle');
  passwordToggle.hidden = false;
  passwordToggle.addEventListener('click', () => {
    const reveal = password.type === 'password';
    password.type = reveal ? 'text' : 'password';
    passwordToggle.setAttribute('aria-label', reveal ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    passwordToggle.firstElementChild.className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
</script>
