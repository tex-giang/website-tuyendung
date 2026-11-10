<div class="admin-cms-actions admin-content-editor__actions">
  <p class="admin-muted" data-admin-content-status role="status"><?= $errors === [] ? 'Mọi thay đổi đã được lưu.' : 'Có thay đổi chưa lưu.' ?></p>
  <button type="submit" class="btn btn-primary"><i class="bi bi-floppy" aria-hidden="true"></i> <?= e($saveLabel ?? 'Lưu thay đổi') ?></button>
</div>
