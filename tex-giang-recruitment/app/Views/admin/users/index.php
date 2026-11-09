<?php
/** Danh sách người dùng quản trị (super_admin). */
$me = \App\Core\Session::get('auth_id');
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Người dùng quản trị</h1>
  </div>
  <a href="<?= url('admin/users/create') ?>" class="btn btn-primary">+ Thêm tài khoản</a>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table table table-hover align-middle js-datatable">
      <thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Đăng nhập gần nhất</th><th class="is-actions">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
          <td>
            <strong><?= e($user['full_name']) ?></strong>
            <span class="admin-muted admin-mono"><?= e($user['username']) ?><?= (int) $user['id'] === (int) $me ? ' · bạn' : '' ?></span>
            <span class="admin-muted"><?= e($user['email'] ?? '') ?></span>
          </td>
          <td><?= e($roles[$user['role_name']] ?? $user['role_name']) ?></td>
          <td>
            <span class="admin-badge <?= (int) $user['is_active'] === 1 ? 'admin-badge--published' : 'admin-badge--draft' ?>">
              <?= (int) $user['is_active'] === 1 ? 'Đang hoạt động' : 'Đã khoá' ?>
            </span>
          </td>
          <td class="admin-muted"><?= e(vn_date($user['last_login_at'], 'd/m/Y H:i') ?: '—') ?></td>
          <td class="is-actions">
            <div class="admin-actions">
              <a href="<?= url('admin/users/' . $user['id'] . '/edit') ?>" class="btn btn-outline btn-icon" title="Sửa" aria-label="Sửa"><?= admin_icon_svg('pencil') ?></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-card">
  <h2 class="admin-h2">Hồ sơ của bạn</h2>
  <p class="admin-muted">Đổi mật khẩu và xem thông tin tài khoản đang đăng nhập.</p>
  <a href="<?= url('admin/profile') ?>" class="btn btn-outline mt-4">Mở hồ sơ cá nhân</a>
</div>
