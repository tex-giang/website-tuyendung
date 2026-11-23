<?php
$page['ajax_fragment'] = true;
/** Form thêm/sửa tin tuyển dụng. $job = null khi tạo mới. */
$isEdit = $job !== null;
$action = $isEdit ? url('admin/jobs/' . $job['id'] . '/edit') : url('admin/jobs/create');
$val = static fn(string $key, string $default = ''): string => (string) ($values[$key] ?? $default);
// Hiển thị ngày theo định dạng Việt Nam dd/mm/yyyy (ô text, không dùng input date của trình duyệt).
$postedAt = $val('posted_at');
if ($postedAt !== '') {
    $postedParsed = vn_parse_datetime($postedAt);
    $postedAt = $postedParsed !== null ? date('d/m/Y H:i', strtotime($postedParsed)) : $postedAt;
}
$deadline = $val('deadline');
if ($deadline !== '') {
    $deadlineParsed = vn_parse_date($deadline);
    $deadline = $deadlineParsed !== null ? date('d/m/Y', strtotime($deadlineParsed)) : $deadline;
}
?>
<div class="admin-page-head admin-job-heading">
  <div>
    <a href="<?= url('admin/jobs') ?>" class="admin-job-heading__back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Tin tuyển dụng</a>
    <h1 class="admin-h1"><?= $isEdit ? 'Sửa tin tuyển dụng' : 'Thêm tin tuyển dụng' ?></h1>
    <p class="admin-sub"><?= $isEdit ? 'Cập nhật thông tin vị trí tuyển dụng.' : 'Điền thông tin vị trí cần tuyển.' ?> Các trường có dấu * là bắt buộc.</p>
  </div>
  <?php if ($isEdit): ?>
  <a href="<?= url('jobs/' . $job['slug']) ?>" target="_blank" rel="noopener" class="btn btn-outline"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Xem trên website</a>
  <?php endif; ?>
</div>

<?php if ($errors !== []): ?>
<div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại. Chi tiết hiển thị dưới từng ô.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" class="admin-form admin-job-form" data-ajax="job">
  <?= csrf_field() ?>

  <section class="admin-job-form__section" aria-labelledby="job-section-overview">
    <div class="admin-job-form__intro">
      <h2 id="job-section-overview">Thông tin chung</h2>
      <p>Tên vị trí, mã tin và đường dẫn hiển thị trên website.</p>
    </div>
    <div class="field-grid admin-job-form__fields">
      <label class="field field--span2">
        <span class="field__label">Tiêu đề tin <em>*</em></span>
        <input class="field__input<?= isset($errors['title']) ? ' is-invalid' : '' ?>" type="text" name="title"
               value="<?= e($val('title')) ?>" maxlength="220" placeholder="Nhập tên vị trí cần tuyển" required>
        <?php if (isset($errors['title'])): ?><span class="field__error"><?= e($errors['title']) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Mã tin</span>
        <input class="field__input<?= isset($errors['code']) ? ' is-invalid' : '' ?>" type="text" name="code"
               value="<?= e($val('code')) ?>" maxlength="20" placeholder="Nhập mã tin tuyển dụng">
        <?php if (isset($errors['code'])): ?><span class="field__error"><?= e($errors['code']) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Đường dẫn (slug)</span>
        <input class="field__input<?= isset($errors['slug']) ? ' is-invalid' : '' ?>" type="text" placeholder="Nhập đường dẫn riêng của tin" name="slug"
               value="<?= e($val('slug')) ?>" maxlength="220" aria-describedby="job-slug-hint">
        <span class="admin-job-form__hint" id="job-slug-hint">Để trống để tạo đường dẫn từ tiêu đề.</span>
        <?php if (isset($errors['slug'])): ?><span class="field__error"><?= e($errors['slug']) ?></span><?php endif; ?>
      </label>
    </div>
  </section>

  <section class="admin-job-form__section" aria-labelledby="job-section-position">
    <div class="admin-job-form__intro">
      <h2 id="job-section-position">Vị trí tuyển dụng</h2>
      <p>Khu sản xuất, Khối, bộ phận, số lượng và địa điểm làm việc.</p>
    </div>
    <div class="field-grid admin-job-form__fields">
      <label class="field">
        <span class="field__label">Khu sản xuất <em>*</em></span>
        <select class="field__input<?= isset($errors['branch_id']) ? ' is-invalid' : '' ?>" name="branch_id" required>
          <option value="" hidden>Chọn khu sản xuất</option>
          <?php foreach ($branches as $branch): ?>
          <option value="<?= (int) $branch['id'] ?>"<?= (string) $val('branch_id') === (string) $branch['id'] ? ' selected' : '' ?>><?= e($branch['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['branch_id'])): ?><span class="field__error"><?= e($errors['branch_id']) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Khối <em>*</em></span>
        <select class="field__input<?= isset($errors['block']) ? ' is-invalid' : '' ?>" name="block" required data-job-block>
          <option value="" hidden>Chọn Khối</option>
          <?php foreach ($blocks as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $val('block') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="admin-job-form__hint" id="job-block-hint">Khối cố định: Văn phòng hoặc Xí nghiệp. Bộ phận bên cạnh sẽ lọc theo Khối này.</span>
        <?php if (isset($errors['block'])): ?><span class="field__error"><?= e($errors['block']) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Bộ phận</span>
        <?php
        // Chỉ hiện bộ phận đang có tin (kèm số tin) + bộ phận đang gán cho tin này.
        // Bộ phận có data-block để JS lọc theo Khối đã chọn.
        $selectedBlock = (string) $val('block');
        ?>
        <select class="field__input" name="category_id" data-job-category aria-describedby="job-block-hint">
          <option value="">Chọn bộ phận</option>
          <?php foreach ($categories as $category):
              $categoryBlock = (string) ($category['block'] ?? '');
              $matches = $selectedBlock === '' || $categoryBlock === '' || $categoryBlock === $selectedBlock;
              $jobCount = (int) ($category['job_count'] ?? 0);
          ?>
          <option value="<?= (int) $category['id'] ?>"
                  data-block="<?= e($categoryBlock) ?>"
                  <?= $matches ? '' : 'hidden' ?>
                  <?= (string) $val('category_id') === (string) $category['id'] ? ' selected' : '' ?>><?= e($category['name']) ?><?= $jobCount > 0 ? ' (' . $jobCount . ' tin)' : ' (chưa có tin)' ?><?= $categoryBlock === '' ? ' · dùng chung' : '' ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category_id'])): ?><span class="field__error"><?= e($errors['category_id']) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Số lượng cần tuyển <em>*</em></span>
        <input class="field__input<?= isset($errors['quantity']) ? ' is-invalid' : '' ?>" type="number" placeholder="Nhập số người cần tuyển" name="quantity"
               min="1" max="10000" value="<?= e($val('quantity', '1')) ?>" required>
        <?php if (isset($errors['quantity'])): ?><span class="field__error"><?= e($errors['quantity']) ?></span><?php endif; ?>
      </label>

      <label class="field">
        <span class="field__label">Địa điểm làm việc</span>
        <input class="field__input" type="text" name="location" value="<?= e($val('location')) ?>" maxlength="255"
               placeholder="Nhập địa điểm làm việc cụ thể">
      </label>
    </div>
  </section>

  <section class="admin-job-form__section" aria-labelledby="job-section-pay">
    <div class="admin-job-form__intro">
      <h2 id="job-section-pay">Lương và yêu cầu</h2>
      <p>Nhập lương tối thiểu/tối đa — phần hiển thị và khoảng lương tự sinh nếu để trống.</p>
    </div>
    <div class="field-grid admin-job-form__fields">
      <label class="field">
        <span class="field__label">Lương tối thiểu (VNĐ)</span>
        <input class="field__input<?= isset($errors['salary_min']) ? ' is-invalid' : '' ?>" type="number" name="salary_min"
               min="0" step="100000" value="<?= e($val('salary_min')) ?>" placeholder="Nhập mức lương thấp nhất (VNĐ)">
        <?php if (isset($errors['salary_min'])): ?><span class="field__error"><?= e($errors['salary_min']) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Lương tối đa (VNĐ)</span>
        <input class="field__input<?= isset($errors['salary_max']) ? ' is-invalid' : '' ?>" type="number" name="salary_max"
               min="0" step="100000" value="<?= e($val('salary_max')) ?>" placeholder="Nhập mức lương cao nhất (VNĐ)">
        <?php if (isset($errors['salary_max'])): ?><span class="field__error"><?= e($errors['salary_max']) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Lương hiển thị <span class="admin-muted">(trống = tự sinh)</span></span>
        <input class="field__input" type="text" name="salary_text" value="<?= e($val('salary_text')) ?>" maxlength="150"
               placeholder="Nhập mức lương hiển thị cho ứng viên">
      </label>
      <label class="field">
        <span class="field__label">Khoảng lương <span class="admin-muted">(trống = tự sinh)</span></span>
        <select class="field__input<?= isset($errors['salary_range']) ? ' is-invalid' : '' ?>" name="salary_range">
          <?php foreach ($salaryRanges as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= (string) $val('salary_range') === (string) $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['salary_range'])): ?><span class="field__error"><?= e($errors['salary_range']) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Kinh nghiệm</span>
        <input class="field__input" type="text" name="experience" value="<?= e($val('experience')) ?>" maxlength="150"
               placeholder="Nhập yêu cầu về kinh nghiệm">
      </label>
      <label class="field">
        <span class="field__label">Hình thức làm việc</span>
        <input class="field__input" type="text" name="employment_type" value="<?= e($val('employment_type')) ?>" maxlength="100"
               placeholder="Nhập hình thức làm việc">
      </label>
    </div>
  </section>

  <section class="admin-job-form__section" aria-labelledby="job-section-content">
    <div class="admin-job-form__intro">
      <h2 id="job-section-content">Nội dung tin</h2>
      <p>Mô tả công việc, yêu cầu ứng viên, hồ sơ xin việc và quyền lợi.</p>
    </div>
    <div class="field-grid admin-job-form__fields">
      <label class="field field--span2">
        <span class="field__label">Mô tả công việc <em>*</em></span>
        <textarea class="field__input<?= isset($errors['description']) ? ' is-invalid' : '' ?>" placeholder="Mô tả nhiệm vụ của vị trí tuyển dụng" name="description" rows="5" required><?= e($val('description')) ?></textarea>
        <?php if (isset($errors['description'])): ?><span class="field__error"><?= e($errors['description']) ?></span><?php endif; ?>
      </label>
      <label class="field field--span2">
        <span class="field__label">Yêu cầu ứng viên</span>
        <textarea class="field__input" placeholder="Nhập các yêu cầu đối với ứng viên" name="requirements" rows="4"><?= e($val('requirements')) ?></textarea>
      </label>
      <label class="field field--span2">
        <span class="field__label">Hồ sơ xin việc</span>
        <textarea class="field__input" placeholder="Mỗi dòng 1 giấy tờ ứng viên cần chuẩn bị (VD: Đơn xin việc, Sơ yếu lý lịch, CCCD photo…)" name="application_documents" rows="4"><?= e($val('application_documents')) ?></textarea>
        <span class="admin-job-form__hint">Hiển thị thành mục “Hồ sơ xin việc” ở trang chi tiết tin. Để trống nếu không yêu cầu giấy tờ riêng.</span>
      </label>
      <label class="field field--span2">
        <span class="field__label">Quyền lợi</span>
        <textarea class="field__input" placeholder="Nhập các phúc lợi của vị trí" name="benefits" rows="4"><?= e($val('benefits')) ?></textarea>
      </label>
    </div>
  </section>

  <section class="admin-job-form__section" aria-labelledby="job-section-publish">
    <div class="admin-job-form__intro">
      <h2 id="job-section-publish">Đăng tin</h2>
      <p>Trạng thái, thời gian đăng và hạn nộp hồ sơ.</p>
    </div>
    <div class="field-grid admin-job-form__fields">
      <label class="field">
        <span class="field__label">Trạng thái <em>*</em></span>
        <select class="field__input" name="status">
          <?php foreach ($statuses as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $val('status') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span class="field__label">Thứ tự ưu tiên</span>
        <input class="field__input" type="number" placeholder="Nhập thứ tự hiển thị" name="sort_order" value="<?= e($val('sort_order', '0')) ?>">
      </label>
      <label class="field">
        <span class="field__label">Ngày đăng <span class="admin-muted">(dd/mm/yyyy HH:MM · trống = hôm nay)</span></span>
        <input class="field__input<?= isset($errors['posted_at']) ? ' is-invalid' : '' ?>" type="text" name="posted_at"
               value="<?= e($postedAt) ?>" placeholder="Nhập ngày giờ đăng (dd/mm/yyyy HH:MM)" autocomplete="off" maxlength="16"
               data-datepicker="datetime">
        <?php if (isset($errors['posted_at'])): ?><span class="field__error"><?= e($errors['posted_at']) ?></span><?php endif; ?>
      </label>
      <label class="field">
        <span class="field__label">Hạn nộp hồ sơ <span class="admin-muted">(dd/mm/yyyy · trống = chưa xác định)</span></span>
        <input class="field__input<?= isset($errors['deadline']) ? ' is-invalid' : '' ?>" type="text" name="deadline"
               value="<?= e($deadline) ?>" placeholder="Nhập hạn nộp hồ sơ (dd/mm/yyyy)" autocomplete="off" maxlength="10"
               data-datepicker="date">
        <?php if (isset($errors['deadline'])): ?><span class="field__error"><?= e($errors['deadline']) ?></span><?php endif; ?>
      </label>
      <label class="field field--span2 admin-job-form__switch-row">
        <span>
          <span class="field__label" id="job-urgent-label">Tuyển gấp</span>
          <span class="admin-job-form__hint" id="job-urgent-hint">Ưu tiên hiển thị tin trên website tuyển dụng.</span>
        </span>
        <input type="checkbox" role="switch" name="is_urgent" value="1" aria-labelledby="job-urgent-label"
               aria-describedby="job-urgent-hint"<?= (int) $val('is_urgent', '0') === 1 ? ' checked' : '' ?>>
      </label>
    </div>
  </section>

  <section class="admin-job-form__section" aria-labelledby="job-section-seo">
    <div class="admin-job-form__intro">
      <h2 id="job-section-seo">SEO</h2>
      <p>Nội dung hiển thị trên kết quả tìm kiếm.</p>
    </div>
    <div class="field-grid admin-job-form__fields">
      <label class="field field--span2">
        <span class="field__label">Tiêu đề SEO</span>
        <input class="field__input" type="text" placeholder="Nhập tiêu đề tin trên công cụ tìm kiếm" name="meta_title" value="<?= e($val('meta_title')) ?>" maxlength="220">
      </label>
      <label class="field field--span2">
        <span class="field__label">Mô tả SEO</span>
        <textarea class="field__input" placeholder="Nhập mô tả ngắn cho công cụ tìm kiếm" name="meta_description" rows="2" maxlength="320"><?= e($val('meta_description')) ?></textarea>
      </label>
    </div>
  </section>

  <div class="admin-job-form__actions">
    <a href="<?= url('admin/jobs') ?>" class="btn btn-outline">Hủy</a>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo tin tuyển dụng' ?></button>
  </div>
</form>

<?php if ($isEdit): ?>
<div class="admin-form__delete">
  <form method="POST" action="<?= url('admin/jobs/' . $job['id'] . '/duplicate') ?>" data-ajax>
    <?= csrf_field() ?><button type="submit" class="btn btn-outline"><i class="bi bi-copy me-1" aria-hidden="true"></i> Nhân bản tin này</button>
  </form>
  <form method="POST" action="<?= url('admin/jobs/' . $job['id'] . '/delete') ?>" data-ajax
        data-confirm="Xoá tin &quot;<?= e($val('title')) ?>&quot;? Thao tác không thể hoàn tác.">
    <?= csrf_field() ?><button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Xoá tin</button>
  </form>
</div>
<?php endif; ?>
