/* Tin tức nghề nghiệp — dữ liệu minh hoạ, hiển thị theo layout blog */
(() => {
  const IMG = {
    cover1: 'assets/images/news/cover-1.webp',
    cover2: 'assets/images/news/cover-2.webp',
    cover3: 'assets/images/news/cover-3.webp',
    cover4: 'assets/images/news/cover-4.webp',
    cover5: 'assets/images/news/cover-5.webp',
    cover6: 'assets/images/news/cover-6.webp',
    solar: 'assets/images/company/environment.webp',
  };

  const POSTS = [
    {
      id: 'tuyen-300-cong-nhan',
      date: '2026-10-30',
      category: 'Tuyển dụng',
      title: 'TEX-GIANG tuyển 300 công nhân may cho chuyền áo khoác xuất khẩu',
      excerpt: 'Không yêu cầu kinh nghiệm, có lớp kèm việc trả lương trong 2 tuần đầu. Làm việc tại khu Tân Hương, xe đưa rước và cơm ca miễn phí.',
      img: IMG.cover1,
      pos: 'center 50%',
    },
    {
      id: 'khach-hang-chau-au-kiem-tra',
      date: '2026-10-27',
      category: 'Hoạt động nội bộ',
      title: 'Nhà máy Tân Hương 2 đón đoàn khách hàng châu Âu kiểm tra quy trình',
      excerpt: 'Đoàn đánh giá dây chuyền may áo khoác down, hệ thống kiểm phẩm QC và điều kiện làm việc theo tiêu chuẩn SA8000.',
      img: IMG.cover2,
      pos: 'center 45%',
    },
    {
      id: 'dao-tao-may-1-kim',
      date: '2026-10-24',
      category: 'Đào tạo',
      title: 'Hơn 120 công nhân hoàn thành lớp đào tạo may 1 kim nâng bậc',
      excerpt: 'Khóa 6 tuần do tổ kỹ thuật đứng lớp, học viên được đánh giá tay nghề và xếp bậc lương mới ngay sau khi tốt nghiệp.',
      img: IMG.cover3,
      pos: 'center 50%',
    },
    {
      id: 'ngay-hoi-viec-lam-chau-thanh',
      date: '2026-10-20',
      category: 'Sự kiện',
      title: 'Ngày hội việc làm Châu Thành 2026: gần 500 lượt ứng viên tham gia',
      excerpt: 'Gian hàng TEX-GIANG nhận 180 hồ sơ trực tiếp, trong đó 60 ứng viên được hẹn phỏng vấn tại nhà máy trong tuần.',
      img: IMG.cover5,
      pos: 'center 40%',
    },
    {
      id: 'thuong-thang-13',
      date: '2026-10-16',
      category: 'Đời sống',
      title: 'Thưởng tháng 13 và quà Tết cho toàn bộ CBCNV',
      excerpt: 'Công ty chi thưởng tháng 13 cùng quà Tết cho hơn 3.500 lao động tại ba khu sản xuất, chi trả trước ngày nghỉ Tết hai tuần.',
      img: IMG.cover4,
      pos: 'center 50%',
    },
    {
      id: 'bep-an-cai-lay',
      date: '2026-10-12',
      category: 'Đời sống',
      title: 'Khu Cái Lậy cải tạo bếp ăn tập thể, thêm suất ăn ca tối',
      excerpt: 'Bếp mới rộng 420 m², công suất 1.200 suất/ngày; ca tối được phục vụ thêm cháo hoặc bún trước giờ vào chuyền.',
      img: IMG.cover5,
      pos: 'center 55%',
    },
    {
      id: 'sa8000-nam-14',
      date: '2026-10-08',
      category: 'An toàn – Chất lượng',
      title: 'TEX-GIANG duy trì chứng nhận SA8000 năm thứ 14',
      excerpt: 'Đợt đánh giá giám sát ghi nhận hồ sơ giờ làm, an toàn lao động và đối thoại công nhân đều đạt yêu cầu.',
      img: IMG.cover2,
      pos: 'center 40%',
    },
    {
      id: 'dien-mat-troi-cho-gao',
      date: '2026-10-03',
      category: 'Hoạt động nội bộ',
      title: 'Lắp thêm điện mặt trời mái xưởng tại khu Chợ Gạo',
      excerpt: 'Hệ thống 780 kWp giúp giảm khoảng 18% điện năng tiêu thụ giờ cao điểm, mái xưởng mát hơn rõ rệt vào buổi trưa.',
      img: IMG.solar,
      pos: 'center 60%',
    },
    {
      id: 'cam-nang-phong-van',
      date: '2026-09-28',
      category: 'Nghề nghiệp',
      title: 'Cẩm nang phỏng vấn: 5 câu hỏi thường gặp khi ứng tuyển may mặc',
      excerpt: 'Nhân sự TEX-GIANG gợi ý cách trả lời về kinh nghiệm chuyền, tay nghề từng công đoạn và nguyện vọng thu nhập.',
      img: IMG.cover4,
      pos: 'center 45%',
    },
    {
      id: 'chuyen-tho-chuyen-truong',
      date: '2026-09-22',
      category: 'Nghề nghiệp',
      title: 'Chuyện thợ: từ học việc đến chuyền trưởng sau 4 năm',
      excerpt: 'Chị Ngọc (khu Tân Hương) kể lại lộ trình từ học việc may 1 kim, lên tổ phó rồi chuyền trưởng với 42 người.',
      img: IMG.cover5,
      pos: 'center 45%',
    },
    {
      id: 'xe-dua-ruoc-thang-11',
      date: '2026-09-15',
      category: 'Đời sống',
      title: 'Xe đưa rước thêm tuyến Chợ Gạo – Tân Hương từ tháng 11',
      excerpt: 'Tuyến mới đón công nhân dọc quốc lộ 50, giờ xe và điểm đón cụ thể do nhân sự thông báo tới từng tổ.',
      img: IMG.cover3,
      pos: 'center 55%',
    },
    {
      id: 'hoi-thao-cong-nhan',
      date: '2026-09-09',
      category: 'Sự kiện',
      title: 'Hội thao công nhân 2026: 8 đội tranh tài ở 4 môn',
      excerpt: 'Bóng đá, kéo co, nhảy bao bố và bơi tiếp sức; hội thao diễn ra trong hai ngày cuối tuần tại sân khu Tân Hương.',
      img: IMG.cover1,
      pos: 'center 45%',
    },
    {
      id: 'dien-tap-pccc',
      date: '2026-09-02',
      category: 'An toàn – Chất lượng',
      title: 'An toàn lao động: diễn tập chữa cháy tại xưởng 2',
      excerpt: 'Toàn bộ 620 công nhân ca A thực hành thoát hiểm trong 4 phút, đội PCCC cơ sở xử lý tình huống giả định.',
      img: IMG.cover4,
      pos: 'center 50%',
    },
    {
      id: 'tuyen-qc-ie',
      date: '2026-08-26',
      category: 'Tuyển dụng',
      title: 'Tuyển 5 giám sát QC và 2 kỹ sư IE làm việc tại khu Tân Hương',
      excerpt: 'Yêu cầu 2 năm kinh nghiệm ngành may, ưu tiên ứng viên biết GSD và từng làm hàng down jacket xuất khẩu.',
      img: IMG.cover2,
      pos: 'center 50%',
    },
    {
      id: 'com-cong-doan',
      date: '2026-08-19',
      category: 'Đời sống',
      title: 'Bữa cơm công đoàn tháng 8 tại ba khu sản xuất',
      excerpt: 'Mỗi suất thêm một món mặn và tráng miệng, kinh phí do công đoàn công ty hỗ trợ toàn bộ.',
      img: IMG.cover6,
      pos: 'center 60%',
    },
  ];

  const formatDate = (iso) => {
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
  };

  const cardMarkup = (post, variant = '') => `
    <article class="news-card ${variant}">
      <a class="news-card__link" href="news-detail.html?id=${post.id}">
        <img class="news-card__img" src="${post.img}" alt="${post.title}" loading="lazy" decoding="async" style="object-position:${post.pos}">
        <div class="news-card__body">
          <h3 class="news-card__title">${post.title}</h3>
          <p class="news-card__meta"><span class="news-card__date">${formatDate(post.date)}</span><span class="news-card__dot" aria-hidden="true">·</span><span class="news-card__cat">${post.category}</span></p>
          <p class="news-card__excerpt">${post.excerpt}</p>
        </div>
      </a>
    </article>`;

  const state = { category: 'Tất cả', page: 1 };
  const PAGE_SIZE = 6;

  const featuredEl = document.getElementById('news-featured');
  const gridEl = document.getElementById('news-grid');
  const pagerEl = document.getElementById('news-pager');
  const chipsEl = document.getElementById('news-chips');
  const countEl = document.getElementById('news-count');

  if (!featuredEl || !gridEl) return;

  // Featured: 1 lớn + 2 nhỏ
  featuredEl.innerHTML = `
    ${cardMarkup(POSTS[0], 'news-card--lead')}
    <div class="news-featured__aside">
      ${cardMarkup(POSTS[1], 'news-card--compact')}
      ${cardMarkup(POSTS[2], 'news-card--compact')}
    </div>`;

  // Danh mục
  const categories = ['Tất cả', ...new Set(POSTS.map((p) => p.category))];
  chipsEl.innerHTML = categories
    .map((c) => {
      const n = c === 'Tất cả' ? POSTS.length : POSTS.filter((p) => p.category === c).length;
      return `<button type="button" class="news-chip${c === state.category ? ' is-active' : ''}" data-category="${c}">${c}<span class="news-chip__count">${n}</span></button>`;
    })
    .join('');

  const filtered = () => (state.category === 'Tất cả' ? POSTS : POSTS.filter((p) => p.category === state.category));
  const pageCount = () => Math.max(1, Math.ceil(filtered().length / PAGE_SIZE));

  function render() {
    const list = filtered();
    const total = list.length;
    const pages = pageCount();
    state.page = Math.min(state.page, pages);

    const start = (state.page - 1) * PAGE_SIZE;
    const items = list.slice(start, start + PAGE_SIZE);

    gridEl.innerHTML = items.map((p) => cardMarkup(p)).join('') ||
      '<p class="news-empty">Chưa có bài viết trong mục này.</p>';

    if (countEl) {
      countEl.textContent = total
        ? `Hiển thị ${start + 1}–${Math.min(start + PAGE_SIZE, total)} trong ${total} bài viết`
        : '0 bài viết';
    }

    if (pagerEl) {
      if (pages <= 1) {
        pagerEl.innerHTML = '';
        pagerEl.hidden = true;
      } else {
        pagerEl.hidden = false;
        pagerEl.innerHTML = Array.from({ length: pages }, (_, i) => {
          const page = i + 1;
          return `<button type="button" class="news-pager__btn${page === state.page ? ' is-active' : ''}" data-page="${page}" aria-label="Trang ${page}"${page === state.page ? ' aria-current="page"' : ''}>${page}</button>`;
        }).join('');
      }
    }
  }

  chipsEl.addEventListener('click', (event) => {
    const button = event.target.closest('[data-category]');
    if (!button) return;
    state.category = button.dataset.category;
    state.page = 1;
    chipsEl.querySelectorAll('[data-category]').forEach((el) => el.classList.toggle('is-active', el === button));
    render();
  });

  pagerEl?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-page]');
    if (!button) return;
    state.page = Number(button.dataset.page);
    render();
    document.getElementById('news-all')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  render();
})();
