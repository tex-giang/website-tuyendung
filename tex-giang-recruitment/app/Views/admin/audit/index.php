<?php
$page['ajax_fragment'] = true;
$listUrl = url('admin/audit');
$listPager = true;
$actionLabels = \App\Models\AuditLog::ACTION_LABELS;
$entities = [
    'user' => 'Tài khoản', 'job' => 'Tin tuyển dụng', 'application' => 'Hồ sơ ứng tuyển',
    'post' => 'Bài viết', 'banner' => 'Banner', 'settings' => 'Cài đặt', 'page' => 'Trang nội dung',
    'menu' => 'Menu', 'branch' => 'Khu sản xuất', 'category' => 'Khối phòng ban',
    'post-category' => 'Chuyên mục tin tức', 'contact_message' => 'Tin nhắn liên hệ',
];
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="admin-list-title">Nhật ký thao tác</h1>
    <p class="admin-sub">Theo dõi hoạt động và các thay đổi trong hệ thống quản trị.</p>
  </div>
</div>

<section class="admin-list-panel">
  <form class="admin-filters admin-list-toolbar" method="GET" role="search" aria-label="Bộ lọc nhật ký" action="<?= $listUrl ?>" data-admin-live-filter>
    <label class="admin-list-search">
      <span class="visually-hidden">Tìm trong nhật ký</span>
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" id="audit-search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo thao tác, đối tượng, IP…">
    </label>
    <label>
      <span class="visually-hidden">Lọc theo người thực hiện</span>
      <select name="user_id" class="field__input admin-list-select" data-submit-onchange>
        <option value="">Tất cả người thực hiện</option>
        <?php foreach ($users as $user): ?>
        <option value="<?= (int) $user['id'] ?>"<?= (string) $filters['user_id'] === (string) $user['id'] ? ' selected' : '' ?>><?= e($user['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <noscript><button type="submit" class="btn btn-outline">Áp dụng</button></noscript>
  </form>
  <?php if ($result['items'] === []): ?>
  <p class="admin-list-empty">Không có bản ghi phù hợp.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-directory-table admin-directory-table--audit" aria-labelledby="admin-list-title">
      <thead><tr><th scope="col">Thời gian</th><th scope="col">Người thực hiện</th><th scope="col">Thao tác</th><th scope="col">Đối tượng</th><th scope="col">Địa chỉ IP</th></tr></thead>
      <tbody>
        <?php foreach ($result['items'] as $row): ?>
        <tr>
          <td class="admin-mono admin-audit-date" data-label="Thời gian"><?= e(vn_date($row['created_at'], 'H:i:s d/m/Y')) ?></td>
          <td data-label="Người thực hiện"><?= e($row['user_name'] ?? '—') ?></td>
          <td class="admin-directory-name"><strong><?= e($actionLabels[$row['action']] ?? $row['action']) ?></strong></td>
          <td data-label="Đối tượng">
            <?= e($entities[$row['entity_type']] ?? $row['entity_type'] ?? '—') ?><?= $row['entity_id'] ? ' #' . (int) $row['entity_id'] : '' ?>
            <?php if (!empty($row['meta'])): ?>
            <span class="admin-muted admin-mono"><?= e(excerpt((string) $row['meta'], 80)) ?></span>
            <?php endif; ?>
          </td>
          <td class="admin-mono" data-label="Địa chỉ IP"><?= e($row['ip_address'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php require BASE_PATH . '/app/Views/partials/admin/pager.php'; ?>
  <?php endif; ?>
</section>
