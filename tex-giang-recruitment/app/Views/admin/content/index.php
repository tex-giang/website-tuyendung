<?php
$page['ajax_fragment'] = true;
$errors = \App\Core\Session::pull('_errors', []);
$values = array_merge($settings, \App\Core\Session::pull('_old', []));
$renderField = static function (string $key, bool $wide = true) use ($fields, $errors, $values): void {
    [$label, $type, $max] = $fields[$key];
    $value = (string) ($values[$key] ?? '');
    $error = $errors[$key] ?? '';
    $required = in_array($key, ['content_hero_title_1', 'content_hero_title_2', 'content_about_1', 'content_about_title', 'content_why_title'], true);
    ?>
    <label class="field<?= $wide ? ' field--span2' : '' ?>">
      <span class="field__label"><?= e($label) ?><?= $required ? ' <em>*</em>' : '' ?></span>
      <?php if ($type === 'textarea'): ?>
      <textarea class="field__input<?= $error !== '' ? ' is-invalid' : '' ?>" name="<?= e($key) ?>" rows="3"
                maxlength="<?= (int) $max ?>"<?= $required ? ' required' : '' ?>><?= e($value) ?></textarea>
      <?php else: ?>
      <input class="field__input<?= $error !== '' ? ' is-invalid' : '' ?>" type="text" name="<?= e($key) ?>"
             value="<?= e($value) ?>" maxlength="<?= (int) $max ?>"<?= $required ? ' required' : '' ?>>
      <?php endif; ?>
      <?php if ($error !== ''): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
    </label>
    <?php
};
?>
<div class="admin-content-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <h1 class="admin-h1">Nội dung website</h1>
      <p class="admin-sub">Chỉnh sửa nội dung hiển thị trên website TEX-GIANG.</p>
    </div>
  </div>

  <?php if ($errors !== []): ?>
  <div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
  <?php endif; ?>

  <form data-ajax method="POST" action="<?= url('admin/content') ?>" class="admin-form admin-cms-form">
    <?= csrf_field() ?>

    <section class="admin-cms-card admin-cms-fields" aria-labelledby="content-hero-title">
      <header class="admin-cms-section-intro">
        <h2 id="content-hero-title">Hero trang chủ</h2>
        <p>Khối đầu tiên khách truy cập nhìn thấy.</p>
      </header>
      <div class="field-grid admin-cms-fields">
        <?php $renderField('content_hero_title_1', false); $renderField('content_hero_title_2', false); ?>
        <?php $renderField('content_hero_subtitle'); ?>
      </div>
    </section>

    <section class="admin-cms-card admin-cms-fields" aria-labelledby="content-intro-title">
      <header class="admin-cms-section-intro">
        <h2 id="content-intro-title">Giới thiệu và triết lý</h2>
        <p>Nội dung giới thiệu hiển thị ở trang chủ và chân trang.</p>
      </header>
      <div class="field-grid admin-cms-fields">
        <?php foreach (['content_about_1', 'content_about_2', 'content_philosophy', 'content_footer_intro'] as $key) { $renderField($key); } ?>
      </div>
    </section>

    <section class="admin-cms-card admin-cms-fields" aria-labelledby="content-stats-title">
      <header class="admin-cms-section-intro">
        <h2 id="content-stats-title">Số liệu nổi bật</h2>
        <p>Bốn cặp giá trị và nhãn thống kê hiển thị ở trang chủ.</p>
      </header>
      <div class="field-grid admin-cms-fields">
        <?php for ($index = 1; $index <= 4; $index++): ?>
        <fieldset class="admin-content-stat admin-cms-fields">
          <legend>Số liệu <?= $index ?></legend>
          <?php $renderField('stat_' . $index . '_value', false); $renderField('stat_' . $index . '_label', false); ?>
        </fieldset>
        <?php endfor; ?>
      </div>
    </section>

    <section class="admin-cms-card admin-cms-fields" aria-labelledby="content-pages-title">
      <header class="admin-cms-section-intro">
        <h2 id="content-pages-title">Tiêu đề các trang</h2>
        <p>Tiêu đề và đoạn dẫn của trang Về chúng tôi và Phúc lợi.</p>
      </header>
      <div class="field-grid admin-cms-fields">
        <?php foreach (['content_about_title', 'content_about_lead', 'content_why_title', 'content_why_lead'] as $key) { $renderField($key); } ?>
      </div>
    </section>

    <div class="admin-cms-actions admin-content-editor__actions">
      <p class="admin-muted" data-admin-content-status role="status"><?= $errors === [] ? 'Mọi thay đổi đã được lưu.' : 'Có thay đổi chưa lưu.' ?></p>
      <button type="submit" class="btn btn-primary"><i class="bi bi-floppy" aria-hidden="true"></i> Lưu thay đổi</button>
    </div>
  </form>
</div>
