<?php
/** Nhật ký thao tác quản trị. */
$start = $result['total'] === 0 ? 0 : (($result['page'] - 1) * $result['per_page']) + 1;
$end = min($result['page'] * $result['per_page'], $result['total']);
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
    <h1 class="admin-h1">Nhật ký thao tác</h1>
  </div>
</div>

<form class="admin-filters" method="GET" action="<?= url('admin/audit') ?>">
  <input type="search" name="q" value="<?= e($filters['q']) ?>" class="field__input" placeholder="Tìm theo hành động, người dùng, IP…">
  <select name="user_id" class="field__input" data-submit-onchange>
    <option value="">Tất cả người dùng</option>
    <?php foreach ($users as $user): ?>
    <option value="<?= (int) $user['id'] ?>"<?= (string) $filters['user_id'] === (string) $user['id'] ? ' selected' : '' ?>><?= e($user['full_name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline">Lọc</button>
  <a href="<?= url('admin/audit') ?>" class="btn btn-ghost">Xoá lọc</a>
</form>

<div class="admin-card">
  <?php if ($result['items'] === []): ?>
  <p class="admin-empty">Chưa có bản ghi nào.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable">
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

  <?php if ($result['pages'] > 1): ?>
  <div class="admin-pager">
    <p class="admin-muted">Hiển thị <?= $start ?>–<?= $end ?> / <?= (int) $result['total'] ?></p>
    <div class="admin-pager__links">
      <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
        <?php $qs = http_build_query(array_filter(['q' => $filters['q'], 'user_id' => $filters['user_id'], 'page' => $p])); ?>
        <?php if ($p === $result['page']): ?><span class="admin-pager__item is-current"><?= $p ?></span>
        <?php else: ?><a class="admin-pager__item" href="<?= url('admin/audit') ?>?<?= e($qs) ?>"><?= $p ?></a><?php endif; ?>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
