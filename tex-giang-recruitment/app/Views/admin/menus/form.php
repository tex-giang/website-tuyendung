<?php
$page['ajax_fragment'] = true;
$isEdit = $menu !== null;
$action = $isEdit ? url('admin/menus/' . (int) $menu['id'] . '/edit') : url('admin/menus/create');
$backUrl = url('admin/menus');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
?>
<div class="admin-compact-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <a href="<?= $backUrl ?>" class="admin-editor-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách menu</a>
      <h1 class="admin-h1"><?= $isEdit ? 'Sửa menu' : 'Thêm menu' ?></h1>
      <p class="admin-sub">Mục menu hiển thị chung ở đầu trang và chân trang website.</p>
    </div>
  </div>
  <?php if ($errors !== []): ?>
  <div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
  <?php endif; ?>
  <form method="POST" action="<?= $action ?>" class="admin-form admin-cms-card admin-cms-fields admin-compact-card" data-ajax>
    <?= csrf_field() ?>
    <label class="field">
      <span class="field__label">Tên hiển thị <em>*</em></span>
      <input class="field__input<?= $err('label') ? ' is-invalid' : '' ?>" name="label" value="<?= e($val('label')) ?>" maxlength="120" required placeholder="Ví dụ: Tuyển dụng">
      <?php if ($err('label')): ?><span class="field__error"><?= e($err('label')) ?></span><?php endif; ?>
    </label>
    <label class="field">
      <span class="field__label">Đường dẫn</span>
      <input class="field__input<?= $err('url') ? ' is-invalid' : '' ?>" name="url" value="<?= e($val('url')) ?>" maxlength="255" placeholder="/jobs">
      <span class="admin-muted">Ví dụ: /jobs hoặc https://texgiang.vn. Để trống để trỏ về trang chủ.</span>
      <?php if ($err('url')): ?><span class="field__error"><?= e($err('url')) ?></span><?php endif; ?>
    </label>
    <label class="field">
      <span class="field__label">Thứ tự hiển thị</span>
      <input class="field__input admin-editor-order" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>">
      <span class="admin-muted">Số nhỏ hiển thị trước. Khi thêm mới, giá trị 0 đặt mục này cuối danh sách.</span>
    </label>
    <label class="admin-cms-visibility admin-editor-visibility">
      <span>Hiển thị trên website<span class="admin-muted">Tắt để ẩn mục này.</span></span>
      <input class="admin-cms-switch" type="checkbox" role="switch" name="is_active" value="1"<?= $val('is_active', '1') === '1' ? ' checked' : '' ?>>
    </label>
    <div class="admin-cms-actions admin-compact-actions">
      <a href="<?= $backUrl ?>" class="btn btn-outline">Hủy</a>
      <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm menu' ?></button>
    </div>
  </form>
</div>
