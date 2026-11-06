<?php
/**
 * Dialog ứng tuyển nhanh — hiển thị trên mọi trang công khai (trừ trang /apply).
 * Form POST thật về /apply, dùng cùng controller/validate với trang đầy đủ.
 */
$dialogJobs = $dialogJobs ?? \App\Models\Job::latestPublished(20);
$hotline = settings('hotline', '02733937274');
$hotlineDisplay = settings('hotline_display', '0273 393 7274');
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
      <form id="apply-dialog-form" class="space-y-4" method="POST" action="<?= url('apply') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="source" value="Dialog ứng tuyển">

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="form-label" for="apply-dialog-fullname">Họ và tên <span class="req">*</span></label>
            <input type="text" id="apply-dialog-fullname" name="full_name" required autocomplete="name" maxlength="150"
                   placeholder="Ví dụ: Lê Thị Mai" class="form-input">
          </div>
          <div>
            <label class="form-label" for="apply-dialog-phone">Số điện thoại <span class="req">*</span></label>
            <input type="tel" id="apply-dialog-phone" name="phone" required autocomplete="tel" inputmode="tel"
                   placeholder="0912 345 678" class="form-input">
          </div>
        </div>

        <div>
          <label class="form-label" for="apply-dialog-job">Vị trí mong muốn <span class="req">*</span></label>
          <select id="apply-dialog-job" name="job_slug" required class="form-input">
            <option value="">— Chọn vị trí —</option>
            <?php foreach ($dialogJobs as $job): ?>
            <option value="<?= e($job['slug']) ?>">
              <?= e(preg_replace('/^\d+\s+/', '', (string) $job['title'])) ?><?= $job['salary_text'] ? ' — ' . e(salary_short((string) $job['salary_text'])) : '' ?>
            </option>
            <?php endforeach; ?>
            <option value="khac">Vị trí khác — ghi rõ ở ghi chú</option>
          </select>
        </div>

        <div>
          <label class="form-label" for="apply-dialog-address">Địa chỉ hiện tại <span class="req">*</span></label>
          <input type="text" id="apply-dialog-address" name="address" required autocomplete="address-line1" maxlength="255"
                 placeholder="Ấp/Xã, Huyện Châu Thành, Tỉnh Đồng Tháp" class="form-input">
          <p class="form-hint">Giúp ban tuyển dụng xếp bạn vào tuyến xe đưa rước gần nhất.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="form-label" for="apply-dialog-email">Email (nếu có)</label>
            <input type="email" id="apply-dialog-email" name="email" autocomplete="email" maxlength="190"
                   placeholder="email@gmail.com" class="form-input">
          </div>
          <div>
            <label class="form-label" for="apply-dialog-cv">CV / hình hồ sơ (nếu có)</label>
            <input type="file" id="apply-dialog-cv" name="cv" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-input">
            <p class="form-hint">PDF, DOC, DOCX, JPG, PNG · tối đa 10MB</p>
          </div>
        </div>

        <div>
          <label class="form-label" for="apply-dialog-message">Giới thiệu ngắn (không bắt buộc)</label>
          <textarea id="apply-dialog-message" name="message" rows="2" maxlength="2000" class="form-input"
                    placeholder="Kinh nghiệm may, vị trí từng làm, tuyến xe…"></textarea>
        </div>

        <label class="flex cursor-pointer items-start gap-3 rounded-card bg-surface p-3 text-[14px] leading-[1.6] text-muted">
          <input type="checkbox" id="apply-dialog-terms" name="terms" required value="1"
                 class="mt-0.5 h-5 w-5 shrink-0 rounded border-line text-accent focus:ring-accent">
          <span>Tôi đồng ý để ban tuyển dụng TEX-GIANG liên hệ qua điện thoại hoặc Zalo.</span>
        </label>
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
