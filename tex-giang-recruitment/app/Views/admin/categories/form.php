<?php
$page['ajax_fragment'] = true;
$isEdit = $record !== null;
$isBranch = $type === 'branch';
$action = url('admin/categories/' . $type . ($isEdit ? '/' . (int) $record['id'] . '/edit' : '/create'));
$backUrl = url('admin/categories') . '?' . http_build_query(['type' => $type]);
$heading = ($isEdit ? 'Sửa ' : 'Thêm ') . $config['singular'];
$val = static fn(string $key): string => (string) ($values[$key] ?? '');
$renderField = static function (string $key, string $label, int $max, string $kind = 'text', bool $wide = false, string $hint = '') use ($val, $errors): void {
    $error = (string) ($errors[$key] ?? '');
    ?>
    <label class="field<?= $wide ? ' field--span2' : '' ?>">
      <span class="field__label"><?= e($label) ?><?= $key === 'name' ? ' <em>*</em>' : '' ?></span>
      <?php if ($kind === 'textarea'): ?>
      <textarea class="field__input<?= $error !== '' ? ' is-invalid' : '' ?>" name="<?= e($key) ?>" maxlength="<?= $max ?>" rows="2"><?= e($val($key)) ?></textarea>
      <?php else: ?>
      <input class="field__input<?= $error !== '' ? ' is-invalid' : '' ?>" type="<?= e($kind) ?>" name="<?= e($key) ?>" value="<?= e($val($key)) ?>"<?= $kind !== 'number' ? ' maxlength="' . $max . '"' : ' min="-32768" max="32767"' ?><?= $key === 'name' ? ' required' : '' ?>>
      <?php endif; ?>
      <?php if ($hint !== ''): ?><span class="admin-muted"><?= e($hint) ?></span><?php endif; ?>
      <?php if ($error !== ''): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
    </label>
    <?php
};
$contactRow = static function (int|string $index, array $contact = []): void { ?>
  <div class="admin-editor-row admin-branch-contact-row" data-row>
    <input type="hidden" name="contacts[<?= $index ?>][is_active]" value="<?= isset($contact['is_active']) ? (int) $contact['is_active'] : 1 ?>">
    <label class="field">
      <span class="field__label">Họ tên</span>
      <input class="field__input" name="contacts[<?= $index ?>][full_name]" maxlength="120" placeholder="Ví dụ: Chị Trân" value="<?= e($contact['full_name'] ?? '') ?>">
    </label>
    <label class="field">
      <span class="field__label">Vai trò</span>
      <input class="field__input" name="contacts[<?= $index ?>][title]" maxlength="80" placeholder="Ví dụ: Nhân sự" value="<?= e($contact['title'] ?? '') ?>">
    </label>
    <label class="field">
      <span class="field__label">Số điện thoại</span>
      <input class="field__input" type="tel" name="contacts[<?= $index ?>][phone]" maxlength="30" placeholder="0900 000 000" value="<?= e($contact['phone'] ?? '') ?>">
    </label>
    <button class="btn btn-ghost btn-icon admin-editor-delete" type="button" data-remove aria-label="Xóa liên hệ" title="Xóa liên hệ"><?= admin_icon_svg('trash') ?></button>
  </div>
<?php };
$mapLink = (string) ($values['map_link'] ?? '');
if (!preg_match('#^https?://#i', $mapLink)) {
    $mapLink = '';
}
if ($mapLink === '' && trim($val('address')) !== '') {
    $mapLink = 'https://www.google.com/maps/search/?' . http_build_query(['api' => 1, 'query' => $val('address')]);
}
?>
<div class="admin-content-editor admin-taxonomy-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <a href="<?= e($backUrl) ?>" class="admin-editor-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh mục dùng chung</a>
      <h1 class="admin-h1"><?= e($heading) ?></h1>
      <p class="admin-sub"><?= e($config['hint']) ?></p>
    </div>
  </div>

  <?php if ($errors !== []): ?>
  <div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
  <?php endif; ?>

  <form data-ajax method="POST" action="<?= $action ?>" class="admin-form admin-cms-form">
    <?= csrf_field() ?>
    <section class="admin-cms-card admin-cms-fields">
      <h2>Thông tin chung</h2>
      <div class="field-grid admin-cms-fields">
        <?php
        $renderField('name', 'Tên', 120);
        $renderField('slug', 'Đường dẫn (slug)', 80, hint: 'Để trống để tự tạo từ tên.');
        if ($type === 'category') {
            $renderField('description', 'Thông tin mô tả', 255, 'textarea', true);
        }
        $renderField('sort_order', 'Thứ tự hiển thị', 0, 'number');
        ?>
      </div>
      <label class="admin-cms-visibility admin-editor-visibility">
        <span>Hiển thị<span class="admin-muted">Tắt để ẩn khỏi các lựa chọn trên website.</span></span>
        <input class="admin-cms-switch" type="checkbox" role="switch" name="is_active" value="1"<?= !empty($values['is_active']) ? ' checked' : '' ?>>
      </label>
    </section>

    <?php if ($isBranch): ?>
    <section class="admin-cms-card admin-cms-fields">
      <h2>Địa điểm khu sản xuất</h2>
      <div class="field-grid admin-cms-fields">
        <?php
        $renderField('address', 'Địa chỉ', 255, 'textarea', true);
        $renderField('phone', 'Số điện thoại', 30, 'tel');
        $renderField('map_url', 'Liên kết bản đồ nhúng', 500, hint: 'Dán liên kết nhúng từ Google Maps (Chia sẻ → Nhúng bản đồ).');
        $renderField('map_link', 'Liên kết mở Google Maps', 500, wide: true, hint: 'Có thể để trống để tìm theo địa chỉ.');
        ?>
      </div>
      <div>
        <a class="btn btn-outline btn-sm admin-branch-map-link" data-branch-map<?= $mapLink !== '' ? ' href="' . e($mapLink) . '"' : ' aria-disabled="true"' ?> target="_blank" rel="noopener noreferrer"><i class="bi bi-geo-alt" aria-hidden="true"></i> Mở Google Maps <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
      </div>
    </section>

    <section class="admin-cms-card admin-cms-fields" data-repeater="branch-contacts">
      <header class="admin-editor-heading">
        <div class="admin-cms-section-intro">
          <h2>Đầu mối liên hệ tuyển dụng</h2>
          <p>Người ứng viên có thể liên hệ khi quan tâm đến khu sản xuất này.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" data-add><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm liên hệ</button>
      </header>
      <?php if (!empty($errors['contacts'])): ?>
      <p class="field__error" role="alert"><?= e($errors['contacts']) ?></p>
      <?php endif; ?>
      <div class="admin-editor-stack" data-rows>
        <?php foreach (array_values($contacts) as $index => $contact) { $contactRow($index, $contact); } ?>
      </div>
      <p class="admin-muted admin-repeater-empty">Chưa có đầu mối liên hệ nào.</p>
      <template data-template><?php $contactRow('__INDEX__'); ?></template>
    </section>
    <?php endif; ?>

    <div class="admin-cms-actions">
      <a href="<?= e($backUrl) ?>" class="btn btn-outline">Hủy</a>
      <button type="submit" class="btn btn-primary"><?= e($isEdit ? 'Lưu thay đổi' : $heading) ?></button>
    </div>
  </form>
</div>
