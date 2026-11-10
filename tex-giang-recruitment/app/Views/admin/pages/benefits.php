<?php
$page['ajax_fragment'] = true;
$partials = BASE_PATH . '/app/Views/partials/admin/';
$iconLabels = [
    'shield-check' => 'Bảo hiểm', 'check2' => 'Dấu kiểm', 'briefcase' => 'Công việc',
    'bus-front' => 'Xe đưa rước', 'buildings' => 'Nhà máy', 'geo-alt' => 'Địa điểm',
    'telephone' => 'Điện thoại', 'envelope' => 'Email', 'file-earmark-text' => 'Hồ sơ',
    'send' => 'Gửi hồ sơ', 'list' => 'Danh sách', 'sliders' => 'Tùy chỉnh',
];
$itemRow = static function (int|string $gi, int|string $ii, array $item = []) use ($partials): void { ?>
  <div class="admin-editor-row" data-item-row>
    <?php if (isset($item['is_active']) && !(int) $item['is_active']): ?><input type="hidden" name="groups[<?= $gi ?>][items][<?= $ii ?>][is_active]" value="0"><?php endif; ?>
    <input class="field__input" name="groups[<?= $gi ?>][items][<?= $ii ?>][text]" value="<?= e($item['text'] ?? '') ?>" maxlength="500" aria-label="Nội dung phúc lợi" placeholder="Nội dung dòng phúc lợi">
    <?php $removeAttribute = 'data-item-remove'; require $partials . 'repeater-actions.php'; ?>
  </div>
<?php };
$groupCard = static function (int|string $gi, array $group = []) use ($partials, $itemRow, $icons, $iconLabels): void {
    $title = (string) ($group['title'] ?? '');
    $selected = in_array($group['icon'] ?? '', $icons, true) ? $group['icon'] : 'check2';
    ?>
    <section class="admin-benefit-group admin-cms-fields" data-group>
      <?php if (isset($group['is_active']) && !(int) $group['is_active']): ?><input type="hidden" name="groups[<?= $gi ?>][is_active]" value="0"><?php endif; ?>
      <header class="admin-editor-heading">
        <h3 class="admin-benefit-group-title">
          <span class="admin-benefit-icon"><img src="<?= asset('images/icons/' . $selected . '.svg') ?>" data-benefit-icon-preview alt="" width="18" height="18"></span>
          <span>Nhóm <span data-group-number><?= is_int($gi) ? $gi + 1 : '' ?></span><span data-group-title><?= $title !== '' ? ' · ' . e($title) : '' ?></span></span>
        </h3>
        <?php $removeAttribute = 'data-group-remove'; $removeLabel = 'Xóa nhóm'; require $partials . 'repeater-actions.php'; ?>
      </header>
      <div class="admin-benefit-group-fields">
        <label class="field">
          <span class="field__label">Tiêu đề nhóm <em>*</em></span>
          <input class="field__input" name="groups[<?= $gi ?>][title]" value="<?= e($title) ?>" maxlength="200" placeholder="Tên nhóm phúc lợi" data-benefit-title required>
        </label>
        <label class="field">
          <span class="field__label">Biểu tượng</span>
          <select class="field__input admin-benefit-icon-select" name="groups[<?= $gi ?>][icon]" data-benefit-icon>
            <?php foreach ($icons as $icon): ?>
            <option value="<?= e($icon) ?>" data-preview="<?= asset('images/icons/' . $icon . '.svg') ?>"<?= $icon === $selected ? ' selected' : '' ?>><?= e($iconLabels[$icon] ?? $icon) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field admin-benefit-intro">
          <span class="field__label">Mô tả</span>
          <textarea class="field__input" name="groups[<?= $gi ?>][intro]" rows="2" maxlength="500"><?= e($group['intro'] ?? '') ?></textarea>
        </label>
      </div>
      <div class="admin-editor-stack">
        <div class="admin-editor-heading">
          <h4>Các dòng chi tiết</h4>
          <button type="button" class="btn btn-outline btn-sm" data-item-add><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm dòng</button>
        </div>
        <div class="admin-editor-stack" data-item-rows>
          <?php foreach (array_values($group['items'] ?? []) as $ii => $item) { $itemRow($gi, $ii, $item); } ?>
        </div>
      </div>
    </section>
<?php };
$comparisonRow = static function (int|string $index, array $row = []) use ($partials): void { ?>
  <div class="admin-editor-row admin-comparison-row" data-row>
    <?php if (isset($row['is_active']) && !(int) $row['is_active']): ?><input type="hidden" name="comparisons[<?= $index ?>][is_active]" value="0"><?php endif; ?>
    <?php foreach (['label' => ['Hạng mục', 120], 'tex_giang' => ['Tại TEX-GIANG', 300], 'other_value' => ['Nơi khác thường thấy', 300]] as $key => [$label, $max]): ?>
    <label class="field">
      <span class="admin-comparison-mobile-label field__label"><?= e($label) ?></span>
      <input class="field__input" name="comparisons[<?= $index ?>][<?= $key ?>]" value="<?= e($row[$key] ?? '') ?>" aria-label="<?= e($label) ?>" maxlength="<?= $max ?>" placeholder="<?= e($label) ?>">
    </label>
    <?php endforeach; ?>
    <?php require $partials . 'repeater-actions.php'; ?>
  </div>
<?php }; ?>
<div class="admin-content-editor admin-page-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <h1 class="admin-h1">Phúc lợi</h1>
      <p class="admin-sub">Chỉnh sửa nhóm phúc lợi và bảng so sánh chế độ.</p>
    </div>
  </div>
  <?php if ($errors !== []): ?>
  <div class="admin-alert admin-alert--error" role="alert"><?= e($errors['general'] ?? 'Có trường cần kiểm tra lại.') ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= url('admin/pages/benefits') ?>" class="admin-form admin-cms-form" data-ajax>
    <?= csrf_field() ?>
    <section class="admin-cms-card admin-cms-fields">
      <header class="admin-editor-heading">
        <div class="admin-cms-section-intro">
          <h2>Nhóm phúc lợi</h2>
          <p>Mỗi nhóm gồm biểu tượng, mô tả và các dòng chi tiết.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" data-group-add><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm nhóm</button>
      </header>
      <div class="admin-cms-fields" data-group-list>
        <?php foreach (array_values($groups) as $gi => $group) { $groupCard($gi, $group); } ?>
      </div>
      <template id="group-template"><?php $groupCard('__G__'); ?></template>
      <template id="item-template"><?php $itemRow('__G__', '__I__'); ?></template>
    </section>

    <section class="admin-cms-card admin-cms-fields" data-repeater="comparisons">
      <header class="admin-editor-heading">
        <div class="admin-cms-section-intro">
          <h2>Bảng so sánh</h2>
          <p>Đối chiếu từng chế độ tại TEX-GIANG với chế độ thường thấy ở nơi khác.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" data-add><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm dòng</button>
      </header>
      <div class="admin-comparison">
        <div class="admin-comparison-row admin-comparison-head" aria-hidden="true">
          <span>Hạng mục</span><span>Tại TEX-GIANG</span><span>Nơi khác thường thấy</span><span></span>
        </div>
        <div data-rows>
          <?php foreach (array_values($comparisons) as $index => $row) { $comparisonRow($index, $row); } ?>
        </div>
      </div>
      <template data-template><?php $comparisonRow('__INDEX__'); ?></template>
    </section>

    <input type="hidden" name="_benefits_complete" value="1">
    <?php require $partials . 'editor-save.php'; ?>
  </form>
</div>
