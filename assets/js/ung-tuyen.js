/* Form đăng ký tuyển dụng — trang độc lập, một trang, không wizard */
(() => {
  const form = document.getElementById('gf-form');
  if (!form) return;

  const $ = (id) => document.getElementById(id);
  const q = (sel, root = document) => root.querySelector(sel);
  const qa = (sel, root = document) => [...root.querySelectorAll(sel)];

  const fields = {
    name: $('gf-name'),
    phone: $('gf-phone'),
    position: $('gf-position'),
    birth: $('gf-birth'),
    gender: $('gf-gender'),
    email: $('gf-email'),
    address: $('gf-address'),
    cccd: $('gf-cccd'),
    cv: $('gf-cv'),
    note: $('gf-note'),
  };

  const success = $('gf-success');
  const codeEl = $('gf-code');
  const counter = $('gf-note-counter');
  const fileHint = $('gf-file-hint');
  const head = q('.gf-head');

  const digits = (value) => (value || '').replace(/[\s().-]/g, '');

  function setError(field, hasError) {
    const wrap = q(`[data-field="${field}"]`);
    if (wrap) wrap.classList.toggle('has-error', hasError);
  }

  function clearErrors() {
    qa('.gf-field').forEach((el) => el.classList.remove('has-error'));
  }

  function validate() {
    clearErrors();
    let firstInvalid = null;
    const fail = (field, el) => {
      setError(field, true);
      if (!firstInvalid) firstInvalid = el || null;
    };

    if (fields.name.value.trim().split(/\s+/).length < 2) fail('name', fields.name);
    if (!/^(0|\+?84)[35789]\d{8}$/.test(digits(fields.phone.value))) fail('phone', fields.phone);
    if (!q('input[name="location"]:checked')) fail('location', q('input[name="location"]'));
    if (!fields.position.value) fail('position', fields.position);

    const birth = Number(fields.birth.value);
    if (fields.birth.value && (birth < 1950 || birth > 2010)) fail('birth', fields.birth);
    if (fields.email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(fields.email.value)) fail('email', fields.email);
    if (fields.cccd.value && !/^\d{12}$/.test(digits(fields.cccd.value))) fail('cccd', fields.cccd);

    const file = fields.cv.files?.[0];
    if (file) {
      const okType = /\.(pdf|docx?|jpe?g|png)$/i.test(file.name);
      if (!okType || file.size > 10 * 1024 * 1024) fail('cv', fields.cv);
    }

    if (firstInvalid) {
      firstInvalid.focus({ preventScroll: false });
      firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
    return !firstInvalid;
  }

  // điền sẵn từ tham số URL: ?vai-tri=qc&khu=cai-lay&job=...
  (function applyQueryPrefill() {
    const params = new URLSearchParams(location.search);
    const job = params.get('job');
    const position = params.get('vai-tri');
    const khuCode = params.get('khu');

    if (position) {
      const match = [...fields.position.options].find((opt) => opt.value === position || opt.textContent.trim() === position);
      if (match) fields.position.value = match.value;
    }

    if (khuCode) {
      const radio = q(`input[name="location"][value="${khuCode}"]`);
      if (radio) radio.checked = true;
    }

    if (job) {
      const note = document.querySelector('.gf-desc');
      if (note && !note.dataset.jobApplied) {
        note.dataset.jobApplied = '1';
        note.insertAdjacentHTML('afterend', `<p class="gf-applying">Bạn đang ứng tuyển: <strong>${job.replace(/[<>&]/g, '')}</strong></p>`);
      }
      if (!position && !fields.position.value) {
        const guess = [...fields.position.options].find((opt) => opt.textContent.trim().toLowerCase() === job.trim().toLowerCase());
        if (guess) fields.position.value = guess.value;
      }
    }
  })();

  // đếm ký tự
  fields.note.addEventListener('input', () => {
    counter.textContent = `${fields.note.value.length}/600`;
  });

  // hiện tên tệp
  fields.cv.addEventListener('change', () => {
    const file = fields.cv.files?.[0];
    setError('cv', false);
    if (!file) {
      fileHint.textContent = 'PDF, DOC, DOCX, JPG, PNG · tối đa 10 MB';
      return;
    }
    fileHint.textContent = `${file.name} · ${Math.round(file.size / 1024)} KB`;
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!validate()) return;

    const code = `TG-2026-${String(Math.floor(1000 + Math.random() * 9000))}`;
    codeEl.textContent = code;

    if (window.TGPendingApplications) {
      const positionSelect = fields.position;
      const loc = q('input[name="location"]:checked');
      const locLabels = { 'tan-huong': 'Khu Tân Hương', 'cai-lay': 'Khu Cái Lậy', 'cho-gao': 'Khu Chợ Gạo', any: 'Theo sắp xếp' };
      window.TGPendingApplications.add({
        name: fields.name.value,
        phone: fields.phone.value,
        email: fields.email.value,
        birth: fields.birth.value,
        address: fields.address.value,
        jobTitle: positionSelect && positionSelect.selectedIndex > 0 ? positionSelect.options[positionSelect.selectedIndex].text : '',
        note: loc ? locLabels[loc.value] || '' : '',
        message: fields.note.value,
        source: 'Website — trang ứng tuyển',
        attachment: fields.cv.files && fields.cv.files[0] ? fields.cv.files[0].name : ''
      });
    }

    form.hidden = true;
    if (head) head.hidden = true;
    success.hidden = false;
    success.focus({ preventScroll: true });
    success.scrollIntoView({ block: 'center', behavior: 'smooth' });
  });

  $('gf-again')?.addEventListener('click', () => {
    form.reset();
    counter.textContent = '0/600';
    fileHint.textContent = 'PDF, DOC, DOCX, JPG, PNG · tối đa 10 MB';
    form.hidden = false;
    if (head) head.hidden = false;
    success.hidden = true;
    fields.name.focus();
  });

  // Enter chuyển xuống ô kế tiếp thay vì gửi form
  form.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || event.target.tagName === 'TEXTAREA' || event.target.type === 'submit') return;
    event.preventDefault();
    const list = qa('input:not([type="radio"]):not([type="file"]), select, textarea', form).filter((el) => !el.disabled);
    const index = list.indexOf(event.target);
    if (index > -1 && list[index + 1]) list[index + 1].focus();
  });
})();
