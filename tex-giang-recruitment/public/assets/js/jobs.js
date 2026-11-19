/**
 * TEX-GIANG — trang danh sách việc làm.
 *
 * Lọc / sắp xếp / phân trang / bỏ chip **không tải lại cả trang**: JS gọi fetch
 * kèm header X-Public-Fragment, server trả về đúng phần kết quả + ô lọc, JS thay
 * tại chỗ. Nhờ vậy trang KHÔNG nhảy về đầu mỗi lần bấm chọn.
 *
 * Nguyên tắc:
 *   1. Không có JS, hoặc fetch lỗi  → để form submit bình thường (vẫn dùng được).
 *   2. URL đổi bằng history.pushState → back/forward và copy link vẫn đúng.
 *   3. Vị trí cuộn giữ nguyên; chỉ khi phân trang mới đưa khu kết quả vào tầm mắt.
 */
(function () {
  'use strict';

  var form = document.getElementById('jobs-filter-form');
  var layout = document.querySelector('.jobs-layout');
  if (!form || !layout) return;

  var drawer = document.getElementById('filters-sidebar');
  var overlay = document.getElementById('filter-overlay');
  var openBtn = document.getElementById('toggle-mobile-filters');
  var closeBtn = document.getElementById('close-filter-drawer');

  var controller = null;   // AbortController của request đang chạy
  var inFlight = false;
  var searchTimer = null;

  function isMobile() { return window.innerWidth < 1024; }

  // ---------------------------------------------------------------- Drawer

  function openDrawer() {
    if (!drawer) return;
    drawer.classList.remove('hidden');
    overlay?.classList.remove('hidden');
    document.body.classList.add('has-dialog');
    openBtn?.setAttribute('aria-expanded', 'true');
    closeBtn?.focus();
  }

  function closeDrawer(restoreFocus) {
    if (!drawer) return;
    drawer.classList.add('hidden');
    overlay?.classList.add('hidden');
    document.body.classList.remove('has-dialog');
    openBtn?.setAttribute('aria-expanded', 'false');
    if (restoreFocus !== false && isMobile()) openBtn?.focus();
  }

  // ------------------------------------------------------- Lọc bằng fetch

  function currentUrl() {
    return new URL(window.location.href);
  }

  /** Bỏ tham số rỗng để URL sạch, giữ nguyên các tham số khác. */
  function paramsFromForm(source) {
    var params = new URLSearchParams(new FormData(source));
    Array.from(params.keys()).forEach(function (key) {
      if (params.get(key) === '') params.delete(key);
    });
    return params;
  }

  /** Tên các trường của một form (dùng để biết tham số nào do form quản lý). */
  function fieldNames(source) {
    var names = [];
    new FormData(source).forEach(function (_value, key) { names.push(key); });
    // Trường bị bỏ vì rỗng vẫn thuộc form → phải tính là do form quản lý.
    source.querySelectorAll('[name]').forEach(function (el) {
      if (names.indexOf(el.name) === -1) names.push(el.name);
    });
    return names;
  }

  function buildTarget(sourceForm, overrides, keepPage) {
    var url = currentUrl();
    var params = paramsFromForm(sourceForm);
    var owned = fieldNames(sourceForm);

    // Giữ lại tham số KHÔNG thuộc form này (ví dụ `sort` khi lọc từ sidebar,
    // hoặc bộ lọc khi đổi sắp xếp). Tham số thuộc form mà rỗng thì KHÔNG giữ —
    // đó chính là hành vi "chọn lại Tất cả để bỏ lọc".
    var existing = new URLSearchParams(url.search);
    existing.forEach(function (value, key) {
      if (key === 'page') return;
      if (owned.indexOf(key) !== -1) return;
      params.set(key, value);
    });

    if (overrides) {
      Object.keys(overrides).forEach(function (key) {
        var value = overrides[key];
        if (value === '' || value === null) params.delete(key);
        else params.set(key, value);
      });
    }
    if (!keepPage) params.delete('page');

    url.search = params.toString();
    return url;
  }

  function setBusy(busy) {
    layout.setAttribute('aria-busy', busy ? 'true' : 'false');
    var list = layout.querySelector('[data-jobs-list]');
    if (list) list.style.opacity = busy ? '0.55' : '';
  }

  function replaceRegion(root, selector, html) {
    var incoming = html.querySelector(selector);
    var current = root.querySelector(selector);
    if (!incoming || !current) return false;
    current.replaceWith(incoming);
    return true;
  }

  async function load(url, options) {
    options = options || {};
    if (inFlight && controller) controller.abort();
    controller = new AbortController();
    inFlight = true;
    setBusy(true);
    announce('Đang tải danh sách việc làm…');

    try {
      var response = await fetch(url.toString(), {
        headers: {
          'X-Public-Fragment': '1',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html'
        },
        signal: controller.signal,
        credentials: 'same-origin'
      });
      if (!response.ok) throw new Error('HTTP ' + response.status);

      var text = await response.text();
      var doc = new DOMParser().parseFromString(text, 'text/html');

      // Cập nhật danh sách Bộ phận theo Khối mới. Các ô lọc là con trực tiếp của
      // form (để CSS grid xếp ngang), nên thay theo TỪNG Ô theo id — không có
      // wrapper bao ngoài để thay một lần.
      syncFilterOptions(doc);

      var replaced = replaceRegion(layout, '[data-jobs-results]', doc);
      if (!replaced) throw new Error('Thiếu vùng kết quả trong phản hồi.');

      if (options.pushState !== false) {
        window.history.pushState({ jobsFilter: true }, '', url.toString());
      }
      if (options.scrollToResults) scrollToResults();
      else clampScroll();
      announce(layout.querySelector('#results-count')
        ? layout.querySelector('#results-count').textContent + ' vị trí phù hợp.'
        : '');
      return true;
    } catch (error) {
      if (error.name === 'AbortError') return false;
      // Không fetch được → để trình duyệt tự điều hướng, vẫn dùng được như cũ.
      window.location.assign(url.toString());
      return false;
    } finally {
      inFlight = false;
      setBusy(false);
    }
  }

  var liveRegion = null;
  function announce(message) {
    if (!message) return;
    if (!liveRegion) {
      liveRegion = document.getElementById('jobs-live-region');
      if (!liveRegion) {
        liveRegion = document.createElement('p');
        liveRegion.id = 'jobs-live-region';
        // public.css định nghĩa .sr-only; utilities.css (Tailwind) không có visually-hidden.
        liveRegion.className = 'sr-only';
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        layout.parentNode.insertBefore(liveRegion, layout);
      }
    }
    liveRegion.textContent = message;
  }

  /**
   * Đồng bộ các ô lọc từ phản hồi của server (nguồn chân lý):
   *   - #filter-department: danh sách bộ phận đổi theo Khối.
   *   - #filter-khoi / #filter-location / #filter-q: giữ đúng giá trị đã lọc.
   * Không thay cả form để tránh mất trạng thái focus và phá grid CSS.
   */
  function syncFilterOptions(doc) {
    ['filter-q', 'filter-location', 'filter-khoi', 'filter-department'].forEach(function (id) {
      var incoming = doc.getElementById(id);
      var current = document.getElementById(id);
      if (!incoming || !current) return;

      if (incoming.tagName === 'SELECT') {
        current.innerHTML = incoming.innerHTML;
        current.value = incoming.value;
      } else {
        // Ô nhập: chỉ cập nhật khi khác, tránh nhảy con trỏ khi đang gõ.
        if (current.value !== incoming.value && document.activeElement !== current) {
          current.value = incoming.value;
        }
      }
    });
  }

  /** Đưa khu kết quả vào tầm mắt mà không nhảy về đầu trang. */
  function scrollToResults() {
    var target = layout.querySelector('[data-jobs-results]');
    if (!target) return;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var top = target.getBoundingClientRect().top + window.scrollY - 96;
    window.scrollTo({ top: Math.max(0, top), behavior: reduce ? 'instant' : 'smooth' });
  }

  /**
   * Sau khi thay kết quả, nếu trang ngắn đi (lọc ra ít tin) thì vị trí cuộn cũ
   * có thể nằm dưới đáy tài liệu → người dùng thấy khoảng trống. Kéo lên vừa đủ
   * để đáy nội dung khớp đáy khung nhìn, KHÔNG nhảy về đầu trang.
   */
  function clampScroll() {
    var maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    if (maxScroll >= 0 && window.scrollY > maxScroll) {
      var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: Math.max(0, maxScroll), behavior: reduce ? 'instant' : 'auto' });
    }
  }

  /**
   * Submit form (bộ lọc hoặc sắp xếp) bằng fetch.
   *
   * LƯU Ý về tham số rỗng: khi người dùng chọn lại "Tất cả …" (value = ""),
   * tham số đó PHẢI bị xoá khỏi URL để bỏ lọc. Vì vậy hàm này truyền danh sách
   * tên trường của form vào buildTarget — những tên đó không được "cứu" lại từ
   * URL cũ, nếu không việc bỏ lọc sẽ không có tác dụng.
   */
  function submitByFetch(sourceForm, overrides, keepPage, scrollToResultsFlag) {
    var url = buildTarget(sourceForm, overrides, keepPage);
    if (url.toString() === window.location.href) return;
    void load(url, { scrollToResults: scrollToResultsFlag === true });
  }

  // ------------------------------------------------------------- Sự kiện

  // Bộ lọc: submit bằng nút "Áp dụng" hoặc Enter trong ô từ khoá.
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    void submitByFetch(form, null, false, false);
  });

  // Đổi select trong sidebar → tự lọc.
  form.querySelectorAll('select').forEach(function (select) {
    select.addEventListener('change', function () {
      if (select.hasAttribute('data-block-filter')) {
        // Đổi Khối: bỏ bộ phận đang chọn để tránh tổ hợp không hợp lệ.
        var department = form.querySelector('[data-department-filter]');
        if (department) department.value = '';
      }
      void submitByFetch(form, null, false, false);
    });
  });

  // Ô từ khoá: gõ xong dừng 400ms thì lọc (chỉ desktop, mobile chờ bấm nút).
  var searchInput = form.querySelector('#filter-q');
  searchInput?.addEventListener('input', function (event) {
    if (event.isComposing) return;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      if (form.isConnected) void submitByFetch(form, null, false, false);
    }, 400);
  });

  // Bấm vào link trong vùng kết quả (chip, phân trang, xoá hết) → fetch.
  layout.addEventListener('click', function (event) {
    var link = event.target.closest('[data-jobs-link]');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href || link.target === '_blank' || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

    event.preventDefault();
    var url = new URL(href, window.location.origin);
    // Phân trang thì đưa kết quả vào tầm mắt; bỏ chip / xoá hết thì giữ nguyên chỗ.
    var isPager = Boolean(link.closest('#jobs-pager'));
    void load(url, { scrollToResults: isPager });
  });

  // Sắp xếp: form nằm trong vùng kết quả nên bắt ở cấp layout.
  layout.addEventListener('change', function (event) {
    var select = event.target;
    if (!(select instanceof HTMLSelectElement) || select.id !== 'filter-sort') return;
    var sortForm = select.closest('form');
    if (!sortForm) return;
    void submitByFetch(sortForm, { sort: select.value }, true, false);
  });

  layout.addEventListener('submit', function (event) {
    var sortForm = event.target;
    if (!(sortForm instanceof HTMLFormElement) || sortForm.id !== 'jobs-sort-form') return;
    event.preventDefault();
    void submitByFetch(sortForm, null, true, false);
  });

  // Back/forward của trình duyệt → nạp lại đúng trạng thái, không push thêm history.
  window.addEventListener('popstate', function () {
    void load(currentUrl(), { pushState: false });
  });

  // ------------------------------------------------------- Drawer sự kiện

  openBtn?.addEventListener('click', openDrawer);
  closeBtn?.addEventListener('click', function () { closeDrawer(); });
  overlay?.addEventListener('click', function () { closeDrawer(); });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && drawer && !drawer.classList.contains('hidden') && isMobile() && !drawer.classList.contains('lg:block')) {
      closeDrawer();
    }
  });
  window.addEventListener('resize', function () {
    if (!isMobile()) closeDrawer(false);
  });
})();
