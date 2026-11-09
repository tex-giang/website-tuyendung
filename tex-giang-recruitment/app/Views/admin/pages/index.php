<?php
/**
 * Danh sách nội dung trang (Về chúng tôi / Phúc lợi) — dùng chung cho 6 loại.
 *
 * @var string $type
 * @var array<string, mixed> $config
 * @var array<string, mixed> $groupConfig
 * @var array<int, array<string, mixed>> $tabs
 * @var array<int, array<string, mixed>> $rows
 * @var array<int, array<string, mixed>> $groupNames
 */
$activeTab = '';
foreach ($tabs as $tab) {
    if ($tab['type'] === $type) {
        $activeTab = $tab['label'];
    }
}
$groupNameById = [];
foreach ($groupNames as $group) {
    $groupNameById[(int) $group['id']] = (string) $group['title'];
}
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb">Nội dung website / <?= e($groupConfig['label']) ?></p>
    <h1 class="admin-h1"><?= e($config['label']) ?></h1>
  </div>
  <a href="<?= url('admin/pages/' . $type . '/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm <?= e($config['singular']) ?></a>
</div>


<?php if (!empty($quickFields)): ?>
<section class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-h2">Thêm nhanh</h2>
    <span class="admin-muted">Nhập nhanh một dòng — lưu bằng AJAX, không rời trang.</span>
  </div>
  <form method="POST" action="<?= url('admin/pages/' . $type . '/create') ?>" data-ajax data-ajax-reload>
    <?= csrf_field() ?>
    <input type="hidden" name="is_active" value="1">
    <input type="hidden" name="sort_order" value="0">
    <div class="field-grid">
      <?php foreach ($quickFields as $name => $label): ?>
        <?php if ($name === 'icon'): ?>
        <label class="field">
          <span class="field__label"><?= e($label) ?></span>
          <select class="field__input" name="icon">
            <?php foreach ($icons as $icon): ?><option value="<?= e($icon) ?>"><?= e($icon) ?></option><?php endforeach; ?>
          </select>
        </label>
        <?php elseif (in_array($name, ['body', 'intro'], true)): ?>
        <label class="field field--span2">
          <span class="field__label"><?= e($label) ?></span>
          <textarea class="field__input" name="<?= e($name) ?>" rows="1" maxlength="800"></textarea>
        </label>
        <?php else: ?>
        <label class="field">
          <span class="field__label"><?= e($label) ?> <em>*</em></span>
          <input class="field__input" type="text" name="<?= e($name) ?>" maxlength="500" required>
        </label>
        <?php endif; ?>
      <?php endforeach; ?>
      <div class="field admin-quickadd__actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm nhanh</button>
      </div>
    </div>
  </form>
</section>
<?php endif; ?>

<nav class="admin-tabbar" aria-label="Loại nội dung của trang">
  <?php foreach ($tabs as $tab): ?>
  <a href="<?= $tab['url'] ?>" class="btn btn-sm <?= $tab['type'] === $type ? 'btn-primary' : 'btn-outline' ?>"><?= e($tab['label']) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($rows === []): ?>
<section class="admin-card">
  <p class="admin-empty">Chưa có <?= e($config['singular']) ?> nào. <a href="<?= url('admin/pages/' . $type . '/create') ?>">Thêm ngay</a>.</p>
</section>
<?php else: ?>
<section class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable" data-dt-compact>
      <thead>
        <tr>
          <?php foreach ($config['columns'] as $columnLabel): ?><th><?= e($columnLabel) ?></th><?php endforeach; ?>
          <th>Trạng thái</th>
          <th class="is-actions">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach (array_keys($config['columns']) as $column): ?>
          <td>
            <?php if ($column === 'is_highlight'): ?>
              <?= (int) ($row[$column] ?? 0) === 1 ? '<span class="admin-badge admin-badge--published">Đang vận hành</span>' : '<span class="admin-muted">—</span>' ?>
            <?php elseif ($column === 'group_id'): ?>
              <span class="admin-badge admin-badge--soft"><?= e($groupNameById[(int) ($row[$column] ?? 0)] ?? '—') ?></span>
            <?php elseif ($column === 'icon'): ?>
              <span class="admin-mono"><?= e((string) $row[$column]) ?></span>
            <?php elseif ($column === 'label'): ?>
              <strong><?= e(excerpt((string) $row[$column], 90)) ?></strong>
            <?php else: ?>
              <span class="<?= $column === 'text' || $column === 'body' || $column === 'intro' ? 'admin-muted' : '' ?>"><?= e(excerpt((string) $row[$column], 110)) ?></span>
            <?php endif; ?>
          </td>
          <?php endforeach; ?>
          <td>
            <span class="admin-badge <?= (int) $row['is_active'] === 1 ? 'admin-badge--published' : 'admin-badge--draft' ?>">
              <?= (int) $row['is_active'] === 1 ? 'Đang hiện' : 'Đang ẩn' ?>
            </span>
          </td>
          <td class="is-actions">
            <div class="admin-actions">
              <form method="POST" action="<?= url('admin/pages/' . $type . '/' . $row['id'] . '/toggle') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-outline btn-icon" type="submit" title="<?= (int) $row['is_active'] === 1 ? 'Ẩn' : 'Hiện' ?>" aria-label="Ẩn/Hiện"><?= (int) $row['is_active'] === 1 ? admin_icon_svg('eye-off') : admin_icon_svg('eye') ?></button>
              </form>
              <a href="<?= url('admin/pages/' . $type . '/' . $row['id'] . '/edit') ?>" class="btn btn-outline btn-icon" title="Sửa" aria-label="Sửa"><?= admin_icon_svg('pencil') ?></a>
              <form method="POST" action="<?= url('admin/pages/' . $type . '/' . $row['id'] . '/delete') ?>"
                    data-confirm="Xoá <?= e($config['singular']) ?> này khỏi trang?">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-icon" type="submit" title="Xoá" aria-label="Xoá"><?= admin_icon_svg('trash') ?></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
