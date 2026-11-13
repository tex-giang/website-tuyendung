<?php
$page['ajax_fragment'] = true;
$listUrl = url('admin/banners');
$typeHints = [
    'hero' => 'Ảnh lớn luân phiên ở đầu trang chủ website tuyển dụng.',
    'notice' => 'Dòng thông báo ngắn hiển thị phía trên cùng của website.',
    'popup' => 'Thẻ nhỏ nổi ở góc màn hình để thu hút ứng viên.',
    'ad' => 'Dải kêu gọi ứng tuyển xen giữa các khối nội dung.',
];
?>
<div class="admin-page-head admin-list-head admin-banners-head">
  <div>
    <h1 class="admin-h1">Banner và thông báo</h1>
    <p class="admin-sub">Quản lý các banner hiển thị trên website tuyển dụng theo từng nhóm.</p>
  </div>
</div>

<div class="admin-banner-groups">
  <?php foreach ($types as $type => $typeLabel): ?>
  <section class="admin-list-panel" aria-labelledby="banner-group-<?= e($type) ?>">
    <header class="admin-banner-group-head">
      <div>
        <h2 id="banner-group-<?= e($type) ?>"><?= e($typeLabel) ?> <span><?= count($grouped[$type]) ?></span></h2>
        <p><?= e($typeHints[$type]) ?></p>
      </div>
      <a href="<?= $listUrl ?>/create?type=<?= e($type) ?>" class="btn btn-primary btn-sm"<?= can('banners', 'create') ? '' : ' hidden' ?>><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm banner</a>
    </header>
    <?php if ($grouped[$type] === []): ?>
    <p class="admin-list-empty">Chưa có banner trong nhóm này.</p>
    <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table admin-cms-table admin-banner-table table table-hover align-middle js-datatable" data-dt-compact aria-labelledby="banner-group-<?= e($type) ?>">
        <thead><tr>
          <th scope="col">Ảnh</th><th scope="col">Nội dung hiển thị</th><th scope="col">Thời gian</th>
          <th scope="col" class="admin-banner-order">Thứ tự</th><th scope="col">Hiển thị</th><th scope="col" class="is-actions">Thao tác</th>
        </tr></thead>
        <tbody>
          <?php foreach ($grouped[$type] as $banner): ?>
          <?php $bannerUrl = $listUrl . '/' . (int) $banner['id']; $active = (int) $banner['is_active'] === 1; ?>
          <tr>
            <td>
              <?php if (!empty($banner['image_path'])): ?>
              <img class="admin-cms-thumb" src="<?= e(asset($banner['image_path'])) ?>" alt="" loading="lazy">
              <?php else: ?>
              <span class="admin-cms-thumb admin-cms-thumb--empty" aria-label="Chưa có ảnh"><i class="bi bi-image" aria-hidden="true"></i></span>
              <?php endif; ?>
            </td>
            <td class="admin-cms-table__title">
              <a href="<?= $bannerUrl ?>/edit"><?= e($banner['title'] ?: $banner['name']) ?></a>
              <?php if (!empty($banner['title'])): ?><span class="admin-muted"><?= e($banner['name']) ?></span><?php endif; ?>
            </td>
            <td class="admin-muted admin-mono admin-nowrap" data-order="<?= e($banner['starts_at'] ?? $banner['ends_at'] ?? '') ?>">
              <?php if (!empty($banner['starts_at']) || !empty($banner['ends_at'])): ?>
              <?= e(vn_date($banner['starts_at'], 'H:i d/m/Y') ?: '…') ?> → <?= e(vn_date($banner['ends_at'], 'H:i d/m/Y') ?: '…') ?>
              <?php else: ?>Luôn hiển thị<?php endif; ?>
            </td>
            <td class="admin-banner-order"><?= (int) $banner['sort_order'] ?></td>
            <td>
              <form data-ajax data-ajax-refresh method="POST" action="<?= $bannerUrl ?>/toggle">
                <?= csrf_field() ?>
                <button class="admin-cms-switch-button" type="submit" role="switch" aria-checked="<?= $active ? 'true' : 'false' ?>"
                        aria-label="Hiển thị banner <?= e($banner['name']) ?>" title="<?= $active ? 'Tắt hiển thị' : 'Bật hiển thị' ?>">
                  <span class="admin-cms-switch" aria-hidden="true"></span>
                </button>
              </form>
            </td>
            <td class="is-actions">
              <div class="admin-actions">
                <?php if (can('banners', 'update')): ?>
              <a href="<?= $bannerUrl ?>/edit" class="admin-row-icon" title="Chỉnh sửa" aria-label="Chỉnh sửa banner"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                <?php endif; ?>
              <?php if (can('banners', 'delete')): ?>
              <form data-ajax data-ajax-refresh method="POST" action="<?= $bannerUrl ?>/delete" data-confirm="Xóa banner &quot;<?= e($banner['name']) ?>&quot;?">
                  <?= csrf_field() ?><button class="admin-row-icon admin-row-icon--danger" type="submit" title="Xóa banner" aria-label="Xóa banner"><i class="bi bi-trash" aria-hidden="true"></i></button>
                </form>
              <?php endif; ?>
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
</div>
