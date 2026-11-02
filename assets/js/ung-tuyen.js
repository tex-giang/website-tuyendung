/* Form đăng ký ứng tuyển — 3 bước, validate tại chỗ, lưu nháp cục bộ */
(() => {
  const form = document.getElementById('apx-form');
  if (!form) return;

  const DRAFT_KEY = 'texgiang_apply_ung_tuyen_v1';
  const $ = (id) => document.getElementById(id);
  const q = (sel, root = document) => root.querySelector(sel);
  const qa = (sel, root = document) => [...root.querySelectorAll(sel)];

  const steps = qa('.apx-step');
  const indicators = qa('.apx-step-ind');
  const progressBar = q('#apx-progress');
  const navMeta = q('#apx-nav-meta');
  const btnNext = q('#apx-next');
  const btnBack = q('#apx-back');
  const btnSubmit = q('#apx-submit');
  const alertBox = q('#apx-alert');
  const reviewList = q('#apx-review');
  const successPanel = q('#apx-success');
  const codeEl = q('#apx-code');

  const fields = {
    name: q('#apx-name'),
    phone: q('#apx-phone'),
    birth: q('#apx-birth'),
    gender: q('#apx-gender'),
    email: q('#apx-email'),
    cccd: q('#apx-cccd'),
    address: q('#apx-address'),
    position: q('#apx-position'),
    exp: q('#apx-exp'),
    skill: q('#apx-skill'),
    shift: q('#apx-shift'),
    salary: q('#apx-salary'),
    note: q('#apx-note'),
    cv: q('#apx-cv'),
    consent: q('#apx-consent'),
  };

  let step = 1;
  let cvFile = null;
  let submitted = false;

  const text = (value) => (value || '').trim();
  const digits = (value) => (value || '').replace(/[\s().-]/g, '');

  function setFieldError(field, hasError) {
    const wrap = q(`[data-field="${field}"]`);
    if (wrap) wrap.classList.toggle('has-error', hasError);
  }

  function clearErrors() {
    qa('.apx-field').forEach((el) => el.classList.remove('has-error'));
    alertBox.textContent = '';
    alertBox.style.display = 'none';
  }

  function showAlert(message) {
    alertBox.textContent = message;
    alertBox.style.display = 'block';
  }

  // ---------- kiểm tra từng bước ----------
  function validateStep(current) {
    clearErrors();
    let ok = true;
    let firstInvalid = null;

    const fail = (field, el) => {
      ok = false;
      setFieldError(field, true);
      if (!firstInvalid) firstInvalid = el || null;
    };

    if (current === 1) {
      if (text(fields.name.value).split(/\s+/).length < 2) fail('name', fields.name);
      if (!/^(0|\+?84)[35789]\d{8}$/.test(digits(fields.phone.value))) fail('phone', fields.phone);
      const birth = Number(fields.birth.value);
      if (fields.birth.value && (birth < 1950 || birth > 2010)) fail('birth', fields.birth);
      if (fields.email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(fields.email.value)) fail('email', fields.email);
      if (fields.cccd.value && !/^\d{12}$/.test(digits(fields.cccd.value))) fail('cccd', fields.cccd);
    }

    if (current === 2) {
      const loc = q('input[name="location"]:checked');
      if (!loc) fail('location', q('input[name="location"]') ? q('input[name="location"]').closest('.apx-choices') : null);
      if (!fields.position.value) fail('position', fields.position);
      if (fields.salary.value) {
        const salary = Number(fields.salary.value);
        if (Number.isNaN(salary) || salary < 0 || salary > 60) fail('salary', fields.salary);
      }
      if (cvFile && cvFile.size > 10 * 1024 * 1024) fail('cv', fields.cv);
    }

    if (current === 3 && !fields.consent.checked) fail('consent', fields.consent);

    if (!ok) {
      showAlert('Vui lòng kiểm tra các ô được đánh dấu đỏ.');
      if (firstInvalid && firstInvalid.focus) firstInvalid.focus();
    }
    return ok;
  }

  // ---------- điều hướng ----------
  function renderStep() {
    steps.forEach((section) => {
      section.classList.toggle('is-active', Number(section.dataset.step) === step);
    });
    indicators.forEach((ind) => {
      const index = Number(ind.dataset.ind);
      ind.classList.toggle('is-active', index === step);
      ind.classList.toggle('is-done', index < step);
    });
    progressBar.style.width = `${(step / steps.length) * 100}%`;
    navMeta.textContent = `Bước ${step}/${steps.length}`;
    btnBack.hidden = step === 1;
    btnNext.hidden = step === steps.length;
    btnSubmit.hidden = step !== steps.length;
    if (step === steps.length) renderReview();

    const focusTarget = q(`.apx-step[data-step="${step}"] .form-input, .apx-step[data-step="${step}"] input, .apx-step[data-step="${step}"] select`);
    if (focusTarget && !focusTarget.type?.includes('hidden')) focusTarget.focus({ preventScroll: true });
  }

  btnNext.addEventListener('click', () => {
    if (!validateStep(step)) return;
    step = Math.min(step + 1, steps.length);
    renderStep();
    scrollCard();
  });

  btnBack.addEventListener('click', () => {
    clearErrors();
    step = Math.max(step - 1, 1);
    renderStep();
    scrollCard();
  });

  function scrollCard() {
    const card = q('.apx-card');
    if (!card) return;
    const top = card.getBoundingClientRect().top + window.scrollY - 96;
    window.scrollTo({ top, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
  }

  // Enter để sang bước tiếp
  form.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    const tag = event.target.tagName;
    if (tag === 'TEXTAREA' || tag === 'BUTTON' || event.target.type === 'submit') return;
    event.preventDefault();
    if (step < steps.length) btnNext.click();
  });

  // ---------- tổng kết bước 3 ----------
  const locationLabels = {
    'tan-huong': 'Khu Tân Hương',
    'cai-lay': 'Khu Cái Lậy',
    'cho-gao': 'Khu Chợ Gạo',
    any: 'Theo sắp xếp phù hợp',
  };
  const labelOf = (select) => (select && select.value ? select.options[select.selectedIndex].textContent.trim() : '');

  function renderReview() {
    const loc = q('input[name="location"]:checked');
    const rows = [
      ['Họ và tên', text(fields.name.value)],
      ['Số điện thoại', text(fields.phone.value)],
      ['Năm sinh', text(fields.birth.value)],
      ['Giới tính', labelOf(fields.gender)],
      ['Email', text(fields.email.value)],
      ['Số CCCD', text(fields.cccd.value)],
      ['Địa chỉ', text(fields.address.value)],
      ['Nơi làm việc', loc ? locationLabels[loc.value] : ''],
      ['Vị trí', labelOf(fields.position)],
      ['Kinh nghiệm', labelOf(fields.exp)],
      ['Tay nghề', labelOf(fields.skill)],
      ['Ca làm', labelOf(fields.shift)],
      ['Thu nhập mong muốn', fields.salary.value ? `${fields.salary.value} triệu/tháng` : ''],
      ['Tệp đính kèm', cvFile ? `${cvFile.name} (${Math.round(cvFile.size / 1024)} KB)` : ''],
      ['Giới thiệu', text(fields.note.value)],
    ];

    const filled = rows.filter(([, value]) => String(value || '').trim() !== '');

    reviewList.innerHTML = filled
      .map(([label, value]) => `<div class="apx-review__row"><dt>${label}</dt><dd class="${value ? '' : 'is-empty'}">${value ? escapeHtml(value) : 'Chưa điền'}</dd></div>`)
      .join('');
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]);
  }

  // ---------- tệp đính kèm ----------
  const fileBox = q('#apx-file');
  const fileName = q('#apx-file-name');

  fields.cv.addEventListener('change', () => {
    const file = fields.cv.files?.[0];
    if (!file) return;
    const allowed = /\.(pdf|docx?|jpe?g|png)$/i.test(file.name);
    if (!allowed || file.size > 10 * 1024 * 1024) {
      setFieldError('cv', true);
      fields.cv.value = '';
      cvFile = null;
      fileBox.hidden = true;
      return;
    }
    setFieldError('cv', false);
    cvFile = file;
    fileName.textContent = `${file.name} · ${Math.round(file.size / 1024)} KB`;
    fileBox.hidden = false;
  });

  q('#apx-file-remove')?.addEventListener('click', () => {
    fields.cv.value = '';
    cvFile = null;
    fileBox.hidden = true;
  });

  // ---------- đếm ký tự ----------
  const counter = q('#apx-note-counter');
  fields.note.addEventListener('input', () => {
    counter.textContent = `${fields.note.value.length}/600`;
  });

  // ---------- lưu nháp ----------
  const draftBanner = q('#apx-draft');
  let saveTimer = null;

  function collect() {
    const data = {};
    Object.entries(fields).forEach(([key, el]) => {
      if (!el || key === 'cv') return;
      data[key] = el.type === 'checkbox' ? el.checked : el.value;
    });
    const loc = q('input[name="location"]:checked');
    data.location = loc ? loc.value : '';
    return data;
  }

  function saveDraft() {
    if (submitted) return;
    try {
      localStorage.setItem(DRAFT_KEY, JSON.stringify(collect()));
    } catch (_) {}
  }

  function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(saveDraft, 400);
  }

  form.addEventListener('input', scheduleSave);
  form.addEventListener('change', scheduleSave);

  function readDraft() {
    try {
      const raw = localStorage.getItem(DRAFT_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  }

  function hasDraftContent(draft) {
    if (!draft) return false;
    return ['name', 'phone', 'position', 'location', 'note'].some((key) => text(String(draft[key] || '')) !== '');
  }

  q('#apx-draft-restore')?.addEventListener('click', () => {
    const draft = readDraft();
    if (!draft) return;
    Object.entries(fields).forEach(([key, el]) => {
      if (!el || key === 'cv') return;
      if (el.type === 'checkbox') el.checked = Boolean(draft[key]);
      else if (draft[key] !== undefined) el.value = draft[key];
    });
    if (draft.location) {
      const radio = q(`input[name="location"][value="${draft.location}"]`);
      if (radio) radio.checked = true;
    }
    counter.textContent = `${fields.note.value.length}/600`;
    draftBanner.classList.remove('is-visible');
    step = 1;
    renderStep();
  });

  q('#apx-draft-discard')?.addEventListener('click', () => {
    localStorage.removeItem(DRAFT_KEY);
    draftBanner.classList.remove('is-visible');
    form.reset();
    counter.textContent = '0/600';
    cvFile = null;
    fileBox.hidden = true;
  });

  // ---------- gửi ----------
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!validateStep(3)) return;

    submitted = true;
    clearTimeout(saveTimer);

    const code = `TG-2026-${String(Math.floor(1000 + Math.random() * 9000))}`;
    codeEl.textContent = code;

    form.hidden = true;
    successPanel.classList.add('is-visible');
    successPanel.focus({ preventScroll: true });
    scrollCard();

    try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
    if (typeof window.showToast === 'function') {
      window.showToast('Đã ghi nhận hồ sơ demo. Nhân sự sẽ gọi lại trong 24 giờ làm việc.', 'success');
    }
  });

  q('#apx-again')?.addEventListener('click', () => {
    submitted = false;
    form.reset();
    cvFile = null;
    fileBox.hidden = true;
    counter.textContent = '0/600';
    form.hidden = false;
    successPanel.classList.remove('is-visible');
    step = 1;
    renderStep();
    scrollCard();
  });

  // ---------- khởi tạo ----------
  renderStep();
  if (hasDraftContent(readDraft())) draftBanner.classList.add('is-visible');
})();
