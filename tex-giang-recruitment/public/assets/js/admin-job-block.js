(function () {
  'use strict';

  /**
   * Phụ thuộc 2 cấp ở form tin tuyển dụng: Khối → Bộ phận.
   *
   *   <select name="block" data-job-block>
   *   <select name="category_id" data-job-category>
   *     <option data-block="enterprise" hidden>…</option>
   *     <option data-block="">…</option>   <!-- dùng chung, luôn hiện -->
   *
   * - Bộ phận có data-block rỗng = dùng chung → hiện ở mọi Khối.
   * - Nếu bộ phận đang chọn không thuộc Khối mới, tự bỏ chọn để tránh lưu sai.
   * - Hoạt động cả khi nội dung admin được nạp lại bằng AJAX (MutationObserver).
   */
  function sync(blockSelect, categorySelect) {
    const block = blockSelect.value;

    Array.prototype.forEach.call(categorySelect.options, function (option) {
      if (option.value === '') return; // option "Chọn bộ phận"
      const ownBlock = option.getAttribute('data-block') || '';
      const visible = ownBlock === '' || block === '' || ownBlock === block;
      option.hidden = !visible;
      option.disabled = !visible;
    });

    // Bỏ chọn nếu lựa chọn hiện tại không còn thuộc Khối vừa đổi.
    const current = categorySelect.selectedOptions && categorySelect.selectedOptions[0];
    if (current && current.disabled) {
      categorySelect.value = '';
      categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function initOne(blockSelect) {
    if (blockSelect._jobBlockBound) return;
    const form = blockSelect.closest('form');
    if (!form) return;
    const categorySelect = form.querySelector('[data-job-category]');
    if (!categorySelect) return;

    blockSelect._jobBlockBound = true;
    blockSelect.addEventListener('change', function () {
      sync(blockSelect, categorySelect);
    });
    sync(blockSelect, categorySelect);
  }

  function initAll(root) {
    (root || document).querySelectorAll('[data-job-block]').forEach(initOne);
  }

  function boot() {
    initAll(document);

    const content = document.getElementById('admin-content');
    if (!content) return;

    const observer = new MutationObserver(function (mutations) {
      for (const mutation of mutations) {
        if (mutation.addedNodes && mutation.addedNodes.length) {
          window.requestAnimationFrame(function () {
            initAll(content);
          });
          return;
        }
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
