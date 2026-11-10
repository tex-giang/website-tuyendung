<form method="POST" action="<?= $toggleUrl ?>" data-ajax>
  <?= csrf_field() ?>
  <button type="submit" class="admin-cms-switch-button admin-list-switch" role="switch" aria-checked="<?= $active ? 'true' : 'false' ?>" aria-label="<?= e('Hiển thị ' . $itemName) ?>">
    <span class="admin-cms-switch" aria-hidden="true"></span>
    <span class="admin-muted" aria-hidden="true"><?= $active ? 'Bật' : 'Tắt' ?></span>
  </button>
</form>
