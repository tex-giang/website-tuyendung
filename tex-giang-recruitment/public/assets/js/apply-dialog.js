/**
 * TEX-GIANG — DIALOG ỨNG TUYỂN DÙNG CHUNG (bản PHP)
 * Mọi link tới /apply trên trang public được chặn để mở form trong <dialog>.
 * Form là POST thật về /apply; nếu JS lỗi hoặc trình duyệt không hỗ trợ <dialog>,
 * người dùng vẫn mở được trang ứng tuyển đầy đủ như bình thường.
 *
 * Chọn nhà máy (radio nhóm `branch_slug`) trước → danh sách vị trí `job_slug`
 * được lọc theo đúng các tin đang tuyển của nhà máy đó.
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
  var jobSelect = form.querySelector('#apply-dialog-job');
  var jobHint = form.querySelector('#apply-dialog-job-hint');
  var branchRadios = form.querySelectorAll('input[name="branch_slug"]');
  var jobsDataEl = document.getElementById('apply-dialog-jobs');

  var jobs = [];
  try { jobs = JSON.parse((jobsDataEl && jobsDataEl.textContent) || '[]'); } catch (_) { jobs = []; }
  if (!Array.isArray(jobs)) jobs = [];

  var jobsByBranch = {};
  jobs.forEach(function (job) {
    var key = String(job.branch || '');
    if (!jobsByBranch[key]) jobsByBranch[key] = [];
    jobsByBranch[key].push(job);
  });

  function checkedBranch() {
    for (var i = 0; i < branchRadios.length; i++) {
      if (branchRadios[i].checked) return branchRadios[i].value;
    }
    return '';
  }

  function rebuildPositions(branchSlug, selectedSlug) {
    if (!jobSelect) return;
    var list = branchSlug ? (jobsByBranch[branchSlug] || []) : [];

    jobSelect.innerHTML = '';

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = branchSlug ? '— Chọn vị trí —' : '— Chọn nhà máy trước —';
    jobSelect.appendChild(placeholder);

    list.forEach(function (job) {
      var option = document.createElement('option');
      option.value = job.slug;
      option.textContent = job.title + (job.salary ? ' — ' + job.salary : '');
      jobSelect.appendChild(option);
    });

    var other = document.createElement('option');
    other.value = 'khac';
    other.textContent = 'Vị trí khác — ghi rõ ở ghi chú';
    jobSelect.appendChild(other);

    jobSelect.disabled = !branchSlug;
    if (branchSlug && selectedSlug) {
      jobSelect.value = selectedSlug;
      if (jobSelect.value !== selectedSlug) jobSelect.value = '';
    }

    if (jobHint) {
      if (!branchSlug) {
        jobHint.textContent = 'Chọn nhà máy trước để xem vị trí đang tuyển tại khu đó.';
      } else if (list.length === 0) {
        jobHint.textContent = 'Nhà máy này chưa có vị trí đang tuyển — chọn “Vị trí khác” hoặc chọn nhà máy khác.';
      } else {
        jobHint.textContent = list.length + ' vị trí đang tuyển tại nhà máy này.';
      }
    }
  }

  function syncPositions() {
    var current = jobSelect ? jobSelect.value : '';
    rebuildPositions(checkedBranch(), current);
  }

  branchRadios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      rebuildPositions(radio.value, '');
    });
  });

  // ---- Bắt buộc đồng ý điều khoản mới cho phép gửi (nút nằm ngoài form) ----
  var dialogTerms = document.getElementById('apply-dialog-terms');
  var dialogSubmit = document.querySelector('.apply-dialog__submit');
  if (dialogTerms && dialogSubmit) {
    var syncDialogTerms = function () {
      dialogSubmit.disabled = !dialogTerms.checked;
      if (!dialogTerms.checked) {
        dialogSubmit.setAttribute('title', 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ');
        dialogSubmit.setAttribute('aria-disabled', 'true');
      } else {
        dialogSubmit.removeAttribute('title');
        dialogSubmit.removeAttribute('aria-disabled');
      }
    };
    dialogTerms.addEventListener('change', syncDialogTerms);
    syncDialogTerms();
  }
  function isApplyUrl(url) {
    var path = url.pathname.replace(/\/+$/, '');
    return /\/apply$/.test(path);
  }

  function focusClose() {
    var close = dialog.querySelector('[data-tg-apply-close]');
    if (close) close.focus();
  }

  /** Mở dialog từ link ?job=...: tự chọn đúng nhà máy rồi chọn vị trí đó. */
  function prefillFromLink(data) {
    var jobSlug = data && data.job ? String(data.job) : '';
    if (!jobSlug) {
      syncPositions();
      return;
    }

    var job = null;
    for (var i = 0; i < jobs.length; i++) {
      if (jobs[i].slug === jobSlug) { job = jobs[i]; break; }
    }
    if (!job) {
      syncPositions();
      return;
    }

    for (var r = 0; r < branchRadios.length; r++) {
      branchRadios[r].checked = branchRadios[r].value === job.branch;
    }
    rebuildPositions(job.branch, jobSlug);
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

  // Trạng thái ban đầu: chưa chọn nhà máy → ô vị trí tạm khoá.
  syncPositions();

  // ---- Gửi hồ sơ bằng AJAX: validate hiển thị NGAY trên dialog ----
  var alertBox = form.querySelector('[data-apply-alert]');
  var submitBtn = document.querySelector('.apply-dialog__submit');

  function clearErrors() {
    form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
    form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    if (alertBox) {
      alertBox.hidden = true;
      alertBox.textContent = '';
    }
  }

  function showErrors(errors, message) {
    clearErrors();
    if (alertBox && message) {
      alertBox.textContent = message;
      alertBox.hidden = false;
    }
    var firstField = null;
    Object.keys(errors || {}).forEach(function (name) {
      var holder = form.querySelector('[data-error-for="' + name + '"]');
      if (holder) holder.textContent = errors[name];
      var input = form.querySelector('[name="' + name + '"]');
      if (input && input.type !== 'radio' && input.type !== 'checkbox') {
        input.classList.add('is-invalid');
        if (!firstField) firstField = input;
      }
    });
    if (firstField) {
      firstField.focus();
    } else if (alertBox) {
      alertBox.scrollIntoView({ block: 'nearest' });
    }
  }

  form.addEventListener('submit', function (event) {
    if (!window.fetch || !window.FormData) return; // trình duyệt cũ: submit thật như trước
    event.preventDefault();
    clearErrors();
    if (submitBtn) submitBtn.disabled = true;

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      credentials: 'same-origin'
    })
      .then(function (response) { return response.json().catch(function () { return null; }); })
      .then(function (data) {
        if (!data) { form.submit(); return; }
        if (data.ok) {
          window.location.href = data.redirect || '/apply/success';
          return;
        }
        showErrors(data.errors || {}, data.message || 'Hồ sơ chưa hoàn tất. Vui lòng kiểm tra các mục được đánh dấu.');
      })
      .catch(function () { form.submit(); })
      .finally(function () {
        if (submitBtn) submitBtn.disabled = !!(dialogTerms && !dialogTerms.checked);
      });
  });

  form.addEventListener('input', function (event) {
    var target = event.target;
    if (!target || !target.name) return;
    var holder = form.querySelector('[data-error-for="' + target.name + '"]');
    if (holder && holder.textContent) holder.textContent = '';
    target.classList.remove('is-invalid');
  });

  window.TGApplyDialog = { open: open, close: close };
})();
