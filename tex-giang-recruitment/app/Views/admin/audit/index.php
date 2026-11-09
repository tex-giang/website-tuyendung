<?php
$page['ajax_fragment'] = true;
/** Nhật ký thao tác quản trị. */
$actionLabels = [
    'auth.login' => 'Đăng nhập', 'auth.logout' => 'Đăng xuất',
    'job.create' => 'Tạo tin', 'job.update' => 'Sửa tin', 'job.status' => 'Đổi trạng thái tin',
    'job.duplicate' => 'Nhân bản tin', 'job.delete' => 'Xoá tin',
    'application.status' => 'Đổi trạng thái hồ sơ', 'application.cv_download' => 'Tải CV',
    'post.create' => 'Tạo bài viết', 'post.update' => 'Sửa bài viết', 'post.status' => 'Đổi trạng thái bài',
    'post.delete' => 'Xoá bài viết',
    'banner.create' => 'Tạo banner', 'banner.update' => 'Sửa banner', 'banner.toggle' => 'Bật/tắt banner',
    'banner.delete' => 'Xoá banner',
    'settings.update' => 'Lưu cài đặt', 'content.update' => 'Lưu nội dung',
    'contact.status' => 'Xử lý tin nhắn', 'contact.delete' => 'Xoá tin nhắn',
    'user.create' => 'Tạo tài khoản', 'user.update' => 'Sửa tài khoản',
    'user.password_reset' => 'Đặt lại mật khẩu', 'user.password_change' => 'Đổi mật khẩu',
];
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1" id="admin-list-title">Nhật ký thao tác</h1>
  </div>
</div>

<form class="admin-filters" method="GET" role="search" aria-label="Bộ lọc danh sách" action="<?= url('admin/audit') ?>">
  <label class="field">
    <span class="field__label">Từ khóa</span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo hành động, người dùng, IP…">
  </label>
  <label class="field">
    <span class="field__label">Người dùng</span>
    <select name="user_id" class="field__input">
      <option value="">Tất cả người dùng</option>
      <?php foreach ($users as $user): ?>
      <option value="<?= (int) $user['id'] ?>"<?= (string) $filters['user_id'] === (string) $user['id'] ? ' selected' : '' ?>><?= e($user['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="admin-filter-actions">
    <button type="submit" class="btn btn-outline"><i class="bi bi-funnel me-1" aria-hidden="true"></i> Áp dụng</button>
    <a href="<?= url('admin/audit') ?>" class="btn btn-ghost"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Xoá lọc</a>
  </div>
</form>

<div class="admin-card">
  <?php if ($result['items'] === []): ?>
  <p class="admin-empty">Chưa có bản ghi nào.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle" aria-labelledby="admin-list-title">
      <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Hành động</th><th>Đối tượng</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($result['items'] as $row): ?>
        <tr>
          <td class="admin-muted"><?= e(vn_date($row['created_at'], 'd/m/Y H:i:s')) ?></td>
          <td><?= e($row['user_name'] ?? '—') ?></td>
          <td><strong><?= e($actionLabels[$row['action']] ?? $row['action']) ?></strong></td>
          <td class="admin-muted admin-mono">
            <?= $row['entity_type'] ? e((string) $row['entity_type']) : '—' ?><?= $row['entity_id'] ? ' #' . (int) $row['entity_id'] : '' ?>
            <?php if (!empty($row['meta'])): ?>
            <span class="admin-muted"><?= e(excerpt((string) $row['meta'], 80)) ?></span>
            <?php endif; ?>
          </td>
          <td class="admin-muted admin-mono"><?= e($row['ip_address'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php $listUrl = url('admin/audit'); require BASE_PATH . '/app/Views/partials/admin/pager.php'; ?>
  <?php endif; ?>
</div>
