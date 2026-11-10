<?php
$page['ajax_fragment'] = true;
/** Form thêm/sửa banner. */
$isEdit = $banner !== null;
$action = $isEdit ? url('admin/banners/' . $banner['id'] . '/edit') : url('admin/banners/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$currentImage = $val('image_path');
?>
<div class="admin-page-head admin-cms-form-head">
  <div>
    <a href="<?= url('admin/banners') ?>" class="admin-job-heading__back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Quay lại danh sách</a>
    <h1 class="admin-h1"><?= $isEdit ? 'Chỉnh sửa banner' : 'Thêm banner' ?></h1>
    <p class="admin-sub">Nhập nội dung, hình ảnh và lịch hiển thị cho banner.</p>
  </div>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form data-ajax method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form admin-cms-form admin-banner-editor">
  <?= csrf_field() ?>

  <section class="admin-cms-card admin-cms-split" aria-labelledby="banner-form-content">
    <div class="admin-cms-section-intro">
      <h2 id="banner-form-content">Nội dung hiển thị</h2>
      <p>Tên quản trị, loại banner và nội dung khách truy cập nhìn thấy.</p>
    </div>
    <div class="field-grid admin-cms-fields">
      <label class="field field--span2">
        <span class="field__label">Tên nội bộ <em>*</em></span>
        <input class="field__input<?= $err('name') ? ' is-invalid' : '' ?>" type="text" name="name"
               value="<?= e($val('name')) ?>" maxlength="120" required placeholder="Ví dụ: Slide 1 — Cổng nhà máy">
        <?php if ($err('name')): ?><span class="field__error"><?= e($err('name')) ?></span><?php endif; ?>
      </label>
      <label class="field field--span2">
        <span class="field__label">Loại banner</span>
        <select class="field__input" name="type">
          <?php foreach ($types as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $val('type') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field field--span2">
        <span class="field__label">Tiêu đề hiển thị</span>
        <input class="field__input" type="text" name="title" value="<?= e($val('title')) ?>" maxlength="220">
      </label>
      <label class="field">
        <span class="field__label">Dòng phụ (eyebrow)</span>
        <input class="field__input" type="text" name="subtitle" value="<?= e($val('subtitle')) ?>" maxlength="255"
               placeholder="Ví dụ: Chi nhánh Cái Lậy">
      </label>
      <label class="field">
        <span class="field__label">Nhãn nút kêu gọi</span>
        <input class="field__input" type="text" name="cta_label" value="<?= e($val('cta_label')) ?>" maxlength="120"
               placeholder="Ví dụ: Nộp hồ sơ ngay">
      </label>
      <label class="field field--span2">
        <span class="field__label">Mô tả ngắn</span>
        <textarea class="field__input" name="description" rows="2" maxlength="600"><?= e($val('description')) ?></textarea>
        <?php if ($err('description')): ?><span class="field__error"><?= e($err('description')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Liên kết đích</span>
        <input class="field__input" type="text" name="link_url" value="<?= e($val('link_url')) ?>" maxlength="500"
               placeholder="/apply hoặc https://…">
      </label>
      <label class="field">
        <span class="field__label">Nhãn link</span>
        <input class="field__input" type="text" name="link_label" value="<?= e($val('link_label')) ?>" maxlength="120"
               placeholder="Ví dụ: Xem điều kiện">
      </label>
    </div>
  </section>

  <section class="admin-cms-card admin-cms-split" aria-labelledby="banner-form-image">
    <div class="admin-cms-section-intro">
      <h2 id="banner-form-image">Hình ảnh</h2>
      <p>Ảnh chính cho máy tính và ảnh riêng cho điện thoại nếu có.</p>
    </div>
    <div class="field-grid admin-cms-fields">
      <?php foreach ([
          ['image', 'Ảnh chính', $currentImage],
          ['image_small', 'Ảnh trên điện thoại', $val('image_path_small')],
      ] as [$uploadName, $uploadLabel, $imagePath]): ?>
      <?php
      $uploadImage = $imagePath !== '' ? asset($imagePath) : '';
      $uploadError = $err($uploadName);
      require BASE_PATH . '/app/Views/partials/admin/image-picker.php';
      ?>
      <?php endforeach; ?>
      <label class="field field--span2">
        <span class="field__label">Văn bản thay thế của ảnh</span>
        <input class="field__input" type="text" name="image_alt" value="<?= e($val('image_alt')) ?>" maxlength="255"
               placeholder="Mô tả ảnh cho trình đọc màn hình và SEO">
      </label>
    </div>
  </section>

  <section class="admin-cms-card admin-cms-split" aria-labelledby="banner-form-schedule">
    <div class="admin-cms-section-intro">
      <h2 id="banner-form-schedule">Lịch hiển thị</h2>
      <p>Để trống thời gian nếu banner luôn được hiển thị.</p>
    </div>
    <div class="field-grid admin-cms-fields">
      <label class="field">
        <span class="field__label">Bắt đầu</span>
        <input class="field__input<?= $err('starts_at') ? ' is-invalid' : '' ?>" type="datetime-local" name="starts_at" value="<?= e($startsAt) ?>">
        <?php if ($err('starts_at')): ?><span class="field__error"><?= e($err('starts_at')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Kết thúc</span>
        <input class="field__input<?= $err('ends_at') ? ' is-invalid' : '' ?>" type="datetime-local" name="ends_at" value="<?= e($endsAt) ?>">
        <?php if ($err('ends_at')): ?><span class="field__error"><?= e($err('ends_at')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Thứ tự hiển thị</span>
        <input class="field__input" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>">
      </label>
      <label class="admin-cms-visibility">
        <span>Hiển thị trên website</span>
        <input class="admin-cms-switch" type="checkbox" role="switch" name="is_active" value="1"<?= (int) $val('is_active', '1') === 1 ? ' checked' : '' ?>>
      </label>
    </div>
  </section>

  <div class="admin-cms-actions">
    <a href="<?= url('admin/banners') ?>" class="btn btn-outline">Hủy</a>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo banner' ?></button>
  </div>
</form>
