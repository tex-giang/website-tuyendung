<?php
$page['ajax_fragment'] = true;
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="roles-title">Phân quyền</h1>
    <p class="admin-sub">Gán quyền xem / thêm / sửa / xoá cho từng menu quản trị theo vai trò. Quản trị hệ thống luôn có toàn quyền.</p>
  </div>
</div>

<section class="admin-list-panel">
  <div class="admin-table-wrap">
    <table class="admin-table admin-role-table" aria-labelledby="roles-title">
      <thead>
        <tr>
          <th scope="col">Vai trò</th>
          <th scope="col">Tài khoản</th>
          <th scope="col" class="admin-role-table__num">Xem</th>
          <th scope="col" class="admin-role-table__num">Thêm</th>
          <th scope="col" class="admin-role-table__num">Sửa</th>
          <th scope="col" class="admin-role-table__num">Xoá</th>
          <th scope="col" class="is-actions">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($roles as $role):
            $id = (int) $role['id'];
            $isSuper = $role['name'] === 'super_admin';
            $label = trim((string) ($role['description'] ?? '')) !== '' ? (string) $role['description'] : (string) $role['name'];
            $editUrl = url('admin/roles/' . $id . '/edit');
            $stat = static fn(int $n): string => $n >= $totalModules ? 'Toàn bộ' : ($n . '/' . $totalModules);
        ?>
        <tr>
          <td class="admin-directory-name" data-label="Vai trò">
            <?php if ($isSuper): ?>
            <span class="admin-link"><?= e($label) ?></span>
            <?php else: ?>
            <a href="<?= $editUrl ?>" class="admin-link"><?= e($label) ?></a>
            <?php endif; ?>
            <span class="admin-muted"><?= e($role['name']) ?></span>
          </td>
          <td class="admin-mono" data-label="Tài khoản"><?= (int) $role['users_count'] ?></td>
          <?php if ($isSuper): ?>
          <td class="admin-role-table__num admin-role-table__super" colspan="4" data-label="Quyền">Toàn quyền</td>
          <?php else: ?>
          <td class="admin-role-table__num" data-label="Xem"><?= e($stat((int) $role['view_count'])) ?></td>
          <td class="admin-role-table__num" data-label="Thêm"><?= e($stat((int) $role['create_count'])) ?></td>
          <td class="admin-role-table__num" data-label="Sửa"><?= e($stat((int) $role['update_count'])) ?></td>
          <td class="admin-role-table__num" data-label="Xoá"><?= e($stat((int) $role['delete_count'])) ?></td>
          <?php endif; ?>
          <td class="is-actions">
            <?php if ($isSuper): ?>
            <span class="admin-muted">Không cần chỉnh</span>
            <?php else: ?>
            <a href="<?= $editUrl ?>" class="btn btn-ghost btn-icon" title="Chỉnh phân quyền" aria-label="<?= e('Chỉnh phân quyền ' . $label) ?>"><?= admin_icon_svg('pencil') ?></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
