/* Hàng chờ hồ sơ ứng tuyển (demo): trang public ghi vào sessionStorage,
   trang quản trị đọc rồi đưa vào danh sách hồ sơ. */
(() => {
  const KEY = 'texgiang_pending_applications';

  function read() {
    try {
      const raw = sessionStorage.getItem(KEY);
      const list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list : [];
    } catch (_) {
      return [];
    }
  }

  function write(list) {
    try {
      sessionStorage.setItem(KEY, JSON.stringify(list));
    } catch (_) {}
  }

  function add(payload) {
    const list = read();
    const record = {
      id: 'p' + Date.now().toString(36) + Math.floor(Math.random() * 1000),
      name: (payload.name || '').trim(),
      phone: (payload.phone || '').trim(),
      email: (payload.email || '').trim(),
      birth: payload.birth ? Number(payload.birth) : null,
      address: (payload.address || '').trim(),
      jobId: payload.jobId || '',
      jobTitle: payload.jobTitle || '',
      note: (payload.note || '').trim(),
      message: (payload.message || '').trim(),
      source: payload.source || 'Website',
      status: 'new',
      submitted: new Date().toISOString(),
      attachment: payload.attachment || '',
      history: [
        {
          at: new Date().toISOString(),
          status: 'new',
          note: 'Hồ sơ gửi từ website (demo).',
        },
      ],
    };
    list.push(record);
    write(list);
    return record;
  }

  function drain() {
    const list = read();
    write([]);
    return list;
  }

  window.TGPendingApplications = { add, read, drain };
})();
