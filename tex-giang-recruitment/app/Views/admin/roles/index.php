<?php
$page['ajax_fragment'] = true;
$shortLabels = \App\Core\Permissions::config()['role_labels'] ?? [];
$protectedRoles = array_keys($shortLabels);
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="roles-title">Phân quyền</h1>
    <p class="admin-sub">Tạo vai trò, gán quyền xem / thêm / sửa / xoá cho từng menu và giới hạn nhà máy được xem. Quản trị hệ thống luôn có toàn quyền.</p>
  </div>
  <a href="<?= url('admin/roles/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tạo vai trò</a>
</div>

<section class="admin-list-panel">
  <div class="admin-table-wrap">
    <table class="admin-table admin-role-table" aria-labelledby="roles-title">
      <thead>
        <tr>
          <th scope="col">Vai trò</th>
          <th scope="col">Tài khoản</th>
          <th scope="col">Nhà máy</th>
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
            $label = $shortLabels[(string) $role['name']] ?? (trim((string) ($role['description'] ?? '')) !== '' ? (string) $role['description'] : (string) $role['name']);
            $editUrl = url('admin/roles/' . $id . '/edit');
            $usersCount = (int) $role['users_count'];
            $allBranches = (int) ($role['all_branches'] ?? 1) === 1;
            $branchNames = trim((string) ($role['branch_names'] ?? ''));
            $canDelete = $usersCount === 0 && !in_array((string) $role['name'], $protectedRoles, true);
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
          <td class="admin-mono" data-label="Tài khoản"><?= $usersCount ?></td>
          <td data-label="Nhà máy">
            <?php if ($isSuper || $allBranches): ?>
            <span class="admin-badge admin-badge--soft">Tất cả</span>
            <?php elseif ($branchNames !== ''): ?>
            <span class="admin-muted"><?= e($branchNames) ?></span>
            <?php else: ?>
            <span class="admin-badge admin-badge--draft">Chưa gán</span>
            <?php endif; ?>
          </td>
          <?php if ($isSuper): ?>
          <td class="admin-role-table__num admin-role-table__super" colspan="4" data-label="Quyền">Toàn quyền</td>
          <?php else: ?>
          <td class="admin-role-table__num" data-label="Xem"><?= e($stat((int) $role['view_count'])) ?></td>
          <td class="admin-role-table__num" data-label="Thêm"><?= e($stat((int) $role['create_count'])) ?></td>
          <td class="admin-role-table__num" data-label="Sửa"><?= e($stat((int) $role['update_count'])) ?></td>
          <td class="admin-role-table__num" data-label="Xoá"><?= e($stat((int) $role['delete_count'])) ?></td>
          <?php endif; ?>
          <td class="is-actions">
            <div class="admin-actions admin-editor-row-actions">
              <?php if ($isSuper): ?>
              <span class="admin-muted">Không cần chỉnh</span>
              <?php else: ?>
              <a href="<?= $editUrl ?>" class="btn btn-ghost btn-icon" title="Chỉnh vai trò" aria-label="<?= e('Chỉnh vai trò ' . $label) ?>"><?= admin_icon_svg('pencil') ?></a>
              <?php if ($canDelete): ?>
              <form method="POST" action="<?= url('admin/roles/' . $id . '/delete') ?>" data-ajax
                    data-confirm="Xoá vai trò &quot;<?= e($label) ?>&quot;?">
                <?= csrf_field() ?>
                <button class="btn btn-ghost btn-icon admin-editor-delete" type="submit" title="Xoá vai trò" aria-label="<?= e('Xoá vai trò ' . $label) ?>"><?= admin_icon_svg('trash') ?></button>
              </form>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
