<?php
/**
 * Trang about — port tự động từ E:/websitetuyendung/about.html.
 * Chạy: node tools/port-static-page.mjs about
 * Nội dung tĩnh; phần cần động (số liệu, timeline, form) chỉnh tay sau khi port.
 */
?>
<section class="tg-container pt-14 lg:pt-20" data-reveal>
      <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
        <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="text-body">Về chúng tôi</span>
      </nav>

      <h1 class="mt-4 max-w-[720px] text-[clamp(2rem,5vw,3.25rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        May xuất khẩu tại Tân Hương từ năm 2007
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        Công ty Cổ phần TEX-GIANG (TGI) thành lập tháng 3/2007, vận hành 3 khu sản xuất tại Tân Hương, Cái Lậy và Chợ Gạo.
      </p>

      <figure class="mt-12 lg:mt-16">
        <img src="<?= asset('images/banner-texgiang.webp') ?>" srcset="<?= asset('images/banner-texgiang-800.webp') ?> 800w, <?= asset('images/banner-texgiang.webp') ?> 2048w" sizes="(max-width: 768px) 100vw, 1200px"
             alt="Cổng và khu nhà xưởng Nhà máy Tân Hương 2 — Công ty Cổ phần TEX-GIANG"
             width="2048" height="768" class="w-full rounded-media">
      </figure>
    </section>

    <section class="mt-16 bg-surface py-16 lg:mt-20 lg:py-20">
      <div class="tg-container grid grid-cols-2 gap-x-5 gap-y-7 md:gap-x-8 lg:grid-cols-4">
        <div class="stat" data-reveal>
          <span class="block text-[clamp(1.75rem,3vw,3rem)] font-medium leading-none tracking-[-0.02em] text-accent">2007</span>
          <span class="mt-2 block text-[14px] leading-snug text-muted">Thành lập tại KCN Tân Hương</span>
        </div>
        <div class="stat" data-reveal>
          <span class="block text-[clamp(1.75rem,3vw,3rem)] font-medium leading-none tracking-[-0.02em] text-accent">3</span>
          <span class="mt-2 block text-[14px] leading-snug text-muted">Khu sản xuất: Tân Hương, Cái Lậy, Chợ Gạo</span>
        </div>
        <div class="stat" data-reveal>
          <span class="block text-[clamp(1.75rem,3vw,3rem)] font-medium leading-none tracking-[-0.02em] text-accent">3.500</span>
          <span class="mt-2 block text-[14px] leading-snug text-muted">Cán bộ, kỹ sư và công nhân (TGI)</span>
        </div>
        <div class="stat" data-reveal>
          <span class="block text-[clamp(1.75rem,3vw,3rem)] font-medium leading-none tracking-[-0.02em] text-accent">8 triệu</span>
          <span class="mt-2 block text-[14px] leading-snug text-muted">Sản phẩm xuất khẩu mỗi năm</span>
        </div>
      </div>
    </section>

    <section class="tg-container py-20 lg:py-28">
      <div class="grid items-start gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">
        <div data-reveal>
          <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] tracking-[-0.015em] text-ink">
            Chúng tôi làm gì
          </h2>
          <p class="mt-5 max-w-[62ch] text-[16px] leading-[1.7] text-muted">
            Trụ sở và Nhà máy 1 đặt tại Lô BII-8, đường D3, KCN Tân Hương, xã Tân Hương, tỉnh Đồng Tháp. Tháng 8/2024 công ty nhận thêm <strong class="font-medium text-body">Nhà máy Tân Hương 2</strong> rộng 4,8 ha (lô BI-5,6,7,8) trong cùng khu công nghiệp; hai khu còn lại ở Cái Lậy và Chợ Gạo. Hiện công ty có 3 khu sản xuất và khoảng 3.500 lao động.
          </p>
          <p class="mt-4 max-w-[62ch] text-[16px] leading-[1.7] text-muted">
            Sản phẩm chính là áo khoác down, insulated, workwear bảo hộ, đồ outdoor và denim. Khách hàng gồm Kathmandu, Dickies, Helly Hansen, Wilson, Haglofs, First Tactical, Didriksons… tại châu Âu, Mỹ, Úc và New Zealand. Công suất khoảng 8 triệu sản phẩm/năm (TGI).
          </p>

          <figure class="mt-8"><img src="<?= asset('images/company/tan-huong-1.webp') ?>" width="360" height="300" loading="lazy" alt="Nhà máy Tân Hương 1 với hệ thống điện mặt trời trên mái" class="w-full max-w-[480px] rounded-media"><figcaption class="photo-credit">Nhà máy Tân Hương 1 · Hệ thống TEX-GIANG</figcaption></figure>

                    <h2 class="mt-12 text-[20px] font-medium text-ink">Mốc phát triển</h2>
          <ol class="about-timeline">
            <li class="about-timeline__item">
              <span class="about-timeline__year">2007</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">Thành lập, khởi công nhà máy trong KCN Tân Hương.</p>
            </li>
            <li class="about-timeline__item">
              <span class="about-timeline__year">2012</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">Đạt chứng nhận SA8000.</p>
            </li>
            <li class="about-timeline__item">
              <span class="about-timeline__year">2014</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">Bổ sung công nghệ laser cut và dán (lamination).</p>
            </li>
            <li class="about-timeline__item">
              <span class="about-timeline__year">03/2015</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">Khai trương nhà máy Cái Lậy, cách trụ sở 40 km; đạt ISO 9001 do SGS cấp.</p>
            </li>
            <li class="about-timeline__item">
              <span class="about-timeline__year">2016</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">Đạt OEKO-TEX STEP.</p>
            </li>
            <li class="about-timeline__item">
              <span class="about-timeline__year">2021–2022</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">Mở nhà máy Chợ Gạo; tham gia Higg FEM và FSLM.</p>
            </li>
            <li class="about-timeline__item">
              <span class="about-timeline__year">08/2024</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">Nhận Nhà máy Tân Hương 2 rộng 4,8 ha trong KCN Tân Hương.</p>
            </li>
            <li class="about-timeline__item about-timeline__item--now">
              <span class="about-timeline__year">Hiện tại</span>
              <span class="about-timeline__rail" aria-hidden="true"></span>
              <p class="about-timeline__text">3 khu sản xuất: Tân Hương, Cái Lậy và Chợ Gạo; khoảng 3.500 lao động. <span class="about-timeline__badge">Đang vận hành</span></p>
            </li>
          </ol>

          <p class="mt-14 text-[clamp(1.25rem,2.4vw,1.5rem)] font-medium leading-[1.4] text-ink">
            Vì con người — vì chất lượng — vì tương lai bền vững
          </p>
          <p class="mt-5 max-w-[62ch] text-[16px] leading-[1.7] text-muted">
            Người lao động là tài sản của công ty. Nhà xưởng có điều hòa, ánh sáng chuẩn, hút bụi vải và PCCC tự động; bếp ăn tập thể phục vụ miễn phí, xe đưa rước nhiều tuyến mỗi ngày, lương trả đúng ngày mùng 10 qua tài khoản.
          </p>
        </div>

        <aside class="space-y-6" data-reveal>
          <div class="rounded-card bg-surface p-6">
            <h2 class="text-[16px] font-medium text-ink">Chứng nhận đang áp dụng</h2>
            <ul class="mt-4 space-y-3 text-[16px] leading-[1.6] text-muted">
              <li>SA8000 — trách nhiệm xã hội (từ 2012)</li>
              <li>ISO 9001 — do SGS chứng nhận (từ 2015)</li>
              <li>OEKO-TEX STEP (từ 2016)</li>
              <li>Higg FEM &amp; FSLM (từ 2022)</li>
            </ul>
          </div>

          <div class="rounded-card bg-surface p-6">
            <h2 class="text-[16px] font-medium text-ink">Thông tin doanh nghiệp</h2>
            <p class="mt-4 text-[16px] leading-[1.7] text-muted">
              <strong class="font-medium text-body">Công ty Cổ phần TEX-GIANG (TGI)</strong><br>
              Lô BII-8, đường D3, KCN Tân Hương, xã Tân Hương, tỉnh Đồng Tháp<br>
              ĐT: <a href="tel:02733937274" class="text-body hover:text-accent">(0273) 393 7274</a> · Fax: (0273) 393 7275
            </p>
            <a href="<?= url('contact') ?>" class="tg-link mt-5">Xem bản đồ và liên hệ <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]"></a>
          </div>
        </aside>
      </div>
    </section>

    <section class="tg-container pb-20 lg:pb-28">
      <div class="grid gap-10 md:grid-cols-3 md:gap-12" data-reveal>
        <div>
          <h2 class="text-[20px] font-medium text-ink">Tầm nhìn</h2>
          <p class="mt-3 max-w-[46ch] text-[16px] leading-[1.7] text-muted">
            Trở thành doanh nghiệp may mặc xuất khẩu dẫn đầu khu vực miền Nam, tiên phong sản xuất xanh và là nơi làm việc đáng gắn bó với người lao động.
          </p>
        </div>
        <div>
          <h2 class="text-[20px] font-medium text-ink">Sứ mệnh</h2>
          <p class="mt-3 max-w-[46ch] text-[16px] leading-[1.7] text-muted">
            Giao đúng hẹn những lô hàng đạt chuẩn quốc tế, đồng thời nâng cao thu nhập và đời sống của công nhân viên tại Đồng Tháp.
          </p>
        </div>
        <div>
          <h2 class="text-[20px] font-medium text-ink">Giá trị cốt lõi</h2>
          <p class="mt-3 max-w-[46ch] text-[16px] leading-[1.7] text-muted">
            Tâm — Tín — Tinh — Đoàn — Nghĩa: tận tâm với việc, giữ chữ tín với khách hàng, tinh gọn trong sản xuất, đoàn kết nội bộ và nghĩa tình với người lao động.
          </p>
        </div>
      </div>
    </section>

    <section class="tg-container pb-20 lg:pb-28">
      <div class="flex flex-col items-start justify-between gap-10 rounded-media bg-accent p-8 lg:flex-row lg:items-center lg:p-14" data-reveal>
        <div class="max-w-[560px]">
          <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] text-white">Cùng làm việc tại Tân Hương 2</h2>
          <p class="mt-4 text-[16px] leading-[1.7] text-white/85"><?= (int) $jobCount ?> vị trí đang mở, từ công nhân may chưa có kinh nghiệm đến kỹ sư IE và QC.</p>
        </div>
        <div class="flex w-full flex-col items-start gap-5 sm:flex-row sm:items-center lg:w-auto">
          <a href="<?= url('jobs') ?>" class="tg-btn tg-btn--white w-full sm:w-auto">Xem việc làm</a>
          <a href="<?= url('apply') ?>" class="inline-flex items-center gap-2 whitespace-nowrap text-[16px] font-medium text-white/90 transition-colors hover:text-white">
            Nộp hồ sơ <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
          </a>
        </div>
      </div>
    </section>

<section class="tg-container pb-20 lg:pb-28" aria-labelledby="company-scenes">
  <div class="image-story">
    <div><h2 id="company-scenes">Con người và nơi làm việc</h2><p>Khám phá không gian nhà máy, hoạt động cộng đồng và định hướng phát triển bền vững trong hệ thống TEX-GIANG.</p><a href="<?= url('about') ?>" class="tg-link mt-5">Tìm hiểu doanh nghiệp <img class="tg-icon" src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" width="20" height="20"></a></div>
    <figure><img src="<?= asset('images/company/social.webp') ?>" width="600" height="450" loading="lazy" alt="Đại diện doanh nghiệp trao quà trong hoạt động cộng đồng"><figcaption>Gắn kết cùng cộng đồng</figcaption></figure>
    <figure><img src="<?= asset('images/company/environment.webp') ?>" width="600" height="450" loading="lazy" alt="Hệ thống điện mặt trời trên mái nhà xưởng TEX-GIANG"><figcaption>Hướng tới sản xuất bền vững</figcaption></figure>
  </div>
</section>
