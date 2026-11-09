<?php
/**
 * Form thêm/sửa menu website.
 *
 * @var array<string, mixed>|null $menu
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var array<string, string> $locations (không dùng)
 */
$isEdit = !empty($values['id']);
$action = $isEdit ? url('admin/menus/' . $values['id'] . '/edit') : url('admin/menus/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= url('admin/menus') ?>">Menu website</a> / <?= $isEdit ? 'Sửa menu' : 'Thêm menu' ?></p>
    <h1 class="admin-h1"><?= $isEdit ? 'Sửa menu' : 'Thêm menu' ?></h1>
  </div>
  <a href="<?= url('admin/menus') ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Danh sách</a>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" class="admin-form" data-ajax>
  <?= csrf_field() ?>
  <section class="admin-card">
    <h2 class="admin-h2">Thông tin menu</h2>
    <div class="field-grid">
      <label class="field">
        <span class="field__label">Nhãn hiển thị <em>*</em></span>
        <input class="field__input<?= $err('label') ? ' is-invalid' : '' ?>" type="text" name="label"
               value="<?= e($val('label')) ?>" maxlength="120" required placeholder="VD: Việc làm">
        <?php if ($err('label')): ?><span class="field__error"><?= e($err('label')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Đường dẫn</span>
        <input class="field__input<?= $err('url') ? ' is-invalid' : '' ?>" type="text" name="url"
               value="<?= e($val('url')) ?>" maxlength="255" placeholder="jobs, about, contact…">
        <?php if ($err('url')): ?><span class="field__error"><?= e($err('url')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Thứ tự</span>
        <input class="field__input" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>">
      </label>
      <label class="field field--checkbox">
        <input type="checkbox" name="is_active" value="1"<?= (int) $val('is_active', '1') === 1 ? ' checked' : '' ?>>
        <span>Hiển thị ngoài website</span>
      </label>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Thêm menu' ?></button>
    <a href="<?= url('admin/menus') ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>
