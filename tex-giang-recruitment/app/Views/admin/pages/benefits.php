<?php
$page['ajax_fragment'] = true;
/**
 * Trang Phúc lợi — sửa TẤT CẢ trong một form repeater (kiểu Người liên hệ tuyển dụng).
 * Mỗi nhóm: tiêu đề + icon + mô tả, bên dưới là các dòng phúc lợi (thêm/xoá từng dòng).
 * Cuối trang: bảng so sánh dạng repeater. Lưu một lần bằng AJAX.
 *
 * @var array<int, array<string, mixed>> $groups
 * @var array<int, array<string, mixed>> $comparisons
 * @var array<int, string> $icons
 * @var array<string, string> $errors
 */
$val = static function (array $row, string $key, string $default = ''): string {
    return (string) ($row[$key] ?? $default);
};
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb">Nội dung website / Trang Phúc lợi</p>
    <h1 class="admin-h1">Trang Phúc lợi</h1>
  </div>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert"><?= e((string) ($errors['general'] ?? 'Có trường cần kiểm tra lại.')) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/pages/benefits') ?>" class="admin-form" data-ajax>
  <?= csrf_field() ?>

  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Nhóm phúc lợi</h2>
      <span class="admin-badge admin-badge--soft"><span data-group-count><?= count($groups) ?></span> nhóm</span>
    </div>

    <div data-group-list>
      <?php foreach ($groups as $gi => $group): ?>
      <div class="admin-group-card" data-group>
        <div class="admin-group-card__head">
          <span class="admin-badge admin-badge--soft">Nhóm <span data-group-number><?= $gi + 1 ?></span></span>
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển nhóm lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển nhóm xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-danger btn-icon" data-group-remove title="Xoá nhóm" aria-label="Xoá nhóm"><?= admin_icon_svg('trash') ?></button>
          </div>
        </div>

        <div class="field-grid">
          <label class="field">
            <span class="field__label">Tiêu đề nhóm <em>*</em></span>
            <input class="field__input" type="text" name="groups[<?= (int) $gi ?>][title]"
                   value="<?= e($val($group, 'title')) ?>" maxlength="200" placeholder="VD: Môi trường làm việc an toàn, mát">
          </label>
          <div class="field field--span2">
            <span class="field__label">Icon</span>
            <div class="icon-picker">
              <?php foreach ($icons as $icon): ?>
              <label class="icon-picker__item" title="<?= e($icon) ?>">
                <input type="radio" name="groups[<?= (int) $gi ?>][icon]" value="<?= e($icon) ?>"<?= $val($group, 'icon', 'check2') === $icon ? ' checked' : '' ?>>
                <img src="<?= asset('images/icons/' . e($icon) . '.svg') ?>" alt="" width="24" height="24">
                <span><?= e($icon) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <label class="field field--span2">
            <span class="field__label">Mô tả ngắn</span>
            <textarea class="field__input" name="groups[<?= (int) $gi ?>][intro]" rows="2" maxlength="500" placeholder="Mô tả ngắn hiển thị dưới tiêu đề nhóm"><?= e($val($group, 'intro')) ?></textarea>
          </label>
        </div>

        <div class="admin-repeater admin-repeater--items" data-items>
          <p class="admin-repeater__caption">Các dòng phúc lợi</p>
          <div class="admin-repeater__rows" data-item-rows>
            <?php foreach (($group['items'] ?? []) as $ii => $item): ?>
            <div class="admin-repeater__row" data-item-row>
              <input class="field__input" type="text" name="groups[<?= (int) $gi ?>][items][<?= (int) $ii ?>][text]"
                     value="<?= e($val($item, 'text')) ?>" maxlength="500" placeholder="VD: Cơm trưa và cơm tăng ca tại nhà máy.">
              <div class="admin-repeater__actions">
              <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển dòng lên"><?= admin_icon_svg('arrow-up') ?></button>
              <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển dòng xuống"><?= admin_icon_svg('arrow-down') ?></button>
              <button type="button" class="btn btn-ghost btn-icon" data-item-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-outline btn-sm" data-item-add><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm dòng</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <template id="group-template">
      <div class="admin-group-card" data-group>
        <div class="admin-group-card__head">
          <span class="admin-badge admin-badge--soft">Nhóm <span data-group-number></span></span>
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển nhóm lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển nhóm xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-danger btn-icon" data-group-remove title="Xoá nhóm" aria-label="Xoá nhóm"><?= admin_icon_svg('trash') ?></button>
          </div>
        </div>
        <div class="field-grid">
          <label class="field">
            <span class="field__label">Tiêu đề nhóm <em>*</em></span>
            <input class="field__input" type="text" name="groups[__G__][title]" maxlength="200" placeholder="VD: Xe đưa rước và ăn ca miễn phí">
          </label>
          <div class="field">
            <span class="field__label">Icon</span>
            <div class="icon-picker">
              <?php foreach ($icons as $icon): ?>
              <label class="icon-picker__item" title="<?= e($icon) ?>">
                <input type="radio" name="groups[__G__][icon]" value="<?= e($icon) ?>">
                <img src="<?= asset('images/icons/' . e($icon) . '.svg') ?>" alt="" width="24" height="24">
                <span><?= e($icon) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <label class="field field--span2">
            <span class="field__label">Mô tả ngắn</span>
            <textarea class="field__input" name="groups[__G__][intro]" rows="2" maxlength="500"></textarea>
          </label>
        </div>
        <div class="admin-repeater admin-repeater--items" data-items>
          <p class="admin-repeater__caption">Các dòng phúc lợi</p>
          <div class="admin-repeater__rows" data-item-rows></div>
          <button type="button" class="btn btn-outline btn-sm" data-item-add><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm dòng</button>
        </div>
      </div>
    </template>

    <template id="item-template">
      <div class="admin-repeater__row" data-item-row>
        <input class="field__input" type="text" name="groups[__G__][items][__I__][text]" maxlength="500" placeholder="Nội dung dòng phúc lợi…">
        <div class="admin-repeater__actions">
              <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển dòng lên"><?= admin_icon_svg('arrow-up') ?></button>
              <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển dòng xuống"><?= admin_icon_svg('arrow-down') ?></button>
              <button type="button" class="btn btn-ghost btn-icon" data-item-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            </div>
      </div>
    </template>

    <button type="button" class="btn btn-outline btn-sm" data-group-add><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm nhóm phúc lợi</button>
  </section>

  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Bảng so sánh</h2>
      <span class="admin-badge admin-badge--soft"><span data-count="comparisons"><?= count($comparisons) ?></span> dòng</span>
    </div>
    <div class="admin-repeater" data-repeater="comparisons">
      <div class="admin-repeater__head" aria-hidden="true">
        <span>Hạng mục</span><span>Tại TEX-GIANG</span><span>Nơi khác thường thấy</span><span></span>
      </div>
      <div class="admin-repeater__rows" data-rows>
        <?php foreach ($comparisons as $ci => $row): ?>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="comparisons[<?= (int) $ci ?>][label]" aria-label="Hạng mục so sánh"
                 value="<?= e($val($row, 'label')) ?>" maxlength="120" placeholder="VD: Thưởng tuyển mới">
          <input class="field__input" type="text" name="comparisons[<?= (int) $ci ?>][tex_giang]" aria-label="Phúc lợi tại TEX-GIANG"
                 value="<?= e($val($row, 'tex_giang')) ?>" maxlength="300" placeholder="Tại TEX-GIANG">
          <input class="field__input" type="text" name="comparisons[<?= (int) $ci ?>][other_value]" aria-label="Phúc lợi ở nơi khác"
                 value="<?= e($val($row, 'other_value')) ?>" maxlength="300" placeholder="Nơi khác">
          <div class="admin-repeater__actions">
          <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển dòng lên"><?= admin_icon_svg('arrow-up') ?></button>
          <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển dòng xuống"><?= admin_icon_svg('arrow-down') ?></button>
          <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
        </div>
        </div>
        <?php endforeach; ?>
      </div>
      <template data-template>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="comparisons[__INDEX__][label]" aria-label="Hạng mục so sánh" maxlength="120" placeholder="Hạng mục">
          <input class="field__input" type="text" name="comparisons[__INDEX__][tex_giang]" aria-label="Phúc lợi tại TEX-GIANG" maxlength="300" placeholder="Tại TEX-GIANG">
          <input class="field__input" type="text" name="comparisons[__INDEX__][other_value]" aria-label="Phúc lợi ở nơi khác" maxlength="300" placeholder="Nơi khác">
          <div class="admin-repeater__actions">
          <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển dòng lên"><?= admin_icon_svg('arrow-up') ?></button>
          <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển dòng xuống"><?= admin_icon_svg('arrow-down') ?></button>
          <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
        </div>
        </div>
      </template>
      <button type="button" class="btn btn-outline btn-sm" data-add><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm dòng so sánh</button>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> Lưu thay đổi</button>
  </div>
</form>
