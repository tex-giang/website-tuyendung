<?php
$page['ajax_fragment'] = true;
/** Form thêm/sửa banner. */
$isEdit = $banner !== null;
$action = $isEdit ? url('admin/banners/' . $banner['id'] . '/edit') : url('admin/banners/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$currentImage = $val('image_path');
$typeHint = [
    'hero' => 'Ảnh ngang 2048×768 (slide). Bắt buộc có ảnh.',
    'notice' => 'Tiêu đề hiển thị trên dải thông báo; link tuỳ chọn.',
    'popup' => 'Tiêu đề, mô tả ngắn, nhãn nút và link CTA.',
    'ad' => 'Tiêu đề + mô tả + nhãn nút cho dải CTA.',
][$val('type', 'hero')] ?? '';
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= url('admin/banners') ?>">Banner</a> / <?= $isEdit ? 'Sửa' : 'Thêm' ?></p>
    <h1 class="admin-h1"><?= $isEdit ? 'Sửa banner' : 'Thêm banner' ?></h1>
  </div>
  <a href="<?= url('admin/banners') ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Danh sách</a>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
<?php endif; ?>

<form data-ajax method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form">
  <?= csrf_field() ?>

  <section class="admin-card">
    <h2 class="admin-h2">Thông tin banner</h2>
    <div class="field-grid">
      <label class="field">
        <span class="field__label">Tên nội bộ <em>*</em></span>
        <input class="field__input<?= $err('name') ? ' is-invalid' : '' ?>" type="text" name="name"
               value="<?= e($val('name')) ?>" maxlength="120" required placeholder="Ví dụ: Slide 1 — Cổng nhà máy">
        <?php if ($err('name')): ?><span class="field__error"><?= e($err('name')) ?></span><?php endif; ?>
      </label>
      <label class="field">
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
        <span class="field__label">Nhãn nút (CTA)</span>
        <input class="field__input" type="text" name="cta_label" value="<?= e($val('cta_label')) ?>" maxlength="120"
               placeholder="Ví dụ: Nộp hồ sơ ngay">
      </label>
      <label class="field field--span2">
        <span class="field__label">Mô tả ngắn</span>
        <textarea class="field__input" name="description" rows="2" maxlength="600"><?= e($val('description')) ?></textarea>
        <?php if ($err('description')): ?><span class="field__error"><?= e($err('description')) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Link đích</span>
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

  <section class="admin-card">
    <h2 class="admin-h2">Ảnh banner</h2>
    <div class="field-grid">
      <div class="field">
        <span class="field__label">Chọn ảnh</span>
        <input class="field__input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
        <?php if ($err('image')): ?><span class="field__error"><?= e($err('image')) ?></span><?php endif; ?>
        <input type="hidden" name="image_path" value="<?= e($currentImage) ?>">
        <input type="hidden" name="image_path_small" value="<?= e($val('image_path_small')) ?>">
      </div>
      <div class="field">
        <span class="field__label">Ảnh hiện tại</span>
        <?php if ($currentImage !== ''): ?>
        <img class="admin-banner-preview" src="<?= e(asset($currentImage)) ?>" alt="">
        <?php if (($val('image_path_small')) !== ''): ?>
        <img class="admin-thumb mt-2" src="<?= e(asset($val('image_path_small'))) ?>" alt="Ảnh nhỏ">
        <?php endif; ?>
        <?php else: ?>
        <span class="admin-muted">Chưa có ảnh.</span>
        <?php endif; ?>
      </div>
      <label class="field">
        <span class="field__label">Ảnh nhỏ (mobile)</span>
        <input class="field__input" type="file" name="image_small" accept=".jpg,.jpeg,.png,.webp">
        <?php if ($err('image_small')): ?><span class="field__error"><?= e($err('image_small')) ?></span><?php endif; ?>
      </label>
      <label class="field field--span2">
        <span class="field__label">Mô tả ảnh (alt)</span>
        <input class="field__input" type="text" name="image_alt" value="<?= e($val('image_alt')) ?>" maxlength="255"
               placeholder="Mô tả ảnh cho trình đọc màn hình và SEO">
      </label>
    </div>
  </section>

  <section class="admin-card">
    <h2 class="admin-h2">Lịch &amp; trạng thái</h2>
    <div class="field-grid">
      <label class="field">
        <span class="field__label">Bắt đầu hiển thị</span>
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
      <label class="field field--checkbox">
        <input type="checkbox" name="is_active" value="1"<?= (int) $val('is_active', '1') === 1 ? ' checked' : '' ?>>
        <span>Đang bật — hiển thị trên website khi còn trong thời gian hiệu lực</span>
      </label>
    </div>
  </section>

  <div class="admin-form__actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Tạo banner' ?></button>
    <a href="<?= url('admin/banners') ?>" class="btn btn-ghost">Huỷ</a>
  </div>
</form>
