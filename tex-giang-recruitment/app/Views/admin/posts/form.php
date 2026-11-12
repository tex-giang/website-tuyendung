<?php
/** Form thêm/sửa bài viết. */
$isEdit = $post !== null;
$action = $isEdit ? url('admin/posts/' . $post['id'] . '/edit') : url('admin/posts/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$currentImage = $val('image_path');
?>
<div class="admin-page-head admin-cms-form-head">
  <div>
    <a href="<?= url('admin/posts') ?>" class="admin-job-heading__back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách bài viết</a>
    <h1 class="admin-h1"><?= $isEdit ? 'Chỉnh sửa bài viết' : 'Thêm bài viết' ?></h1>
  </div>
  <div class="admin-head-actions">
    <?php if ($isEdit && $post['status'] === 'published'): ?>
    <a href="<?= url('news/' . $post['slug']) ?>" target="_blank" rel="noopener" class="btn btn-outline"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Xem trên website</a>
    <?php endif; ?>
    <a href="<?= url('admin/posts') ?>" class="btn btn-outline">Hủy</a>
    <button type="submit" form="post-editor-form" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo bài viết' ?></button>
  </div>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form id="post-editor-form" method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form admin-post-editor admin-cms-form" data-ajax="post">
  <?= csrf_field() ?>
  <section class="admin-cms-card admin-cms-fields" aria-label="Nội dung bài viết">
      <label class="field">
        <span class="field__label">Tiêu đề <em>*</em></span>
        <input class="field__input<?= $err('title') ? ' is-invalid' : '' ?>" type="text" name="title"
               value="<?= e($val('title')) ?>" maxlength="220" required>
        <?php if ($err('title')): ?><span class="field__error"><?= e($err('title')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Đường dẫn (slug)</span>
        <input class="field__input<?= $err('slug') ? ' is-invalid' : '' ?>" type="text" name="slug"
               value="<?= e($val('slug')) ?>" maxlength="220">
        <span class="admin-muted">Để trống để tạo đường dẫn từ tiêu đề.</span>
        <?php if ($err('slug')): ?><span class="field__error"><?= e($err('slug')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Mô tả ngắn</span>
        <textarea class="field__input" name="excerpt" rows="2" maxlength="400"><?= e($val('excerpt')) ?></textarea>
        <?php if ($err('excerpt')): ?><span class="field__error"><?= e($err('excerpt')) ?></span><?php endif; ?>
      </label>
      <div class="field">
        <div class="admin-post-editor__body-head">
          <label class="field__label" id="post-body-label" for="post-body">Nội dung <em>*</em></label>
          <button type="button" class="btn btn-ghost btn-sm" data-post-source aria-pressed="false" hidden>Mã HTML</button>
        </div>
        <textarea class="field__input<?= $err('body') ? ' is-invalid' : '' ?>" id="post-body" name="body" rows="10" required><?= e($val('body')) ?></textarea>
        <div data-post-rich hidden>
          <div id="post-rich-editor" data-upload-url="<?= e(url('admin/posts/upload-image') . '?responseType=json') ?>"
               data-upload-token="<?= e(csrf_token()) ?>"></div>
        </div>
        <input type="file" accept=".jpg,.jpeg,.png,.webp" data-post-inline-image hidden>
        <p class="admin-muted" role="status" data-post-editor-status hidden></p>
        <?php if ($err('body')): ?><span class="field__error"><?= e($err('body')) ?></span><?php endif; ?>
      </div>
  </section>

  <div class="admin-post-editor__side">
    <section class="admin-cms-card admin-cms-fields" aria-label="Xuất bản bài viết">
      <label class="field">
        <span class="field__label">Chuyên mục</span>
        <select class="field__input<?= $err('category_id') ? ' is-invalid' : '' ?>" name="category_id">
          <option value="">Chọn chuyên mục</option>
          <?php foreach ($categories as $category): ?>
          <option value="<?= (int) $category['id'] ?>"<?= (string) $val('category_id') === (string) $category['id'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($err('category_id')): ?><span class="field__error"><?= e($err('category_id')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Trạng thái</span>
        <select class="field__input" name="status">
          <?php foreach ($statuses as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $val('status') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span class="field__label">Ngày đăng <span class="admin-muted">(dd/mm/yyyy HH:MM)</span></span>
        <input class="field__input<?= $err('published_at') ? ' is-invalid' : '' ?>" type="text" name="published_at"
               value="<?= e($publishedAt) ?>" placeholder="28/10/2026 08:00" autocomplete="off" data-datepicker="datetime">
        <span class="admin-muted">Để trống để dùng thời điểm đăng bài.</span>
        <?php if ($err('published_at')): ?><span class="field__error"><?= e($err('published_at')) ?></span><?php endif; ?>
      </label>
    </section>

    <section class="admin-cms-card admin-cms-fields" aria-labelledby="post-image-title">
      <h2 id="post-image-title">Ảnh đại diện</h2>
      <?php
      $uploadName = 'image';
      $uploadLabel = 'Chọn ảnh đại diện';
      $uploadImage = $currentImage !== '' ? asset($currentImage) : '';
      $uploadError = $err('image');
      require BASE_PATH . '/app/Views/partials/admin/image-picker.php';
      ?>
      <?php if ($currentImage !== ''): ?>
      <label class="admin-cms-inline-check"><input type="checkbox" name="remove_image" value="1"> Gỡ ảnh hiện tại</label>
      <?php endif; ?>
      <label class="field">
        <span class="field__label">Văn bản thay thế (alt)</span>
        <input class="field__input" type="text" name="image_alt" value="<?= e($val('image_alt')) ?>" maxlength="255"
               placeholder="Ví dụ: Toàn cảnh Nhà máy Tân Hương 2">
      </label>
      <label class="field">
        <span class="field__label">Chú thích ảnh</span>
        <input class="field__input" type="text" name="image_caption" value="<?= e($val('image_caption')) ?>" maxlength="255"
               placeholder="Ví dụ: Ảnh: TEX-GIANG">
      </label>
    </section>
  </div>
</form>

<?php if ($isEdit): ?>
<form method="POST" action="<?= url('admin/posts/' . $post['id'] . '/delete') ?>"
      class="admin-form__delete" data-ajax
      data-confirm="Xoá bài &quot;<?= e($val('title')) ?>&quot;? Bài sẽ gỡ khỏi website.">
  <?= csrf_field() ?>
  <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Xoá bài viết</button>
</form>
<?php endif; ?>

<link rel="stylesheet" href="<?= asset('vendor/quill/dist/quill.snow.css') ?>">
<script src="<?= asset('vendor/quill/dist/quill.js') ?>"></script>
<script src="<?= asset('js/admin-post-editor.js') ?>"></script>
