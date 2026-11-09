<?php
/** Trang báo lỗi gửi hồ sơ. */
$message = $message ?? 'Có lỗi khi gửi hồ sơ. Vui lòng thử lại.';
?>
<div class="tg-container">
  <div class="mx-auto max-w-[640px]">
    <h1 class="mt-3 text-[clamp(2.25rem,5vw,4.5rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
      Hồ sơ chưa được gửi
    </h1>

    <p class="mt-4 text-[18px] leading-[1.6] text-muted"><?= e($message) ?></p>

    <div class="mt-10 rounded-card bg-surface p-6 lg:p-8">
      <h2 class="text-[16px] font-medium text-ink">Thử theo thứ tự này</h2>
      <ul class="mt-4 space-y-4 text-[16px] leading-[1.6] text-muted">
        <li><span class="block font-medium text-body">Kiểm tra tệp CV</span> Chỉ nhận PDF, DOC, DOCX, JPG, PNG và tối đa 10MB.</li>
        <li><span class="block font-medium text-body">Gửi lại</span> Bấm nút bên dưới, điền lại nhanh các trường bắt buộc.</li>
        <li><span class="block font-medium text-body">Vẫn lỗi?</span> Liên hệ người phụ trách theo khu vực tại
          <a href="<?= url('contact') ?>" class="text-body underline underline-offset-2 hover:text-accent">trang Liên hệ</a>
          để nhân sự nhận hồ sơ trực tiếp.</li>
      </ul>
    </div>

    <div class="mt-10 flex flex-wrap gap-3">
      <a href="<?= url('apply') ?>" class="tg-btn tg-btn--primary">Gửi lại hồ sơ</a>
      <a href="<?= url('contact') ?>" class="tg-btn tg-btn--ghost">Liên hệ ban tuyển dụng</a>
    </div>
  </div>
  <img class="notice-art" src="<?= asset('images/application-error.svg') ?>" width="400" height="330" alt="" aria-hidden="true">
</div>
