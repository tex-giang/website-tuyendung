/**
 * AJAX quản trị trên các route PHP hiện tại. POST nhận JSON kết quả;
 * GET có X-Admin-Fragment nhận nội dung đã render để cập nhật khung chính.
 */
(function () {
  'use strict';

  var hasSwal = typeof window.Swal !== 'undefined';
  var nextErrorId = 0;

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function fieldErrorHtml(errors) {
    var keys = Object.keys(errors || {});
    if (!keys.length) return '';
    var lis = keys.map(function (key) {
      return '<li>' + esc(errors[key]) + '</li>';
    }).join('');
    return '<ul class="tg-swal-errors">' + lis + '</ul>';
  }

  function confirmDialog(message) {
    if (!hasSwal) return Promise.resolve(window.confirm(message));
    var text = String(message || 'Bạn chắc chắn muốn thực hiện thao tác này?')
      .replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    return Swal.fire({
      icon: 'warning',
      title: 'Xác nhận thao tác',
      text: text,
      showCancelButton: true,
      confirmButtonText: 'Đồng ý',
      cancelButtonText: 'Huỷ',
      confirmButtonColor: '#087a55',
      cancelButtonColor: '#6b7280',
      reverseButtons: true,
      focusCancel: true
    }).then(function (result) { return result.isConfirmed; });
  }

  function errorDialog(message, errors, title = 'Không lưu được') {
    if (!hasSwal) {
      var detail = Object.keys(errors || {}).map(function (k) { return errors[k]; }).join('\n');
      window.alert((message || 'Có lỗi xảy ra.') + (detail ? '\n\n' + detail : ''));
      return;
    }
    return Swal.fire({
      icon: 'error',
      title: title,
      html: '<p class="tg-swal-msg">' + esc(message || 'Có lỗi xảy ra.') + '</p>' + fieldErrorHtml(errors),
      confirmButtonText: 'Đã hiểu',
      confirmButtonColor: '#087a55'
    });
  }

  function clearFieldErrors(form) {
    form.querySelectorAll('[data-ajax-error]').forEach(function (input) {
      var id = input.dataset.ajaxError;
      var descriptions = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(function (value) {
        return value && value !== id;
      });
      if (descriptions.length) input.setAttribute('aria-describedby', descriptions.join(' '));
      else input.removeAttribute('aria-describedby');
      input.removeAttribute('aria-invalid');
      delete input.dataset.ajaxError;
      document.getElementById(id)?.remove();
    });
  }

  function showFieldErrors(form, errors) {
    var first = null;
    var remaining = {};
    Object.entries(errors || {}).forEach(function ([name, message]) {
      var input = form.elements.namedItem(name);
      var field = input instanceof HTMLElement && input.closest('.field');
      if (!field || !input.getClientRects().length) {
        remaining[name] = message;
        return;
      }
      var error = document.createElement('span');
      error.id = 'admin-field-error-' + ++nextErrorId;
      error.className = 'field__error';
      error.textContent = message;
      input.dataset.ajaxError = error.id;
      input.setAttribute('aria-invalid', 'true');
      var describedBy = input.getAttribute('aria-describedby');
      input.setAttribute('aria-describedby', describedBy ? describedBy + ' ' + error.id : error.id);
      field.append(error);
      if (!first) first = input;
    });
    return { first: first, remaining: remaining };
  }

  const main = document.getElementById('admin-content');
  const announcement = document.getElementById('admin-ajax-announcement');
  const base = document.body.dataset.adminBase;
  const ajaxHeaders = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
  let reading = null;
  let writing = false;
  let renderedUrl = new URL(window.location.href);

  function adminUrl(value) {
    if (typeof value !== 'string' || !value) return null;
    const url = new URL(value, window.location.href);
    return url.origin === window.location.origin
      && (url.pathname === base || url.pathname.startsWith(base + '/')) ? url : null;
  }

  function announce(message) {
    if (announcement) announcement.textContent = message;
  }

  function enhanceForms(root) {
    root.querySelectorAll('form[data-ajax]').forEach(form => { form.noValidate = true; });
  }

  async function request(url, options = {}) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      mode: 'same-origin',
      cache: 'no-store',
      ...options,
      headers: { ...ajaxHeaders, ...options.headers },
    });
    if (!response.headers.get('content-type')?.includes('application/json')) {
      throw new Error('Phản hồi không hợp lệ (HTTP ' + response.status + ').');
    }
    return { response, data: await response.json() };
  }

  function updateNav(data) {
    document.querySelectorAll('[data-admin-nav]').forEach(link => {
      const key = link.dataset.adminNav;
      const active = key === data.nav;
      link.classList.toggle('is-active', active);
      if (active) link.setAttribute('aria-current', 'page');
      else link.removeAttribute('aria-current');

      const count = link.querySelector('[data-admin-count]');
      if (count) {
        const value = Number(data.counts[key]) || 0;
        count.textContent = value;
        count.parentElement.hidden = value === 0;
      }
    });
  }

  async function renderFragment(target, { signal, push = true, focus = true } = {}) {
    const { response, data } = await request(target, {
      signal,
      headers: { 'X-Admin-Fragment': '1' },
    });
    if (!response.ok) {
      const error = new Error(data.message || 'Không tải được nội dung quản trị.');
      error.payload = data;
      throw error;
    }
    if (data.fragment !== true) return false;
    if (signal?.aborted) return false;

    const destination = adminUrl(response.url);
    if (!destination || typeof data.content !== 'string') throw new Error('Nội dung quản trị không hợp lệ.');
    destination.hash = target.hash;
    window.TGDataTable?.destroy(main);
    main.innerHTML = data.content;
    main.setAttribute('data-admin-fragment', '');
    document.title = data.title;
    updateNav(data);
    if (push && destination.href !== window.location.href) {
      history.pushState({ tgAdminFragment: true }, '', destination);
    } else {
      history.replaceState({ ...history.state, tgAdminFragment: true }, '', destination);
    }
    renderedUrl = destination;
    enhanceForms(main);
    window.TGDataTable?.init();
    if (focus) {
      const heading = main.querySelector('h1') || main;
      heading.setAttribute('tabindex', '-1');
      heading.focus();
    }
    return true;
  }

  async function requestLogin(data) {
    const target = adminUrl(data.redirect);
    if (target && await confirmDialog(data.message)) window.location.assign(target);
  }

  async function loadView(target, fromHistory = false) {
    reading?.abort();
    const controller = new AbortController();
    reading = controller;
    main.setAttribute('aria-busy', 'true');
    announce('Đang tải dữ liệu…');
    try {
      const rendered = await renderFragment(target, { signal: controller.signal, push: !fromHistory });
      if (controller.signal.aborted) return;
      if (!rendered) {
        window.location.assign(target);
        return;
      }
      announce('Đã cập nhật kết quả.');
    } catch (error) {
      if (controller.signal.aborted) return;
      announce('');
      if (error.payload?.code === 'auth_required') {
        await requestLogin(error.payload);
      } else if (fromHistory) {
        window.location.reload();
      } else {
        await errorDialog(error.payload?.message || 'Vui lòng thử lại.', null, 'Không tải được dữ liệu');
      }
    } finally {
      if (reading === controller) {
        reading = null;
        main.removeAttribute('aria-busy');
      }
    }
  }

  function showSuccess(message) {
    if (hasSwal) {
      Swal.fire({ toast: true, position: 'top-end', icon: 'success', titleText: message, timer: 3000, showConfirmButton: false });
    } else {
      announce(message);
    }
  }

  async function showFailure(form, data) {
    if (data.code === 'auth_required') {
      await requestLogin(data);
      return;
    }
    const validation = showFieldErrors(form, data.errors);
    if (form.classList.contains('auth-card') && !validation.first) {
      const alert = document.createElement('p');
      alert.className = 'admin-alert admin-alert--error';
      alert.setAttribute('role', 'alert');
      alert.setAttribute('data-ajax-notice', '');
      alert.textContent = data.message;
      form.querySelector('.auth-sub').after(alert);
    } else if (!validation.first || Object.keys(validation.remaining).length) {
      await errorDialog(data.message, validation.remaining);
    }
    validation.first?.focus();
  }

  async function submitAjax(form, submitter, action) {
    clearFieldErrors(form);
    form.querySelectorAll('[data-ajax-notice]').forEach(node => node.remove());
    const body = submitter ? new FormData(form, submitter) : new FormData(form);
    const button = submitter || form.querySelector('[type="submit"]');
    const originalHtml = button instanceof HTMLButtonElement ? button.innerHTML : '';
    const iconButton = button?.classList.contains('btn-icon');
    const originalLabel = iconButton ? button.getAttribute('aria-label') : null;
    const active = document.activeElement;
    const autosubmit = form.querySelector('select[data-submit-onchange]');
    form.inert = true;
    form.setAttribute('aria-busy', 'true');
    announce('Đang xử lý…');
    if (button instanceof HTMLButtonElement) {
      button.classList.add('is-loading');
      if (iconButton) button.setAttribute('aria-label', 'Đang xử lý');
      button.innerHTML = '<span class="tg-spinner" aria-hidden="true"></span>' + (iconButton ? '' : ' Đang xử lý…');
    }

    let success;
    try {
      const { response, data } = await request(action, { method: 'POST', body });
      if (response.ok && data.ok === true) {
        success = data;
      } else {
        if (autosubmit) {
          autosubmit.selectedIndex = Math.max(0, Array.from(autosubmit.options).findIndex(option => option.defaultSelected));
        }
        form.inert = false;
        if (form.contains(active)) active.focus();
        await showFailure(form, data);
      }
    } catch (error) {
      form.inert = false;
      if (form.contains(active)) active.focus();
      await showFailure(form, { message: 'Chưa nhận được kết quả. Kiểm tra lại dữ liệu trước khi gửi lại.' });
    } finally {
      form.inert = false;
      form.removeAttribute('aria-busy');
      announce('');
      if (button instanceof HTMLButtonElement) {
        button.classList.remove('is-loading');
        button.innerHTML = originalHtml;
        if (iconButton && originalLabel === null) button.removeAttribute('aria-label');
        else if (iconButton) button.setAttribute('aria-label', originalLabel);
      }
    }
    if (!success) return;

    const target = form.hasAttribute('data-ajax-refresh')
      ? renderedUrl
      : adminUrl(success.redirect);
    const message = success.message || 'Đã lưu thành công.';
    if (!target) {
      showSuccess(message);
      return;
    }
    if (!main || form.hasAttribute('data-ajax-navigate')) {
      window.location.assign(target);
      return;
    }

    const samePage = target.pathname === renderedUrl.pathname && target.search === renderedUrl.search;
    main.setAttribute('aria-busy', 'true');
    try {
      if (await renderFragment(target, { focus: !samePage })) {
        if (samePage && form.contains(active)) {
          // Form cũ đã được thay bằng dữ liệu mới. Giữ vị trí cuộn khi khôi phục focus.
          main.focus({ preventScroll: true });
        }
        showSuccess(message);
        return;
      }
    } catch (error) {
      if (error.payload?.code === 'auth_required') {
        await requestLogin(error.payload);
        return;
      }
    } finally {
      main.removeAttribute('aria-busy');
    }
    // POST đã thành công. Chỉ điều hướng GET khi không thể làm mới khung nội dung.
    window.location.assign(target);
  }

  document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const action = adminUrl(form.action);
    if (!action) return;

    if (main?.hasAttribute('data-admin-fragment') && form.matches('.admin-filters, .admin-period-form')) {
      event.preventDefault();
      if (!writing) {
        action.search = new URLSearchParams(new FormData(form)).toString();
        void loadView(action);
      }
      return;
    }

    const ajax = form.hasAttribute('data-ajax');
    const confirmation = form.getAttribute('data-confirm');
    if (!ajax && !confirmation) return;
    event.preventDefault();
    if (writing) return;
    writing = true;
    reading?.abort();
    try {
      if (confirmation && !await confirmDialog(confirmation)) return;
      if (ajax) await submitAjax(form, event.submitter, action);
      else form.submit();
    } finally {
      writing = false;
    }
  });

  document.addEventListener('change', event => {
    const select = event.target;
    if (select instanceof HTMLSelectElement && select.matches('[data-submit-onchange]')) {
      select.form?.requestSubmit();
    }
  });

  main?.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) return;
    const link = event.target.closest('.admin-pager a, .admin-filters a');
    if (!link || link.target || link.hasAttribute('download')) return;
    const target = adminUrl(link.href);
    if (!target) return;
    event.preventDefault();
    if (!writing) void loadView(target);
  });

  if (main?.hasAttribute('data-admin-fragment')) {
    history.replaceState({ ...history.state, tgAdminFragment: true }, '');
  }
  if (main) {
    window.addEventListener('popstate', event => {
      const target = new URL(window.location.href);
      if (target.pathname === renderedUrl.pathname && target.search === renderedUrl.search) return;
      if (writing || !event.state?.tgAdminFragment) {
        window.location.reload();
      } else {
        void loadView(target, true);
      }
    });
  }
  enhanceForms(document);
})();
