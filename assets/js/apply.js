/**
 * TEX-GIANG RECRUITMENT PORTAL - ONLINE APPLICATION JAVASCRIPT
 * Handles client-side form validation, CV drag & drop upload preview, progress simulation.
 */

document.addEventListener('DOMContentLoaded', function () {
  const applyForm = document.getElementById('online-apply-form');
  const dropZone = document.getElementById('cv-drop-zone');
  const fileInput = document.getElementById('cv-file-input');
  const filePreview = document.getElementById('file-preview-card');
  const fileNameDisplay = document.getElementById('file-name-display');
  const fileSizeDisplay = document.getElementById('file-size-display');
  const removeFileBtn = document.getElementById('remove-file-btn');

  // Pre-fill job title from URL parameter (e.g. apply.html?job=Công%20Nhân%20May)
  const urlParams = new URLSearchParams(window.location.search);
  const prefilledJob = urlParams.get('job');
  const company = document.getElementById('apply-company');
  if (company && [...company.options].some(option => option.value === urlParams.get('company'))) company.value = urlParams.get('company');
  const jobSelect = document.getElementById('apply-position');

  if (prefilledJob && jobSelect) {
    // So khớp mềm: bỏ dấu, bỏ ký tự đặc biệt, chấm điểm theo từ trùng
    const normalize = function (text) {
      return (text || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    };

    const target = normalize(prefilledJob);
    const targetWords = target.split(' ').filter(function (w) { return w.length > 2; });

    let bestIndex = -1;
    let bestScore = 0;

    for (let i = 0; i < jobSelect.options.length; i++) {
      const option = jobSelect.options[i];
      if (!option.value) continue;

      const optionText = normalize(option.text);
      let score = 0;

      if (optionText && target.indexOf(optionText) !== -1) score += 10;
      if (target && optionText.indexOf(target) !== -1) score += 10;
      targetWords.forEach(function (word) {
        if (optionText.indexOf(word) !== -1) score += 1;
      });

      if (score > bestScore) {
        bestScore = score;
        bestIndex = i;
      }
    }

    if (bestIndex > -1 && bestScore >= 2) {
      jobSelect.selectedIndex = bestIndex;
    }
  }

  // Files remain local: this static prototype does not upload personal data.
  const cvError = document.getElementById('cv-error');
  const skipCv = document.getElementById('skip-cv');
  const summary = document.getElementById('apply-error-summary');
  const fieldIds = ['apply-fullname', 'apply-phone', 'apply-position', 'apply-address', 'apply-email', 'apply-birth', 'apply-terms'];
  fieldIds.forEach(id => {
    const field = document.getElementById(id);
    if (!field) return;
    const error = document.createElement('p');
    error.id = id + '-error';
    error.className = 'field-error';
    error.setAttribute('aria-live', 'polite');
    const group = id === 'apply-terms' ? field.closest('label') : field;
    group.insertAdjacentElement('afterend', error);
    field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
    field.addEventListener('input', () => { setError(id, ''); if (summary) summary.textContent = ''; });
    field.addEventListener('change', () => { setError(id, ''); if (summary) summary.textContent = ''; });
  });
  function setError(id, message) {
    const field = document.getElementById(id);
    const error = document.getElementById(id + '-error');
    if (field) field.setAttribute('aria-invalid', message ? 'true' : 'false');
    if (error) error.textContent = message;
    return !!message;
  }
  function clearFile() {
    if (fileInput) fileInput.value = '';
    if (filePreview) filePreview.classList.add('hidden');
    if (dropZone) dropZone.classList.remove('hidden');
    if (cvError) cvError.textContent = '';
    skipCv?.classList.add('hidden');
    fileInput?.removeAttribute('aria-invalid');
  }
  function handleFileSelection(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    let message = '';
    if (!['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'].includes(extension)) message = 'Chọn tệp PDF, DOC, DOCX, JPG hoặc PNG.';
    else if (!file.size) message = 'Tệp đang trống. Vui lòng chọn tệp khác.';
    else if (file.size > 10 * 1024 * 1024) message = 'Tệp lớn hơn 10 MB. Vui lòng chọn tệp nhỏ hơn.';
    clearFile();
    if (message) {
      if (cvError) cvError.textContent = message;
      skipCv?.classList.remove('hidden');
      fileInput?.setAttribute('aria-invalid', 'true');
      fileInput?.focus();
      return;
    }
    // Keep dropped and picker-selected files on the same native input.
    try { const transfer = new DataTransfer(); transfer.items.add(file); fileInput.files = transfer.files; } catch (_) {}
    if (dropZone) dropZone.classList.add('hidden');
    if (filePreview) filePreview.classList.remove('hidden');
    if (fileNameDisplay) fileNameDisplay.textContent = file.name;
    if (fileSizeDisplay) fileSizeDisplay.textContent = (file.size < 1024 * 1024 ? Math.ceil(file.size / 1024) + ' KB' : (file.size / 1024 / 1024).toFixed(2) + ' MB') + ' · Đã chọn trên máy';
    removeFileBtn?.focus();
  }
  if (dropZone && fileInput) {
    skipCv?.addEventListener('click', () => { clearFile(); fileInput.focus(); });
    ['dragenter', 'dragover'].forEach(name => dropZone.addEventListener(name, e => { e.preventDefault(); dropZone.classList.add('drag-active'); }));
    ['dragleave', 'drop'].forEach(name => dropZone.addEventListener(name, e => { e.preventDefault(); dropZone.classList.remove('drag-active'); }));
    dropZone.addEventListener('drop', e => { if (e.dataTransfer.files[0]) handleFileSelection(e.dataTransfer.files[0]); });
    fileInput.addEventListener('change', () => { if (fileInput.files[0]) handleFileSelection(fileInput.files[0]); });
    removeFileBtn?.addEventListener('click', e => { e.preventDefault(); clearFile(); fileInput.focus(); });
  }
  if (applyForm) applyForm.addEventListener('submit', e => {
    e.preventDefault();
    const value = id => document.getElementById(id)?.value.trim() || '';
    const phone = value('apply-phone').replace(/[\s().-]/g, '');
    const errors = [
      ['apply-fullname', value('apply-fullname') ? '' : 'Nhập họ và tên của bạn.'],
      ['apply-phone', /^(?:0|\+?84)[35789]\d{8}$/.test(phone) ? '' : 'Nhập số di động Việt Nam hợp lệ, ví dụ 0918 123 456.'],
      ['apply-position', value('apply-position') ? '' : 'Chọn vị trí bạn muốn ứng tuyển.'],
      ['apply-address', value('apply-address') ? '' : 'Nhập địa chỉ hiện tại của bạn.'],
      ['apply-email', !value('apply-email') || document.getElementById('apply-email').validity.valid ? '' : 'Kiểm tra lại địa chỉ email.'],
      ['apply-birth', !value('apply-birth') || document.getElementById('apply-birth').validity.valid ? '' : 'Nhập năm sinh trong khoảng 1950–2010.'],
      ['apply-terms', document.getElementById('apply-terms')?.checked ? '' : 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ.']
    ];
    errors.forEach(([id, message]) => setError(id, message));
    const first = errors.find(([, message]) => message);
    if (first || cvError?.textContent) {
      if (summary) summary.textContent = 'Hồ sơ chưa hoàn tất. Kiểm tra các mục được đánh dấu bên dưới.';
      document.getElementById(first ? first[0] : 'cv-file-input')?.focus();
      return;
    }
    const submit = applyForm.querySelector('button[type="submit"]');
    submit.disabled = true;
    submit.textContent = 'Đang mô phỏng gửi hồ sơ…';
    applyForm.setAttribute('aria-busy', 'true');
    // Demo only; no request is sent and no personal data is persisted.
    setTimeout(() => { window.location.href = 'application-success.html'; }, 700);
  });
});
