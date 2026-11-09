<?php
/**
 * Trang Về chúng tôi — sửa TẤT CẢ trong một form repeater (kiểu Người liên hệ tuyển dụng).
 * Mỗi khối là danh sách dòng: thêm dòng / xoá dòng, lưu một lần bằng AJAX.
 *
 * @var array<int, array<string, mixed>> $milestones
 * @var array<int, array<string, mixed>> $values
 * @var array<int, array<string, mixed>> $certifications
 * @var array<string, string> $errors
 */
$val = static function (array $row, string $key, string $default = ''): string {
    return (string) ($row[$key] ?? $default);
};
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb">Nội dung website / Trang Về chúng tôi</p>
    <h1 class="admin-h1">Trang Về chúng tôi</h1>
  </div>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert"><?= e((string) ($errors['general'] ?? 'Có trường cần kiểm tra lại.')) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/pages/about') ?>" class="admin-form" data-ajax>
  <?= csrf_field() ?>

  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Mốc phát triển</h2>
      <span class="admin-badge admin-badge--soft"><span data-count="milestones"><?= count($milestones) ?></span> dòng</span>
    </div>
    <div class="admin-repeater" data-repeater="milestones">
      <div class="admin-repeater__head" aria-hidden="true">
        <span>Mốc</span><span>Nội dung</span><span>Đang vận hành</span><span></span>
      </div>
      <div class="admin-repeater__rows" data-rows>
        <?php foreach ($milestones as $index => $row): ?>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="milestones[<?= (int) $index ?>][year_label]"
                 value="<?= e($val($row, 'year_label')) ?>" maxlength="40" placeholder="VD: 2025">
          <input class="field__input" type="text" name="milestones[<?= (int) $index ?>][text]"
                 value="<?= e($val($row, 'text')) ?>" maxlength="500" placeholder="Nội dung mốc">
          <label class="admin-repeater__check">
            <input type="checkbox" name="milestones[<?= (int) $index ?>][is_highlight]" value="1"<?= (int) $val($row, 'is_highlight', '0') === 1 ? ' checked' : '' ?>>
          </label>
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <template data-template>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="milestones[__INDEX__][year_label]" maxlength="40" placeholder="VD: 2025">
          <input class="field__input" type="text" name="milestones[__INDEX__][text]" maxlength="500" placeholder="Nội dung mốc">
          <label class="admin-repeater__check"><input type="checkbox" name="milestones[__INDEX__][is_highlight]" value="1"></label>
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            
          </div>
        </div>
      </template>
      <button type="button" class="btn btn-outline btn-sm" data-add>+ Thêm mốc</button>
    </div>
  </section>

  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Tầm nhìn — Sứ mệnh — Giá trị cốt lõi</h2>
      <span class="admin-badge admin-badge--soft"><span data-count="values"><?= count($values) ?></span> dòng</span>
    </div>
    <div class="admin-repeater" data-repeater="values">
      <div class="admin-repeater__head" aria-hidden="true"><span>Tiêu đề</span><span>Nội dung</span><span></span></div>
      <div class="admin-repeater__rows" data-rows>
        <?php foreach ($values as $index => $row): ?>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="values[<?= (int) $index ?>][title]"
                 value="<?= e($val($row, 'title')) ?>" maxlength="120" placeholder="VD: Tầm nhìn">
          <input class="field__input" type="text" name="values[<?= (int) $index ?>][body]"
                 value="<?= e($val($row, 'body')) ?>" maxlength="800" placeholder="Nội dung">
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <template data-template>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="values[__INDEX__][title]" maxlength="120" placeholder="VD: Tầm nhìn">
          <input class="field__input" type="text" name="values[__INDEX__][body]" maxlength="800" placeholder="Nội dung">
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            
          </div>
        </div>
      </template>
      <button type="button" class="btn btn-outline btn-sm" data-add>+ Thêm dòng</button>
    </div>
  </section>

  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Chứng nhận đang áp dụng</h2>
      <span class="admin-badge admin-badge--soft"><span data-count="certifications"><?= count($certifications) ?></span> dòng</span>
    </div>
    <div class="admin-repeater" data-repeater="certifications">
      <div class="admin-repeater__head" aria-hidden="true"><span>Chứng nhận</span><span></span></div>
      <div class="admin-repeater__rows" data-rows>
        <?php foreach ($certifications as $index => $row): ?>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="certifications[<?= (int) $index ?>][label]"
                 value="<?= e($val($row, 'label')) ?>" maxlength="200" placeholder="VD: SA8000 — trách nhiệm xã hội (từ 2012)">
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <template data-template>
        <div class="admin-repeater__row" data-row>
          <input class="field__input" type="text" name="certifications[__INDEX__][label]" maxlength="200" placeholder="Tên chứng nhận">
          <div class="admin-repeater__actions">
            <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển lên"><?= admin_icon_svg('arrow-up') ?></button>
            <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển xuống"><?= admin_icon_svg('arrow-down') ?></button>
            <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
            
          </div>
        </div>
      </template>
      <button type="button" class="btn btn-outline btn-sm" data-add>+ Thêm chứng nhận</button>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
  </div>
</form>

<script>
(function () {
  document.querySelectorAll('[data-repeater]').forEach(function (repeater) {
    var card = repeater.closest('.admin-card');
    var rows = repeater.querySelector('[data-rows]');
    var template = repeater.querySelector('[data-template]');
    var addButton = repeater.querySelector('[data-add]');
    var counter = card ? card.querySelector('[data-count]') : null;

    function refresh() {
      if (counter) counter.textContent = rows.querySelectorAll('[data-row]').length;
    }

    addButton.addEventListener('click', function () {
      var index = rows.querySelectorAll('[data-row]').length;
      var html = template.innerHTML.replace(/__INDEX__/g, index);
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var row = wrap.firstElementChild;
      rows.appendChild(row);
      var first = row.querySelector('input[type="text"]');
      if (first) first.focus();
      refresh();
    });

    repeater.addEventListener('click', function (event) {
      var remove = event.target.closest('[data-remove]');
      if (!remove) return;
      remove.closest('[data-row]').remove();
      refresh();
    });
  });
})();
</script>

<script>
(function () {
  document.querySelectorAll('[data-repeater]').forEach(function (repeater) {
    var rows = repeater.querySelector('[data-rows]');
    var prefix = repeater.getAttribute('data-repeater');
    function reindex() {
      rows.querySelectorAll('[data-row]').forEach(function (row, index) {
        row.querySelectorAll('[name]').forEach(function (input) {
          input.name = input.name.replace(new RegExp('^' + prefix + '\\[\\d+\\]'), prefix + '[' + index + ']');
        });
      });
    }
    repeater.addEventListener('click', function (event) {
      var move = event.target.closest('[data-move]');
      if (move) {
        var row = move.closest('[data-row]');
        if (!row) return;
        if (move.getAttribute('data-move') === 'up' && row.previousElementSibling) {
          rows.insertBefore(row, row.previousElementSibling);
        } else if (move.getAttribute('data-move') === 'down' && row.nextElementSibling) {
          rows.insertBefore(row.nextElementSibling, row);
        }
        reindex();
      }
    });
  });
})();
</script>
