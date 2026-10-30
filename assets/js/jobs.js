/**
 * TEX-GIANG RECRUITMENT PORTAL - JOBS LIST & FILTER JAVASCRIPT
 * Handles client-side mock search, multi-facet filtering, active filter pills, and pagination.
 */

// Demo Jobs Dataset (Clearly labeled as mock data for frontend demonstration)

let currentFilters = {
  keyword: '',
  company: '',
  location: '',
  department: '',
  salary: '',
  sort: 'newest'
};

// Phân trang phía client: mỗi trang 6 tin
const PAGE_SIZE = 6;
let currentPage = 1;

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
      document.getElementById('filter-overlay')?.classList.remove('hidden');
      document.body.classList.add('has-dialog');
      mobileFilterBtn.setAttribute('aria-expanded', 'true');
      closeFilterDrawerBtn?.focus();
    });
  }

  if (closeFilterDrawerBtn && filterDrawer) {
    closeFilterDrawerBtn.addEventListener('click', () => {
      closeJobFilters();
    });
  }
  document.getElementById('filter-overlay')?.addEventListener('click', closeJobFilters);
  document.addEventListener('keydown', (event) => {
    if (window.innerWidth >= 1024 || !filterDrawer || filterDrawer.classList.contains('hidden')) return;
    if (event.key === 'Escape') closeJobFilters();
    if (event.key === 'Tab') {
      const controls = [...filterDrawer.querySelectorAll('button,input,select')].filter(el => el.getClientRects().length);
      const first = controls[0], last = controls[controls.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) closeJobFilters(false);
  });
});

function closeJobFilters(restoreFocus = true) {
  const drawer = document.getElementById('filters-sidebar');
  const wasOpen = drawer && !drawer.classList.contains('hidden');
  drawer?.classList.add('hidden');
  document.getElementById('filter-overlay')?.classList.add('hidden');
  document.body.classList.remove('has-dialog');
  const toggle = document.getElementById('toggle-mobile-filters');
  toggle?.setAttribute('aria-expanded', 'false');
  if (restoreFocus && wasOpen && window.innerWidth < 1024) toggle?.focus();
}

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
  Object.keys(currentFilters).forEach(key => {
    const input = document.getElementById(`filter-${key}`);
    const value = params.get(key);
    if (!input || !value) return;
    if (input.tagName === 'SELECT' && ![...input.options].some(option => option.value === value)) return;
    input.value = value;
    currentFilters[key] = value;
  });
  let debounce;
  keywordInput?.addEventListener('input', () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => { if (window.innerWidth >= 1024) applyFilters(); }, 250);
  });
  [companySelect, locationSelect, departmentSelect, salarySelect].forEach(input => {
    input?.addEventListener('change', () => { if (window.innerWidth >= 1024) applyFilters(); });
  });

  if (filterForm) {
    filterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      applyFilters();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', function () {
      currentFilters.sort = this.value;
      currentPage = 1;
      renderJobs();
    });
  }
}

function applyFilters() {
  currentPage = 1;
  currentFilters.keyword = (document.getElementById('filter-keyword')?.value || '').trim();
  currentFilters.company = document.getElementById('filter-company')?.value || '';
  currentFilters.location = document.getElementById('filter-location')?.value || '';
  currentFilters.department = document.getElementById('filter-department')?.value || '';
  currentFilters.salary = document.getElementById('filter-salary')?.value || '';

  renderJobs();

  // Hide mobile drawer if open
  const filterDrawer = document.getElementById('filters-sidebar');
  if (filterDrawer && window.innerWidth < 1024) {
    closeJobFilters();
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
  const sort = document.getElementById('filter-sort');
  if (sort) sort.value = 'newest';
  currentPage = 1;
  renderJobs();
}

function removeFilter(key) {
  currentPage = 1;
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
  const query = new URLSearchParams();
  Object.entries(currentFilters).forEach(([key, value]) => {
    if (value && !(key === 'sort' && value === 'newest')) query.set(key, value);
  });
  // Also works when opened directly from disk, where history mutation may be denied.
  try { history.replaceState(null, '', location.pathname + (query.size ? '?' + query : '') + location.hash); } catch (_) {}

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
    filtered.sort((a, b) => Number(b.status === 'urgent') - Number(a.status === 'urgent'));
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
        const select = document.getElementById(`filter-${key}`);
        if (select?.tagName === 'SELECT') label = select.selectedOptions[0]?.textContent || label;

        const chip = document.createElement('span');
        chip.className = 'tg-chip inline-flex items-center gap-1.5';
        const text = document.createElement('span');
        text.textContent = label;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'filter-chip-remove';
        remove.setAttribute('aria-label', `Bỏ lọc ${label}`);
        remove.innerHTML = '<img class="tg-icon" src="assets/images/icons/x-lg.svg" width="16" height="16" alt="" aria-hidden="true">';
        remove.addEventListener('click', () => removeFilter(key));
        chip.append(text, remove);
        activeChipsContainer.appendChild(chip);
      });

      const clearAllBtn = document.createElement('button');
      clearAllBtn.type = 'button';
      clearAllBtn.className = 'ml-1 text-[14px] font-medium text-accent hover:underline';
      clearAllBtn.textContent = 'Xoá tất cả bộ lọc';
      clearAllBtn.onclick = resetFilters;
      activeChipsContainer.appendChild(clearAllBtn);
    } else {
      activeChipsContainer.classList.add('hidden');
    }
  }

  // Render Job Cards or Empty State (có phân trang)
  const pager = document.getElementById('jobs-pager');

  if (filtered.length === 0) {
    listContainer.innerHTML = '';
    if (emptyState) emptyState.classList.remove('hidden');
    if (pager) {
      pager.innerHTML = '';
      pager.classList.add('hidden');
    }
    return;
  }

  if (emptyState) emptyState.classList.add('hidden');

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  const start = (currentPage - 1) * PAGE_SIZE;
  const pageItems = filtered.slice(start, start + PAGE_SIZE);

  listContainer.innerHTML = pageItems.map(renderJobCard).join('');

  renderPager(pager, filtered.length, totalPages);
}

function renderPager(pager, total, totalPages) {
  if (!pager) return;

  if (totalPages <= 1) {
    pager.innerHTML = '';
    pager.classList.add('hidden');
    return;
  }

  pager.classList.remove('hidden');
  pager.innerHTML = '';

  const start = (currentPage - 1) * PAGE_SIZE + 1;
  const end = Math.min(currentPage * PAGE_SIZE, total);

  const summary = document.createElement('p');
  summary.className = 'text-[14px] text-muted';
  summary.textContent = `Hiển thị ${start}–${end} trong ${total} vị trí`;
  pager.appendChild(summary);

  const controls = document.createElement('div');
  controls.className = 'flex items-center gap-2';

  const makeButton = (label, page, options) => {
    const opts = options || {};
    const button = document.createElement('button');
    button.type = 'button';
    button.className = opts.current
      ? 'grid h-9 min-w-9 place-items-center rounded-lg bg-accent-soft px-3 text-[14px] font-medium text-accent'
      : 'grid h-9 min-w-9 place-items-center rounded-lg border border-line px-3 text-[14px] text-body transition-colors hover:border-accent hover:text-accent disabled:cursor-not-allowed disabled:opacity-40';
    button.innerHTML = label;
    if (opts.disabled) button.disabled = true;
    if (opts.current) button.setAttribute('aria-current', 'page');
    if (!opts.disabled && !opts.current) {
      button.addEventListener('click', () => {
        currentPage = page;
        renderJobs();
        document.getElementById('jobs-list-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
    return button;
  };

  controls.appendChild(makeButton('Trước', currentPage - 1, { disabled: currentPage === 1 }));

  for (let page = 1; page <= totalPages; page += 1) {
    controls.appendChild(makeButton(String(page), page, { current: page === currentPage }));
  }

  controls.appendChild(makeButton('Sau', currentPage + 1, { disabled: currentPage === totalPages }));

  pager.appendChild(controls);
}

function renderJobCard(job) {
  const isClosed = job.status === 'closed';
  const tags = job.tags
    .map(function (t) {
      return '<span class="tg-chip' + (isClosed ? ' tg-chip--muted' : '') + '">' + t + '</span>';
    })
    .join('');

  return `
    <article class="job-row${isClosed ? ' is-closed' : ''}">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[14px] text-muted">
          <span class="font-medium text-body">${job.company}</span>
          <span aria-hidden="true">·</span>
          <span>${job.department}</span>
          ${tags}
        </div>

        <h3 class="mt-2 text-[20px] font-medium leading-[1.4] text-ink">
          <a href="job-detail.html?id=${job.id}" class="transition-colors hover:text-accent">${job.title}</a>
        </h3>

        <p class="mt-2 line-clamp-2 text-[16px] leading-[1.6] text-muted">${job.description}</p>

        <p class="mt-3 text-[14px] text-muted">${job.location} · Hạn nộp ${job.deadline} · Mã ${job.id}</p>
      </div>

      <div class="job-row__side">
        <span class="text-[16px] font-semibold text-accent">${job.salary.replace(' VNĐ', ' đ/tháng')}</span>
        ${isClosed
          ? '<span class="text-[14px] text-muted">Đã hết hạn tuyển — vẫn nhận CV cho đợt sau</span>'
          : `<a href="apply.html?job=${encodeURIComponent(job.title)}" class="tg-btn tg-btn--primary h-11 px-5">Ứng tuyển</a>
             <a href="job-detail.html?id=${job.id}" class="text-[16px] text-muted transition-colors hover:text-accent">Xem chi tiết</a>`}
      </div>
    </article>
  `;
}
