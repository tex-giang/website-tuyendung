<?php
/**
 * Dialog ứng tuyển nhanh — hiển thị trên mọi trang công khai (trừ trang /apply).
 * Form POST thật về /apply, dùng cùng controller/validate với trang đầy đủ.
 * Có chọn khu vực trước (radio), sau đó vị trí chỉ hiện các tin thuộc khu đó.
 */
$dialogJobs = $dialogJobs ?? \App\Models\Job::latestPublished(50);
$applyBranches = $applyBranches ?? \App\Models\Branch::active();

$dialogJobData = [];
foreach ($dialogJobs as $dialogJob) {
    $dialogJobData[] = [
        'slug'   => (string) $dialogJob['slug'],
        'title'  => trim((string) preg_replace('/^\d+\s+/', '', (string) $dialogJob['title'])),
        'branch' => (string) ($dialogJob['branch_slug'] ?? ''),
        'salary' => ($dialogJob['salary_text'] ?? '') !== '' ? salary_short((string) $dialogJob['salary_text']) : '',
    ];
}
?>
<dialog id="apply-dialog" class="apply-dialog" aria-labelledby="apply-dialog-title">
  <div class="apply-dialog__content">
    <header class="apply-dialog__header">
      <div class="apply-dialog__heading">
        <p class="apply-dialog__eyebrow">TEX-GIANG</p>
        <h2 class="apply-dialog__title" id="apply-dialog-title">Nộp hồ sơ trực tuyến</h2>
        <p class="apply-dialog__subtitle">Điền 2 phút, không cần tài khoản. Ban tuyển dụng gọi lại trong 24 giờ làm việc.</p>
      </div>
      <button type="button" class="apply-dialog__close" data-tg-apply-close aria-label="Đóng biểu mẫu ứng tuyển">
        <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">
      </button>
    </header>

    <div class="apply-dialog__body">
      <form id="apply-dialog-form" class="space-y-4" method="POST" action="<?= url('apply') ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="source" value="Dialog ứng tuyển">

        <div class="apply-dialog__alert" data-apply-alert role="alert" hidden></div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="form-label" for="apply-dialog-fullname">Họ và tên <span class="req">*</span></label>
            <input type="text" id="apply-dialog-fullname" name="full_name" required autocomplete="name" maxlength="150"
                   placeholder="Ví dụ: Lê Thị Mai" class="form-input">
            <p class="field-error" data-error-for="full_name"></p>
          </div>
          <div>
            <label class="form-label" for="apply-dialog-phone">Số điện thoại <span class="req">*</span></label>
            <input type="tel" id="apply-dialog-phone" name="phone" required autocomplete="tel" inputmode="tel"
                   placeholder="0912 345 678" class="form-input" data-phone-format>
            <p class="field-error" data-error-for="phone"></p>
          </div>
        </div>

        <fieldset>
          <legend class="form-label">Khu vực muốn làm việc <span class="req">*</span></legend>
          <div class="apply-branch-picker" data-apply-branches>
            <?php foreach ($applyBranches as $applyBranch): ?>
            <label class="apply-branch-option">
              <input type="radio" name="branch_slug" value="<?= e($applyBranch['slug']) ?>" required
                     data-branch-slug="<?= e($applyBranch['slug']) ?>">
              <span><?= e($applyBranch['name']) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <p class="form-hint">Chọn khu vực bạn muốn làm việc — danh sách vị trí bên dưới sẽ lọc theo khu vực đó.</p>
          <p class="field-error" data-error-for="branch_slug"></p>
        </fieldset>

        <div>
          <label class="form-label" for="apply-dialog-job">Vị trí mong muốn <span class="req">*</span></label>
          <select id="apply-dialog-job" name="job_slug" required class="form-input">
            <option value="">— Chọn khu vực trước —</option>
            <?php foreach ($dialogJobData as $dialogJobOption): ?>
            <option value="<?= e($dialogJobOption['slug']) ?>" data-branch="<?= e($dialogJobOption['branch']) ?>">
              <?= e($dialogJobOption['title']) ?><?= $dialogJobOption['salary'] !== '' ? ' — ' . e($dialogJobOption['salary']) : '' ?>
            </option>
            <?php endforeach; ?>
            <option value="khac">Vị trí khác — ghi rõ ở ghi chú</option>
          </select>
          <p class="form-hint" id="apply-dialog-job-hint">Chọn khu vực trước để xem vị trí đang tuyển tại khu đó.</p>
          <p class="field-error" data-error-for="job_slug"></p>
        </div>

        <div>
          <label class="form-label" for="apply-dialog-address">Địa chỉ hiện tại <span class="req">*</span></label>
          <input type="text" id="apply-dialog-address" name="address" required autocomplete="address-line1" maxlength="255"
                 placeholder="Ấp/Xã, Huyện Châu Thành, Tỉnh Đồng Tháp" class="form-input">
          <p class="form-hint">Giúp ban tuyển dụng xếp bạn vào tuyến xe đưa rước gần nhất.</p>
          <p class="field-error" data-error-for="address"></p>
        </div>

        <div>
          <label class="form-label" for="apply-dialog-cv">CV / hình hồ sơ (nếu có)</label>
          <input type="file" id="apply-dialog-cv" name="cv" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-input">
          <p class="form-hint">PDF, DOC, DOCX, JPG, PNG · tối đa 10MB</p>
          <p class="field-error" data-error-for="cv"></p>
        </div>

        <div>
          <label class="form-label" for="apply-dialog-message">Giới thiệu ngắn (không bắt buộc)</label>
          <textarea id="apply-dialog-message" name="message" rows="2" maxlength="2000" class="form-input"
                    placeholder="Kinh nghiệm may, vị trí từng làm, tuyến xe…"></textarea>
          <p class="field-error" data-error-for="message"></p>
        </div>

        <label class="flex cursor-pointer items-start gap-3 rounded-card bg-surface p-3 text-[14px] leading-[1.6] text-muted">
          <input type="checkbox" id="apply-dialog-terms" name="terms" required value="1"
                 class="mt-0.5 h-5 w-5 shrink-0 rounded border-line text-accent focus:ring-accent">
          <span>Tôi đồng ý để ban tuyển dụng TEX-GIANG liên hệ qua điện thoại hoặc Zalo.</span>
        </label>
        <p class="field-error" data-error-for="terms"></p>
      </form>
    </div>

    <footer class="apply-dialog__footer">
      <a class="apply-dialog__page-link" data-tg-apply-page-link href="<?= url('apply') ?>">Cần bản đầy đủ? Mở trang ứng tuyển</a>
      <div class="apply-dialog__actions">
        <button type="submit" class="tg-btn tg-btn--primary apply-dialog__submit" form="apply-dialog-form">
          <img src="<?= asset('images/icons/send.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">Gửi hồ sơ
        </button>
      </div>
    </footer>
  </div>
</dialog>

<script type="application/json" id="apply-dialog-jobs"><?= json_encode($dialogJobData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
