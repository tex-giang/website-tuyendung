<?php
/**
 * Trang why-tex-giang — port tự động từ E:/websitetuyendung/why-tex-giang.html.
 * Chạy: node tools/port-static-page.mjs why-tex-giang
 * Nội dung tĩnh; phần cần động (số liệu, timeline, form) chỉnh tay sau khi port.
 */
?>
<section class="tg-container pt-14 lg:pt-20"><div class="page-intro"><div>
      <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
        <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="text-body">Phúc lợi</span>
      </nav>

      <h1 class="mt-4 max-w-[900px] text-[clamp(2rem,5vw,3.25rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        Vì sao chọn TEX-GIANG
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        Bốn điều người lao động hỏi nhiều nhất khi đến nhà máy: môi trường có an toàn không, thu nhập bao nhiêu, có được dạy nghề không, và đi lại thế nào.
      </p>
    </div><figure class="intro-photo"><img src="<?= asset('images/company/tan-huong-2.webp') ?>" width="360" height="300" alt="Toàn cảnh nhà máy Tân Hương 2 trong hệ thống TEX-GIANG" fetchpriority="high"><figcaption>Nhà máy Tân Hương 2 · TEX-GIANG</figcaption></figure></div></section>

    <section class="tg-container pb-20 pt-12 lg:pb-24">
      <div class="grid gap-12 md:grid-cols-2 md:gap-x-16 md:gap-y-14">

        <div data-reveal>
          <h2 class="text-[20px] font-medium text-ink"><img class="tg-icon" src="<?= asset('images/icons/shield-check.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Môi trường làm việc an toàn, mát</h2>
          <p class="mt-3 max-w-[52ch] text-[16px] leading-[1.7] text-muted">
            Nhà xưởng tại KCN Tân Hương có điều hòa, ánh sáng chuẩn bảo vệ mắt, hệ thống hút bụi vải và PCCC tự động.
          </p>
          <ul class="mt-4 space-y-2 text-[16px] leading-[1.6] text-muted">
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Đạt SA8000 từ 2012 — trách nhiệm xã hội.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> ISO 9001 do SGS chứng nhận từ 2015; OEKO-TEX STEP từ 2016.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Tham gia Higg FEM &amp; FSLM từ 2022; điện mặt trời tại tất cả nhà máy.</li>
          </ul>
        </div>

        <div data-reveal>
          <h2 class="text-[20px] font-medium text-ink"><img class="tg-icon" src="<?= asset('images/icons/check2.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Thu nhập minh bạch, trả đúng hạn</h2>
          <p class="mt-3 max-w-[52ch] text-[16px] leading-[1.7] text-muted">
            Lương tính theo sản phẩm và năng suất chuyền; mức cụ thể trao đổi khi phỏng vấn. Ngoài lương còn các khoản thưởng và phụ cấp đang áp dụng.
          </p>
          <ul class="mt-4 space-y-2 text-[16px] leading-[1.6] text-muted">
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Thưởng tháng 13; thưởng dịp 30/4, 1/5, 2/9, Tết Dương lịch.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Thưởng hoàn thành kế hoạch, thi đua, thâm niên; tiền nuôi con nhỏ, tiền xăng xe.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> BHXH, BHYT, BHTN đầy đủ theo luật lao động.</li>
          </ul>
        </div>

        <div data-reveal>
          <h2 class="text-[20px] font-medium text-ink"><img class="tg-icon" src="<?= asset('images/icons/briefcase.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Đào tạo nghề miễn phí, có lương</h2>
          <p class="mt-3 max-w-[52ch] text-[16px] leading-[1.7] text-muted">
            Công ty tuyển cả người chưa có tay nghề may; người mới được kèm tại chuyền theo từng công đoạn (1 kim, 2 kim, vắt sổ) trước khi vào định mức chính thức.
          </p>
          <ul class="mt-4 space-y-2 text-[16px] leading-[1.6] text-muted">
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Học tại xưởng, có tổ trưởng kỹ thuật hướng dẫn trực tiếp.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Lộ trình lên chuyền phó, chuyền trưởng, kỹ thuật mẫu.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Thời gian học và mức lương học việc trao đổi khi phỏng vấn.</li>
          </ul>
        </div>

        <div data-reveal>
          <h2 class="text-[20px] font-medium text-ink"><img class="tg-icon" src="<?= asset('images/icons/bus-front.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Xe đưa rước và ăn ca miễn phí</h2>
          <p class="mt-3 max-w-[52ch] text-[16px] leading-[1.7] text-muted">
            Công ty bố trí xe đưa đón công nhân ở xa miễn phí giữa các nhà máy Tân Hương, Cái Lậy và Chợ Gạo.
          </p>
          <ul class="mt-4 space-y-2 text-[16px] leading-[1.6] text-muted">
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Cơm trưa và cơm tăng ca tại nhà máy.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Đồng phục, bảo hộ lao động theo ca sản xuất.</li>
            <li class="flex gap-2.5"><img src="<?= asset('images/icons/check2.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon mt-1 shrink-0 text-accent"> Điểm đón và giờ xe do nhân sự xác nhận theo từng khu vực.</li>
          </ul>
        </div>

      </div>
    </section>

    <section class="bg-surface py-20 lg:py-24">
      <div class="tg-container">
        <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] tracking-[-0.015em] text-ink" data-reveal>
          So sánh với nơi khác
        </h2>
        <p class="mt-3 max-w-[56ch] text-[16px] leading-[1.7] text-muted" data-reveal>
          Những điểm ứng viên thường hỏi lại sau buổi phỏng vấn.
        </p>

        <div class="mt-8 overflow-x-auto">
          <table class="compare-table w-full border-collapse text-left" data-reveal>
            <thead>
              <tr>
                <th scope="col" class="py-3 pr-3 text-[14px] font-medium text-muted sm:pr-6">Hạng mục</th>
                <th scope="col" class="py-3 pr-3 text-[14px] font-medium text-accent sm:pr-6">Tại TEX-GIANG</th>
                <th scope="col" class="py-3 text-[14px] font-medium text-muted">Nơi khác thường thấy</th>
              </tr>
            </thead>
            <tbody class="text-[15px] leading-[1.6] sm:text-[16px]">
              <tr class="border-t border-line">
                <th scope="row" class="py-4 pr-3 text-left font-medium text-ink sm:pr-6">Thưởng tuyển mới</th>
                <td class="py-4 pr-3 text-body sm:pr-6" data-label="TEX-GIANG">2.000.000đ tiền mặt</td>
                <td class="py-4 text-muted" data-label="Nơi khác">Không có hoặc điều kiện khắt khe</td>
              </tr>
              <tr class="border-t border-line">
                <th scope="row" class="py-4 pr-3 text-left font-medium text-ink sm:pr-6">Người chưa biết may</th>
                <td class="py-4 pr-3 text-body sm:pr-6" data-label="TEX-GIANG">Đào tạo miễn phí, có lương học việc</td>
                <td class="py-4 text-muted" data-label="Nơi khác">Chỉ tuyển thợ sẵn tay nghề</td>
              </tr>
              <tr class="border-t border-line">
                <th scope="row" class="py-4 pr-3 text-left font-medium text-ink sm:pr-6">Đi lại</th>
                <td class="py-4 pr-3 text-body sm:pr-6" data-label="TEX-GIANG">Xe đưa rước miễn phí nhiều tuyến</td>
                <td class="py-4 text-muted" data-label="Nơi khác">Tự túc phương tiện</td>
              </tr>
              <tr class="border-t border-line">
                <th scope="row" class="py-4 pr-3 text-left font-medium text-ink sm:pr-6">Bữa ăn ca</th>
                <td class="py-4 pr-3 text-body sm:pr-6" data-label="TEX-GIANG">Miễn phí, đổi món mỗi ngày</td>
                <td class="py-4 text-muted" data-label="Nơi khác">Trừ vào lương</td>
              </tr>
              <tr class="border-t border-line">
                <th scope="row" class="py-4 pr-3 text-left font-medium text-ink sm:pr-6">Ngày trả lương</th>
                <td class="py-4 pr-3 text-body sm:pr-6" data-label="TEX-GIANG">Mùng 10 hằng tháng qua tài khoản</td>
                <td class="py-4 text-muted" data-label="Nơi khác">Trễ hạn hoặc chia đợt</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="tg-container py-20 lg:py-28">
      <div class="flex flex-col items-start justify-between gap-10 rounded-media bg-accent p-8 lg:flex-row lg:items-center lg:p-14" data-reveal>
        <div class="max-w-[560px]">
          <h2 class="text-[clamp(1.75rem,3.4vw,2.5rem)] font-medium leading-[1.2] text-white">Bắt đầu từ một buổi phỏng vấn</h2>
          <p class="mt-4 text-[16px] leading-[1.7] text-white/85">Điền 2 phút, nhân sự gọi lại trong 24 giờ làm việc và hẹn lịch thử tay nghề.</p>
        </div>
        <div class="flex w-full flex-col items-start gap-5 sm:flex-row sm:items-center lg:w-auto">
          <a href="<?= url('apply') ?>" class="tg-btn tg-btn--white w-full sm:w-auto">Nộp hồ sơ</a>
          <a href="<?= url('jobs') ?>" class="inline-flex items-center gap-2 whitespace-nowrap text-[16px] font-medium text-white/90 transition-colors hover:text-white">
            Xem việc làm <img src="<?= asset('images/icons/arrow-right.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
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
