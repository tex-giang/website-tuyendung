<?php
$page['ajax_fragment'] = true;
$partials = BASE_PATH . '/app/Views/partials/admin/';
$valueGroups = ['Tầm nhìn' => [], 'Sứ mệnh' => [], 'Giá trị cốt lõi' => []];
foreach ($values as $row) {
    $valueGroups[(string) ($row['title'] ?? '')][] = $row;
}
$milestoneRow = static function (int|string $index, array $row = []) use ($partials): void { ?>
  <div class="admin-editor-row admin-editor-row--milestone" data-row>
    <input type="hidden" name="milestones[<?= $index ?>][is_active]" value="<?= (int) ($row['is_active'] ?? 1) ?>">
    <input class="field__input" name="milestones[<?= $index ?>][year_label]" aria-label="Năm" maxlength="40" placeholder="Năm" value="<?= e($row['year_label'] ?? '') ?>">
    <input class="field__input" name="milestones[<?= $index ?>][text]" aria-label="Nội dung mốc phát triển" maxlength="500" placeholder="Nội dung mốc phát triển" value="<?= e($row['text'] ?? '') ?>">
    <label class="admin-cms-inline-check"><input type="checkbox" name="milestones[<?= $index ?>][is_highlight]" value="1"<?= !empty($row['is_highlight']) ? ' checked' : '' ?>> Hiện tại</label>
    <?php require $partials . 'repeater-actions.php'; ?>
  </div>
<?php };
$valueRow = static function (string $title, string $index, array $row = []) use ($partials): void { ?>
  <div class="admin-editor-row" data-row>
    <input type="hidden" name="values[<?= $index ?>][is_active]" value="<?= (int) ($row['is_active'] ?? 1) ?>">
    <input type="hidden" name="values[<?= $index ?>][title]" value="<?= e($title) ?>">
    <input class="field__input" name="values[<?= $index ?>][body]" aria-label="Nội dung <?= e($title) ?>" maxlength="800" placeholder="Nhập nội dung" value="<?= e($row['body'] ?? '') ?>">
    <?php require $partials . 'repeater-actions.php'; ?>
  </div>
<?php };
$certificationRow = static function (int|string $index, array $row = []) use ($partials): void { ?>
  <div class="admin-editor-row" data-row>
    <input type="hidden" name="certifications[<?= $index ?>][is_active]" value="<?= (int) ($row['is_active'] ?? 1) ?>">
    <input class="field__input" name="certifications[<?= $index ?>][label]" aria-label="Tên chứng nhận" maxlength="200" placeholder="Tên chứng nhận" value="<?= e($row['label'] ?? '') ?>">
    <?php require $partials . 'repeater-actions.php'; ?>
  </div>
<?php }; ?>
<div class="admin-content-editor admin-page-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <h1 class="admin-h1">Về chúng tôi</h1>
      <p class="admin-sub">Chỉnh sửa mốc phát triển, tầm nhìn, sứ mệnh và chứng nhận.</p>
    </div>
  </div>
  <?php if ($errors !== []): ?>
  <div class="admin-alert admin-alert--error" role="alert"><?= e($errors['general'] ?? 'Có trường cần kiểm tra lại.') ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= url('admin/pages/about') ?>" class="admin-form admin-cms-form" data-ajax>
    <?= csrf_field() ?>
    <section class="admin-cms-card admin-cms-fields" data-repeater="milestones">
      <header class="admin-editor-heading">
        <div class="admin-cms-section-intro">
          <h2>Mốc phát triển</h2>
          <p>Các cột mốc theo thứ tự hiển thị trên trang.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" data-add><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm mốc</button>
      </header>
      <div class="admin-editor-milestones" data-rows>
        <?php foreach ($milestones as $index => $row) { $milestoneRow($index, $row); } ?>
      </div>
      <template data-template><?php $milestoneRow('__INDEX__'); ?></template>
    </section>

    <section class="admin-cms-card admin-cms-fields">
      <header class="admin-cms-section-intro">
        <h2>Tầm nhìn – Sứ mệnh – Giá trị cốt lõi</h2>
        <p>Mỗi dòng là một ý hiển thị riêng trên trang.</p>
      </header>
      <?php $groupIndex = 0; foreach ($valueGroups as $title => $rows): ?>
      <div class="admin-editor-stack" data-repeater="values">
        <div class="admin-editor-heading">
          <h3><?= e($title !== '' ? $title : 'Mục chưa có tiêu đề') ?></h3>
          <button type="button" class="btn btn-outline btn-sm" data-add aria-label="Thêm dòng <?= e($title) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm dòng</button>
        </div>
        <div class="admin-editor-stack" data-rows>
          <?php foreach ($rows as $index => $row) { $valueRow($title, $groupIndex . '_' . $index, $row); } ?>
        </div>
        <template data-template><?php $valueRow($title, $groupIndex . '___INDEX__'); ?></template>
      </div>
      <?php $groupIndex++; endforeach; ?>
    </section>

    <section class="admin-cms-card admin-cms-fields" data-repeater="certifications">
      <header class="admin-cms-section-intro">
        <h2>Chứng nhận</h2>
        <p>Các chứng nhận, tiêu chuẩn công ty đã đạt được.</p>
      </header>
      <div class="admin-editor-stack">
        <div class="admin-editor-heading">
          <h3>Chứng nhận</h3>
          <button type="button" class="btn btn-outline btn-sm" data-add aria-label="Thêm dòng chứng nhận"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm dòng</button>
        </div>
        <div class="admin-editor-stack" data-rows>
          <?php foreach ($certifications as $index => $row) { $certificationRow($index, $row); } ?>
        </div>
      </div>
      <template data-template><?php $certificationRow('__INDEX__'); ?></template>
    </section>

    <?php require $partials . 'editor-save.php'; ?>
  </form>
</div>
