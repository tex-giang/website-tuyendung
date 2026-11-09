<?php
/** Sidebar quản trị: nhóm theo nghiệp vụ, ẩn module ngoài quyền của vai trò hiện tại. */
$nav = $page['nav'] ?? '';
$user = \App\Core\Session::user() ?? ['full_name' => '', 'role' => ''];
$can = static fn(string $module): bool => \App\Core\Permissions::currentCan($module);

$groups = [
    'Tuyển dụng' => [
        ['dashboard', 'Bảng điều khiển', url('admin/dashboard'), 0, 'dashboard', 'bi-speedometer2'],
        ['jobs', 'Tin tuyển dụng', url('admin/jobs'), 0, 'jobs', 'bi-briefcase'],
        ['applications', 'Hồ sơ ứng tuyển', url('admin/applications'), \App\Models\Application::newCount(), 'applications', 'bi-file-earmark-person'],
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
        ['contact', 'Hộp thư liên hệ', url('admin/contact'), \App\Models\ContactMessage::newCount(), 'contact', 'bi-envelope'],
    ],
    'Hệ thống' => [
        ['settings', 'Cài đặt hệ thống', url('admin/settings'), 0, 'settings', 'bi-gear'],
        ['users', 'Người dùng quản trị', url('admin/users'), 0, 'users', 'bi-people'],
        ['audit', 'Nhật ký thao tác', url('admin/audit'), 0, 'audit', 'bi-clock-history'],
    ],
];
$roleLabel = \App\Core\Permissions::roleLabels()[$user['role'] ?? ''] ?? ($user['role'] ?? '');
$words = preg_split('/\s+/u', trim((string) $user['full_name'])) ?: [];
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= mb_substr($word, 0, 1, 'UTF-8');
}
$initials = mb_strtoupper($initials !== '' ? $initials : 'TG', 'UTF-8');
?>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Menu quản trị">
  <a href="<?= url('admin/dashboard') ?>" class="admin-brand">
    <img src="<?= asset('images/logo-tgi-mark.webp') ?>" alt="TEX-GIANG" width="60" height="40">
    <span>Quản trị HR</span>
  </a>

  <nav class="admin-nav">
    <?php foreach ($groups as $groupLabel => $items): ?>
      <?php
      $visible = array_values(array_filter($items, static fn(array $item): bool => $can((string) ($item[4] ?? $item[0]))));
      if ($visible === []) {
          continue;
      }
      ?>
      <p class="admin-nav__group"><?= e($groupLabel) ?></p>
      <?php foreach ($visible as [$key, $label, $href, $badge, $perm, $icon]): ?>
      <a href="<?= $href ?>" class="admin-nav__item <?= $nav === $key ? 'is-active' : '' ?>" title="<?= e($label) ?>">
        <i class="bi <?= e($icon) ?> admin-nav__icon" aria-hidden="true"></i>
        <span class="admin-nav__label"><?= e($label) ?></span>
        <?php if ((int) $badge > 0): ?><span class="admin-nav__count"><?= (int) $badge ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="admin-sidebar__foot">
    <a href="<?= url('admin/profile') ?>" class="admin-sidebar__user">
      <span class="admin-sidebar__avatar" aria-hidden="true"><?= e($initials) ?></span>
      <span class="admin-sidebar__meta">
        <strong title="<?= e($user['full_name']) ?>"><?= e($user['full_name']) ?></strong>
        <em><?= e($roleLabel) ?></em>
      </span>
    </a>
    <a href="<?= url('') ?>" target="_blank" rel="noopener" class="admin-sidebar__link"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> Website công khai</a>
  </div>
</aside>
