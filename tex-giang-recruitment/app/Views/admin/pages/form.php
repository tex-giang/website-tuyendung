<?php
/**
 * Form thêm/sửa nội dung trang (Về chúng tôi / Phúc lợi).
 *
 * @var string $type
 * @var array<string, mixed> $config
 * @var array<string, mixed> $groupConfig
 * @var array<int, array<string, mixed>> $tabs
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var array<int, string> $icons
 * @var array<int, array<string, mixed>> $groupNames
 */
$isEdit = !empty($values['id']);
$action = $isEdit
    ? url('admin/pages/' . $type . '/' . $values['id'] . '/edit')
    : url('admin/pages/' . $type . '/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$backUrl = url('admin/pages/' . $type);
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= $backUrl ?>"><?= e($config['label']) ?></a> / <?= e($groupConfig['label']) ?></p>
    <h1 class="admin-h1"><?= $isEdit ? 'Sửa ' : 'Thêm ' ?><?= e($config['singular']) ?></h1>
  </div>
  <a href="<?= $backUrl ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Danh sách</a>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" class="admin-form" data-ajax>
  <?= csrf_field() ?>
  <section class="admin-card">
    <h2 class="admin-h2">Nội dung</h2>
    <div class="field-grid">
      <?php foreach ($config['fields'] as $name => [$label, $kind, $max, $required]): ?>
        <?php if ($kind === 'checkbox'): ?>
          <label class="field field--checkbox">
            <input type="checkbox" name="<?= e($name) ?>" value="1"<?= (int) $val($name, '0') === 1 ? ' checked' : '' ?>>
            <span><?= e($label) ?></span>
          </label>
        <?php elseif ($kind === 'textarea'): ?>
          <label class="field field--span2">
            <span class="field__label"><?= e($label) ?> <?php if ($required): ?><em>*</em><?php endif; ?></span>
            <textarea class="field__input<?= $err($name) ? ' is-invalid' : '' ?>" name="<?= e($name) ?>" rows="3"<?= $max > 0 ? ' maxlength="' . (int) $max . '"' : '' ?>><?= e($val($name)) ?></textarea>
            <?php if ($err($name)): ?><span class="field__error"><?= e($err($name)) ?></span><?php endif; ?>
          </label>
        <?php elseif ($kind === 'select'): ?>
          <label class="field">
            <span class="field__label"><?= e($label) ?> <?php if ($required): ?><em>*</em><?php endif; ?></span>
            <select class="field__input<?= $err($name) ? ' is-invalid' : '' ?>" name="<?= e($name) ?>">
              <?php foreach ($icons as $icon): ?>
              <option value="<?= e($icon) ?>"<?= $val($name, 'check2') === $icon ? ' selected' : '' ?>><?= e($icon) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($err($name)): ?><span class="field__error"><?= e($err($name)) ?></span><?php endif; ?>
          </label>
        <?php elseif ($kind === 'select-group'): ?>
          <label class="field">
            <span class="field__label"><?= e($label) ?> <?php if ($required): ?><em>*</em><?php endif; ?></span>
            <select class="field__input<?= $err($name) ? ' is-invalid' : '' ?>" name="<?= e($name) ?>">
              <option value="">— Chọn nhóm —</option>
              <?php foreach ($groupNames as $group): ?>
              <option value="<?= (int) $group['id'] ?>"<?= (int) $val($name, '0') === (int) $group['id'] ? ' selected' : '' ?>><?= e($group['title']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($err($name)): ?><span class="field__error"><?= e($err($name)) ?></span><?php endif; ?>
          </label>
        <?php else: ?>
          <label class="field<?= $kind === 'number' ? '' : '' ?>">
            <span class="field__label"><?= e($label) ?> <?php if ($required): ?><em>*</em><?php endif; ?></span>
            <input class="field__input<?= $err($name) ? ' is-invalid' : '' ?>" type="<?= $kind === 'number' ? 'number' : 'text' ?>" name="<?= e($name) ?>"
                   value="<?= e($val($name, $kind === 'number' ? '0' : '')) ?>"<?= $max > 0 && $kind !== 'number' ? ' maxlength="' . (int) $max . '"' : '' ?><?= $required ? ' required' : '' ?>>
            <?php if ($err($name)): ?><span class="field__error"><?= e($err($name)) ?></span><?php endif; ?>
          </label>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($type === 'benefit-group'): ?>
  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Các dòng phúc lợi</h2>
      <span class="admin-badge admin-badge--soft"><span data-item-count><?= count($items) ?></span> dòng</span>
    </div>
    <div class="admin-repeater admin-repeater--items" data-item-repeater>
      <div class="admin-repeater__rows" data-item-rows>
        <?php foreach ($items as $index => $item): ?>
        <div class="admin-repeater__row" data-item-row>
          <input type="hidden" name="items[<?= (int) $index ?>][id]" value="<?= (int) $item['id'] ?>">
          <input class="field__input" type="text" name="items[<?= (int) $index ?>][text]"
                 value="<?= e((string) $item['text']) ?>" maxlength="500" placeholder="VD: Cơm trưa và cơm tăng ca tại nhà máy.">
          <button type="button" class="btn btn-ghost btn-icon" data-item-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
        </div>
        <?php endforeach; ?>
      </div>
      <template id="item-row-template">
        <div class="admin-repeater__row" data-item-row>
          <input type="hidden" name="items[__INDEX__][id]" value="">
          <input class="field__input" type="text" name="items[__INDEX__][text]" maxlength="500" placeholder="Nội dung dòng phúc lợi…">
          <button type="button" class="btn btn-ghost btn-icon" data-item-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
        </div>
      </template>
      <button type="button" class="btn btn-outline btn-sm" data-item-add><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm dòng</button>
    </div>
  </section>
  <?php endif; ?>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Thêm ' . e($config['singular']) ?></button>
    <a href="<?= $backUrl ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>

<script>
(function () {
  var repeater = document.querySelector('[data-item-repeater]');
  if (!repeater) return;
  var rows = repeater.querySelector('[data-item-rows]');
  var template = document.getElementById('item-row-template');
  var addButton = repeater.querySelector('[data-item-add]');
  var counter = repeater.closest('.admin-card').querySelector('[data-item-count]');
  var nextIndex = rows.querySelectorAll('[data-item-row]').length;

  function refreshCount() {
    if (counter) counter.textContent = rows.querySelectorAll('[data-item-row]').length;
  }

  addButton.addEventListener('click', function () {
    var html = template.innerHTML.replace(/__INDEX__/g, nextIndex++);
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var row = wrap.firstElementChild;
    rows.appendChild(row);
    row.querySelector('input[type="text"]').focus();
    refreshCount();
  });

  repeater.addEventListener('click', function (event) {
    var remove = event.target.closest('[data-item-remove]');
    if (!remove) return;
    remove.closest('[data-item-row]').remove();
    refreshCount();
  });
})();
</script>
