/**
 * TEX-GIANG RECRUITMENT PORTAL - JOBS LIST & FILTER JAVASCRIPT
 * Handles client-side mock search, multi-facet filtering, active filter pills, and pagination.
 */

// Demo Jobs Dataset (Clearly labeled as mock data for frontend demonstration)
const MOCK_JOBS = [
  {
    id: 'TG-01',
    title: '300 Công Nhân May Chuyền Công Nghiệp (Áo Polo, Jacket Xuất Khẩu)',
    company: 'TEX-GIANG',
    companyCode: 'tex-giang',
    location: 'Nhà máy Tân Hương 2, Đồng Tháp',
    locationCode: 'tan-huong',
    department: 'Sản xuất may mặc',
    departmentCode: 'production',
    salary: '8.000.000 - 16.000.000 VNĐ',
    salaryRange: '8-12',
    date: '28/10/2026',
    deadline: '30/11/2026',
    tags: ['Tuyển Gấp', 'Đang Tuyển'],
    badgeColor: 'bg-rose-500',
    status: 'urgent',
    description: 'Vận hành máy may 1 kim, 2 kim, vắt sổ theo công đoạn chuyền. Hưởng lương sản phẩm + thưởng năng suất + ăn trưa miễn phí + xe đưa rước. Chưa biết may được đào tạo có lương.'
  },
  {
    id: 'TG-02',
    title: '05 Giám Sát Kiểm Phẩm Chất Lượng QC May Mặc (QC Team Leader)',
    company: 'TG APPAREL',
    companyCode: 'tg-apparel',
    location: 'Xưởng 2, KCN Tân Hương, Đồng Tháp',
    locationCode: 'tan-huong',
    department: 'Kiểm soát chất lượng (QA/QC)',
    departmentCode: 'qc',
    salary: '12.000.000 - 18.000.000 VNĐ',
    salaryRange: '12-18',
    date: '26/10/2026',
    deadline: '25/11/2026',
    tags: ['Hot', 'Đang Tuyển'],
    badgeColor: 'bg-[#12345A]',
    status: 'hot',
    description: 'Kiểm tra thông số kỹ thuật, chất lượng đường may trên chuyền may xuất khẩu; quản lý tổ kiểm phẩm và làm việc với khách hàng quốc tế.'
  },
  {
    id: 'TG-03',
    title: '02 Kỹ Sư Công Nghiệp & Định Mức Năng Suất (IE Engineer)',
    company: 'TEX-GIANG',
    companyCode: 'tex-giang',
    location: 'Nhà máy Tân Hương 2, Đồng Tháp',
    locationCode: 'tan-huong',
    department: 'Kỹ thuật, IE & Mẫu rập',
    departmentCode: 'ie',
    salary: '15.000.000 - 22.000.000 VNĐ',
    salaryRange: 'over-18',
    date: '25/10/2026',
    deadline: '28/11/2026',
    tags: ['Đang Tuyển'],
    badgeColor: 'bg-[#087A55]',
    status: 'active',
    description: 'Bấm giờ thao tác, chuẩn hóa quy trình may GSD, cân bằng chuyền may và áp dụng các giải pháp cải tiến tinh gọn Lean Manufacturing.'
  },
  {
    id: 'TG-04',
    title: '03 Chuyên Viên Quản Lý Đơn Hàng Ngành May (Merchandiser)',
    company: 'TG APPAREL',
    companyCode: 'tg-apparel',
    location: 'KCN Tân Hương & VP Đại Diện',
    locationCode: 'hcm',
    department: 'Văn phòng & Kinh doanh',
    departmentCode: 'office',
    salary: '13.000.000 - 20.000.000 VNĐ',
    salaryRange: '12-18',
    date: '24/10/2026',
    deadline: '26/11/2026',
    tags: ['Đang Tuyển'],
    badgeColor: 'bg-[#087A55]',
    status: 'active',
    description: 'Làm việc trực tiếp với khách hàng nước ngoài, theo dõi tiến độ phát triển mẫu, cung ứng nguyên phụ liệu may và xuất hàng FOB/CMT.'
  },
  {
    id: 'TG-05',
    title: '04 Nhân Viên Bảo Trì Máy May Công Nghiệp & Điện Xưởng',
    company: 'TEX-GIANG',
    companyCode: 'tex-giang',
    location: 'KCN Tân Hương, Châu Thành, Đồng Tháp',
    locationCode: 'tan-huong',
    department: 'Bảo trì cơ điện',
    departmentCode: 'maintenance',
    salary: '9.500.000 - 14.500.000 VNĐ',
    salaryRange: '8-12',
    date: '22/10/2026',
    deadline: '22/11/2026',
    tags: ['Đang Tuyển'],
    badgeColor: 'bg-[#087A55]',
    status: 'active',
    description: 'Bảo trì, sửa chữa máy may 1 kim, 2 kim, kansai, máy cắt tự động và hệ thống điện nhà xưởng sản xuất.'
  },
  {
    id: 'TG-06',
    title: '02 Kế Toán Chi Phí & Giá Thành May Xuất Khẩu',
    company: 'TEX-GIANG',
    companyCode: 'tex-giang',
    location: 'Nhà máy Tân Hương 2, Đồng Tháp',
    locationCode: 'tan-huong',
    department: 'Văn phòng & Kinh doanh',
    departmentCode: 'office',
    salary: '11.000.000 - 16.000.000 VNĐ',
    salaryRange: '8-12',
    date: '20/10/2026',
    deadline: '31/10/2026',
    tags: ['Sắp Hết Hạn'],
    badgeColor: 'bg-amber-500',
    status: 'expiring',
    description: 'Tập hợp chi phí nguyên phụ liệu vải sợi, tính giá thành đơn hàng may xuất khẩu, kiểm soát định mức và lập báo cáo tài chính.'
  },
  {
    id: 'TG-07',
    title: '100 Học Nghề May Miễn Phí (Được Trợ Cấp Lương Học Việc)',
    company: 'TEX-GIANG',
    companyCode: 'tex-giang',
    location: 'Trung tâm đào tạo Tân Hương 2',
    locationCode: 'tan-huong',
    department: 'Sản xuất may mặc',
    departmentCode: 'production',
    salary: 'Hỗ trợ lương học việc + Cơm ca',
    salaryRange: 'under-8',
    date: '18/10/2026',
    deadline: '30/11/2026',
    tags: ['Đào Tạo Có Lương'],
    badgeColor: 'bg-emerald-600',
    status: 'training',
    description: 'Dành cho lao động chưa từng may vá. Đào tạo 7 - 15 ngày các kỹ năng may cơ bản, bao cơm trưa và bố trí việc làm ngay sau khi hoàn thành.'
  },
  {
    id: 'TG-08',
    title: 'Nhân Viên Lái Xe Đưa Đón Cán Bộ Công Nhân Viên (Bằng D/E)',
    company: 'TG APPAREL',
    companyCode: 'tg-apparel',
    location: 'Tuyến Châu Thành - Tân Hương',
    locationCode: 'tan-huong',
    department: 'Hành chính hậu cần',
    departmentCode: 'office',
    salary: '9.000.000 - 11.000.000 VNĐ',
    salaryRange: '8-12',
    date: '10/10/2026',
    deadline: '20/10/2026',
    tags: ['Đã Đóng Tuyển'],
    badgeColor: 'bg-slate-400',
    status: 'closed',
    description: 'Vị trí này đã tuyển đủ chỉ tiêu cho đợt 3. Ứng viên quan tâm có thể gửi hồ sơ chờ cho các đợt mở rộng tiếp theo.'
  }
];

let currentFilters = {
  keyword: '',
  company: '',
  location: '',
  department: '',
  salary: '',
  sort: 'newest'
};

document.addEventListener('DOMContentLoaded', function () {
  initJobFilters();
  renderJobs();

  // Mobile drawer filter toggle
  const mobileFilterBtn = document.getElementById('toggle-mobile-filters');
  const filterDrawer = document.getElementById('filters-sidebar');
  const closeFilterDrawerBtn = document.getElementById('close-filter-drawer');

  if (mobileFilterBtn && filterDrawer) {
    mobileFilterBtn.addEventListener('click', () => {
      filterDrawer.classList.remove('hidden');
    });
  }

  if (closeFilterDrawerBtn && filterDrawer) {
    closeFilterDrawerBtn.addEventListener('click', () => {
      filterDrawer.classList.add('hidden');
    });
  }
});

function initJobFilters() {
  const keywordInput = document.getElementById('filter-keyword');
  const companySelect = document.getElementById('filter-company');
  const locationSelect = document.getElementById('filter-location');
  const departmentSelect = document.getElementById('filter-department');
  const salarySelect = document.getElementById('filter-salary');
  const sortSelect = document.getElementById('filter-sort');
  const filterForm = document.getElementById('jobs-filter-form');

  // Parse URL query params if present
  const params = new URLSearchParams(window.location.search);
  if (params.get('keyword') && keywordInput) {
    keywordInput.value = params.get('keyword');
    currentFilters.keyword = params.get('keyword');
  }
  if (params.get('company') && companySelect) {
    companySelect.value = params.get('company');
    currentFilters.company = params.get('company');
  }
  if (params.get('location') && locationSelect) {
    locationSelect.value = params.get('location');
    currentFilters.location = params.get('location');
  }

  if (filterForm) {
    filterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      applyFilters();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', function () {
      currentFilters.sort = this.value;
      renderJobs();
    });
  }
}

function applyFilters() {
  currentFilters.keyword = (document.getElementById('filter-keyword')?.value || '').trim();
  currentFilters.company = document.getElementById('filter-company')?.value || '';
  currentFilters.location = document.getElementById('filter-location')?.value || '';
  currentFilters.department = document.getElementById('filter-department')?.value || '';
  currentFilters.salary = document.getElementById('filter-salary')?.value || '';

  renderJobs();

  // Hide mobile drawer if open
  const filterDrawer = document.getElementById('filters-sidebar');
  if (filterDrawer && window.innerWidth < 1024) {
    filterDrawer.classList.add('hidden');
  }
}

function resetFilters() {
  currentFilters = {
    keyword: '',
    company: '',
    location: '',
    department: '',
    salary: '',
    sort: 'newest'
  };

  const form = document.getElementById('jobs-filter-form');
  if (form) form.reset();
  renderJobs();
}

function removeFilter(key) {
  currentFilters[key] = '';
  const input = document.getElementById(`filter-${key}`);
  if (input) input.value = '';
  renderJobs();
}

function renderJobs() {
  const listContainer = document.getElementById('jobs-list-container');
  const emptyState = document.getElementById('jobs-empty-state');
  const activeChipsContainer = document.getElementById('active-filters-chips');
  const resultsCountElem = document.getElementById('results-count');

  if (!listContainer) return;

  // Filter list
  let filtered = MOCK_JOBS.filter((job) => {
    // Keyword matching
    if (currentFilters.keyword) {
      const q = currentFilters.keyword.toLowerCase();
      const match = job.title.toLowerCase().includes(q) ||
                    job.description.toLowerCase().includes(q) ||
                    job.department.toLowerCase().includes(q);
      if (!match) return false;
    }
    // Company matching
    if (currentFilters.company && job.companyCode !== currentFilters.company) {
      return false;
    }
    // Location matching
    if (currentFilters.location && job.locationCode !== currentFilters.location) {
      return false;
    }
    // Department matching
    if (currentFilters.department && job.departmentCode !== currentFilters.department) {
      return false;
    }
    // Salary matching
    if (currentFilters.salary && job.salaryRange !== currentFilters.salary) {
      return false;
    }
    return true;
  });

  // Sorting
  if (currentFilters.sort === 'newest') {
    // default order
  } else if (currentFilters.sort === 'urgent') {
    filtered.sort((a, b) => (a.status === 'urgent' ? -1 : 1));
  }

  // Update counter
  if (resultsCountElem) {
    resultsCountElem.textContent = filtered.length;
  }

  // Render active filter chips
  if (activeChipsContainer) {
    activeChipsContainer.innerHTML = '';
    const activeEntries = Object.entries(currentFilters).filter(([k, v]) => v && k !== 'sort');

    if (activeEntries.length > 0) {
      activeChipsContainer.classList.remove('hidden');
      activeEntries.forEach(([key, val]) => {
        let label = val;
        if (key === 'company') label = val === 'tex-giang' ? 'Cty TEX-GIANG' : 'Cty TG APPAREL';
        if (key === 'location') label = val === 'tan-huong' ? 'KCN Tân Hương' : 'TP.HCM';

        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-semibold border border-emerald-200';
        chip.innerHTML = `
          <span>${label}</span>
          <button onclick="removeFilter('${key}')" class="hover:text-rose-600 focus:outline-none" title="Bỏ lọc">
            <i class="bi bi-x-circle-fill"></i>
          </button>
        `;
        activeChipsContainer.appendChild(chip);
      });

      const clearAllBtn = document.createElement('button');
      clearAllBtn.className = 'text-xs text-rose-600 hover:underline font-bold self-center ml-2';
      clearAllBtn.textContent = 'Xóa tất cả bộ lọc';
      clearAllBtn.onclick = resetFilters;
      activeChipsContainer.appendChild(clearAllBtn);
    } else {
      activeChipsContainer.classList.add('hidden');
    }
  }

  // Render Job Cards or Empty State
  if (filtered.length === 0) {
    listContainer.innerHTML = '';
    if (emptyState) emptyState.classList.remove('hidden');
  } else {
    if (emptyState) emptyState.classList.add('hidden');
    listContainer.innerHTML = filtered.map(renderJobCard).join('');
  }
}

function renderJobCard(job) {
  const isClosed = job.status === 'closed';

  return `
    <article class="bg-white rounded-3xl border ${isClosed ? 'border-slate-200 opacity-75' : 'border-emerald-100 hover:border-emerald-300'} p-6 sm:p-7 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group relative overflow-hidden">
      ${job.tags.map(t => `
        <div class="absolute top-0 right-0">
          <span class="${job.badgeColor} text-white text-[11px] font-black px-3.5 py-1 rounded-bl-2xl tracking-wider uppercase">${t}</span>
        </div>
      `).join('')}

      <div class="space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-[#087A55]">
          <span class="px-2.5 py-0.5 rounded-md bg-emerald-50 border border-emerald-200">${job.company}</span>
          <span class="text-slate-300">&bull;</span>
          <span class="text-slate-500 font-medium">${job.department}</span>
        </div>

        <h3 class="text-lg sm:text-xl font-black text-slate-900 group-hover:text-[#087A55] transition leading-snug">
          <a href="job-detail.html?id=${job.id}">${job.title}</a>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-slate-600 pt-1">
          <div class="flex items-center gap-2">
            <i class="bi bi-cash-stack text-[#087A55] text-base"></i>
            <span class="font-extrabold text-[#087A55]">${job.salary}</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="bi bi-geo-alt-fill text-slate-400"></i>
            <span class="truncate">${job.location}</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="bi bi-calendar-event text-slate-400"></i>
            <span>Hạn nộp: <strong class="text-slate-800">${job.deadline}</strong></span>
          </div>
        </div>

        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed line-clamp-2 pt-1">
          ${job.description}
        </p>
      </div>

      <div class="pt-5 mt-5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <span class="text-[11px] text-slate-400 font-medium">Mã tin: <strong class="text-slate-600">${job.id}</strong> &bull; Đăng: ${job.date}</span>
        <div class="flex items-center gap-2">
          ${isClosed ? `
            <span class="px-4 py-2 bg-slate-100 text-slate-500 font-bold text-xs rounded-xl cursor-not-allowed">Đã Hết Hạn Tuyển</span>
          ` : `
            <a href="job-detail.html?id=${job.id}" class="px-4 py-2 text-xs font-bold text-slate-700 hover:text-[#087A55] hover:bg-emerald-50 rounded-xl transition">
              Xem Chi Tiết
            </a>
            <a href="apply.html?job=${encodeURIComponent(job.title)}" class="px-5 py-2.5 bg-[#087A55] hover:bg-[#006837] text-white font-extrabold text-xs rounded-xl shadow-md transition transform hover:-translate-y-0.5">
              Ứng Tuyển Ngay
            </a>
          `}
        </div>
      </div>
    </article>
  `;
}
