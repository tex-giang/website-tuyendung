<?php
/** Nội dung trang chủ: hero, giới thiệu, số liệu, footer. */
$errors = \App\Core\Session::pull('_errors', []);
$values = array_merge($settings, \App\Core\Session::pull('_old', []));
$v = static fn(string $key): string => (string) ($values[$key] ?? '');
$e = static fn(string $key): string => (string) ($errors[$key] ?? '');
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Nội dung website</h1>
  </div>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/content') ?>" class="admin-form">
  <?= csrf_field() ?>

  <section class="admin-card">
    <h2 class="admin-h2">Trang Về chúng tôi &amp; Phúc lợi</h2>
    <div class="field-grid">
      <?php foreach (['content_about_title', 'content_about_lead', 'content_why_title', 'content_why_lead'] as $key): ?>
        <?php [$label, $type, $max] = $fields[$key]; ?>
        <label class="field <?= $type === 'textarea' ? 'field--span2' : '' ?>">
          <span class="field__label"><?= e($label) ?></span>
          <?php if ($type === 'textarea'): ?>
          <textarea class="field__input" name="<?= e($key) ?>" rows="2" maxlength="<?= (int) $max ?>"><?= e($v($key)) ?></textarea>
          <?php else: ?>
          <input class="field__input" type="text" name="<?= e($key) ?>" value="<?= e($v($key)) ?>" maxlength="<?= (int) $max ?>">
          <?php endif; ?>
          <?php if ($e($key)): ?><span class="field__error"><?= e($e($key)) ?></span><?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="admin-card">
    <h2 class="admin-h2">Khối hero trang chủ</h2>
    <div class="field-grid">
      <?php foreach (['content_hero_title_1', 'content_hero_title_2', 'content_hero_subtitle'] as $key): ?>
        <?php [$label, $type, $max] = $fields[$key]; ?>
        <label class="field <?= $type === 'textarea' ? 'field--span2' : '' ?>">
          <span class="field__label"><?= e($label) ?></span>
          <?php if ($type === 'textarea'): ?>
          <textarea class="field__input" name="<?= e($key) ?>" rows="2" maxlength="<?= (int) $max ?>"><?= e($v($key)) ?></textarea>
          <?php else: ?>
          <input class="field__input" type="text" name="<?= e($key) ?>" value="<?= e($v($key)) ?>" maxlength="<?= (int) $max ?>">
          <?php endif; ?>
          <?php if ($e($key)): ?><span class="field__error"><?= e($e($key)) ?></span><?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="admin-card">
    <h2 class="admin-h2">Giới thiệu trên trang chủ</h2>
    <div class="field-grid">
      <?php foreach (['content_about_1', 'content_about_2', 'content_footer_intro'] as $key): ?>
        <?php [$label, $type, $max] = $fields[$key]; ?>
        <label class="field <?= $type === 'textarea' ? 'field--span2' : '' ?>">
          <span class="field__label"><?= e($label) ?></span>
          <?php if ($type === 'textarea'): ?>
          <textarea class="field__input" name="<?= e($key) ?>" rows="3" maxlength="<?= (int) $max ?>"><?= e($v($key)) ?></textarea>
          <?php else: ?>
          <input class="field__input" type="text" name="<?= e($key) ?>" value="<?= e($v($key)) ?>" maxlength="<?= (int) $max ?>">
          <?php endif; ?>
          <?php if ($e($key)): ?><span class="field__error"><?= e($e($key)) ?></span><?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="admin-card">
    <h2 class="admin-h2">Dải số liệu trang chủ</h2>
    <div class="field-grid">
      <?php for ($i = 1; $i <= 4; $i++): ?>
        <?php $keyValue = 'stat_' . $i . '_value'; $keyLabel = 'stat_' . $i . '_label'; ?>
        <label class="field">
          <span class="field__label"><?= e($fields[$keyValue][0]) ?></span>
          <input class="field__input" type="text" name="<?= e($keyValue) ?>" value="<?= e($v($keyValue)) ?>" maxlength="20">
          <?php if ($e($keyValue)): ?><span class="field__error"><?= e($e($keyValue)) ?></span><?php endif; ?>
        </label>
        <label class="field">
          <span class="field__label"><?= e($fields[$keyLabel][0]) ?></span>
          <input class="field__input" type="text" name="<?= e($keyLabel) ?>" value="<?= e($v($keyLabel)) ?>" maxlength="120">
          <?php if ($e($keyLabel)): ?><span class="field__error"><?= e($e($keyLabel)) ?></span><?php endif; ?>
        </label>
      <?php endfor; ?>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> Lưu nội dung</button>
  </div>
</form>
