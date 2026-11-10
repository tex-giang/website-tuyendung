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
    // ---- Bắt buộc đồng ý điều khoản mới cho phép gửi hồ sơ ----
    var termsBox = document.getElementById('apply-terms');
    var submitButton = form.querySelector('button[type="submit"]');
    if (termsBox && submitButton) {
      var syncTerms = function () {
        submitButton.disabled = !termsBox.checked;
        if (!termsBox.checked) {
          submitButton.setAttribute('title', 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ');
          submitButton.setAttribute('aria-disabled', 'true');
        } else {
          submitButton.removeAttribute('title');
          submitButton.removeAttribute('aria-disabled');
        }
      };
      termsBox.addEventListener('change', syncTerms);
      syncTerms();
    }
    // ---- Chọn khu sản xuất trước → lọc danh sách vị trí theo khu ----
    var branchSelect = document.getElementById('apply-branch');
    var positionSelect = document.getElementById('apply-position');
    var positionHint = document.getElementById('apply-position-hint');

    if (branchSelect && positionSelect) {
      var allPositionOptions = Array.prototype.slice.call(positionSelect.options).map(function (option) {
        return {
          value: option.value,
          label: option.textContent.trim(),
          branch: option.getAttribute('data-branch') || '',
          isOther: option.value === 'khac',
          isPlaceholder: option.value === ''
        };
      });

      var rebuildPositions = function () {
        var branch = branchSelect.value;
        var current = positionSelect.value;
        var matched = 0;

        positionSelect.innerHTML = '';

        allPositionOptions.forEach(function (item) {
          if (item.isPlaceholder) {
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = branch ? '— Chọn vị trí —' : '— Chọn khu sản xuất trước —';
            positionSelect.appendChild(placeholder);
            return;
          }
          if (item.isOther || (branch && item.branch === branch)) {
            var option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            positionSelect.appendChild(option);
            if (!item.isOther) matched++;
          }
        });

        positionSelect.disabled = !branch;
        if (branch && current) {
          positionSelect.value = current;
          if (positionSelect.value !== current) positionSelect.value = '';
        }

        if (positionHint) {
          if (!branch) {
            positionHint.textContent = 'Chọn khu sản xuất trước để lọc vị trí đang tuyển.';
          } else if (matched === 0) {
            positionHint.textContent = 'Nhà máy này chưa có vị trí đang tuyển — chọn “Vị trí khác” hoặc khu khác.';
          } else {
            positionHint.textContent = matched + ' vị trí đang tuyển tại nhà máy này.';
          }
        }
      };

      branchSelect.addEventListener('change', rebuildPositions);
      rebuildPositions();
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
