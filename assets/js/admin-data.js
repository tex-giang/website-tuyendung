/* Session-scoped prototype repository. No authentication, network writes or CV storage. */
(() => {
  "use strict";
  const KEY = "texgiang_admin_demo_v2";
  const titles = [
    "Công nhân may chuyền",
    "Giám sát kiểm phẩm QC",
    "Kỹ sư công nghiệp IE",
    "Quản lý đơn hàng",
    "Bảo trì máy may",
    "Kế toán chi phí",
    "Học nghề may có lương",
    "Lái xe đưa đón",
  ];
  const states = {
    new: "Mới nhận",
    contacted: "Đã liên hệ",
    interview: "Hẹn phỏng vấn",
    hired: "Trúng tuyển",
    rejected: "Không phù hợp",
  };
  const date = () =>
    new Intl.DateTimeFormat("sv-SE", { timeZone: "Asia/Ho_Chi_Minh" }).format(
      new Date(),
    );
  const seedJobs = MOCK_JOBS.map((j, i) => {
    const numbers =
      j.salary.match(/[\d.]+/g)?.map((n) => Number(n.replaceAll(".", ""))) ||
      [];
    return {
      ...j,
      title: titles[i],
      quantity: Number(j.title.match(/^\d+/)?.[0] || 1),
      salaryMin: numbers[0] || 0,
      salaryMax: numbers[1] || 0,
      state: j.status === "closed" ? "closed" : "published",
      urgent: ["urgent", "hot"].includes(j.status),
      deadlineISO: j.deadline.split("/").reverse().join("-"),
      requirements: ["TG-01", "TG-07"].includes(j.id)
        ? "Có đào tạo cho người chưa có kinh nghiệm."
        : "Trao đổi kinh nghiệm và yêu cầu chi tiết khi phỏng vấn.",
      benefits:
        "Trao đổi mức thu nhập, chế độ và lịch làm việc cùng bộ phận tuyển dụng.",
    };
  });
  const candidates = [
    {
      id: "1",
      name: "Lê Thị Mai",
      phone: "0918223344",
      email: "lethimai.demo@example.com",
      birth: 1998,
      address: "An Nhơn, Đồng Tháp",
      jobId: "TG-01",
      status: "new",
      note: "Chưa có kinh nghiệm, mong muốn học nghề.",
      submitted: "2026-10-29T13:10:00+07:00",
      source: "Website",
      message:
        "Em muốn đăng ký lớp học nghề có lương và hỏi về tuyến xe tại An Nhơn.",
    },
    {
      id: "2",
      name: "Trần Quốc Bảo",
      phone: "0939556789",
      email: "quocbao.demo@example.com",
      birth: 1992,
      address: "Tân Hương, Đồng Tháp",
      jobId: "TG-02",
      status: "contacted",
      note: "4 năm kinh nghiệm QC may jacket.",
      submitted: "2026-10-29T11:20:00+07:00",
      source: "Zalo / QR",
      message: "Tôi có kinh nghiệm kiểm phẩm và quản lý tổ QC.",
    },
    {
      id: "3",
      name: "Nguyễn Thị Bé Hai",
      phone: "0987112233",
      email: "",
      birth: 1995,
      address: "Tân Hiệp, Đồng Tháp",
      jobId: "TG-01",
      status: "hired",
      note: "Đã xác nhận lịch nhận việc.",
      submitted: "2026-10-28T16:45:00+07:00",
      source: "Giới thiệu",
      message: "Đã làm công nhân may chuyền 5 năm.",
    },
    {
      id: "4",
      name: "Phạm Văn Tư",
      phone: "0909334221",
      email: "",
      birth: 1988,
      address: "Châu Thành, Đồng Tháp",
      jobId: "TG-05",
      status: "interview",
      note: "Trao đổi kỹ năng bảo trì máy điện tử.",
      submitted: "2026-10-28T09:05:00+07:00",
      source: "Website",
      message: "Tôi có 10 năm kinh nghiệm sửa chữa máy may công nghiệp.",
      appointment: "2026-10-30T14:00:00+07:00",
    },
  ].map((c) => ({
    ...c,
    history: [
      {
        at: c.submitted,
        status: c.status,
        note: "Hồ sơ mẫu được khởi tạo trong bản demo.",
      },
    ],
  }));
  const seed = {
    version: 2,
    jobs: seedJobs,
    candidates,
    settings: {
      siteName: "Tuyển dụng TEX-GIANG",
      legalName: "Công ty Cổ phần TEX-GIANG (TGI) & Công ty Cổ phần May TG (TG APPAREL)",
      address: "Lô BII-8, đường D3, KCN Tân Hương, xã Tân Hương, tỉnh Đồng Tháp",
      seoTitle: "Tuyển dụng TEX-GIANG (TGI) & TG APPAREL (TGA) — KCN Tân Hương",
      seoDescription:
        "Vị trí đang tuyển tại Công ty Cổ phần TEX-GIANG và Công ty Cổ phần May TG (TG APPAREL), KCN Tân Hương, Đồng Tháp. Xe đưa rước, cơm ca, BHXH đầy đủ.",
      pageSize: 6,
      applyEmail: "chung.doan@tex-giang.com.vn",
    },
    content: {
      slogan: "Một công việc ổn định. Thu nhập xứng đáng.",
      philosophy: "Vì con người — Vì chất lượng — Vì tương lai bền vững",
      hero: "Cổng tuyển dụng Công ty Cổ phần TEX-GIANG (TGI) và Công ty Cổ phần May TG (TG APPAREL) tại KCN Tân Hương, Đồng Tháp.",
      about:
        "TEX-GIANG thành lập năm 2007, hiện có 3 nhà máy tại Tân Hương, Cái Lậy và Chợ Gạo. TG APPAREL vận hành 4 nhà máy trong cùng hệ thống với khoảng 4.500 lao động.",
      benefits:
        "Môi trường làm việc đạt SA8000, ISO 9001; thưởng tháng 13 và các dịp lễ, phụ cấp nuôi con nhỏ, xe đưa rước và cơm ca.",
      hotline: "02733937274",
      zalo: "02733937274",
      email: "chung.doan@tex-giang.com.vn",
      fanpage: "",
    },
    posts: [
      {
        id: "1",
        title: "Ngày hội việc làm Châu Thành 2026",
        category: "Tuyển dụng",
        state: "published",
        body: "Thông tin minh hoạ về hoạt động tư vấn việc làm và tuyển dụng tại TEX-GIANG.",
      },
      {
        id: "2",
        title: "Hoạt động công đoàn tháng 10",
        category: "Nội bộ",
        state: "draft",
        body: "Nội dung mẫu để thử luồng soạn bài và xem trước.",
      },
      {
        id: "3",
        title: "Chuẩn bị hồ sơ ứng tuyển",
        category: "Cẩm nang",
        state: "draft",
        body: "Chuẩn bị thông tin liên hệ, kinh nghiệm và vị trí mong muốn trước khi ứng tuyển.",
      },
    ],
    banner: {
      src: "../assets/images/banner-texgiang.webp",
      alt: "Cổng Nhà máy Tân Hương 2 — TEX-GIANG",
      focal: 50,
      width: 2048,
      height: 768,
      bytes: 226330,
      notice:
        "Thưởng nóng 2.000.000đ cho công nhân may mới — xem điều kiện tuyển dụng.",
      link: "apply.html",
      enabled: true,
    },
  };
  let state = structuredClone(seed),
    recovered = false;
  try {
    const raw = sessionStorage.getItem(KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (
        parsed.version !== 2 ||
        !Array.isArray(parsed.jobs) ||
        parsed.jobs.length > 200 ||
        !Array.isArray(parsed.candidates) ||
        parsed.candidates.length !== 4 ||
        !parsed.settings ||
        !parsed.content ||
        !parsed.banner ||
        !Array.isArray(parsed.posts)
      )
        throw Error("Invalid session");
      const allowedIds = new Set(candidates.map((c) => c.id));
      const str = (v, max = 8000) => typeof v === "string" && v.length <= max;
      const validDate = (v) =>
        typeof v === "string" && Number.isFinite(Date.parse(v));
      if (
        parsed.candidates.some(
          (c) =>
            !allowedIds.has(c.id) ||
            !states[c.status] ||
            !str(c.note, 2000) ||
            !str(c.name, 140) ||
            !/^0\d{9}$/.test(c.phone) ||
          !str(c.email, 160) ||
          !Number.isInteger(c.birth) || c.birth < 1900 || c.birth > 2100 ||
            !str(c.message) ||
            !str(c.address, 300) ||
            !str(c.source, 100) ||
            !validDate(c.submitted) ||
            !Array.isArray(c.history) ||
            c.history.length > 100 ||
            c.history.some(
              (h) =>
                !validDate(h.at) || !states[h.status] || !str(h.note, 2000),
            ),
        )
      )
        throw Error("Invalid candidates");
      if (new Set(parsed.candidates.map((c) => c.id)).size !== 4)
        throw Error("Duplicate candidates");
      if (
        parsed.jobs.some(
          (j) =>
            !/^TG-[\w-]+$/.test(j.id) ||
            !["draft", "published", "closed"].includes(j.state) ||
            !str(j.title, 140) ||
            !str(j.description) ||
            !str(j.requirements) ||
            !str(j.benefits) ||
            !str(j.location, 300) ||
            !["tex-giang", "tg-apparel"].includes(j.companyCode) ||
            !str(j.company, 140) ||
          !str(j.salary, 200) ||
          !Number.isInteger(j.quantity) || j.quantity < 1 || j.quantity > 10000 ||
          !Number.isFinite(j.salaryMin) || j.salaryMin < 0 || j.salaryMin > 1000000000 ||
          !Number.isFinite(j.salaryMax) || j.salaryMax < 0 || j.salaryMax > 1000000000 ||
            (j.deadlineISO && !validDate(j.deadlineISO)),
        )
      )
        throw Error("Invalid jobs");
      if (
        new Set(parsed.jobs.map((j) => j.id)).size !== parsed.jobs.length ||
        parsed.candidates.some(
          (c) => !parsed.jobs.some((j) => j.id === c.jobId),
        )
      )
        throw Error("Invalid relationship");
      if (
        !Number.isInteger(parsed.settings.pageSize) ||
        parsed.settings.pageSize < 2 ||
        parsed.settings.pageSize > 50 ||
        Object.values(parsed.settings).some(
          (v) => typeof v !== "number" && !str(v, 500),
        )
      )
        throw Error("Invalid settings");
      if (Object.keys(seed.content).some((k) => !str(parsed.content[k])))
        throw Error("Invalid content");
      if (
        parsed.posts.length > 200 ||
        parsed.posts.some(
          (p) =>
            !str(p.id, 80) ||
            !str(p.title, 160) ||
            !str(p.body, 12000) ||
            !str(p.category, 80) ||
            !["draft", "published"].includes(p.state),
        )
      )
        throw Error("Invalid posts");
      if (
        !str(parsed.banner.src, 1200000) ||
        !str(parsed.banner.alt, 240) ||
        !str(parsed.banner.notice, 400) ||
        !str(parsed.banner.link, 300) ||
        !Number.isFinite(parsed.banner.width) ||
        !Number.isFinite(parsed.banner.height) ||
        !Number.isFinite(parsed.banner.bytes)
      )
        throw Error("Invalid media");
      state = parsed;
    }
  } catch (_) {
    recovered = true;
  }
  const save = (next) => {
    // Publish in-memory state only after the session write succeeds.
    sessionStorage.setItem(KEY, JSON.stringify(next));
    state = next;
    window.dispatchEvent(new Event("admin-data-change"));
  };
  window.AdminData = {
    states,
    date,
    recovered,
    snapshot: () => structuredClone(state),
    job: (id) => structuredClone(state.jobs.find((j) => j.id === id) || null),
    candidate: (id) =>
      structuredClone(state.candidates.find((c) => c.id === id) || null),
    jobStatus: (j) =>
      j.state === "draft"
        ? "draft"
        : j.state === "closed"
          ? "closed"
          : j.deadlineISO < date()
            ? "expired"
            : "published",
    update: (fn) => {
      const next = structuredClone(state);
      fn(next);
      save(next);
    },
    reset: () => save(structuredClone(seed)),
    safeLink: (value) => {
      try {
        const u = new URL(value, location.href);
        return ["http:", "https:"].includes(u.protocol) ? u.href : null;
      } catch (_) {
        return null;
      }
    },
  };
})();
