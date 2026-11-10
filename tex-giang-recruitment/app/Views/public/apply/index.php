<?php
/**
 * Form ứng tuyển trực tuyến — port từ markup của apply-form.js (bản tĩnh),
 * nay là form POST thật, lưu MySQL + CV vào storage/uploads/cv.
 * @var array $jobs @var array $branches @var array $old @var array $errors
 */
$val = static fn(string $key, string $default = ''): string => (string) ($old[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$positionValue = $val('job_slug');
$branchValue = $val('branch_slug');
?>
<section class="tg-container pt-14 lg:pt-20">
  <div class="page-intro">
    <div>
      <h1 class="max-w-[820px] text-[clamp(2rem,4.4vw,3rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        Nộp hồ sơ trực tuyến
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        Điền 2 phút, không cần tài khoản. Ban tuyển dụng TEX-GIANG gọi lại trong 24 giờ làm việc để hẹn lịch phỏng vấn.
      </p>
    </div>
    <figure class="intro-photo">
      <img src="<?= asset('images/company/tan-huong-2.webp') ?>" width="360" height="300"
           alt="Toàn cảnh nhà máy Tân Hương 2 trong hệ thống TEX-GIANG" fetchpriority="high">
      <figcaption>Nhà máy Tân Hương 2 · TEX-GIANG</figcaption>
    </figure>
  </div>
</section>

<section class="tg-container pb-20 pt-10 lg:pb-28">
  <div class="grid items-start gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">

    <div>
      <p class="mb-6 text-[14px] text-muted">Chỉ muốn nộp nhanh 4 thông tin? <a href="<?= url('tuyen-dung') ?>" class="tg-link">Dùng mẫu nộp nhanh</a>.</p>
<form id="online-apply-form" class="space-y-10" method="POST" action="<?= url('apply') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="sr-only" aria-hidden="true">
          <label for="apply-website">Website</label>
          <input type="text" id="apply-website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <?php if ($errors !== []): ?>
        <div class="form-summary" role="alert" tabindex="-1">
          Hồ sơ chưa hoàn tất. Kiểm tra <?= count($errors) ?> mục được đánh dấu bên dưới.
        </div>
        <?php endif; ?>

        <fieldset data-reveal>
          <legend class="form-group-title">
            <img class="tg-icon" src="<?= asset('images/icons/briefcase.svg') ?>" width="20" height="20" alt="" aria-hidden="true">
            Vị trí ứng tuyển
          </legend>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="form-label" for="apply-branch">Khu sản xuất <span class="req">*</span></label>
              <select id="apply-branch" name="branch_slug" class="form-input<?= $err('branch_slug') ? ' is-invalid' : '' ?>" required>
                <option value="">— Chọn khu —</option>
                <?php foreach ($branches as $slug => $name): ?>
                <option value="<?= e($slug) ?>"<?= $branchValue === $slug ? ' selected' : '' ?>>Khu <?= e($name) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($err('branch_slug')): ?><p class="field-error"><?= e($err('branch_slug')) ?></p><?php endif; ?>
            </div>
            <div>
              <label class="form-label" for="apply-position">Vị trí mong muốn <span class="req">*</span></label>
              <select id="apply-position" name="job_slug" class="form-input<?= $err('job_slug') ? ' is-invalid' : '' ?>" required>
                <option value="">— Chọn vị trí —</option>
                <?php foreach ($jobs as $job): ?>
                <option value="<?= e($job['slug']) ?>" data-branch="<?= e((string) ($job['branch_slug'] ?? '')) ?>"<?= $positionValue === $job['slug'] ? ' selected' : '' ?>>
                  <?= e($job['title']) ?><?= $job['salary_text'] ? ' (' . e(salary_short((string) $job['salary_text'])) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
                <option value="khac"<?= $positionValue === 'khac' ? ' selected' : '' ?>>Vị trí khác — ghi rõ ở phần giới thiệu</option>
              </select>
              <p class="form-hint" id="apply-position-hint">Chọn khu sản xuất trước để lọc vị trí đang tuyển.</p>
              <?php if ($err('job_slug')): ?><p class="field-error"><?= e($err('job_slug')) ?></p><?php endif; ?>
            </div>
          </div>
        </fieldset>

        <fieldset data-reveal>
          <legend class="form-group-title">
            <img class="tg-icon" src="<?= asset('images/icons/file-earmark-text.svg') ?>" width="20" height="20" alt="" aria-hidden="true">
            Thông tin cá nhân
          </legend>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="form-label" for="apply-fullname">Họ và tên <span class="req">*</span></label>
              <input type="text" id="apply-fullname" name="full_name" required autocomplete="name" maxlength="150"
                     placeholder="Ví dụ: Lê Thị Mai" class="form-input<?= $err('full_name') ? ' is-invalid' : '' ?>"
                     value="<?= e($val('full_name')) ?>">
              <?php if ($err('full_name')): ?><p class="field-error"><?= e($err('full_name')) ?></p><?php endif; ?>
            </div>
            <div>
              <label class="form-label" for="apply-phone">Số điện thoại <span class="req">*</span></label>
              <input type="tel" id="apply-phone" name="phone" required autocomplete="tel" inputmode="tel"
                     placeholder="0912 345 678" class="form-input<?= $err('phone') ? ' is-invalid' : '' ?>"
                     value="<?= e($val('phone')) ?>" data-phone-format>
              <?php if ($err('phone')): ?><p class="field-error"><?= e($err('phone')) ?></p><?php endif; ?>
            </div>
            <div>
              <label class="form-label" for="apply-email">Email (nếu có)</label>
              <input type="email" id="apply-email" name="email" autocomplete="email" maxlength="190"
                     placeholder="email@gmail.com" class="form-input<?= $err('email') ? ' is-invalid' : '' ?>"
                     value="<?= e($val('email')) ?>">
              <?php if ($err('email')): ?><p class="field-error"><?= e($err('email')) ?></p><?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label" for="apply-birth">Năm sinh</label>
                <input type="number" id="apply-birth" name="birth_year" min="<?= (int) date('Y') - 60 ?>"
                       max="<?= (int) date('Y') - 18 ?>" placeholder="1996"
                       class="form-input<?= $err('birth_year') ? ' is-invalid' : '' ?>" value="<?= e($val('birth_year')) ?>">
                <?php if ($err('birth_year')): ?><p class="field-error"><?= e($err('birth_year')) ?></p><?php endif; ?>
              </div>
              <div>
                <label class="form-label" for="apply-gender">Giới tính</label>
                <select id="apply-gender" name="gender" class="form-input">
                  <option value="nu"<?= $val('gender') === 'nu' ? ' selected' : '' ?>>Nữ</option>
                  <option value="nam"<?= $val('gender') === 'nam' ? ' selected' : '' ?>>Nam</option>
                  <option value="khac"<?= $val('gender') === 'khac' ? ' selected' : '' ?>>Khác</option>
                </select>
              </div>
            </div>
            <div class="sm:col-span-2">
              <label class="form-label" for="apply-address">Địa chỉ hiện tại <span class="req">*</span></label>
              <input type="text" id="apply-address" name="address" required autocomplete="address-line1" maxlength="255"
                     placeholder="Ấp/Xã, Huyện Châu Thành, Tỉnh Đồng Tháp"
                     class="form-input<?= $err('address') ? ' is-invalid' : '' ?>" value="<?= e($val('address')) ?>">
              <p class="form-hint">Giúp ban tuyển dụng xếp bạn vào tuyến xe đưa rước gần nhất.</p>
              <?php if ($err('address')): ?><p class="field-error"><?= e($err('address')) ?></p><?php endif; ?>
            </div>
          </div>
        </fieldset>

        <fieldset data-reveal>
          <legend class="form-group-title">
            <img class="tg-icon" src="<?= asset('images/icons/file-earmark-text.svg') ?>" width="20" height="20" alt="" aria-hidden="true">
            CV hoặc hình hồ sơ
          </legend>
          <div id="cv-drop-zone" class="dropzone">
            <input type="file" id="cv-file-input" name="cv" class="hidden"
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" aria-describedby="cv-hint cv-error">
            <label for="cv-file-input" class="block cursor-pointer">
              <img src="<?= asset('images/icons/cloud-arrow-up.svg') ?>" alt="" aria-hidden="true" width="20" height="20"
                   class="tg-icon text-[28px] text-accent">
              <span class="mt-2 block text-[16px] font-medium text-ink">Kéo thả tệp vào đây, hoặc chọn từ máy</span>
              <span id="cv-hint" class="mt-1 block text-[16px] text-muted">PDF, DOC, DOCX, JPG, PNG · tối đa 10MB</span>
            </label>
          </div>
          <p id="cv-error" class="field-error" role="alert"></p>
          <button type="button" id="skip-cv" class="hidden tg-link mt-2">Bỏ tệp này, tiếp tục không có CV</button>
          <div id="file-preview-card" class="mt-4 hidden items-center justify-between gap-4 rounded-card bg-surface p-4">
            <div class="flex min-w-0 items-center gap-3">
              <img src="<?= asset('images/icons/file-earmark-check.svg') ?>" alt="" aria-hidden="true" width="20" height="20"
                   class="tg-icon text-[22px] text-accent">
              <div class="min-w-0">
                <strong id="file-name-display" class="block truncate text-[16px] font-medium text-ink">HoSo_UngVien.pdf</strong>
                <span id="file-size-display" class="text-[14px] text-muted">Đã chọn trên máy</span>
              </div>
            </div>
            <button type="button" id="remove-file-btn"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-muted transition-colors hover:bg-white hover:text-ink"
                    aria-label="Gỡ tệp">
              <img src="<?= asset('images/icons/trash.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
            </button>
          </div>
        </fieldset>

        <fieldset data-reveal>
          <legend class="form-group-title">Kinh nghiệm &amp; nguyện vọng</legend>
          <label class="form-label" for="apply-message">Giới thiệu ngắn (không bắt buộc)</label>
          <textarea id="apply-message" name="message" rows="4" class="form-input"
                    placeholder="Kinh nghiệm may, vị trí từng làm, mong muốn thu nhập hoặc tuyến xe đưa đón…"><?= e($val('message')) ?></textarea>
          <?php if ($err('message')): ?><p class="field-error"><?= e($err('message')) ?></p><?php endif; ?>
        </fieldset>

        <div class="space-y-5">
          <label class="flex cursor-pointer items-start gap-3 rounded-card bg-surface p-4 text-[14px] leading-[1.6] text-muted">
            <input type="checkbox" id="apply-terms" name="terms" required value="1"
                   class="mt-0.5 h-5 w-5 shrink-0 rounded border-line text-accent focus:ring-accent"<?= $val('terms') !== '' ? ' checked' : '' ?>>
            <span>Tôi xác nhận thông tin trên là đúng và đồng ý để ban tuyển dụng TEX-GIANG liên hệ qua điện thoại hoặc Zalo.</span>
          </label>
          <?php if ($err('terms')): ?><p class="field-error"><?= e($err('terms')) ?></p><?php endif; ?>

          <button type="submit" class="tg-btn tg-btn--primary w-full">
            <img src="<?= asset('images/icons/send.svg') ?>" alt="" aria-hidden="true" width="20" height="20"
                 class="tg-icon text-[16px]"> Gửi hồ sơ ứng tuyển
          </button>
          <p class="text-center text-[14px] leading-[1.6] text-muted">
            Thông tin chỉ dùng cho tuyển dụng nội bộ. TEX-GIANG không thu bất kỳ khoản phí nào từ ứng viên.
          </p>
        </div>
      </form>
    </div>

    <aside class="space-y-6 lg:sticky lg:top-24">
      <div class="rounded-card bg-surface p-6" data-reveal>
        <h2 class="text-[16px] font-medium text-ink">Cần hỗ trợ?</h2>
        <p class="mt-3 text-[16px] leading-[1.7] text-muted">Xem người phụ trách theo nhà máy nếu bạn không tiện điền form.</p>
        <?php if (menu_link_visible('contact')): ?><a href="<?= url('contact') ?>" class="tg-btn tg-btn--primary mt-4 w-full">Liên hệ người phụ trách</a><?php endif; ?>
        <?php if (settings('zalo') !== ''): ?>
        <a href="<?= e(zalo_url()) ?>" target="_blank" rel="noopener" class="tg-btn tg-btn--ghost mt-3 w-full">Chat Zalo</a>
        <?php endif; ?>
      </div>

      <div class="rounded-card bg-surface p-6" data-reveal>
        <h2 class="text-[16px] font-medium text-ink">Sau khi gửi</h2>
        <ol class="mt-4 space-y-4 text-[16px] leading-[1.6] text-muted">
          <li><span class="block font-medium text-ink">1. Trong 24 giờ</span> Nhân sự gọi xác nhận thông tin và hẹn lịch.</li>
          <li><span class="block font-medium text-ink">2. Buổi phỏng vấn</span> Gặp trực tiếp tại Nhà máy Tân Hương 2, mang CCCD.</li>
          <li><span class="block font-medium text-ink">3. Nhận việc</span> Thử tay nghề hoặc vào lớp đào tạo có lương.</li>
        </ol>
      </div>

      <div class="p-1">
        <p class="text-[16px] leading-[1.7] text-muted">
          <img src="<?= asset('images/icons/shield-check.svg') ?>" alt="" aria-hidden="true" width="20" height="20"
               class="tg-icon text-accent">
          Thông tin chỉ dùng cho tuyển dụng nội bộ. TEX-GIANG không thu bất kỳ khoản phí nào từ ứng viên.
        </p>
      </div>
    </aside>
  </div>
</section>
