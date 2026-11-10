<div class="field admin-cms-upload" data-admin-upload>
  <span class="field__label" id="upload-label-<?= e($uploadName) ?>"><?= e($uploadLabel) ?></span>
  <label class="admin-cms-upload__drop">
    <input class="admin-cms-upload__input" type="file" id="upload-input-<?= e($uploadName) ?>" name="<?= e($uploadName) ?>" accept=".jpg,.jpeg,.png,.webp"
           aria-labelledby="upload-label-<?= e($uploadName) ?>" aria-describedby="upload-hint-<?= e($uploadName) ?>">
    <img data-upload-preview data-original="<?= e($uploadImage) ?>"<?php if ($uploadImage !== ''): ?> src="<?= e($uploadImage) ?>"<?php else: ?> hidden<?php endif; ?> alt="">
    <span data-upload-placeholder<?= $uploadImage !== '' ? ' hidden' : '' ?>><i class="bi bi-image" aria-hidden="true"></i> Kéo thả hoặc bấm để chọn ảnh</span>
  </label>
  <label class="btn btn-outline btn-sm admin-cms-upload__button" for="upload-input-<?= e($uploadName) ?>"><i class="bi bi-upload" aria-hidden="true"></i> Chọn ảnh</label>
  <p class="admin-muted" id="upload-hint-<?= e($uploadName) ?>">JPG, PNG hoặc WEBP, tối đa 5 MB.</p>
  <p class="admin-muted admin-cms-upload__filename" data-upload-filename aria-live="polite" hidden></p>
  <?php if ($uploadError !== ''): ?><span class="field__error"><?= e($uploadError) ?></span><?php endif; ?>
</div>
