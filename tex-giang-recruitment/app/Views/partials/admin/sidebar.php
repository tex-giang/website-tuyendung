<?php
/** Sidebar quản trị: nhóm theo nghiệp vụ, ẩn module ngoài quyền của vai trò hiện tại. */
$nav = $page['nav'] ?? '';
$can = static fn(string $module): bool => \App\Core\Permissions::currentCan($module);

$groups = [
    'Tuyển dụng' => [
        ['dashboard', 'Bảng điều khiển', url('admin/dashboard'), 0, 'dashboard', 'bi-speedometer2'],
        ['jobs', 'Tin tuyển dụng', url('admin/jobs'), 0, 'jobs', 'bi-briefcase'],
        ['applications', 'Hồ sơ ứng tuyển', url('admin/applications'), $counts['applications'], 'applications', 'bi-file-earmark-person'],
        ['visits', 'Thống kê truy cập', url('admin/visits'), 0, 'dashboard', 'bi-bar-chart-line'],
    ],
    'Nội dung' => [
        ['posts', 'Tin tức & bài viết', url('admin/posts'), 0, 'posts', 'bi-newspaper'],
        ['banners', 'Banner & thông báo', url('admin/banners'), 0, 'banners', 'bi-images'],
        ['content', 'Nội dung website', url('admin/content'), 0, 'content', 'bi-file-earmark-text'],
        ['pages-about', 'Về chúng tôi', url('admin/pages/about'), 0, 'content', 'bi-info-circle'],
        ['pages-benefits', 'Phúc lợi', url('admin/pages/benefits'), 0, 'content', 'bi-gift'],
        ['menus', 'Menu website', url('admin/menus'), 0, 'content', 'bi-menu-button-wide'],
        ['categories', 'Danh mục dùng chung', url('admin/categories'), 0, 'categories', 'bi-tags'],
        ['contact', 'Hộp thư liên hệ', url('admin/contact'), $counts['contact'], 'contact', 'bi-envelope'],
    ],
    'Hệ thống' => [
        ['settings', 'Cài đặt hệ thống', url('admin/settings'), 0, 'settings', 'bi-gear'],
        ['users', 'Người dùng quản trị', url('admin/users'), 0, 'users', 'bi-people'],
        ['audit', 'Nhật ký thao tác', url('admin/audit'), 0, 'audit', 'bi-clock-history'],
    ],
];
?>
<aside class="offcanvas-lg offcanvas-start admin-sidebar" id="admin-sidebar" tabindex="-1" aria-labelledby="admin-sidebar-title">
  <div class="admin-sidebar__head">
    <a href="<?= url('admin/dashboard') ?>" class="admin-brand" id="admin-sidebar-title">
      <img src="<?= asset('images/logo-tgi-mark.webp') ?>" alt="" width="44" height="36">
      <span translate="no">TEX-GIANG</span>
    </a>
    <button type="button" class="admin-sidebar-toggle admin-sidebar__close" aria-label="Đóng menu quản trị"
            data-bs-dismiss="offcanvas" data-bs-target="#admin-sidebar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
  </div>

  <nav class="admin-nav" aria-label="Menu quản trị">
    <?php foreach ($groups as $groupLabel => $items): ?>
      <?php
      $visible = array_values(array_filter($items, static fn(array $item): bool => $can((string) ($item[4] ?? $item[0]))));
      if ($visible === []) {
          continue;
      }
      ?>
      <p class="admin-nav__group"><?= e($groupLabel) ?></p>
      <?php foreach ($visible as [$key, $label, $href, $badge, $perm, $icon]): ?>
      <a href="<?= $href ?>" data-admin-nav="<?= e($key) ?>" class="admin-nav__item<?= $nav === $key ? ' is-active' : '' ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>>
        <i class="bi <?= e($icon) ?> admin-nav__icon" aria-hidden="true"></i>
        <span class="admin-nav__label"><?= e($label) ?></span>
        <?php if (array_key_exists($key, $counts)): ?><span class="admin-nav__count"<?= $badge > 0 ? '' : ' hidden' ?>><span data-admin-count><?= (int) $badge ?></span><span class="visually-hidden"> mới</span></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="admin-sidebar__foot">
    <a href="<?= url('') ?>" target="_blank" rel="noopener" class="admin-sidebar__link" aria-label="Website tuyển dụng (mở tab mới)"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Website tuyển dụng</a>
  </div>
</aside>
