/* Shared application contract for public forms, the local queue and HR demo. */
(() => {
  "use strict";
  const locations = {
    "tan-huong": "Khu Tân Hương",
    "cai-lay": "Khu Cai Lậy",
    "cho-gao": "Khu Chợ Gạo",
    any: "Theo sắp xếp phù hợp",
  };
  const positions = [
    ["cn-may", "TG-01", "Công nhân may chuyền"],
    ["qc", "TG-02", "Giám sát kiểm phẩm QC"],
    ["ie", "TG-03", "Kỹ sư công nghiệp IE"],
    ["merchandiser", "TG-04", "Quản lý đơn hàng"],
    ["bao-tri", "TG-05", "Bảo trì máy may"],
    ["ke-toan", "TG-06", "Kế toán chi phí"],
    ["hoc-nghe", "TG-07", "Học nghề may có lương"],
    ["lai-xe", "TG-08", "Lái xe đưa đón"],
    ["other", "", "Vị trí khác / hồ sơ dự phòng"],
  ].map(([code, jobId, title]) => ({ code, jobId, title }));
  const normalize = (value) =>
    String(value || "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/đ/g, "d")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, " ")
      .trim();
  const phone = (value) =>
    String(value || "")
      .replace(/[\s().-]/g, "")
      .replace(/^\+?84/, "0");
  const text = (v, max) =>
    typeof v === "string" &&
    v.length <= max &&
    !/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/.test(v);
  const resolve = (data) => {
    data = data || {};
    if (data.jobId)
      return positions.find((p) => p.jobId && p.jobId === data.jobId) || null;
    if (data.position)
      return positions.find((p) => p.code === data.position) || null;
    const target = normalize(data.job || data.jobTitle);
    if (!target) return null;
    const job = (window.TGJobs?.all || []).find(
      (j) =>
        normalize(j.title) === target || normalize(j.shortTitle) === target,
    );
    if (job) return positions.find((p) => p.jobId === job.id) || null;
    const exact = positions.find(
      (p) => normalize(p.title) === target || normalize(p.code) === target,
    );
    if (exact) return exact;
    const aliases = {
      "cong nhan may": "cn-may",
      "ky su ie": "ie",
      "giam sat qc kiem pham 12 18 trieu": "qc",
    };
    return positions.find((p) => p.code === aliases[target]) || null;
  };
  function valid(record) {
    return (
      record &&
      /^p[a-z0-9]+$/.test(record.id) &&
      text(record.name, 140) &&
      record.name.trim() &&
      /^0[35789]\d{8}$/.test(record.phone) &&
      text(record.email, 160) &&
      (record.birth === null ||
        (Number.isInteger(record.birth) &&
          record.birth >= 1950 &&
          record.birth <= 2010)) &&
      text(record.address, 300) &&
      text(record.jobId, 80) &&
      (!record.jobId || /^TG-[\w-]+$/.test(record.jobId)) &&
      text(record.jobTitle, 240) &&
      text(record.note, 2000) &&
      text(record.message, 4000) &&
      text(record.source, 100) &&
      text(record.attachment, 240) &&
      (!record.gender || ["nu", "nam", "khac"].includes(record.gender)) &&
      typeof record.submitted === "string" &&
      Number.isFinite(Date.parse(record.submitted)) &&
      (!record.locationCode || Object.hasOwn(locations, record.locationCode))
    );
  }
  function create(payload) {
    const trim = (key) => String(payload[key] || "").trim();
    const record = {
      id:
        "p" + Date.now().toString(36) + Math.random().toString(36).slice(2, 10),
      name: trim("name"),
      phone: phone(payload.phone),
      email: trim("email"),
      birth:
        payload.birth === "" || payload.birth == null
          ? null
          : Number(payload.birth),
      address: trim("address"),
      jobId: trim("jobId"),
      jobTitle: trim("jobTitle"),
      locationCode: trim("locationCode"),
      note: trim("note"),
      message: trim("message"),
      source: trim("source") || "Website",
      attachment: trim("attachment"),
      gender: trim("gender"),
      status: "new",
      submitted: new Date().toISOString(),
    };
    if (!valid(record))
      throw new Error(
        "Thông tin hồ sơ chưa hợp lệ. Kiểm tra lại các trường đã nhập.",
      );
    record.history = [
      {
        at: record.submitted,
        status: "new",
        note: "Hồ sơ được lưu trong phiên demo của trình duyệt.",
      },
    ];
    return record;
  }
  window.TGApplication = {
    locations,
    positions,
    resolve,
    phone,
    create,
    valid,
  };
})();
