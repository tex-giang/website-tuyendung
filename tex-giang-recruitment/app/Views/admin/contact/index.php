<?php
$page['ajax_fragment'] = true;
$statusBadge = ['new' => 'admin-badge--new', 'read' => 'admin-badge--draft', 'replied' => 'admin-badge--published'];
$listUrl = url('admin/contact');
?>
<div class="admin-page-head admin-list-head">
  <div>
    <h1 class="admin-h1" id="admin-list-title">Hộp thư liên hệ</h1>
    <p class="admin-sub">Tin nhắn khách và ứng viên gửi từ website.</p>
  </div>
</div>
<nav class="admin-contact-filters" aria-label="Lọc theo trạng thái">
  <?php foreach (['' => 'Tất cả'] + $statuses as $key => $label):
      $count = $key === '' ? array_sum($counts) : ($counts[$key] ?? 0);
      $filterUrl = $listUrl . ($key === '' ? '' : '?' . http_build_query(['status' => $key]));
  ?>
  <a class="btn btn-outline" href="<?= e($filterUrl) ?>"<?= $filters['status'] === $key ? ' aria-current="page"' : '' ?>>
    <?= e($label) ?> <span class="admin-contact-filter-count"><?= (int) $count ?></span>
  </a>
  <?php endforeach; ?>
</nav>

<section class="admin-list-panel">
  <?php if ($result['items'] === []): ?>
  <p class="admin-list-empty"><?= $filters['status'] === '' ? 'Chưa có tin nhắn nào. Tin gửi từ trang Liên hệ sẽ xuất hiện tại đây.' : 'Chưa có tin nhắn ở trạng thái này.' ?></p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-directory-table admin-directory-table--contact" aria-labelledby="admin-list-title">
      <thead><tr><th scope="col">Người gửi</th><th scope="col">Chủ đề</th><th scope="col">Trạng thái</th><th scope="col">Thời gian nhận</th><th scope="col" class="is-actions"><span class="visually-hidden">Thao tác</span></th></tr></thead>
      <tbody>
        <?php foreach ($result['items'] as $item):
            $detail = url('admin/contact/' . (int) $item['id']);
            $subject = trim((string) ($item['subject'] ?? ''));
        ?>
        <tr>
          <td class="admin-directory-name">
            <a class="admin-link" href="<?= $detail ?>"><?= e($item['full_name']) ?></a>
            <span class="admin-muted"><?= e(implode(' · ', array_filter([$item['phone'], $item['email']]))) ?></span>
          </td>
          <td data-label="Chủ đề">
            <a class="admin-link admin-contact-subject" href="<?= $detail ?>"><?= e($subject !== '' ? $subject : 'Không có chủ đề') ?></a>
            <span class="admin-muted"><?= e(excerpt((string) $item['message'], 110)) ?></span>
          </td>
          <td data-label="Trạng thái"><span class="admin-badge <?= $statusBadge[$item['status']] ?? '' ?>"><?= e($statuses[$item['status']] ?? $item['status']) ?></span></td>
          <td class="admin-mono admin-contact-time" data-label="Thời gian nhận"><?= e(vn_date($item['created_at'], 'H:i d/m/Y')) ?></td>
          <td class="is-actions">
            <div class="admin-actions admin-editor-row-actions">
              <a href="<?= $detail ?>" class="btn btn-ghost btn-icon" title="Xem tin nhắn" aria-label="<?= e('Xem tin nhắn của ' . $item['full_name']) ?>"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
              <?php if (can('contact', 'delete')): ?><form data-ajax-refresh data-ajax method="POST" action="<?= $detail ?>/delete" data-confirm="<?= e('Xóa tin nhắn của “' . $item['full_name'] . '”?') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="return_status" value="<?= e($filters['status']) ?>">
                <button class="btn btn-ghost btn-icon admin-editor-delete" type="submit" title="Xóa" aria-label="<?= e('Xóa tin nhắn của ' . $item['full_name']) ?>"><?= admin_icon_svg('trash') ?></button>
              </form><?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $listPager = true; require BASE_PATH . '/app/Views/partials/admin/pager.php'; ?>
  <?php endif; ?>
</section>
