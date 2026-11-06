<?php
/** Quản lý banner theo 4 loại: hero, notice, popup, ad. */
$typeHints = [
    'hero' => 'Slide ảnh đầu trang chủ. Nên dùng ảnh ngang tỉ lệ 2048×768.',
    'notice' => 'Dải thông báo mảnh ngay dưới header. Chỉ cần tiêu đề + link.',
    'popup' => 'Thẻ nổi góc trái, hiện sau khi khách cuộn trang.',
    'ad' => 'Dải CTA nền xanh ở giữa trang chủ.',
];
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Banner &amp; thông báo</h1>
    <p class="admin-sub"><?= (int) $total ?> banner. Banner đang bật và trong thời gian hiệu lực sẽ hiển thị trên website.</p>
  </div>
  <a href="<?= url('admin/banners/create') ?>" class="btn btn-primary">+ Thêm banner</a>
</div>

<?php foreach ($types as $type => $typeLabel): ?>
<section class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-h2"><?= e($typeLabel) ?> <span class="admin-badge admin-badge--soft"><?= count($grouped[$type]) ?></span></h2>
    <span class="admin-muted"><?= e($typeHints[$type]) ?></span>
  </div>

  <?php if ($grouped[$type] === []): ?>
  <p class="admin-empty">Chưa có banner loại này. <a href="<?= url('admin/banners/create') ?>?type=<?= e($type) ?>">Thêm ngay</a>.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable" data-dt-compact>
      <thead><tr><th>Banner</th><th>Nội dung</th><th>Hiệu lực</th><th>Trạng thái</th><th class="is-actions">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($grouped[$type] as $banner): ?>
        <tr>
          <td>
            <div class="admin-media">
              <?php if (($banner['image_path'] ?? '') !== ''): ?>
              <img class="admin-thumb" src="<?= e(asset((string) $banner['image_path'])) ?>" alt="" loading="lazy">
              <?php else: ?>
              <span class="admin-thumb admin-thumb--empty">no ảnh</span>
              <?php endif; ?>
              <div>
                <strong><?= e($banner['name']) ?></strong>
                <span class="admin-muted">Thứ tự <?= (int) $banner['sort_order'] ?></span>
              </div>
            </div>
          </td>
          <td>
            <?php if (!empty($banner['title'])): ?><span class="admin-muted"><?= e(excerpt((string) $banner['title'], 90)) ?></span><?php endif; ?>
            <?php if (!empty($banner['link_url'])): ?><span class="admin-muted admin-mono">→ <?= e((string) $banner['link_url']) ?></span><?php endif; ?>
            <?php if (!empty($banner['description'])): ?><span class="admin-muted"><?= e(excerpt((string) $banner['description'], 90)) ?></span><?php endif; ?>
          </td>
          <td class="admin-muted">
            <?php if (!empty($banner['starts_at']) || !empty($banner['ends_at'])): ?>
              <?= e(vn_date($banner['starts_at'], 'd/m/Y H:i') ?: '…') ?> → <?= e(vn_date($banner['ends_at'], 'd/m/Y H:i') ?: '…') ?>
            <?php else: ?>
              Không giới hạn
            <?php endif; ?>
          </td>
          <td>
            <span class="admin-badge <?= (int) $banner['is_active'] === 1 ? 'admin-badge--published' : 'admin-badge--draft' ?>">
              <?= (int) $banner['is_active'] === 1 ? 'Đang bật' : 'Đang tắt' ?>
            </span>
          </td>
          <td class="is-actions">
            <div class="admin-actions">
              <form method="POST" action="<?= url('admin/banners/' . $banner['id'] . '/toggle') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-outline btn-sm" type="submit"><?= (int) $banner['is_active'] === 1 ? 'Tắt' : 'Bật' ?></button>
              </form>
              <a href="<?= url('admin/banners/' . $banner['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Sửa</a>
              <form method="POST" action="<?= url('admin/banners/' . $banner['id'] . '/delete') ?>"
                    data-confirm="Xoá banner &quot;<?= e($banner['name']) ?>&quot;?">
                <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Xoá</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>
