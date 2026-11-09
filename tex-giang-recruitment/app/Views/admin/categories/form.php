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
    <?php if ($isEdit): ?><?php endif; ?>
  </div>
  <a href="<?= $backUrl ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Danh sách</a>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form data-ajax method="POST" action="<?= $action ?>" class="admin-form">
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
        <span class="field__label">Đường dẫn (slug)</span>
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
      <label class="field field--span2">
        <span class="field__label">Link nhúng bản đồ (iframe src)</span>
        <textarea class="field__input<?= $err('map_url') ? ' is-invalid' : '' ?>" name="map_url" rows="2" maxlength="500" placeholder="https://www.google.com/maps/embed?pb=…"><?= e($val('map_url')) ?></textarea>
        <?php if ($err('map_url')): ?><span class="field__error"><?= e($err('map_url')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Link mở Google Maps</span>
        <input class="field__input<?= $err('map_link') ? ' is-invalid' : '' ?>" type="text" name="map_link"
               value="<?= e($val('map_link')) ?>" maxlength="500" placeholder="https://maps.app.goo.gl/…">
        <?php if ($err('map_link')): ?><span class="field__error"><?= e($err('map_link')) ?></span><?php endif; ?>
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

  <?php if ($type === 'branch'): ?>
  <section class="admin-card" id="branch-contacts">
    <div class="admin-card__head">
      <h2 class="admin-h2">Người liên hệ tuyển dụng</h2>
      <span class="admin-badge admin-badge--soft"><?= count($contacts) ?> người</span>
    </div>
    <?php if ($err('contacts')): ?>
    <div class="admin-alert admin-alert--error" role="alert"><?= e($err('contacts')) ?></div>
    <?php endif; ?>

    <div class="admin-repeater" data-contact-repeater>
      <div class="admin-repeater__head" aria-hidden="true">
        <span>Họ tên</span>
        <span>Số điện thoại</span>
        <span></span>
      </div>
      <div class="admin-repeater__rows" data-contact-rows>
        <?php foreach ($contacts as $index => $contact): ?>
        <div class="admin-repeater__row" data-contact-row>
          <input class="field__input" type="text" name="contacts[<?= (int) $index ?>][full_name]" aria-label="Họ tên người liên hệ"
                 value="<?= e((string) ($contact['full_name'] ?? '')) ?>" maxlength="120" placeholder="VD: Chị Trân">

          <input class="field__input" type="tel" name="contacts[<?= (int) $index ?>][phone]" aria-label="Số điện thoại người liên hệ"
                 value="<?= e((string) ($contact['phone'] ?? '')) ?>" maxlength="30" placeholder="VD: 0913 131 090">
          <button type="button" class="btn btn-ghost btn-icon" data-contact-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
        </div>
        <?php endforeach; ?>
      </div>

      <template id="contact-row-template">
        <div class="admin-repeater__row" data-contact-row>
          <input class="field__input" type="text" name="contacts[__INDEX__][full_name]" aria-label="Họ tên người liên hệ" maxlength="120" placeholder="VD: Chị Trân">

          <input class="field__input" type="tel" name="contacts[__INDEX__][phone]" aria-label="Số điện thoại người liên hệ" maxlength="30" placeholder="VD: 0913 131 090">
          <button type="button" class="btn btn-ghost btn-icon" data-contact-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
        </div>
      </template>

      <button type="button" class="btn btn-outline btn-sm" data-contact-add><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm người liên hệ</button>
    </div>
  </section>
  <?php endif; ?>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Thêm ' . e($config['singular']) ?></button>
    <a href="<?= $backUrl ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>

<?php if ($type === 'branch'): ?>
<script>
(function () {
  var root = document.querySelector('[data-contact-repeater]');
  if (!root) return;
  var rows = root.querySelector('[data-contact-rows]');
  var template = document.getElementById('contact-row-template');
  var addButton = root.querySelector('[data-contact-add]');
  var nextIndex = rows.querySelectorAll('[data-contact-row]').length + 1;

  addButton.addEventListener('click', function () {
    var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
    rows.insertAdjacentHTML('beforeend', html);
    var last = rows.lastElementChild;
    if (last) {
      last.style.animation = 'none';
      var input = last.querySelector('input');
      if (input) input.focus();
    }
  });

  rows.addEventListener('click', function (event) {
    var removeButton = event.target.closest('[data-contact-remove]');
    if (!removeButton) return;
    var row = removeButton.closest('[data-contact-row]');
    if (row) row.remove();
  });
})();
</script>
<?php endif; ?>
