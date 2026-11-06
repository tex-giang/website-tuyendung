/* Trang nộp hồ sơ ứng tuyển độc lập — 4 trường: họ tên, số điện thoại,
   vị trí tuyển dụng, nơi muốn làm việc. Gửi vào hàng chờ demo để hiện trong admin. */
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
  };

  // Nhãn khu để HR đọc trong hồ sơ.
  const locationLabels = {
    'tan-huong': 'Tân Hương',
    'cai-lay': 'Cái Lậy',
    'cho-gao': 'Chợ Gạo',
    any: 'Theo sắp xếp',
  };

  const success = $('gf-success');
  const codeEl = $('gf-code');
  const head = q('.gf-head');

  const digits = (value) => (value || '').replace(/[\s().-]/g, '');

  function setError(field, hasError) {
    const wrap = q(`[data-field="${field}"]`);
    if (wrap) wrap.classList.toggle('has-error', hasError);
  }

  function clearErrors() {
    qa('.gf-field').forEach((el) => el.classList.remove('has-error'));
  }

  function selectedLocation() {
    return q('input[name="location"]:checked');
  }

  function validate() {
    clearErrors();
    let firstInvalid = null;
    const fail = (field, el) => {
      setError(field, true);
      if (!firstInvalid) firstInvalid = el || null;
    };

    if (fields.name.value.trim().split(/\s+/).filter(Boolean).length < 2) fail('name', fields.name);
    if (!/^(0|\+?84)[35789]\d{8}$/.test(digits(fields.phone.value))) fail('phone', fields.phone);
    if (!fields.position.value) fail('position', fields.position);
    if (!selectedLocation()) fail('location', q('input[name="location"]'));

    if (firstInvalid) {
      firstInvalid.focus({ preventScroll: false });
      firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
    return !firstInvalid;
  }

  // Điền sẵn từ tham số URL: ?vai-tri=qc&khu=cai-lay&job=...
  (function prefillFromQuery() {
    const params = new URLSearchParams(location.search);
    const position = params.get('vai-tri');
    const khu = params.get('khu');
    const job = params.get('job');

    if (position) {
      const match = qa('option', fields.position).find(
        (opt) => opt.value === position || opt.textContent.trim() === position
      );
      if (match) fields.position.value = match.value;
    }

    if (khu) {
      const radio = q(`input[name="location"][value="${CSS.escape(khu)}"]`);
      if (radio) radio.checked = true;
    }

    if (!fields.position.value && job) {
      const guess = qa('option', fields.position).find(
        (opt) => opt.value && opt.textContent.trim().toLowerCase() === job.trim().toLowerCase()
      );
      if (guess) fields.position.value = guess.value;
    }
  })();

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!validate()) return;

    const selectedOption = fields.position.options[fields.position.selectedIndex];
    const loc = selectedLocation();
    const code = `TG-2026-${String(Math.floor(1000 + Math.random() * 9000))}`;
    codeEl.textContent = code;

    if (window.TGPendingApplications) {
      window.TGPendingApplications.add({
        name: fields.name.value.trim(),
        phone: digits(fields.phone.value),
        jobId: selectedOption.getAttribute('data-job-id') || '',
        jobTitle: selectedOption.textContent.trim(),
        note: loc ? locationLabels[loc.value] || '' : '',
        message: '',
        source: 'Website — form ứng tuyển',
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
    clearErrors();
    form.hidden = false;
    if (head) head.hidden = false;
    success.hidden = true;
    fields.name.focus();
  });

  // Enter chuyển xuống ô kế tiếp thay vì gửi form.
  form.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || event.target.type === 'submit') return;
    event.preventDefault();
    const list = qa('input:not([type="radio"]), select', form).filter((el) => !el.disabled);
    const index = list.indexOf(event.target);
    if (index > -1 && list[index + 1]) list[index + 1].focus();
  });
})();
