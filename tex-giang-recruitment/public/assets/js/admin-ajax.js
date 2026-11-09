/**
 * TEX-GIANG — AJAX cho form quản trị + thông báo SweetAlert2
 *
 * Cách dùng trong view:
 *   <form method="POST" action="..." data-ajax>            → gửi AJAX, hiện SweetAlert2
 *   <form ... data-ajax data-ajax-reload>                  → xong thì tải lại trang hiện tại
 *   <form ... data-confirm="...">                          → hộp thoại xác nhận SweetAlert2
 *   <form ... data-ajax data-confirm="...">                → xác nhận rồi gửi AJAX
 *   <select data-submit-onchange> trong form data-ajax     → đổi là gửi AJAX luôn
 *
 * Server trả JSON {ok, message, errors, redirect}: thành công hiện hộp thoại xanh rồi
 * điều hướng; lỗi hiện hộp thoại đỏ kèm danh sách lỗi từng trường.
 * Form không có JS vẫn POST thường như cũ (progressive enhancement).
 */
(function () {
  'use strict';

  var hasSwal = typeof window.Swal !== 'undefined';

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

  function errorDialog(message, errors) {
    if (!hasSwal) {
      var detail = Object.keys(errors || {}).map(function (k) { return errors[k]; }).join('\n');
      window.alert((message || 'Có lỗi xảy ra.') + (detail ? '\n\n' + detail : ''));
      return;
    }
    Swal.fire({
      icon: 'error',
      title: 'Không lưu được',
      html: '<p class="tg-swal-msg">' + esc(message || 'Có lỗi xảy ra.') + '</p>' + fieldErrorHtml(errors),
      confirmButtonText: 'Đã hiểu',
      confirmButtonColor: '#087a55'
    });
  }

  function markInvalid(form, errors) {
    form.querySelectorAll('[aria-invalid="true"]').forEach(function (el) { el.removeAttribute('aria-invalid'); });
    var keys = Object.keys(errors || {});
    if (!keys.length) return;
    var first = null;
    keys.forEach(function (key) {
      var el = form.querySelector('[name="' + key + '"]');
      if (!el) return;
      el.setAttribute('aria-invalid', 'true');
      if (!first) first = el;
    });
    if (first) {
      first.focus({ preventScroll: false });
      first.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
  }

  function submitAjax(form) {
    var submitButton = form.querySelector('[type="submit"]');
    var originalHtml = submitButton ? submitButton.innerHTML : '';
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.classList.add('is-loading');
      submitButton.innerHTML = '<span class="tg-spinner" aria-hidden="true"></span> Đang lưu…';
    }

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
      .then(function (response) {
        return response.json()
          .catch(function () {
            return { ok: false, message: 'Máy chủ trả về dữ liệu không hợp lệ (HTTP ' + response.status + ').' };
          });
      })
      .then(function (data) {
        if (data && data.ok) {
          var message = data.message || 'Đã lưu thành công.';
          if (data.redirect) {
            if (hasSwal) {
              Swal.fire({
                icon: 'success', title: 'Thành công', text: message,
                timer: 1400, showConfirmButton: false, timerProgressBar: true
              }).then(function () { window.location.href = data.redirect; });
            } else {
              window.location.href = data.redirect;
            }
            return;
          }
          if (hasSwal) {
            Swal.fire({
              icon: 'success', title: 'Thành công', text: message,
              timer: 2000, showConfirmButton: false, timerProgressBar: true
            });
          } else {
            window.alert(message);
          }
          if (form.hasAttribute('data-ajax-reload')) {
            setTimeout(function () { window.location.reload(); }, 700);
          }
          return;
        }

        if (submitButton) {
          submitButton.disabled = false;
          submitButton.classList.remove('is-loading');
          submitButton.innerHTML = originalHtml;
        }
        errorDialog((data && data.message) || 'Có lỗi xảy ra.', data && data.errors);
        markInvalid(form, (data && data.errors) || {});
      })
      .catch(function (error) {
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.classList.remove('is-loading');
          submitButton.innerHTML = originalHtml;
        }
        if (hasSwal) {
          Swal.fire({
            icon: 'error', title: 'Lỗi kết nối',
            text: 'Không gửi được dữ liệu. Kiểm tra mạng rồi thử lại.',
            confirmButtonColor: '#087a55'
          });
        } else {
          window.alert('Không gửi được dữ liệu. Kiểm tra mạng rồi thử lại.');
        }
        console.error('[admin-ajax]', error);
      });
  }

// Form AJAX tắt validate HTML5 mặc định: lỗi do server trả về, hiển thị bằng SweetAlert2
  // (tránh trình duyệt chặn submit và bỏ qua hộp thoại lỗi của mình).
  document.querySelectorAll('form[data-ajax]').forEach(function (form) {
    form.setAttribute('novalidate', 'novalidate');
  });
  // Chặn submit cho form data-ajax / data-confirm (thay confirm() mặc định).
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    var isAjax = form.hasAttribute('data-ajax');
    var confirmMessage = form.getAttribute('data-confirm');
    if (!isAjax && !confirmMessage) return;

    event.preventDefault();
    var proceed = confirmMessage ? confirmDialog(confirmMessage) : Promise.resolve(true);
    proceed.then(function (confirmed) {
      if (!confirmed) {
        var sb = form.querySelector('[type="submit"]');
        if (sb) sb.blur();
        return;
      }
      if (isAjax) {
        submitAjax(form);
      } else {
        // Không AJAX: bỏ qua listener để gửi form native.
        form.submit();
      }
    });
  });

  // Đổi select trong form AJAX: gửi luôn, không tải lại trang.
  document.addEventListener('change', function (event) {
    var select = event.target;
    if (!select || !select.matches || !select.matches('select[data-submit-onchange]')) return;
    var form = select.form;
    if (!form || !form.hasAttribute('data-ajax')) return;
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
  });

  window.TGAdminAjax = {
    confirm: confirmDialog,
    error: errorDialog
  };
})();
