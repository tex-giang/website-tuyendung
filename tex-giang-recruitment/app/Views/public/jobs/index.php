<?php
/** Danh sách việc làm — lọc và phân trang phía server (dữ liệu MySQL). */
// Dữ liệu vào: $filters, $result, $query, $branches, $blocks, $categories.
$data = require BASE_PATH . '/app/Views/partials/public/jobs-results-data.php';
$hasFilter = $data['has_filter'];
?>
<section class="tg-container pt-14 lg:pt-20">
  <div class="page-intro">
    <div>
      <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
        <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="text-body">Việc làm</span>
      </nav>

      <h1 class="mt-4 max-w-[900px] text-[clamp(2rem,4.4vw,3rem)] font-medium leading-[1.15] tracking-[-0.02em] text-ink">
        Việc làm tại TEX-GIANG
      </h1>
      <p class="mt-4 max-w-[62ch] text-[18px] leading-[1.6] text-muted">
        Vị trí đang mở tại Nhà máy Tân Hương 2 và TEX-GIANG. Lọc theo khu sản xuất, khối hoặc bộ phận tuyển dụng.
      </p>
    </div>
    <figure class="intro-photo">
      <img src="<?= asset('images/company/tan-huong-2.webp') ?>" width="360" height="300" alt="Toàn cảnh nhà máy Tân Hương 2 trong hệ thống TEX-GIANG" fetchpriority="high">
      <figcaption>Nhà máy Tân Hương 2 · TEX-GIANG</figcaption>
    </figure>
  </div>
</section>

<section class="tg-container pb-20 pt-10 lg:pb-28">
  <div class="jobs-layout">

    <div id="filter-overlay" class="filter-overlay hidden" aria-hidden="true"></div>
    <aside id="filters-sidebar" class="filters-drawer hidden lg:block" aria-label="Bộ lọc việc làm">
      <form id="jobs-filter-form" class="space-y-5 rounded-card bg-surface p-5 lg:p-6" method="GET"
            action="<?= url('jobs') ?>" data-jobs-filter>
        <div class="flex items-center justify-between">
          <h2 class="text-[16px] font-semibold text-ink">Bộ lọc</h2>
          <div class="flex items-center gap-2">
            <a href="<?= url('jobs') ?>" class="text-[14px] font-medium text-accent hover:underline" data-jobs-link>Xoá hết</a>
            <button type="button" id="close-filter-drawer" class="grid h-8 w-8 place-items-center rounded-lg bg-white text-muted lg:hidden" aria-label="Đóng bộ lọc">
              <img src="<?= asset('images/icons/x-lg.svg') ?>" alt="" aria-hidden="true" width="20" height="20" class="tg-icon text-[14px]">
            </button>
          </div>
        </div>

        <?php
        // Các ô lọc là CON TRỰC TIẾP của form để CSS grid ngang (#jobs-filter-form)
        // áp dụng đúng. Không bọc thêm <div> — sẽ phá grid và làm các ô xếp dọc.
        // Fragment cập nhật riêng từng ô theo id, không cần wrapper bao ngoài.
        require BASE_PATH . '/app/Views/partials/public/jobs-filter-fields.php';
        ?>

        <button type="submit" class="tg-btn tg-btn--primary w-full">Áp dụng bộ lọc</button>
      </form>
    </aside>

    <div>
      <?php require BASE_PATH . '/app/Views/partials/public/jobs-results.php'; ?>

      <p class="mt-8 text-[14px] leading-[1.6] text-muted">
        Không thấy vị trí phù hợp? Xem <a href="<?= url('contact') ?>" class="text-body underline underline-offset-2 hover:text-accent">người phụ trách theo nhà máy</a> để được tư vấn nhanh.
      </p>
    </div>
  </div>
</section>
