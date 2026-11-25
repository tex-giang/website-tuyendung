<?php
/**
 * Dialog "Liên hệ tuyển dụng" dùng cho trang độc lập (/tuyen-dung):
 * liệt kê người phụ trách 3 khu sản xuất, số điện thoại bấm gọi được (tel:).
 *
 * Tự lấy dữ liệu từ model nên include được ở bất kỳ layout nào.
 * Nút mở: phần tử có thuộc tính data-open-contact-dialog.
 */
$dialogBranches = $branches ?? \App\Models\Branch::active();
$dialogContacts = $branchContacts ?? \App\Models\BranchContact::activeGrouped();
$dialogZalo = settings('zalo');
$dialogMessenger = messenger_url();
?>
<div id="contact-dialog" class="tg-modal hidden" role="dialog" aria-modal="true" aria-labelledby="contact-dialog-title" data-contact-dialog>
  <div class="relative flex max-h-[min(90vh,640px)] w-full max-w-[420px] flex-col overflow-hidden rounded-2xl bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
      <h2 id="contact-dialog-title" class="text-[17px] font-medium leading-tight text-ink">Liên hệ ban tuyển dụng</h2>
      <button type="button" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-muted transition-colors hover:bg-surface hover:text-ink" data-close-contact-dialog aria-label="Đóng hộp thoại liên hệ">
        <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="18" height="18" class="tg-icon">
      </button>
    </div>

    <div class="flex-1 space-y-2 overflow-y-auto p-3">
      <?php foreach ($dialogBranches as $dialogBranch): ?>
      <?php $contacts = $dialogContacts[(int) $dialogBranch['id']] ?? []; ?>
      <section class="rounded-card border border-line px-3.5 py-2.5">
        <p class="text-[13px] font-medium leading-snug text-ink">Nhà máy <?= e($dialogBranch['name']) ?></p>
        <?php if ($contacts !== []): ?>
        <ul class="mt-1.5 space-y-1">
          <?php foreach ($contacts as $contact): ?>
          <li class="flex items-baseline justify-between gap-3">
            <span class="min-w-0 truncate text-[13px] text-muted">
              <?= e($contact['full_name']) ?><?php if (!empty($contact['title'])): ?><span class="text-[12px] text-muted"> — <?= e((string) $contact['title']) ?></span><?php endif; ?>
            </span>
            <a href="tel:<?= e(\App\Models\BranchContact::telHref((string) $contact['phone'])) ?>"
               class="shrink-0 whitespace-nowrap text-[14px] font-semibold text-accent transition-colors hover:text-accent-strong"><?= e($contact['phone']) ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="mt-1 text-[13px] text-muted">Đang cập nhật</p>
        <?php endif; ?>
      </section>
      <?php endforeach; ?>
      <nav class="flex flex-wrap gap-2 pt-2" aria-label="Kênh liên hệ trực tuyến">
        <?php if ($dialogMessenger !== ''): ?>
        <a class="tg-btn tg-btn--ghost" href="<?= e($dialogMessenger) ?>" target="_blank" rel="noopener">Messenger</a>
        <?php endif; ?>
        <?php if ($dialogZalo !== ''): ?>
        <a class="tg-btn tg-btn--ghost" href="<?= e(zalo_url()) ?>" target="_blank" rel="noopener">Zalo</a>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</div>

<script>
(function () {
  var dialog = document.querySelector('[data-contact-dialog]');
  if (!dialog) return;

  var opener = null;
  function open() {
    dialog.classList.remove('hidden');
    document.body.classList.add('has-dialog');
    var first = dialog.querySelector('[data-close-contact-dialog]');
    if (first) first.focus();
  }
  function close() {
    dialog.classList.add('hidden');
    document.body.classList.remove('has-dialog');
    if (opener) opener.focus();
  }

  // Uỷ quyền click: nút mở có thể nằm bất kỳ đâu trong trang.
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-open-contact-dialog]');
    if (!trigger) return;
    event.preventDefault();
    if (!dialog.classList.contains('hidden')) return;
    opener = trigger;
    open();
  });
  dialog.querySelectorAll('[data-close-contact-dialog]').forEach(function (button) {
    button.addEventListener('click', close);
  });
  dialog.addEventListener('click', function (event) {
    if (event.target === dialog) close();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !dialog.classList.contains('hidden')) close();
  });
})();
</script>
