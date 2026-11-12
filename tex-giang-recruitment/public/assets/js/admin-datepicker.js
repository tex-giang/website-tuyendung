(function () {
  'use strict';

  /**
   * Khởi tạo date picker (flatpickr) cho các ô có data-datepicker.
   *
   *   <input type="text" data-datepicker="date" ...>       -> dd/mm/yyyy
   *   <input type="text" data-datepicker="datetime" ...>   -> dd/mm/yyyy HH:MM
   *
   * - Hoạt động cả khi nội dung admin được nạp lại bằng AJAX (MutationObserver).
   * - Vẫn cho phép gõ tay (allowInput) và giữ nguyên định dạng dd/mm/yyyy để server đọc.
   */
  const FORMAT_DATE = 'd/m/Y';
  const FORMAT_DATETIME = 'd/m/Y H:i';

  function initOne(input) {
    if (input._flatpickr || typeof window.flatpickr !== 'function') {
      return;
    }
    const mode = input.getAttribute('data-datepicker') || 'date';
    const isDateTime = mode === 'datetime';

    // Bọc input trong khung relative riêng để icon lịch luôn nằm giữa ô,
    // bất kể bên dưới có hint / thông báo lỗi hay không.
    let wrap = input.parentElement;
    if (!wrap || !wrap.classList.contains('dp-wrap')) {
      wrap = document.createElement('span');
      wrap.className = 'dp-wrap';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);
    }
    if (!wrap.querySelector('.dp-icon')) {
      const icon = document.createElement('i');
      icon.className = 'bi bi-calendar3 dp-icon';
      icon.setAttribute('aria-hidden', 'true');
      wrap.appendChild(icon);
    }

    window.flatpickr(input, {
      locale: window.flatpickr.l10ns && window.flatpickr.l10ns.vn ? 'vn' : 'default',
      dateFormat: isDateTime ? FORMAT_DATETIME : FORMAT_DATE,
      enableTime: isDateTime,
      time_24hr: true,
      defaultHour: 8,
      minuteIncrement: 5,
      allowInput: true,
      disableMobile: true,
      monthSelectorType: 'static',
      nextArrow: '<i class="bi bi-chevron-right" aria-hidden="true"></i>',
      prevArrow: '<i class="bi bi-chevron-left" aria-hidden="true"></i>',
      onChange: function () {
        // Xoá trạng thái lỗi (nếu có) khi người dùng chọn ngày mới.
        input.classList.remove('is-invalid');
        const field = input.closest('.field');
        const error = field && field.querySelector('.field__error');
        if (error) error.remove();
      },
    });
  }

  function initAll(root) {
    (root || document).querySelectorAll('[data-datepicker]').forEach(initOne);
  }

  function boot() {
    if (typeof window.flatpickr !== 'function') return;
    initAll(document);

    const content = document.getElementById('admin-content');
    if (!content) return;

    const observer = new MutationObserver(function (mutations) {
      let shouldScan = false;
      for (const mutation of mutations) {
        if (mutation.addedNodes && mutation.addedNodes.length) {
          shouldScan = true;
          break;
        }
      }
      if (shouldScan) {
        window.requestAnimationFrame(function () {
          initAll(content);
        });
      }
    });
    observer.observe(content, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
