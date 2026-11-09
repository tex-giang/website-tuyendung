<?php
/**
 * Trang nộp hồ sơ nhanh (/tuyen-dung) — giao diện độc lập giống ung-tuyen.html tĩnh.
 * Chọn khu vực trước → danh sách vị trí lọc theo khu (quick-apply.js).
 *
 * @var array $branches  Danh sách khu đang hoạt động
 * @var array $jobData   Vị trí đang tuyển (slug, title, branch, salary)
 * @var array $old       Dữ liệu cũ khi server trả lỗi
 * @var array $errors    Lỗi theo trường
 * @var array $last      Hồ sơ vừa gửi xong (khi ?done=1)
 * @var bool  $done      Đang hiển thị màn hình cảm ơn
 */
$val = static fn(string $key, string $default = ''): string => (string) ($old[$key] ?? $default);
$err = static fn(string $key): string => (string) ($errors[$key] ?? '');
$fieldClass = static fn(string $key): string => 'gf-field' . ($err($key) !== '' ? ' has-error' : '');
$oldBranch = $val('branch_slug');
$oldJob = $val('job_slug');
?>
  <a class="gf-brand" href="<?= url('') ?>" aria-label="TEX-GIANG — về trang tuyển dụng">
    <img src="<?= asset('images/logo-tgi-mark.webp') ?>" alt="TEX-GIANG" width="80" height="34">
  </a>

  <div class="gf-card">
    <?php if (!empty($done)): ?>
    <div class="gf-success">
      <span class="gf-success__icon" aria-hidden="true">
        <img src="<?= asset('images/icons/check2.svg') ?>" alt="" width="28" height="28">
      </span>
      <h1 class="gf-success__title">Đã nhận hồ sơ của bạn</h1>
      <p class="gf-desc">
        Mã hồ sơ: <strong><?= e($last['code'] ?? '') ?></strong>.
        Nhân sự TEX-GIANG sẽ gọi lại trong 24 giờ làm việc để xác nhận và hẹn lịch.
      </p>
      <div class="gf-success__actions">
        <a class="tg-btn tg-btn--ghost" href="<?= url('tuyen-dung') ?>">Gửi hồ sơ khác</a>
      </div>
    </div>
    <?php else: ?>

    <figure class="gf-banner">
      <img src="<?= asset('images/banner-texgiang.webp') ?>"
           srcset="<?= asset('images/banner-texgiang-800.webp') ?> 800w, <?= asset('images/banner-texgiang.webp') ?> 2048w"
           sizes="(max-width: 760px) 100vw, 720px"
           alt="Nhà máy Tân Hương 2 — Công ty Cổ phần TEX-GIANG"
           width="2048" height="768" fetchpriority="high">
    </figure>

    <header class="gf-head">
      <h1 class="gf-title">Nộp hồ sơ ứng tuyển</h1>
      <p class="gf-desc">Chọn khu vực bạn muốn làm việc, sau đó chọn vị trí đang tuyển tại khu đó. Bộ phận nhân sự TEX-GIANG sẽ gọi lại trong 24 giờ làm việc để xác nhận và hẹn lịch.</p>
      <p class="gf-required">* Bắt buộc</p>
    </header>

    <?php if ($errors !== []): ?>
    <p class="gf-alert" role="alert">Hồ sơ chưa hoàn tất. Vui lòng kiểm tra các mục được đánh dấu bên dưới.</p>
    <?php endif; ?>

    <form id="gf-form" class="gf-form" method="POST" action="<?= url('tuyen-dung') ?>">
      <?= csrf_field() ?>
      <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

      <div class="gf-row">
        <div class="<?= $fieldClass('full_name') ?>" data-field="full_name">
          <label class="gf-label" for="gf-name">Họ và tên <span class="req">*</span></label>
          <input class="form-input" id="gf-name" name="full_name" type="text" autocomplete="name" maxlength="150"
                 placeholder="Ví dụ: Nguyễn Thị Mai" value="<?= e($val('full_name')) ?>" required>
          <p class="gf-error"><?= e($err('full_name') !== '' ? $err('full_name') : 'Vui lòng nhập họ tên đầy đủ.') ?></p>
        </div>

        <div class="<?= $fieldClass('phone') ?>" data-field="phone">
          <label class="gf-label" for="gf-phone">Số điện thoại <span class="req">*</span></label>
          <input class="form-input" id="gf-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel"
                 placeholder="0912 345 678" value="<?= e($val('phone')) ?>" data-phone-format required>
          <p class="gf-error"><?= e($err('phone') !== '' ? $err('phone') : 'Số di động Việt Nam chưa đúng, ví dụ 0912 345 678.') ?></p>
        </div>
      </div>

      <fieldset class="<?= $fieldClass('branch_slug') ?>" data-field="branch_slug">
        <legend class="gf-label">Khu vực muốn làm việc <span class="req">*</span></legend>
        <div class="gf-radios">
          <?php foreach ($branches as $branch): ?>
          <label class="gf-radio">
            <input type="radio" name="branch_slug" value="<?= e($branch['slug']) ?>" required
                   <?= $oldBranch === (string) $branch['slug'] ? 'checked' : '' ?>>
            <span class="gf-radio__box"><?= e($branch['name']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <p class="gf-hint">Chọn khu vực trước — danh sách vị trí bên dưới sẽ lọc theo khu vực đó.</p>
        <p class="gf-error"><?= e($err('branch_slug') !== '' ? $err('branch_slug') : 'Chọn một khu vực bạn muốn làm việc.') ?></p>
      </fieldset>

      <div class="<?= $fieldClass('job_slug') ?>" data-field="job_slug">
        <label class="gf-label" for="gf-position">Vị trí mong muốn <span class="req">*</span></label>
        <select class="form-input" id="gf-position" name="job_slug" required>
          <option value="">— Chọn khu vực trước —</option>
          <?php foreach ($jobData as $job): ?>
          <option value="<?= e($job['slug']) ?>" data-branch="<?= e($job['branch']) ?>"
                  <?= $oldJob === $job['slug'] ? 'selected' : '' ?>>
            <?= e($job['title']) ?><?= $job['salary'] !== '' ? ' — ' . e($job['salary']) : '' ?>
          </option>
          <?php endforeach; ?>
          <option value="khac"<?= $oldJob === 'khac' ? ' selected' : '' ?>>Vị trí khác — ghi rõ khi nhân sự gọi</option>
        </select>
        <p class="gf-hint" id="gf-position-hint">Chọn khu vực trước để xem vị trí đang tuyển tại khu đó.</p>
        <p class="gf-error"><?= e($err('job_slug') !== '' ? $err('job_slug') : 'Chọn vị trí bạn muốn ứng tuyển.') ?></p>
      </div>

      <div class="<?= $fieldClass('terms') ?>" data-field="terms">
        <label class="gf-label" for="gf-terms" style="display:flex;align-items:flex-start;gap:10px;font-weight:400;font-size:14px;line-height:1.6;">
          <input type="checkbox" id="gf-terms" name="terms" value="1" required
                 style="margin-top:3px;width:18px;height:18px;flex:none;accent-color:var(--tg-accent);"
                 <?= !empty($old['terms']) ? 'checked' : '' ?>>
          <span>Tôi đồng ý để ban tuyển dụng TEX-GIANG liên hệ qua điện thoại hoặc Zalo về hồ sơ này.</span>
        </label>
        <p class="gf-error"><?= e($err('terms') !== '' ? $err('terms') : 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ.') ?></p>
      </div>

      <div class="gf-submit">
        <button type="submit" class="tg-btn tg-btn--primary">
          <img src="<?= asset('images/icons/send.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[16px]"> Gửi hồ sơ
        </button>
        <p class="gf-demo">Thông tin chỉ dùng cho tuyển dụng nội bộ, không thu phí ứng viên.</p>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <p class="gf-foot">
    Cần hỗ trợ? <a href="<?= url('contact') ?>">Liên hệ ban tuyển dụng</a> (giờ hành chính)
    <?php if (zalo_url() !== ''): ?>
    hoặc <a href="<?= e(zalo_url()) ?>" target="_blank" rel="noopener">chat Zalo</a>
    <?php endif; ?>
    <span class="gf-foot__sep" aria-hidden="true">·</span>
    <a href="<?= url('') ?>">Về trang tuyển dụng</a>
  </p>
