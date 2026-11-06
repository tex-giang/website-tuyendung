<?php
/**
 * Danh sách tin tức — port shell news.html, dữ liệu từ bảng posts.
 * @var array{items:array,total:int,pages:int,page:int,per_page:int} $result
 * @var array $categories @var array $featured @var string $categorySlug @var int $totalAll
 */
$pageNow = $result['page'];
$chips = [];
foreach ($categories as $category) {
    $chips[] = [
        'slug'  => (string) $category['slug'],
        'name'  => (string) $category['name'],
        'count' => (int) $category['post_count'],
    ];
}
$start = $result['total'] === 0 ? 0 : (($result['page'] - 1) * $result['per_page']) + 1;
$end = min($result['page'] * $result['per_page'], $result['total']);
$queryBase = $categorySlug !== '' ? ['chuyen-muc' => $categorySlug] : [];
?>
<section class="tg-container news-hero">
  <nav aria-label="Breadcrumb" class="news-breadcrumb">
    <a href="<?= url('') ?>">Trang chủ</a><span aria-hidden="true">/</span><span>Tin tức</span>
  </nav>

  <div class="news-hero__head">
    <div>
      <h1 class="news-hero__title">Tin tức &amp; nghề nghiệp TEX-GIANG</h1>
      <p class="news-hero__lead">Thông báo tuyển dụng, hoạt động tại ba khu sản xuất, câu chuyện người thợ và kinh nghiệm ứng tuyển.</p>
    </div>
    <p class="news-hero__updated">Cập nhật <?= date('d/m/Y') ?></p>
  </div>
</section>

<?php if ($featured !== []): ?>
<section class="tg-container pt-10 lg:pt-12" aria-label="Bài viết nổi bật">
  <div class="news-featured" data-reveal>
    <?php $variant = 'news-card--lead'; $post = $featured[0]; require BASE_PATH . '/app/Views/partials/public/news-card.php'; ?>
    <?php if (count($featured) > 1): ?>
    <div class="news-featured__aside">
      <?php foreach (array_slice($featured, 1, 2) as $post): ?>
        <?php $variant = 'news-card--compact'; require BASE_PATH . '/app/Views/partials/public/news-card.php'; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section id="news-all" class="tg-container py-14 lg:py-20">
  <div class="news-section-head">
    <h2 class="news-section-title">Tất cả bài viết</h2>
    <p id="news-count" class="news-count" role="status" aria-live="polite">
      <?= $result['total'] > 0
          ? 'Hiển thị ' . $start . '–' . $end . ' trong ' . (int) $result['total'] . ' bài viết'
          : '0 bài viết' ?>
    </p>
  </div>

  <div id="news-chips" class="news-chips" role="group" aria-label="Lọc bài viết theo chuyên mục">
    <a class="news-chip<?= $categorySlug === '' ? ' is-active' : '' ?>" href="<?= url('news') ?>">Tất cả<span class="news-chip__count"><?= (int) $totalAll ?></span></a>
    <?php foreach ($chips as $chip): ?>
    <a class="news-chip<?= $categorySlug === $chip['slug'] ? ' is-active' : '' ?>"
       href="<?= url('news') . '?chuyen-muc=' . urlencode($chip['slug']) ?>"><?= e($chip['name']) ?><span class="news-chip__count"><?= $chip['count'] ?></span></a>
    <?php endforeach; ?>
  </div>

  <div id="news-grid" class="news-grid">
    <?php if ($result['items'] === []): ?>
    <p class="news-empty">Chưa có bài viết trong mục này.</p>
    <?php else: ?>
      <?php foreach ($result['items'] as $post): ?>
        <?php $variant = ''; require BASE_PATH . '/app/Views/partials/public/news-card.php'; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($result['pages'] > 1): ?>
  <nav id="news-pager" class="news-pager" aria-label="Phân trang tin tức">
    <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
      <?php $qs = http_build_query($queryBase + ($p > 1 ? ['page' => $p] : [])); ?>
      <a class="news-pager__btn<?= $p === $pageNow ? ' is-active' : '' ?>"
         href="<?= url('news') ?><?= $qs !== '' ? '?' . e($qs) : '' ?>"
         aria-label="Trang <?= $p ?>"<?= $p === $pageNow ? ' aria-current="page"' : '' ?>><?= $p ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</section>

<section class="tg-container pb-20 lg:pb-24">
  <div class="news-subscribe">
    <div>
      <h2 class="text-[20px] font-medium text-ink">Nhận thông báo đợt tuyển mới</h2>
      <p class="mt-2 max-w-[52ch] text-[16px] leading-[1.6] text-muted">Để lại email, mỗi tháng chúng tôi gửi một bản tin ngắn về vị trí mới và lịch phỏng vấn tại nhà máy.</p>
    </div>
    <form id="news-subscribe" class="flex w-full flex-wrap gap-3 sm:w-auto" method="POST" action="<?= url('news/subscribe') ?>">
      <?= csrf_field() ?>
      <label class="sr-only" for="news-email">Email nhận tin</label>
      <input id="news-email" name="email" type="email" required placeholder="email@gmail.com" class="form-input sm:w-[260px]"
             value="<?= e(\App\Core\Session::pull('_subscribed_email', '')) ?>">
      <button type="submit" class="tg-btn tg-btn--primary">Đăng ký</button>
    </form>
  </div>
  <?php $subscribeMessage = \App\Core\Session::pull('_subscribe_message'); ?>
  <?php if ($subscribeMessage): ?>
  <p class="news-count mt-3" role="status"><?= e((string) $subscribeMessage) ?></p>
  <?php endif; ?>
</section>
