/**
 * Form ứng tuyển (bản động): chỉ xử lý phần tệp CV phía client cho thân thiện,
 * form vẫn submit POST thật lên server — server kiểm lại toàn bộ.
 */
(function () {
  'use strict';

  var form = document.getElementById('online-apply-form');
  if (!form) return;

  var input = document.getElementById('cv-file-input');
  var dropZone = document.getElementById('cv-drop-zone');
  var preview = document.getElementById('file-preview-card');
  var nameEl = document.getElementById('file-name-display');
  var sizeEl = document.getElementById('file-size-display');
  var errorEl = document.getElementById('cv-error');
  var skipBtn = document.getElementById('skip-cv');
  var removeBtn = document.getElementById('remove-file-btn');
  var submitBtn = form.querySelector('button[type="submit"]');

  var MAX = 10 * 1024 * 1024;
  var ALLOWED = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

  function humanSize(bytes) {
    return bytes < 1024 * 1024
      ? Math.ceil(bytes / 1024) + ' KB'
      : (bytes / 1024 / 1024).toFixed(2) + ' MB';
  }

  function clearFile() {
    if (input) input.value = '';
    preview?.classList.add('hidden');
    preview?.classList.remove('flex');
    dropZone?.classList.remove('hidden');
    if (errorEl) errorEl.textContent = '';
    skipBtn?.classList.add('hidden');
  }

  function handleFile(file) {
    if (!file) return;
    var ext = (file.name.split('.').pop() || '').toLowerCase();
    var message = '';
    if (ALLOWED.indexOf(ext) === -1) {
      message = 'Chọn tệp PDF, DOC, DOCX, JPG hoặc PNG.';
    } else if (!file.size) {
      message = 'Tệp đang trống. Vui lòng chọn tệp khác.';
    } else if (file.size > MAX) {
      message = 'Tệp lớn hơn 10 MB. Vui lòng chọn tệp nhỏ hơn.';
    }

    clearFile();
    if (message) {
      if (errorEl) errorEl.textContent = message;
      skipBtn?.classList.remove('hidden');
      input?.setAttribute('aria-invalid', 'true');
      input?.focus();
      return;
    }

    // Gán tệp kéo-thả vào input gốc để form gửi kèm.
    try {
      var transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
    } catch (_) { /* trình duyệt cũ: bỏ qua */ }

    dropZone?.classList.add('hidden');
    preview?.classList.remove('hidden');
    preview?.classList.add('flex');
    if (nameEl) nameEl.textContent = file.name;
    if (sizeEl) sizeEl.textContent = humanSize(file.size) + ' · Đã chọn trên máy';
    removeBtn?.focus();
  }

  if (input && dropZone) {
    skipBtn?.addEventListener('click', function () { clearFile(); input.focus(); });

    ['dragenter', 'dragover'].forEach(function (name) {
      dropZone.addEventListener(name, function (event) {
        event.preventDefault();
        dropZone.classList.add('drag-active');
      });
    });
    ['dragleave', 'drop'].forEach(function (name) {
      dropZone.addEventListener(name, function (event) {
        event.preventDefault();
        dropZone.classList.remove('drag-active');
      });
    });
    dropZone.addEventListener('drop', function (event) {
      if (event.dataTransfer?.files[0]) handleFile(event.dataTransfer.files[0]);
    });
    input.addEventListener('change', function () {
      if (input.files[0]) handleFile(input.files[0]);
    });
    removeBtn?.addEventListener('click', function (event) {
      event.preventDefault();
      clearFile();
      input.focus();
    });
  }

  form.addEventListener('submit', function () {
    if (!form.checkValidity()) return;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Đang gửi hồ sơ…';
    }
    form.setAttribute('aria-busy', 'true');
  });
})();
