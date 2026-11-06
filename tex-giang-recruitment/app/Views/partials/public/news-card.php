<?php
/**
 * Card bài viết — markup lấy từ news.js bản tĩnh để giữ nguyên design.
 * @var array $post
 * @var string $variant  '' | 'news-card--lead' | 'news-card--compact'
 */
$variant = $variant ?? '';
$image = (string) ($post['image_path'] ?? '');
$imageUrl = $image !== '' ? asset($image) : asset('images/banner-texgiang-800.webp');
$position = (string) ($post['image_caption'] ?? '');
$date = vn_date($post['published_at'] ?? $post['created_at']);
$categoryName = $post['category_name'] ?? 'Tin tức';
?>
<article class="news-card <?= e($variant) ?>">
  <a class="news-card__link" href="<?= url('news/' . $post['slug']) ?>">
    <img class="news-card__img" src="<?= e($imageUrl) ?>" alt="<?= e($post['image_alt'] ?? $post['title']) ?>"
         loading="lazy" decoding="async" style="object-position:center 50%">
    <div class="news-card__body">
      <h3 class="news-card__title"><?= e($post['title']) ?></h3>
      <p class="news-card__meta">
        <span class="news-card__date"><?= e($date) ?></span>
        <span class="news-card__dot" aria-hidden="true">·</span>
        <span class="news-card__cat"><?= e($categoryName) ?></span>
      </p>
      <p class="news-card__excerpt"><?= e(excerpt((string) ($post['excerpt'] ?? $post['body']), 180)) ?></p>
    </div>
  </a>
</article>
