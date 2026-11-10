<?php
$page['ajax_fragment'] = true;
$me = (int) \App\Core\Session::get('auth_id');
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="users-title">Người dùng quản trị</h1>
    <p class="admin-sub">Quản lý tài khoản và quyền truy cập hệ thống tuyển dụng.</p>
  </div>
  <a href="<?= url('admin/users/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm người dùng</a>
</div>

<section class="admin-list-panel">
  <div class="admin-table-wrap">
    <table class="admin-table admin-directory-table admin-directory-table--users" aria-labelledby="users-title">
      <thead><tr><th scope="col">Tên đăng nhập</th><th scope="col">Họ và tên</th><th scope="col">Vai trò</th><th scope="col">Trạng thái</th><th scope="col">Đăng nhập gần nhất</th><th scope="col" class="is-actions">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($users as $user):
            $id = (int) $user['id'];
            $active = (int) $user['is_active'] === 1;
            $action = $active ? 'Khóa tài khoản' : 'Mở khóa tài khoản';
            $editUrl = url('admin/users/' . $id . '/edit');
        ?>
        <tr>
          <td class="admin-mono" data-label="Tên đăng nhập"><?= e($user['username']) ?><?= $id === $me ? ' · Bạn' : '' ?></td>
          <td class="admin-directory-name">
            <a href="<?= $editUrl ?>" class="admin-link"><?= e($user['full_name']) ?></a>
            <?php if (!empty($user['email'])): ?><span class="admin-muted"><?= e($user['email']) ?></span><?php endif; ?>
          </td>
          <td data-label="Vai trò"><span class="admin-badge admin-badge--soft"><?= e($roles[$user['role_name']] ?? $user['role_name']) ?></span></td>
          <td data-label="Trạng thái"><span class="admin-badge <?= $active ? 'admin-badge--published' : 'admin-badge--draft' ?>"><?= $active ? 'Đang hoạt động' : 'Đã khóa' ?></span></td>
          <td class="admin-mono admin-audit-date" data-label="Đăng nhập gần nhất"><?= e(vn_date($user['last_login_at'], 'H:i d/m/Y') ?: '—') ?></td>
          <td class="is-actions">
            <div class="admin-actions admin-editor-row-actions">
              <a href="<?= $editUrl ?>" class="btn btn-ghost btn-icon" title="Chỉnh sửa" aria-label="<?= e('Chỉnh sửa ' . $user['full_name']) ?>"><?= admin_icon_svg('pencil') ?></a>
              <?php if ($id !== $me): ?>
              <form data-ajax method="POST" action="<?= url('admin/users/' . $id . '/status') ?>"<?= $active ? ' data-confirm="' . e('Khóa tài khoản “' . $user['full_name'] . '”?') . '"' : '' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="is_active" value="<?= $active ? '0' : '1' ?>">
                <button type="submit" class="btn btn-ghost btn-icon<?= $active ? ' admin-editor-delete' : '' ?>" title="<?= e($action) ?>" aria-label="<?= e($action . ' ' . $user['full_name']) ?>"><i class="bi <?= $active ? 'bi-lock' : 'bi-unlock' ?>" aria-hidden="true"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
