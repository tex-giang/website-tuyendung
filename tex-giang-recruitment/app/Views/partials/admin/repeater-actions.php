<div class="admin-editor-row-actions">
  <button type="button" class="btn btn-ghost btn-icon" data-move="up" title="Di chuyển lên" aria-label="Di chuyển lên"><?= admin_icon_svg('arrow-up') ?></button>
  <button type="button" class="btn btn-ghost btn-icon" data-move="down" title="Di chuyển xuống" aria-label="Di chuyển xuống"><?= admin_icon_svg('arrow-down') ?></button>
  <button type="button" class="btn btn-ghost btn-icon admin-editor-delete" <?= e($removeAttribute ?? 'data-remove') ?> title="<?= e($removeLabel ?? 'Xóa dòng') ?>" aria-label="<?= e($removeLabel ?? 'Xóa dòng') ?>"><?= admin_icon_svg('trash') ?></button>
</div>
