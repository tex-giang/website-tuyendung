<?php
/**
 * Trang contact — port tự động từ E:/websitetuyendung/contact.html.
 * Chạy: node tools/port-static-page.mjs contact
 * Nội dung tĩnh; phần cần động (số liệu, timeline, form) chỉnh tay sau khi port.
 */
?>
<section class="tg-container pt-14 lg:pt-20"><div class="page-intro"><div>
      <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
        <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="text-body">Liên hệ</span>
      </nav>

      <h1 class="mt-4 max-w-[820px] text-[clamp(2rem,5vw,3.25rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        Liên hệ tuyển dụng
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        Phòng Tuyển dụng TEX-GIANG tiếp nhận hồ sơ tại 3 khu vực: Tân Hương, Chợ Gạo và Cái Lậy. Gọi trực tiếp người phụ trách khu vực bạn muốn làm việc, hoặc nộp hồ sơ tại địa chỉ tương ứng bên dưới.
      </p>
    </div><figure class="intro-photo"><img src="<?= asset('images/company/tan-huong-2.webp') ?>" width="360" height="300" alt="Toàn cảnh nhà máy Tân Hương 2 trong hệ thống TEX-GIANG" fetchpriority="high"></figure></div></section>

    <!-- 3 KHU VỰC TIẾP NHẬN HỒ SƠ -->
    <section class="tg-container pb-8 pt-14 lg:pt-20" aria-labelledby="branches-title">
      <div class="max-w-[720px]" data-reveal>
        <h2 id="branches-title" class="text-[clamp(1.5rem,2.6vw,2rem)] font-medium leading-[1.25] text-ink">
          3 khu vực tiếp nhận hồ sơ
        </h2>
        <p class="mt-2 text-[16px] leading-[1.7] text-muted">
          Mỗi khu vực có người phụ trách tuyển dụng riêng, kèm bản đồ vị trí. Chọn đúng khu vực để được tư vấn nhanh và xếp lịch phỏng vấn gần nhất.
        </p>
      </div>

      <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">

        <!-- 1. TÂN HƯƠNG -->
        <article class="flex flex-col rounded-card border border-line bg-surface p-6 transition-shadow hover:shadow-float" data-reveal>
          <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-accent-soft text-[15px] font-semibold text-accent">1</span>
            <h3 class="text-[18px] font-medium leading-snug text-ink">Khu vực Tân Hương</h3>
          </div>

          <dl class="mb-6 mt-5 space-y-4 text-[15px] leading-[1.65]">
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/buildings.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Địa điểm làm việc
              </dt>
              <dd class="mt-1 text-muted">KCN Tân Hương, Xã Tân Hương, Đồng Tháp.</dd>
            </div>
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/telephone.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Liên hệ ứng tuyển
              </dt>
              <dd class="mt-1 space-y-1 text-muted">
                <span class="block">Chị Trân: <a href="tel:0913131090" class="font-medium text-body transition-colors hover:text-accent">0913 131 090</a></span>
                <span class="block">Chị Ngân: <a href="tel:0332629650" class="font-medium text-body transition-colors hover:text-accent">0332 629 650</a></span>
                <span class="block">Anh Linh: <a href="tel:0945609942" class="font-medium text-body transition-colors hover:text-accent">0945 609 942</a></span>
              </dd>
            </div>
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/geo-alt.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Địa chỉ nhận hồ sơ
              </dt>
              <dd class="mt-1 text-muted">BI-5-6-7-8 Đ. D3, Khu công nghiệp, Tân Hương, Đồng Tháp.</dd>
            </div>
          </dl>

          <div class="map-frame mb-4">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3923.203881427061!2d106.3586310739038!3d10.484587964511139!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310ab768cef00851%3A0x75cf2c4b6dcf43cd!2zQ8O0bmcgVHkgQ-G7lSBQaOG6p24gVGV4LUdpYW5n!5e0!3m2!1svi!2s!4v1793765708852!5m2!1svi!2s"
                    title="Bản đồ Khu vực Tân Hương — TEX-GIANG"
                    style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
          </div>

          <a href="https://maps.app.goo.gl/ju91J21xxb4KCDEe8" target="_blank" rel="noopener"
             class="tg-btn tg-btn--ghost mt-auto h-11 w-full text-[15px]">
            <img src="<?= asset('images/icons/geo-alt.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon"> Mở Google Maps
          </a>
        </article>

        <!-- 2. CHỢ GẠO -->
        <article class="flex flex-col rounded-card border border-line bg-surface p-6 transition-shadow hover:shadow-float" data-reveal>
          <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-accent-soft text-[15px] font-semibold text-accent">2</span>
            <h3 class="text-[18px] font-medium leading-snug text-ink">Khu vực Chợ Gạo</h3>
          </div>

          <dl class="mb-6 mt-5 space-y-4 text-[15px] leading-[1.65]">
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/buildings.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Địa điểm làm việc
              </dt>
              <dd class="mt-1 text-muted">Ấp Hòa Lợi Tiểu, Xã Bình Ninh, Tỉnh Đồng Tháp.</dd>
            </div>
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/telephone.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Liên hệ ứng tuyển
              </dt>
              <dd class="mt-1 space-y-1 text-muted">
                <span class="block">Chị Tuyết: <a href="tel:0963181431" class="font-medium text-body transition-colors hover:text-accent">0963 181 431</a></span>
                <span class="block">Anh Sơn: <a href="tel:0924772499" class="font-medium text-body transition-colors hover:text-accent">0924 772 499</a></span>
              </dd>
            </div>
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/geo-alt.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Địa chỉ nhận hồ sơ
              </dt>
              <dd class="mt-1 text-muted">Ấp Hòa Lợi Tiểu, Xã Bình Ninh, Tỉnh Đồng Tháp.</dd>
            </div>
          </dl>

          <div class="map-frame mb-4">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3925.2436624600177!2d106.52494427390207!3d10.32237476740696!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310aadd3a04d3db5%3A0x6df16003b97e9aba!2zQ8O0bmcgVHkgQ1AgVGV4LUdpYW5nIGNoaSBuaMOhbmggQ2jhu6MgR-G6oW8!5e0!3m2!1svi!2s!4v1793765777202!5m2!1svi!2s"
                    title="Bản đồ Khu vực Chợ Gạo — TEX-GIANG"
                    style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
          </div>

          <a href="https://maps.app.goo.gl/ouoWhsTAtT7F4ma28" target="_blank" rel="noopener"
             class="tg-btn tg-btn--ghost mt-auto h-11 w-full text-[15px]">
            <img src="<?= asset('images/icons/geo-alt.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon"> Mở Google Maps
          </a>
        </article>

        <!-- 3. CÁI LẬY -->
        <article class="flex flex-col rounded-card border border-line bg-surface p-6 transition-shadow hover:shadow-float" data-reveal>
          <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-accent-soft text-[15px] font-semibold text-accent">3</span>
            <h3 class="text-[18px] font-medium leading-snug text-ink">Khu vực Cai Lậy</h3>
          </div>

          <dl class="mb-6 mt-5 space-y-4 text-[15px] leading-[1.65]">
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/buildings.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Địa điểm làm việc
              </dt>
              <dd class="mt-1 text-muted">Ấp Bình Quới, Xã Bình Phú, Tỉnh Đồng Tháp.</dd>
            </div>
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/telephone.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Liên hệ ứng tuyển
              </dt>
              <dd class="mt-1 space-y-1 text-muted">
                <span class="block">Anh Nguyên: <a href="tel:0938583943" class="font-medium text-body transition-colors hover:text-accent">0938 583 943</a></span>
                <span class="block">Chị Hằng: <a href="tel:0908813822" class="font-medium text-body transition-colors hover:text-accent">0908 813 822</a></span>
              </dd>
            </div>
            <div>
              <dt class="flex items-center gap-2 font-medium text-body">
                <img class="tg-icon text-accent" src="<?= asset('images/icons/geo-alt.svg') ?>" width="20" height="20" alt="" aria-hidden="true"> Địa chỉ nhận hồ sơ
              </dt>
              <dd class="mt-1 text-muted">Ấp Bình Quới, Xã Bình Phú, Tỉnh Đồng Tháp.</dd>
            </div>
          </dl>

          <div class="map-frame mb-4">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6832.276131181911!2d106.08859122953558!3d10.41266722163212!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310a97c35289cfa5%3A0x38fbaef867a1615!2zQ8O0bmcgdHkgY-G7lSBwaOG6p24gVEVYIC0gR0lBTkc!5e0!3m2!1svi!2s!4v1793765738154!5m2!1svi!2s"
                    title="Bản đồ Khu vực Cái Lậy — TEX-GIANG"
                    style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
          </div>

          <a href="https://maps.app.goo.gl/Nvd2xR3BMxEWgCSz7" target="_blank" rel="noopener"
             class="tg-btn tg-btn--ghost mt-auto h-11 w-full text-[15px]">
            <img src="<?= asset('images/icons/geo-alt.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon"> Mở Google Maps
          </a>
        </article>

      </div>
    </section>

    <!-- FORM LIÊN HỆ -->
    <section id="lien-he" class="tg-container pb-20 pt-12 lg:pb-28">
      <div class="mx-auto max-w-[860px]">
        <div class="rounded-media border border-line bg-surface p-6 lg:p-10" data-reveal>
          <h2 class="text-[clamp(1.375rem,2.4vw,1.75rem)] font-medium leading-[1.25] text-ink">
            Gửi câu hỏi cho khu vực bạn muốn làm việc
          </h2>
          <p class="mt-2 max-w-[62ch] text-[16px] leading-[1.7] text-muted">
            Chọn khu vực và để lại thông tin — người phụ trách tuyển dụng khu vực đó sẽ gọi lại trong giờ làm việc.
          </p>

          <?php
          $val = static fn(string $key, string $default = ''): string => (string) ($old[$key] ?? $default);
          $err = static fn(string $key): string => (string) ($errors[$key] ?? '');
          $flashSuccess = \App\Core\Session::pull('_success');
          $flashError = \App\Core\Session::pull('_error');
          ?>
          <?php if ($flashSuccess): ?>
          <p class="form-summary" role="status" style="background:#e8f7ef;border-left-color:#087a55;color:#12633f"><?= e((string) $flashSuccess) ?></p>
          <?php endif; ?>
          <?php if ($flashError): ?>
          <p class="form-summary" role="alert"><?= e((string) $flashError) ?></p>
          <?php endif; ?>

          <form id="contact-form" class="mt-6 grid gap-4 sm:grid-cols-2" method="POST" action="<?= url('contact') ?>#lien-he">
            <?= csrf_field() ?>
            <div>
              <label class="form-label" for="contact-name">Họ và tên <span class="req">*</span></label>
              <input type="text" id="contact-name" name="full_name" required autocomplete="name" maxlength="150"
                     placeholder="Ví dụ: Nguyễn Văn C" class="form-input<?= $err('full_name') ? ' is-invalid' : '' ?>"
                     value="<?= e($val('full_name')) ?>">
              <?php if ($err('full_name')): ?><p class="field-error"><?= e($err('full_name')) ?></p><?php endif; ?>
            </div>
            <div>
              <label class="form-label" for="contact-phone">Số điện thoại <span class="req">*</span></label>
              <input type="tel" id="contact-phone" name="phone" required autocomplete="tel" inputmode="tel"
                     placeholder="09xx xxx xxx" class="form-input<?= $err('phone') ? ' is-invalid' : '' ?>"
                     value="<?= e($val('phone')) ?>">
              <?php if ($err('phone')): ?><p class="field-error"><?= e($err('phone')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
              <label class="form-label" for="contact-email">Email (nếu muốn nhận phản hồi qua email)</label>
              <input type="email" id="contact-email" name="email" autocomplete="email" maxlength="190"
                     placeholder="email@gmail.com" class="form-input<?= $err('email') ? ' is-invalid' : '' ?>"
                     value="<?= e($val('email')) ?>">
              <?php if ($err('email')): ?><p class="field-error"><?= e($err('email')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
              <label class="form-label" for="contact-area">Khu vực muốn liên hệ <span class="req">*</span></label>
              <select id="contact-area" name="area" required class="form-input">
                <option value="">— Chọn khu vực —</option>
                <option value="tan-huong"<?= $val('area') === 'tan-huong' ? ' selected' : '' ?>>Khu vực Tân Hương</option>
                <option value="cho-gao"<?= $val('area') === 'cho-gao' ? ' selected' : '' ?>>Khu vực Chợ Gạo</option>
                <option value="cai-lay"<?= $val('area') === 'cai-lay' ? ' selected' : '' ?>>Khu vực Cai Lậy</option>
                <option value="khac"<?= $val('area') === 'khac' ? ' selected' : '' ?>>Không chắc — cần tư vấn thêm</option>
              </select>
              <?php if ($err('area')): ?><p class="field-error"><?= e($err('area')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
              <label class="form-label" for="contact-message">Nội dung cần hỗ trợ <span class="req">*</span></label>
              <textarea id="contact-message" name="message" rows="4" required class="form-input<?= $err('message') ? ' is-invalid' : '' ?>"
                        placeholder="Ví dụ: Em muốn hỏi tuyến xe tại xã An Nhơn và ngày nhận việc của công nhân may."><?= e($val('message')) ?></textarea>
              <?php if ($err('message')): ?><p class="field-error"><?= e($err('message')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2 flex flex-col gap-3 sm:flex-row sm:items-center">
              <button type="submit" class="tg-btn tg-btn--primary">Gửi câu hỏi</button>
              <p class="text-[14px] leading-[1.6] text-muted">Thông tin chỉ dùng để phản hồi tuyển dụng, không dùng cho mục đích khác.</p>
            </div>
          </form>
        </div>
      </div>
    </section>
