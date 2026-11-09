<?php
/** Form thêm/sửa bài viết. */
$isEdit = $post !== null;
$action = $isEdit ? url('admin/posts/' . $post['id'] . '/edit') : url('admin/posts/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$currentImage = $val('image_path');
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= url('admin/posts') ?>">Tin tức &amp; bài viết</a> / <?= $isEdit ? 'Sửa bài' : 'Thêm bài' ?></p>
    <h1 class="admin-h1"><?= $isEdit ? 'Sửa bài viết' : 'Thêm bài viết' ?></h1>
    <?php if ($isEdit): ?><?php endif; ?>
  </div>
  <div class="admin-head-actions">
    <?php if ($isEdit && $post['status'] === 'published'): ?>
    <a href="<?= url('news/' . $post['slug']) ?>" target="_blank" rel="noopener" class="btn btn-outline">Xem trên website ↗</a>
    <?php endif; ?>
    <a href="<?= url('admin/posts') ?>" class="btn btn-ghost">← Danh sách</a>
  </div>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form" data-ajax="post">
  <?= csrf_field() ?>

  <section class="admin-card">
    <h2 class="admin-h2">Nội dung bài viết</h2>
    <div class="field-grid">
      <label class="field field--span2">
        <span class="field__label">Tiêu đề <em>*</em></span>
        <input class="field__input<?= $err('title') ? ' is-invalid' : '' ?>" type="text" name="title"
               value="<?= e($val('title')) ?>" maxlength="220" required>
        <?php if ($err('title')): ?><span class="field__error"><?= e($err('title')) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Đường dẫn (slug)</span>
        <input class="field__input<?= $err('slug') ? ' is-invalid' : '' ?>" type="text" name="slug"
               value="<?= e($val('slug')) ?>" maxlength="220">
        <?php if ($err('slug')): ?><span class="field__error"><?= e($err('slug')) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Chuyên mục</span>
        <select class="field__input<?= $err('category_id') ? ' is-invalid' : '' ?>" name="category_id">
          <option value="">— Không chọn —</option>
          <?php foreach ($categories as $category): ?>
          <option value="<?= (int) $category['id'] ?>"<?= (string) $val('category_id') === (string) $category['id'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($err('category_id')): ?><span class="field__error"><?= e($err('category_id')) ?></span><?php endif; ?>
      </label>

      <label class="field field--span2">
        <span class="field__label">Mô tả ngắn</span>
        <textarea class="field__input" name="excerpt" rows="2" maxlength="400"><?= e($val('excerpt')) ?></textarea>
        <?php if ($err('excerpt')): ?><span class="field__error"><?= e($err('excerpt')) ?></span><?php endif; ?>
      </label>

      <label class="field field--span2">
        <span class="field__label">Nội dung <em>*</em></span>
        <textarea class="field__input<?= $err('body') ? ' is-invalid' : '' ?>" name="body" rows="14" required><?= e($val('body')) ?></textarea>
        <?php if ($err('body')): ?><span class="field__error"><?= e($err('body')) ?></span><?php endif; ?>
      </label>
    </div>
  </section>

  <section class="admin-card">
    <h2 class="admin-h2">Ảnh đại diện</h2>
    <div class="field-grid">
      <div class="field">
        <span class="field__label">Chọn ảnh</span>
        <input class="field__input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
        <?php if ($err('image')): ?><span class="field__error"><?= e($err('image')) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <span class="field__label">Ảnh hiện tại</span>
        <?php if ($currentImage !== '' && $currentImage !== null): ?>
        <div class="admin-media">
          <img class="admin-thumb admin-thumb--lg" src="<?= e(asset($currentImage)) ?>" alt="">
          <label class="field--checkbox" style="grid-column:auto">
            <input type="checkbox" name="remove_image" value="1">
            <span>Gỡ ảnh này</span>
          </label>
        </div>
        <?php else: ?>
        <span class="admin-muted">Chưa có ảnh — bài viết dùng ảnh mặc định.</span>
        <?php endif; ?>
      </div>
      <label class="field">
        <span class="field__label">Mô tả ảnh (alt)</span>
        <input class="field__input" type="text" name="image_alt" value="<?= e($val('image_alt')) ?>" maxlength="255"
               placeholder="Ví dụ: Toàn cảnh Nhà máy Tân Hương 2">
      </label>
      <label class="field">
        <span class="field__label">Chú thích ảnh</span>
        <input class="field__input" type="text" name="image_caption" value="<?= e($val('image_caption')) ?>" maxlength="255"
               placeholder="Ví dụ: Ảnh: TEX-GIANG">
      </label>
    </div>
  </section>

  <section class="admin-card">
    <h2 class="admin-h2">Xuất bản</h2>
    <div class="field-grid">
      <label class="field">
        <span class="field__label">Trạng thái</span>
        <select class="field__input" name="status">
          <?php foreach ($statuses as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $val('status') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span class="field__label">Ngày đăng</span>
        <input class="field__input<?= $err('published_at') ? ' is-invalid' : '' ?>" type="datetime-local" name="published_at"
               value="<?= e($publishedAt) ?>">
        <?php if ($err('published_at')): ?><span class="field__error"><?= e($err('published_at')) ?></span><?php endif; ?>
      </label>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo bài viết' ?></button>
    <a href="<?= url('admin/posts') ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>

<?php if ($isEdit): ?>
<form method="POST" action="<?= url('admin/posts/' . $post['id'] . '/delete') ?>"
      class="admin-form__delete" data-ajax
      data-confirm="Xoá bài &quot;<?= e($val('title')) ?>&quot;? Bài sẽ gỡ khỏi website.">
  <?= csrf_field() ?>
  <button type="submit" class="btn btn-danger">Xoá bài viết</button>
</form>
<?php endif; ?>

<script src="<?= asset('vendor/ckeditor4/ckeditor.js') ?>"></script>
<script>
(function () {
  if (typeof CKEDITOR === 'undefined') return;

  // Ảnh upload qua server (lưu public/assets/uploads/posts/YYYY-MM/post-<thời-gian>-<random>.<ext>)
  var uploadUrl = <?= json_encode(url('admin/posts/upload-image') . '?token=' . csrf_token()) ?>;

  var editor = CKEDITOR.replace('body', {
    language: 'vi',
    height: 460,
    versionCheck: false,
    enterMode: CKEDITOR.ENTER_P,
    shiftEnterMode: CKEDITOR.ENTER_BR,
    filebrowserImageUploadUrl: uploadUrl,
    filebrowserUploadUrl: uploadUrl,
    removePlugins: 'flash',
  });

  // Textarea bị CKEditor ẩn đi — bỏ required để trình duyệt không chặn submit
  // (server vẫn kiểm tra nội dung tối thiểu 20 ký tự).
  if (editor) {
    editor.on('instanceReady', function () {
      var textarea = document.getElementById('body');
      if (textarea) textarea.removeAttribute('required');
    });
  }
})();
</script>