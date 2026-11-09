/**
 * TEX-GIANG — Tự động định dạng số điện thoại khi gõ.
 * Áp dụng cho input[type="tel"][data-phone-format]:
 * gõ liền 0912345678 → hiển thị 0912 345 678 (nhóm 4-3-3).
 * Server vẫn tự chuẩn hoá (bỏ khoảng trắng) trước khi validate/lưu.
 */
(function () {
  'use strict';

  function formatPhone(value) {
    var digits = String(value || '').replace(/\D/g, '');
    if (digits.indexOf('84') === 0 && digits.length > 2) {
      digits = '0' + digits.slice(2);
    }
    if (digits.length <= 4) return digits;
    if (digits.length <= 7) return digits.slice(0, 4) + ' ' + digits.slice(4);
    if (digits.length <= 10) {
      return digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7);
    }
    return digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7, 10) + ' ' + digits.slice(10);
  }

  function apply(el) {
    if (!el || el.tagName !== 'INPUT' || el.type !== 'tel' || !el.hasAttribute('data-phone-format')) return;
    var before = el.value;
    var formatted = formatPhone(before);
    if (formatted === before) return;
    el.value = formatted;
    try {
      el.setSelectionRange(formatted.length, formatted.length);
    } catch (_) {}
  }

  document.addEventListener('input', function (event) {
    apply(event.target);
  }, true);

  document.addEventListener('focusout', function (event) {
    apply(event.target);
  }, true);

  window.TGFormatPhone = formatPhone;
})();
