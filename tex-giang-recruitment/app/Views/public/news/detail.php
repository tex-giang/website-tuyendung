<?php
/**
 * Chi tiết bài viết — port markup từ news-detail.js bản tĩnh, nội dung từ bảng posts.
 * @var array $post @var array $related
 */
$paragraphs = preg_split('/\r\n\r\n|\n\n|\r\r/', trim((string) $post['body'])) ?: [];
$paragraphs = array_values(array_filter(array_map('trim', $paragraphs), static fn(string $p): bool => $p !== ''));
$imageUrl = ($post['image_path'] ?? '') !== '' ? asset((string) $post['image_path']) : asset('images/banner-texgiang.webp');
$publishedDate = vn_date($post['published_at'] ?? $post['created_at']);
?>
<article class="tg-container news-reading" data-reveal>
  <nav aria-label="Breadcrumb" class="text-[14px] text-muted">
    <a href="<?= url('') ?>" class="transition-colors hover:text-accent">Trang chủ</a>
    <span class="mx-1.5" aria-hidden="true">/</span>
    <a href="<?= url('news') ?>" class="transition-colors hover:text-accent">Tin tức</a>
    <span class="mx-1.5" aria-hidden="true">/</span>
    <span class="text-body"><?= e($post['title']) ?></span>
  </nav>

  <h1><?= e($post['title']) ?></h1>
  <p class="news-reading-meta"><?= e($post['category_name'] ?? 'Tin tức') ?> · Ban truyền thông TEX-GIANG · Cập nhật <?= e($publishedDate) ?></p>
  <?php if (!empty($post['excerpt'])): ?>
  <p class="news-reading-lead"><?= e($post['excerpt']) ?></p>
  <?php endif; ?>

  <figure>
    <img src="<?= e($imageUrl) ?>" alt="<?= e($post['image_alt'] ?? $post['title']) ?>" width="1280" height="850" loading="eager" decoding="async">
    <figcaption>
      <?= e($post['image_caption'] ?? 'Ảnh: TEX-GIANG') ?> —
      <a href="https://tex-giang.com.vn/" target="_blank" rel="noopener">TEX-GIANG</a>.
    </figcaption>
  </figure>

  <h2>Thông tin cần biết</h2>
  <?php foreach ($paragraphs as $paragraph): ?>
  <p><?= e($paragraph) ?></p>
  <?php endforeach; ?>

  <div class="article-actions">
    <a class="tg-btn tg-btn--primary" href="<?= url('jobs') ?>">Xem việc đang tuyển</a>
    <a class="tg-btn tg-btn--ghost" href="<?= url('apply') ?>">Nộp hồ sơ trực tuyến</a>
  </div>

  <div class="share-row">
    <button type="button" onclick="copyLink()" class="tg-link">Sao chép liên kết</button>
    <a class="tg-link" href="<?= url('news') ?>">Về danh sách tin</a>
  </div>
</article>

<?php if ($related !== []): ?>
<section class="tg-container news-related" data-reveal>
  <h2>Đọc thêm</h2>
  <ul>
    <?php foreach ($related as $item): ?>
    <li>
      <a href="<?= url('news/' . $item['slug']) ?>">
        <span><?= e($item['category_name'] ?? 'Tin tức') ?></span>
        <strong><?= e($item['title']) ?></strong>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
