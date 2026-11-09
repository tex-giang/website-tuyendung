<?php
/** Cài đặt hệ thống: thông tin chung, liên hệ, mạng xã hội, SEO. */
$errors = \App\Core\Session::pull('_errors', []);
$val = static fn(string $key): string => (string) (\App\Core\Session::pull('_old', [])[$key] ?? ($settings[$key] ?? ''));
// _old đã bị pull ở trên; nạp lại từ settings cho các field còn lại.
$values = array_merge($settings, \App\Core\Session::pull('_old', []));
$v = static fn(string $key): string => (string) ($values[$key] ?? '');
$e = static fn(string $key): string => (string) ($errors[$key] ?? '');
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Cài đặt hệ thống</h1>
  </div>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/settings') ?>" class="admin-form">
  <?= csrf_field() ?>

  <?php foreach ($groups as $groupKey => $group): ?>
  <section class="admin-card">
    <h2 class="admin-h2"><?= e($group['label']) ?></h2>
    <div class="field-grid">
      <?php foreach ($group['fields'] as $key => [$label, $type, $max]): ?>
      <label class="field <?= $type === 'textarea' ? 'field--span2' : '' ?>">
        <span class="field__label"><?= e($label) ?></span>
        <?php if ($type === 'textarea'): ?>
        <textarea class="field__input<?= $e($key) ? ' is-invalid' : '' ?>" name="<?= e($key) ?>" rows="3" maxlength="<?= (int) $max ?>"><?= e($v($key)) ?></textarea>
        <?php else: ?>
        <input class="field__input<?= $e($key) ? ' is-invalid' : '' ?>" type="<?= $type === 'email' ? 'email' : ($type === 'number' ? 'number' : 'text') ?>"
               name="<?= e($key) ?>" value="<?= e($v($key)) ?>" maxlength="<?= (int) $max ?>">
        <?php endif; ?>
        <?php if ($e($key)): ?><span class="field__error"><?= e($e($key)) ?></span><?php endif; ?>
      </label>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> Lưu cài đặt</button>
    <a href="<?= url('admin/dashboard') ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>
