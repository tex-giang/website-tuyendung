<div class="admin-actions admin-editor-row-actions">
  <a href="<?= $editUrl ?>" class="btn btn-ghost btn-icon" title="Sửa" aria-label="<?= e('Sửa ' . $itemName) ?>"><?= admin_icon_svg('pencil') ?></a>
  <form method="POST" action="<?= $deleteUrl ?>" data-ajax data-confirm="<?= e($deletePrompt) ?>">
    <?= csrf_field() ?>
    <button class="btn btn-ghost btn-icon admin-editor-delete" type="submit" title="Xóa" aria-label="<?= e('Xóa ' . $itemName) ?>"><?= admin_icon_svg('trash') ?></button>
  </form>
</div>
