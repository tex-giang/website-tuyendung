/**
 * TEX-GIANG — DIALOG ỨNG TUYỂN DÙNG CHUNG (bản PHP)
 * Mọi link tới /apply trên trang public được chặn để mở form trong <dialog>.
 * Form là POST thật về /apply; nếu JS lỗi hoặc trình duyệt không hỗ trợ <dialog>,
 * người dùng vẫn mở được trang ứng tuyển đầy đủ như bình thường.
 */
(function () {
  'use strict';

  var dialog = document.getElementById('apply-dialog');
  var form = document.getElementById('apply-dialog-form');
  if (!dialog || !form) return;

  var supportsDialog = typeof HTMLDialogElement !== 'undefined' &&
    typeof HTMLDialogElement.prototype.showModal === 'function';
  if (!supportsDialog) return;

  var opener = null;

  function isApplyUrl(url) {
    var path = url.pathname.replace(/\/+$/, '');
    return /\/apply$/.test(path);
  }

  function focusClose() {
    var close = dialog.querySelector('[data-tg-apply-close]');
    if (close) close.focus();
  }

  function prefillFromLink(data) {
    var jobSelect = form.querySelector('#apply-dialog-job');
    if (jobSelect && data.job) {
      var matched = false;
      for (var i = 0; i < jobSelect.options.length; i++) {
        if (jobSelect.options[i].value === data.job) {
          jobSelect.selectedIndex = i;
          matched = true;
          break;
        }
      }
      if (!matched) jobSelect.value = 'khac';
    }
  }

  function open(data, trigger) {
    opener = trigger || document.activeElement;
    prefillFromLink(data || {});
    document.body.classList.add('has-dialog');
    if (!dialog.open) dialog.showModal();
    focusClose();
  }

  function close() {
    document.body.classList.remove('has-dialog');
    if (dialog.open) dialog.close();
  }

  dialog.addEventListener('click', function (event) {
    if (event.target.closest('[data-tg-apply-close]')) {
      close();
      return;
    }
    // Backdrop: đưa focus về nút đóng thay vì đóng ngay (tránh mất dữ liệu đã điền).
    if (event.target === dialog) focusClose();
  });

  dialog.addEventListener('cancel', function (event) {
    event.preventDefault();
    close();
  });

  dialog.addEventListener('close', function () {
    document.body.classList.remove('has-dialog');
    if (opener && document.contains(opener)) opener.focus();
    opener = null;
  });

  // Đóng dialog ngay sau khi form gửi thành công? Form POST điều hướng sang trang
  // /apply/success nên không cần xử lý thêm; submit tự nhiên của form được giữ nguyên.

  function parseUrl(href) {
    try { return new URL(href, window.location.href); } catch (_) { return null; }
  }

  document.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (!event.target.closest) return;
    var link = event.target.closest('a[href]');
    if (!link || (link.target && link.target !== '_self') || link.hasAttribute('download')) return;
    if (link.hasAttribute('data-tg-apply-page-link')) return;

    var url = parseUrl(link.getAttribute('href'));
    if (!url || !isApplyUrl(url)) return;

    event.preventDefault();
    open({ job: url.searchParams.get('job') || '' }, link);
  });

  window.TGApplyDialog = { open: open, close: close };
})();
