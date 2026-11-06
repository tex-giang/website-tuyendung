<?php
/** Form thêm/sửa một danh mục (khu sản xuất / khối phòng ban / chuyên mục tin tức). */
$isEdit = $record !== null;
$action = $isEdit
    ? url('admin/categories/' . $type . '/' . $record['id'] . '/edit')
    : url('admin/categories/' . $type . '/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$backUrl = url('admin/categories');
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= $backUrl ?>">Danh mục</a> / <?= e($config['label']) ?></p>
    <h1 class="admin-h1"><?= $isEdit ? 'Sửa ' : 'Thêm ' ?><?= e($config['singular']) ?></h1>
    <?php if ($isEdit): ?><p class="admin-sub">slug hiện tại <code><?= e($record['slug']) ?></code></p><?php endif; ?>
  </div>
  <a href="<?= $backUrl ?>" class="btn btn-ghost">← Danh sách</a>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" class="admin-form">
  <?= csrf_field() ?>
  <section class="admin-card">
    <h2 class="admin-h2">Thông tin <?= e($config['singular']) ?></h2>
    <div class="field-grid">
      <label class="field">
        <span class="field__label">Tên <em>*</em></span>
        <input class="field__input<?= $err('name') ? ' is-invalid' : '' ?>" type="text" name="name"
               value="<?= e($val('name')) ?>" maxlength="120" required>
        <?php if ($err('name')): ?><span class="field__error"><?= e($err('name')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Đường dẫn (slug) <small>để trống tự sinh</small></span>
        <input class="field__input<?= $err('slug') ? ' is-invalid' : '' ?>" type="text" name="slug"
               value="<?= e($val('slug')) ?>" maxlength="80">
        <?php if ($err('slug')): ?><span class="field__error"><?= e($err('slug')) ?></span><?php endif; ?>
      </label>

      <?php if ($type === 'branch'): ?>
      <label class="field field--span2">
        <span class="field__label">Địa chỉ</span>
        <input class="field__input<?= $err('address') ? ' is-invalid' : '' ?>" type="text" name="address"
               value="<?= e($val('address')) ?>" maxlength="255" placeholder="Lô BII-8, đường D3, KCN Tân Hương…">
        <?php if ($err('address')): ?><span class="field__error"><?= e($err('address')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Điện thoại</span>
        <input class="field__input<?= $err('phone') ? ' is-invalid' : '' ?>" type="tel" name="phone"
               value="<?= e($val('phone')) ?>" maxlength="30">
        <?php if ($err('phone')): ?><span class="field__error"><?= e($err('phone')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Link Google Maps</span>
        <input class="field__input<?= $err('map_url') ? ' is-invalid' : '' ?>" type="text" name="map_url"
               value="<?= e($val('map_url')) ?>" maxlength="500" placeholder="https://maps.app.goo.gl/…">
        <?php if ($err('map_url')): ?><span class="field__error"><?= e($err('map_url')) ?></span><?php endif; ?>
      </label>
      <?php elseif ($type === 'category'): ?>
      <label class="field field--span2">
        <span class="field__label">Mô tả ngắn</span>
        <input class="field__input<?= $err('description') ? ' is-invalid' : '' ?>" type="text" name="description"
               value="<?= e($val('description')) ?>" maxlength="255" placeholder="Ví dụ: Công nhân may, tổ trưởng chuyền…">
        <?php if ($err('description')): ?><span class="field__error"><?= e($err('description')) ?></span><?php endif; ?>
      </label>
      <?php endif; ?>

      <label class="field">
        <span class="field__label">Thứ tự hiển thị</span>
        <input class="field__input" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>">
      </label>
      <label class="field field--checkbox">
        <input type="checkbox" name="is_active" value="1"<?= (int) $val('is_active', '1') === 1 ? ' checked' : '' ?>>
        <span>Đang bật — hiển thị trong bộ lọc và các select ngoài website</span>
      </label>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm ' . e($config['singular']) ?></button>
    <a href="<?= $backUrl ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>
