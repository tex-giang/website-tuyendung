/**
 * TEX-GIANG RECRUITMENT — DIALOG ỨNG TUYỂN DÙNG CHUNG
 *
 * Mọi link ứng tuyển trên trang public (`apply.html`) đều được chặn để mở
 * biểu mẫu trong <dialog> kiểu Bootstrap (tự dựng CSS/JS, không thư viện,
 * chạy được file://). Link gốc vẫn giữ trong HTML nên khi trình duyệt không
 * hỗ trợ <dialog> hoặc JS lỗi, người dùng vẫn mở được trang như trước.
 *
 * Riêng link "Cần bản đầy đủ? Mở trang ứng tuyển" trong dialog được bỏ qua
 * để vẫn điều hướng tới trang ứng tuyển đầy đủ `apply.html`.
 * `ung-tuyen.html` là form độc lập, không đi qua dialog.
 *
 * Trên apply.html, biểu mẫu đã có sẵn trên trang nên link ứng tuyển chỉ cuộn
 * tới form thay vì mở dialog (tránh trùng ID).
 */
(function () {
  'use strict';

  var state = {
    dialog: null,
    mountEl: null,
    footerSubmit: null,
    form: null,
    opener: null,
    inline: false,
    inlineMount: null,
    inlineForm: null
  };

  function supportsDialog() {
    return typeof HTMLDialogElement !== 'undefined' &&
      typeof HTMLDialogElement.prototype.showModal === 'function';
  }

  function isApplyUrl(url) {
    return /(^|\/)apply\.html$/.test(url.pathname);
  }

  /**
   * Đọc dữ liệu điền sẵn từ link ứng tuyển.
   * - `job`  : tiêu đề tin (khớp mềm sang ô vị trí)
   * - `vai-tri` / `position`: mã vị trí chính xác
   * - `khu`  / `company`    : mã khu sản xuất
   */
  function applyDataFromUrl(url) {
    var params = url.searchParams;
    return {
      job: params.get('job'),
      company: params.get('company') || params.get('khu'),
      position: params.get('vai-tri') || params.get('position'),
      location: params.get('khu')
    };
  }

  function parseUrl(href) {
    try { return new URL(href, window.location.href); } catch (_) { return null; }
  }

  function focusCloseButton() {
    var closeButton = state.dialog && state.dialog.querySelector('.apply-dialog__close');
    if (closeButton) closeButton.focus();
  }

  function dialogMarkup() {
    return [
      '<div class="apply-dialog__content">',
      '  <header class="apply-dialog__header">',
      '    <div class="apply-dialog__heading">',
      '      <p class="apply-dialog__eyebrow">TEX-GIANG</p>',
      '      <h2 class="apply-dialog__title" id="apply-dialog-title">Nộp hồ sơ trực tuyến</h2>',
      '      <p class="apply-dialog__subtitle">Điền 2 phút, không cần tài khoản. Ban tuyển dụng gọi lại trong 24 giờ làm việc.</p>',
      '    </div>',
      '    <button type="button" class="apply-dialog__close" data-tg-apply-close aria-label="Đóng biểu mẫu ứng tuyển">',
      '      <img src="assets/images/icons/x-lg.svg" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">',
      '    </button>',
      '  </header>',
      '  <div class="apply-dialog__body" data-tg-apply-mount></div>',
      '  <footer class="apply-dialog__footer">',
      '    <a class="apply-dialog__page-link" data-tg-apply-page-link href="apply.html">Cần bản đầy đủ? Mở trang ứng tuyển</a>',
      '    <div class="apply-dialog__actions">',
      '      <button type="submit" class="tg-btn tg-btn--primary apply-dialog__submit" form="online-apply-form">',
      '        <img src="assets/images/icons/send.svg" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">Gửi hồ sơ',
      '      </button>',
      '    </div>',
      '  </footer>',
      '</div>'
    ].join('\n');
  }

  function successMarkup(positionText, companyText) {
    var detail = [];
    if (positionText) detail.push('<span><strong>Vị trí:</strong> ' + positionText + '</span>');
    if (companyText) detail.push('<span><strong>Đơn vị:</strong> ' + companyText + '</span>');
    return [
      '<div class="apply-success" role="status" aria-live="polite">',
      '  <span class="apply-success__icon">',
      '    <img src="assets/images/icons/check-circle-fill.svg" alt="" aria-hidden="true" width="40" height="40">',
      '  </span>',
      '  <h3 class="apply-success__title">Đã gửi hồ sơ (bản demo)</h3>',
      '  <p class="apply-success__text">Cảm ơn bạn. Hồ sơ đã được ghi vào <strong>hệ thống quản trị (bản demo)</strong> và sẽ hiện trong mục Hồ sơ ứng viên; nhân sự TEX-GIANG gọi lại trong 24 giờ làm việc khi hệ thống thật được kết nối.</p>',
      detail.length ? '  <p class="apply-success__detail">' + detail.join('') + '</p>' : '',
      '  <p class="apply-success__hint">Bấm nút <strong>×</strong> ở góc trên để đóng cửa sổ này.</p>',
      '  <div class="apply-success__actions">',
      '    <button type="button" class="tg-btn tg-btn--ghost" data-tg-apply-restart>Nộp hồ sơ khác</button>',
      '  </div>',
      '</div>'
    ].join('\n');
  }

  function ensureDialog() {
    if (state.dialog) return state.dialog;

    var dialog = document.createElement('dialog');
    dialog.className = 'apply-dialog';
    dialog.id = 'apply-dialog';
    dialog.setAttribute('aria-labelledby', 'apply-dialog-title');
    dialog.innerHTML = dialogMarkup();
    document.body.appendChild(dialog);

    state.dialog = dialog;
    state.mountEl = dialog.querySelector('[data-tg-apply-mount]');
    state.footerSubmit = dialog.querySelector('.apply-dialog__submit');

    dialog.addEventListener('click', function (event) {
      // Chỉ nút × mới đóng được dialog.
      if (event.target.closest('[data-tg-apply-close]')) { close(); return; }
      if (event.target.closest('[data-tg-apply-restart]')) {
        resetDialog();
        var first = state.form && state.form.querySelector('#apply-fullname');
        if (first) first.focus();
        return;
      }
      // Bấm vùng nền: không đóng, chỉ đưa focus về nút × để gợi ý.
      if (event.target === dialog) focusCloseButton();
    });

    // Escape: chặn đóng mặc định; chỉ nút × mới đóng được.
    dialog.addEventListener('cancel', function (event) {
      event.preventDefault();
      focusCloseButton();
    });

    dialog.addEventListener('close', function () {
      document.body.classList.remove('has-dialog');
      // Trang apply.html dùng dialog với chính form trên trang: trả form về chỗ cũ.
      if (state.inline && state.inlineForm && state.inlineForm.parentElement === state.mountEl) {
        state.inlineMount.appendChild(state.inlineForm);
        state.form = state.inlineForm;
      }
      if (state.opener && document.contains(state.opener)) state.opener.focus();
      state.opener = null;
    });

    if (!state.inline) mountForm();
    return dialog;
  }

  function mountForm(prefill) {
    state.form = window.TGApplyForm.mount(state.mountEl, {
      submitButton: state.footerSubmit,
      onSuccess: onFormSuccess,
      prefill: prefill
    });
    return state.form;
  }

  function resetDialog() {
    var dialog = ensureDialog();
    dialog.classList.remove('is-success');
    state.mountEl.innerHTML = '';
    state.mountEl.removeAttribute('data-tg-apply-mounted');
    mountForm();
  }

  function onFormSuccess(form) {
    var position = form.querySelector('#apply-position');
    var company = form.querySelector('#apply-company');
    var positionText = position && position.selectedIndex > 0 ? position.options[position.selectedIndex].text : '';
    var companyText = company && company.selectedIndex > -1 ? company.options[company.selectedIndex].text : '';

    state.mountEl.innerHTML = successMarkup(positionText, companyText);
    state.dialog.classList.add('is-success');
    var primary = state.mountEl.querySelector('[data-tg-apply-close]');
    if (primary) primary.focus();
  }

  function updatePageLink(data) {
    var pageLink = state.dialog && state.dialog.querySelector('[data-tg-apply-page-link]');
    if (!pageLink) return;
    var params = new URLSearchParams();
    if (data && data.job) params.set('job', data.job);
    if (data && data.position) params.set('vai-tri', data.position);
    if (data && data.location) params.set('khu', data.location);
    var query = params.toString();
    pageLink.href = 'apply.html' + (query ? '?' + query : '');
  }

  function open(data, trigger) {
    if (!supportsDialog()) return;
    if (!window.TGApplyForm) return;

    var dialog = ensureDialog();
    if (dialog.classList.contains('is-success')) resetDialog();
    else if (state.form && !window.TGApplyForm.isDirty(state.form)) window.TGApplyForm.prefill(state.form, data || {});

    updatePageLink(data);
    state.opener = trigger || document.activeElement;
    document.body.classList.add('has-dialog');
    if (!dialog.open) dialog.showModal();
    focusCloseButton();
  }

  /**
   * Trang apply.html: mở dialog nhưng dùng chính form đang có trên trang
   * (chuyển vào dialog khi mở, trả về chỗ cũ khi đóng) để không trùng ID.
   */
  function openInline(data, trigger) {
    if (!supportsDialog() || !window.TGApplyForm) return;

    var dialog = ensureDialog();
    if (state.inlineForm && state.inlineForm.parentElement !== state.mountEl) {
      state.mountEl.appendChild(state.inlineForm);
      state.form = state.inlineForm;
      dialog.classList.add('is-inline');
      // Form trên trang có thể đang chờ hiệu ứng hiện dần: bỏ qua để không bị mờ trong dialog.
      var reveal = state.inlineForm.querySelectorAll('[data-reveal]');
      for (var i = 0; i < reveal.length; i++) {
        reveal[i].style.opacity = '1';
        reveal[i].style.transform = 'none';
      }
    }
    if (state.form && !window.TGApplyForm.isDirty(state.form)) {
      window.TGApplyForm.prefill(state.form, data || {});
    }

    updatePageLink(data);
    state.opener = trigger || document.activeElement;
    document.body.classList.add('has-dialog');
    if (!dialog.open) dialog.showModal();
    focusCloseButton();
  }

  function close() {
    document.body.classList.remove('has-dialog');
    if (state.dialog && state.dialog.open) state.dialog.close();
  }

  function decorateLinks() {
    var links = document.querySelectorAll('a[href]');
    for (var i = 0; i < links.length; i++) {
      var link = links[i];
      if (link.hasAttribute('data-tg-apply-page-link')) continue;
      var url = parseUrl(link.getAttribute('href'));
      if (!url || !isApplyUrl(url)) continue;
      link.setAttribute('aria-haspopup', 'dialog');
    }
  }

  function onClick(event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (!event.target.closest) return;
    var link = event.target.closest('a[href]');
    if (!link || (link.target && link.target !== '_self') || link.hasAttribute('download')) return;
    // Link "Mở trang ứng tuyển" trong dialog phải điều hướng bình thường.
    if (link.hasAttribute('data-tg-apply-page-link')) return;
    var url = parseUrl(link.getAttribute('href'));
    if (!url || !isApplyUrl(url)) return;
    if (!supportsDialog() || !window.TGApplyForm) return;

    event.preventDefault();

    if (state.inline) {
      openInline(applyDataFromUrl(url), link);
      return;
    }
    open(applyDataFromUrl(url), link);
  }

  function init() {
    var inlineMount = document.querySelector('[data-tg-apply-inline]');
    if (inlineMount && window.TGApplyForm) {
      state.inline = true;
      state.inlineMount = inlineMount;
      window.TGApplyForm.mount(inlineMount);
      state.inlineForm = inlineMount.querySelector('#online-apply-form');
      state.form = state.inlineForm;
    }
    decorateLinks();
    // Ủy quyền sự kiện: bắt cả link do jobs.js/job-detail.js render sau đó.
    document.addEventListener('click', onClick);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  window.TGApplyDialog = { open: open, openInline: openInline, close: close };
})();
