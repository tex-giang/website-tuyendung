<?php
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
          <button type="button" class="btn btn-outline btn-sm" data-item-add>+ Thêm dòng</button>
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
          <button type="button" class="btn btn-outline btn-sm" data-item-add>+ Thêm dòng</button>
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

    <button type="button" class="btn btn-outline btn-sm" data-group-add>+ Thêm nhóm phúc lợi</button>
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
          <input class="field__input" type="text" name="comparisons[<?= (int) $ci ?>][label]"
                 value="<?= e($val($row, 'label')) ?>" maxlength="120" placeholder="VD: Thưởng tuyển mới">
          <input class="field__input" type="text" name="comparisons[<?= (int) $ci ?>][tex_giang]"
                 value="<?= e($val($row, 'tex_giang')) ?>" maxlength="300" placeholder="Tại TEX-GIANG">
          <input class="field__input" type="text" name="comparisons[<?= (int) $ci ?>][other_value]"
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
          <input class="field__input" type="text" name="comparisons[__INDEX__][label]" maxlength="120" placeholder="Hạng mục">
          <input class="field__input" type="text" name="comparisons[__INDEX__][tex_giang]" maxlength="300" placeholder="Tại TEX-GIANG">
          <input class="field__input" type="text" name="comparisons[__INDEX__][other_value]" maxlength="300" placeholder="Nơi khác">
          <div class="admin-repeater__actions">
          <button type="button" class="btn btn-outline btn-icon" data-move="up" title="Lên" aria-label="Di chuyển dòng lên"><?= admin_icon_svg('arrow-up') ?></button>
          <button type="button" class="btn btn-outline btn-icon" data-move="down" title="Xuống" aria-label="Di chuyển dòng xuống"><?= admin_icon_svg('arrow-down') ?></button>
          <button type="button" class="btn btn-ghost btn-icon" data-remove title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
        </div>
        </div>
      </template>
      <button type="button" class="btn btn-outline btn-sm" data-add>+ Thêm dòng so sánh</button>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
  </div>
</form>

<script>
(function () {
  var list = document.querySelector('[data-group-list]');
  var groupTemplate = document.getElementById('group-template');
  var itemTemplate = document.getElementById('item-template');
  var groupCountEl = document.querySelector('[data-group-count]');

  function renumberGroups() {
    var groups = list.querySelectorAll('[data-group]');
    groups.forEach(function (group, index) {
      var badge = group.querySelector('[data-group-number]');
      if (badge) badge.textContent = index + 1;
      // đổi lại index trong name để server nhận mảng liền mạch
      group.querySelectorAll('[name]').forEach(function (input) {
        input.name = input.name.replace(/groups\[\d+\]/, 'groups[' + index + ']');
        input.name = input.name.replace(/items\[\d+\]/, function (m) {
          return m; // giữ nguyên index item, chỉ đổi group
        });
      });
    });
    if (groupCountEl) groupCountEl.textContent = groups.length;
    return groups.length;
  }

  function addItem(group) {
    var rows = group.querySelector('[data-item-rows]');
    var groupIndex = Array.prototype.indexOf.call(list.querySelectorAll('[data-group]'), group);
    var itemIndex = rows.querySelectorAll('[data-item-row]').length;
    var html = itemTemplate.innerHTML
      .replace(/__G__/g, groupIndex)
      .replace(/__I__/g, itemIndex);
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var row = wrap.firstElementChild;
    rows.appendChild(row);
    var input = row.querySelector('input[type="text"]');
    if (input) input.focus();
  }

  // thêm nhóm
  document.querySelector('[data-group-add]').addEventListener('click', function () {
    var index = renumberGroups();
    var html = groupTemplate.innerHTML.replace(/__G__/g, index);
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var group = wrap.firstElementChild;
    list.appendChild(group);
    renumberGroups();
    var input = group.querySelector('input[type="text"]');
    if (input) input.focus();
  });

  // thêm/xoá dòng trong nhóm (uỷ quyền)
  list.addEventListener('click', function (event) {
    var addItemBtn = event.target.closest('[data-item-add]');
    if (addItemBtn) {
      addItem(addItemBtn.closest('[data-group]'));
      return;
    }
    var removeItemBtn = event.target.closest('[data-item-remove]');
    if (removeItemBtn) {
      removeItemBtn.closest('[data-item-row]').remove();
      return;
    }
    var removeGroupBtn = event.target.closest('[data-group-remove]');
    if (removeGroupBtn) {
      removeGroupBtn.closest('[data-group]').remove();
      renumberGroups();
    }
  });

  // repeater đơn giản (bảng so sánh)
  document.querySelectorAll('[data-repeater]').forEach(function (repeater) {
    var rows = repeater.querySelector('[data-rows]');
    var template = repeater.querySelector('[data-template]');
    var addButton = repeater.querySelector('[data-add]');
    var counter = repeater.closest('.admin-card').querySelector('[data-count]');

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
      var input = row.querySelector('input[type="text"]');
      if (input) input.focus();
      refresh();
    });

    addButton.closest('.admin-repeater').addEventListener('click', function (event) {
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
  var list = document.querySelector('[data-group-list]');
  if (!list) return;

  function reindexGroups() {
    list.querySelectorAll('[data-group]').forEach(function (group, gi) {
      group.querySelectorAll('[name]').forEach(function (input) {
        input.name = input.name.replace(/groups\[\d+\]/, 'groups[' + gi + ']');
      });
      reindexItems(group, gi);
    });
  }
  function reindexItems(group, gi) {
    group.querySelectorAll('[data-item-row]').forEach(function (row, ii) {
      row.querySelectorAll('[name]').forEach(function (input) {
        input.name = input.name.replace(/groups\[\d+\]/, 'groups[' + gi + ']').replace(/items\[\d+\]/, 'items[' + ii + ']');
      });
    });
  }

  document.addEventListener('click', function (event) {
    var move = event.target.closest('[data-move]');
    if (!move) return;
    var itemRow = move.closest('[data-item-row]');
    if (itemRow) {
      var rows = itemRow.parentNode;
      if (move.getAttribute('data-move') === 'up' && itemRow.previousElementSibling) {
        rows.insertBefore(itemRow, itemRow.previousElementSibling);
      } else if (move.getAttribute('data-move') === 'down' && itemRow.nextElementSibling) {
        rows.insertBefore(itemRow.nextElementSibling, itemRow);
      } else { return; }
      var group = itemRow.closest('[data-group]');
      var gi = Array.prototype.indexOf.call(list.querySelectorAll('[data-group]'), group);
      reindexItems(group, gi);
      return;
    }

    var cmpRow = move.closest('[data-row]');
    if (cmpRow) {
      var container = cmpRow.parentNode;
      var repeater = cmpRow.closest('[data-repeater]');
      if (move.getAttribute('data-move') === 'up' && cmpRow.previousElementSibling) {
        container.insertBefore(cmpRow, cmpRow.previousElementSibling);
      } else if (move.getAttribute('data-move') === 'down' && cmpRow.nextElementSibling) {
        container.insertBefore(cmpRow.nextElementSibling, cmpRow);
      } else { return; }
      var prefix = repeater.getAttribute('data-repeater');
      container.querySelectorAll('[data-row]').forEach(function (row, index) {
        row.querySelectorAll('[name]').forEach(function (input) {
          input.name = input.name.replace(new RegExp('^' + prefix + '\\[\\d+\\]'), prefix + '[' + index + ']');
        });
      });
      return;
    }

    var groupCard = move.closest('[data-group]');
    if (groupCard) {
      if (move.getAttribute('data-move') === 'up' && groupCard.previousElementSibling) {
        list.insertBefore(groupCard, groupCard.previousElementSibling);
      } else if (move.getAttribute('data-move') === 'down' && groupCard.nextElementSibling) {
        list.insertBefore(groupCard.nextElementSibling, groupCard);
      } else { return; }
      reindexGroups();
    }
  });
})();
</script>
