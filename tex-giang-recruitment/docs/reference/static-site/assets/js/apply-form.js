/**
 * TEX-GIANG RECRUITMENT — BIỂU MẪU ỨNG TUYỂN DÙNG CHUNG
 *
 * Một nguồn markup duy nhất cho form ứng tuyển:
 *  - apply.html: mount thẳng vào trang (fallback khi mở trực tiếp)
 *  - mọi trang public: mount trong dialog của assets/js/apply-dialog.js
 *
 * Không gửi dữ liệu ra ngoài. Đây là bản demo: không có request, không lưu DB.
 */
(function () {
  'use strict';

  var FORM_HTML = [
    '<form id="online-apply-form" class="space-y-10" novalidate>',
    '  <div id="apply-error-summary" class="form-summary" role="alert" tabindex="-1"></div>',

    '  <fieldset data-reveal>',
    '    <legend class="form-group-title"><img class="tg-icon" src="assets/images/icons/briefcase.svg" width="20" height="20" alt="" aria-hidden="true"> Vị trí ứng tuyển</legend>',
    '    <div class="grid gap-4 sm:grid-cols-2">',
    '      <div>',
    '        <label class="form-label" for="apply-company">Khu sản xuất <span class="req">*</span></label>',
    '        <select id="apply-company" name="company" class="form-input" required>',
    '          <option value="tan-huong">Khu Tân Hương</option>',
    '          <option value="cai-lay">Khu Cai Lậy</option>',
    '          <option value="cho-gao">Khu Chợ Gạo</option>',
    '          <option value="any">Theo sắp xếp phù hợp</option>',
    '        </select>',
    '      </div>',
    '      <div>',
    '        <label class="form-label" for="apply-position">Vị trí mong muốn <span class="req">*</span></label>',
    '        <select id="apply-position" name="position" class="form-input" required>',
    '          <option value="">— Chọn vị trí —</option>',
    '          <option value="cn-may">Công nhân may chuyền (8 – 16 triệu)</option>',
    '          <option value="qc">Giám sát QC — kiểm phẩm (12 – 18 triệu)</option>',
    '          <option value="ie">Kỹ sư IE — công nghiệp &amp; định mức (15 – 22 triệu)</option>',
    '          <option value="merchandiser">Merchandiser — quản lý đơn hàng (13 – 20 triệu)</option>',
    '          <option value="bao-tri">Bảo trì máy may &amp; điện xưởng (9,5 – 14,5 triệu)</option>',
    '          <option value="ke-toan">Kế toán chi phí &amp; giá thành (11 – 16 triệu)</option>',
    '          <option value="hoc-nghe">Học nghề may miễn phí, có lương</option>',
    '          <option value="other">Vị trí khác — ghi rõ ở phần giới thiệu</option>',
    '        </select>',
    '      </div>',
    '    </div>',
    '  </fieldset>',

    '  <fieldset data-reveal>',
    '    <legend class="form-group-title"><img class="tg-icon" src="assets/images/icons/file-earmark-text.svg" width="20" height="20" alt="" aria-hidden="true"> Thông tin cá nhân</legend>',
    '    <div class="grid gap-4 sm:grid-cols-2">',
    '      <div>',
    '        <label class="form-label" for="apply-fullname">Họ và tên <span class="req">*</span></label>',
    '        <input type="text" id="apply-fullname" name="fullname" required autocomplete="name" placeholder="Ví dụ: Lê Thị Mai" class="form-input">',
    '      </div>',
    '      <div>',
    '        <label class="form-label" for="apply-phone">Số điện thoại <span class="req">*</span></label>',
    '        <input type="tel" id="apply-phone" name="phone" required autocomplete="tel" inputmode="tel" placeholder="0912 345 678" class="form-input">',
    '      </div>',
    '      <div>',
    '        <label class="form-label" for="apply-email">Email (nếu có)</label>',
    '        <input type="email" id="apply-email" name="email" autocomplete="email" placeholder="email@gmail.com" class="form-input">',
    '      </div>',
    '      <div class="grid grid-cols-2 gap-3">',
    '        <div>',
    '          <label class="form-label" for="apply-birth">Năm sinh</label>',
    '          <input type="number" id="apply-birth" name="birth_year" min="1950" max="2010" placeholder="1996" class="form-input">',
    '        </div>',
    '        <div>',
    '          <label class="form-label" for="apply-gender">Giới tính</label>',
    '          <select id="apply-gender" name="gender" class="form-input">',
    '            <option value="nu">Nữ</option>',
    '            <option value="nam">Nam</option>',
    '            <option value="khac">Khác</option>',
    '          </select>',
    '        </div>',
    '      </div>',
    '      <div class="sm:col-span-2">',
    '        <label class="form-label" for="apply-address">Địa chỉ hiện tại <span class="req">*</span></label>',
    '        <input type="text" id="apply-address" name="address" required autocomplete="address-line1"',
    '               placeholder="Ấp/Xã, Huyện Châu Thành, Tỉnh Đồng Tháp" class="form-input">',
    '        <p class="form-hint">Giúp ban tuyển dụng xếp bạn vào tuyến xe đưa rước gần nhất.</p>',
    '      </div>',
    '    </div>',
    '  </fieldset>',

    '  <fieldset data-reveal>',
    '    <legend class="form-group-title"><img class="tg-icon" src="assets/images/icons/file-earmark-text.svg" width="20" height="20" alt="" aria-hidden="true"> CV hoặc hình hồ sơ</legend>',
    '    <div id="cv-drop-zone" class="dropzone">',
    '      <input type="file" id="cv-file-input" aria-describedby="cv-hint cv-error" name="cv" class="hidden" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">',
    '      <label for="cv-file-input" class="block cursor-pointer">',
    '        <img src="assets/images/icons/cloud-arrow-up.svg" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[28px] text-accent">',
    '        <span class="mt-2 block text-[16px] font-medium text-ink">Kéo thả tệp vào đây, hoặc chọn từ máy</span>',
    '        <span id="cv-hint" class="mt-1 block text-[16px] text-muted">PDF, DOC, DOCX, JPG, PNG · tối đa 10MB</span>',
    '      </label>',
    '    </div>',
    '    <p id="cv-error" class="field-error" role="alert"></p>',
    '    <button type="button" id="skip-cv" class="hidden tg-link mt-2">Bỏ tệp này, tiếp tục không có CV</button>',
    '    <div id="upload-progress-container" class="mt-4 hidden">',
    '      <div class="flex items-center justify-between text-[14px] text-muted">',
    '        <span>Đang tải tệp…</span>',
    '        <span id="upload-percentage">Vui lòng chờ</span>',
    '      </div>',
    '      <div class="progress-track mt-2">',
    '        <div id="upload-progress-bar" class="progress-bar" style="width: 0%"></div>',
    '      </div>',
    '    </div>',
    '    <div id="file-preview-card" class="mt-4 hidden items-center justify-between gap-4 rounded-card bg-surface p-4">',
    '      <div class="flex min-w-0 items-center gap-3">',
    '        <img src="assets/images/icons/file-earmark-check.svg" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[22px] text-accent">',
    '        <div class="min-w-0">',
    '          <strong id="file-name-display" class="block truncate text-[16px] font-medium text-ink">HoSo_UngVien.pdf</strong>',
    '          <span id="file-size-display" class="text-[14px] text-muted">1.2 MB</span>',
    '        </div>',
    '      </div>',
    '      <button type="button" id="remove-file-btn" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-muted transition-colors hover:bg-white hover:text-ink" aria-label="Gỡ tệp">',
    '        <img src="assets/images/icons/trash.svg" alt="" aria-hidden="true" width="20" height="20" class="tg-icon">',
    '      </button>',
    '    </div>',
    '  </fieldset>',

    '  <fieldset data-reveal>',
    '    <legend class="form-group-title">Kinh nghiệm &amp; nguyện vọng</legend>',
    '    <label class="form-label" for="apply-note">Giới thiệu ngắn (không bắt buộc)</label>',
    '    <textarea id="apply-note" name="note" rows="4" class="form-input"',
    '              placeholder="Kinh nghiệm may, vị trí từng làm, mong muốn thu nhập hoặc tuyến xe đưa đón…"></textarea>',
    '  </fieldset>',

    '  <div class="space-y-5">',
    '    <label class="flex cursor-pointer items-start gap-3 rounded-card bg-surface p-4 text-[14px] leading-[1.6] text-muted">',
    '      <input type="checkbox" id="apply-terms" name="terms" required',
    '             class="mt-0.5 h-5 w-5 shrink-0 rounded border-line text-accent focus:ring-accent">',
    '      <span>Tôi xác nhận thông tin trên là đúng và đồng ý để ban tuyển dụng TEX-GIANG liên hệ qua điện thoại hoặc Zalo.</span>',
    '    </label>',
    '    <button type="submit" class="tg-btn tg-btn--primary w-full">',
    '      <img src="assets/images/icons/send.svg" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[16px]"> Gửi hồ sơ ứng tuyển',
    '    </button>',
    '    <p class="text-center text-[16px] leading-[1.6] text-muted">',
    '      * Biểu mẫu demo: dữ liệu chưa lưu vào cơ sở dữ liệu, chỉ mô phỏng luồng gửi hồ sơ.',
    '    </p>',
    '  </div>',
    '</form>'
  ].join('\n');

  var ERROR_FIELDS = [
    'apply-fullname', 'apply-phone', 'apply-position', 'apply-address',
    'apply-email', 'apply-birth', 'apply-terms'
  ];

  function normalize(text) {
    return (text || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd')
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function readPageParams() {
    try { return new URLSearchParams(window.location.search); } catch (_) { return new URLSearchParams(''); }
  }

  /**
   * Điền sẵn công ty và vị trí. `data.job` là tiêu đề tin (khớp mềm),
   * `data.company` là mã công ty. Không có thì lấy từ query của trang.
   */
  function prefill(form, data) {
    if (!form) return;
    data = data || {};
    var params = readPageParams();

    var company = form.querySelector('#apply-company');
    var companyValue = data.company || data.location || params.get('company') || params.get('khu');
    if (company && companyValue && Array.prototype.some.call(company.options, function (option) {
      return option.value === companyValue;
    })) {
      company.value = companyValue;
    }

    var jobSelect = form.querySelector('#apply-position');
    if (!jobSelect) return;

    // Ưu tiên mã vị trí truyền thẳng (?vai-tri= hoặc data.position),
    // sau đó mới khớp mềm theo tiêu đề tin (?job=).
    var positionValue = data.position || params.get('vai-tri');
    if (positionValue && Array.prototype.some.call(jobSelect.options, function (option) {
      return option.value === positionValue;
    })) {
      jobSelect.value = positionValue;
      return;
    }

    var jobTitle = data.job || params.get('job');
    if (!jobTitle) return;

    var target = normalize(jobTitle);
    var targetWords = target.split(' ').filter(function (word) { return word.length > 2; });
    var bestIndex = -1;
    var bestScore = 0;

    for (var i = 0; i < jobSelect.options.length; i++) {
      var option = jobSelect.options[i];
      if (!option.value) continue;
      var optionText = normalize(option.text);
      var score = 0;
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

    if (bestIndex > -1 && bestScore >= 2) jobSelect.selectedIndex = bestIndex;
  }

  function bind(form, options) {
    options = options || {};
    var q = function (selector) { return form.querySelector(selector); };

    var cvError = q('#cv-error');
    var skipCv = q('#skip-cv');
    var summary = q('#apply-error-summary');
    var fileInput = q('#cv-file-input');
    var dropZone = q('#cv-drop-zone');
    var filePreview = q('#file-preview-card');
    var fileNameDisplay = q('#file-name-display');
    var fileSizeDisplay = q('#file-size-display');
    var removeFileBtn = q('#remove-file-btn');

    // Theo dõi người dùng đã nhập gì để dialog không ghi đè dữ liệu khi mở lại.
    var markDirty = function () { form.dataset.tgDirty = '1'; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    // Lỗi inline cho từng trường bắt buộc.
    ERROR_FIELDS.forEach(function (id) {
      var field = q('#' + id);
      if (!field) return;
      var error = document.createElement('p');
      error.id = id + '-error';
      error.className = 'field-error';
      error.setAttribute('aria-live', 'polite');
      var group = id === 'apply-terms' ? field.closest('label') : field;
      group.insertAdjacentElement('afterend', error);
      field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
      field.addEventListener('input', function () { setError(id, ''); if (summary) summary.textContent = ''; });
      field.addEventListener('change', function () { setError(id, ''); if (summary) summary.textContent = ''; });
    });

    function setError(id, message) {
      var field = q('#' + id);
      var error = q('#' + id + '-error');
      if (field) field.setAttribute('aria-invalid', message ? 'true' : 'false');
      if (error) error.textContent = message;
      return !!message;
    }

    function clearFile() {
      if (fileInput) fileInput.value = '';
      if (filePreview) filePreview.classList.add('hidden');
      if (dropZone) dropZone.classList.remove('hidden');
      if (cvError) cvError.textContent = '';
      if (skipCv) skipCv.classList.add('hidden');
      if (fileInput) fileInput.removeAttribute('aria-invalid');
    }

    function handleFileSelection(file) {
      var extension = file.name.split('.').pop().toLowerCase();
      var message = '';
      if (['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'].indexOf(extension) === -1) message = 'Chọn tệp PDF, DOC, DOCX, JPG hoặc PNG.';
      else if (!file.size) message = 'Tệp đang trống. Vui lòng chọn tệp khác.';
      else if (file.size > 10 * 1024 * 1024) message = 'Tệp lớn hơn 10 MB. Vui lòng chọn tệp nhỏ hơn.';
      clearFile();
      if (message) {
        if (cvError) cvError.textContent = message;
        if (skipCv) skipCv.classList.remove('hidden');
        if (fileInput) { fileInput.setAttribute('aria-invalid', 'true'); fileInput.focus(); }
        return;
      }
      // Giữ nguyên tệp kéo thả trên đúng input gốc.
      try { var transfer = new DataTransfer(); transfer.items.add(file); fileInput.files = transfer.files; } catch (_) {}
      if (dropZone) dropZone.classList.add('hidden');
      if (filePreview) filePreview.classList.remove('hidden');
      if (fileNameDisplay) fileNameDisplay.textContent = file.name;
      if (fileSizeDisplay) {
        fileSizeDisplay.textContent = (file.size < 1024 * 1024
          ? Math.ceil(file.size / 1024) + ' KB'
          : (file.size / 1024 / 1024).toFixed(2) + ' MB') + ' · Đã chọn trên máy';
      }
      if (removeFileBtn) removeFileBtn.focus();
    }

    if (dropZone && fileInput) {
      if (skipCv) skipCv.addEventListener('click', function () { clearFile(); fileInput.focus(); });
      ['dragenter', 'dragover'].forEach(function (name) {
        dropZone.addEventListener(name, function (event) { event.preventDefault(); dropZone.classList.add('drag-active'); });
      });
      ['dragleave', 'drop'].forEach(function (name) {
        dropZone.addEventListener(name, function (event) { event.preventDefault(); dropZone.classList.remove('drag-active'); });
      });
      dropZone.addEventListener('drop', function (event) {
        if (event.dataTransfer.files[0]) handleFileSelection(event.dataTransfer.files[0]);
      });
      fileInput.addEventListener('change', function () {
        if (fileInput.files[0]) handleFileSelection(fileInput.files[0]);
      });
      if (removeFileBtn) removeFileBtn.addEventListener('click', function (event) {
        event.preventDefault(); clearFile(); fileInput.focus();
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var value = function (id) {
        var field = q('#' + id);
        return field && typeof field.value === 'string' ? field.value.trim() : '';
      };
      var phone = value('apply-phone').replace(/[\s().-]/g, '');
      var emailField = q('#apply-email');
      var birthField = q('#apply-birth');
      var termsField = q('#apply-terms');
      var errors = [
        ['apply-fullname', value('apply-fullname') ? '' : 'Nhập họ và tên của bạn.'],
        ['apply-phone', /^(?:0|\+?84)[35789]\d{8}$/.test(phone) ? '' : 'Nhập số di động Việt Nam hợp lệ, ví dụ 0918 123 456.'],
        ['apply-position', value('apply-position') ? '' : 'Chọn vị trí bạn muốn ứng tuyển.'],
        ['apply-address', value('apply-address') ? '' : 'Nhập địa chỉ hiện tại của bạn.'],
        ['apply-email', !value('apply-email') || !emailField || emailField.validity.valid ? '' : 'Kiểm tra lại địa chỉ email.'],
        ['apply-birth', !value('apply-birth') || !birthField || birthField.validity.valid ? '' : 'Nhập năm sinh trong khoảng 1950–2010.'],
        ['apply-terms', termsField && termsField.checked ? '' : 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ.']
      ];

      errors.forEach(function (pair) { setError(pair[0], pair[1]); });
      var first = errors.filter(function (pair) { return pair[1]; })[0];
      if (first || (cvError && cvError.textContent)) {
        if (summary) summary.textContent = 'Hồ sơ chưa hoàn tất. Kiểm tra các mục được đánh dấu bên dưới.';
        var target = q('#' + (first ? first[0] : 'cv-file-input'));
        if (target) target.focus();
        return;
      }

      var submitButton = options.submitButton || form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Đang mô phỏng gửi hồ sơ…';
      }
      form.setAttribute('aria-busy', 'true');

      // Demo: không gửi request lên máy chủ, chỉ ghi vào kho tạm của trình duyệt.
      window.setTimeout(function () {
        form.removeAttribute('aria-busy');

        // Bản demo: ghi hồ sơ vào hàng chờ để trang quản trị đọc lại.
        if (window.TGPendingApplications) {
          var positionSelect = q('#apply-position');
          var messageField = q('#apply-message');
          var fileInput = q('#cv-file-input');
          window.TGPendingApplications.add({
            name: value('apply-fullname'),
            phone: phone,
            email: value('apply-email'),
            birth: value('apply-birth'),
            address: value('apply-address'),
            jobTitle: positionSelect && positionSelect.selectedIndex > 0 ? positionSelect.options[positionSelect.selectedIndex].text : '',
            message: messageField ? messageField.value.trim() : '',
            note: '',
            source: 'Website — form ứng tuyển',
            attachment: fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0].name : ''
          });
        }

        if (typeof options.onSuccess === 'function') {
          options.onSuccess(form);
        } else {
          window.location.href = 'application-success.html';
        }
      }, 700);
    });
  }

  /**
   * Gắn biểu mẫu vào `root`. Trả về phần tử form, hoặc null nếu thiếu root.
   * options: { submitButton, onSuccess, prefill: {job, company} }
   */
  function mount(root, options) {
    if (!root) return null;
    options = options || {};
    if (root.getAttribute('data-tg-apply-mounted') === '1') {
      return root.querySelector('#online-apply-form');
    }
    root.innerHTML = FORM_HTML;
    root.setAttribute('data-tg-apply-mounted', '1');
    var form = root.querySelector('#online-apply-form');
    bind(form, options);
    prefill(form, options.prefill);
    // Trong dialog, CTA nằm ở footer nên bỏ nút submit cuối form để không trùng.
    if (options.submitButton) {
      var inlineSubmit = form.querySelector(':scope > div:last-child > button[type="submit"]');
      if (inlineSubmit) inlineSubmit.remove();
    }
    return form;
  }

  function isDirty(form) {
    return !!form && form.dataset.tgDirty === '1';
  }

  window.TGApplyForm = {
    mount: mount,
    prefill: prefill,
    isDirty: isDirty
  };
})();
