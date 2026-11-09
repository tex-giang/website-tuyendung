<?php
/** Bảng điều khiển: số liệu thật từ DB. */
$statusLabels = [
    'new' => 'Mới nhận', 'contacted' => 'Đã liên hệ', 'interview' => 'Hẹn phỏng vấn',
    'hired' => 'Trúng tuyển', 'rejected' => 'Không phù hợp',
];
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Bảng điều khiển</h1>
  </div>
  <a href="<?= url('admin/jobs/create') ?>" class="btn btn-primary">+ Thêm tin tuyển dụng</a>
</div>

<div class="admin-kpis">
  <div class="admin-kpi">
    <span class="admin-kpi__label">Tin đang đăng</span>
    <strong class="admin-kpi__value"><?= (int) $publishedNow ?></strong>
    <span class="admin-kpi__hint">/ <?= (int) $jobTotal ?> tin tổng cộng</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Hồ sơ ứng tuyển</span>
    <strong class="admin-kpi__value"><?= (int) $appTotal ?></strong>
    <span class="admin-kpi__hint"><?= (int) $newApps ?> hồ sơ mới chờ xử lý</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Đã phỏng vấn</span>
    <strong class="admin-kpi__value"><?= (int) ($appCounts['interview'] ?? 0) ?></strong>
    <span class="admin-kpi__hint"><?= (int) ($appCounts['hired'] ?? 0) ?> trúng tuyển</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Tin nháp / tạm dừng</span>
    <strong class="admin-kpi__value"><?= (int) $jobCounts['draft'] + (int) $jobCounts['paused'] ?></strong>
    <span class="admin-kpi__hint"><?= (int) $jobCounts['closed'] ?> tin đã đóng</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Bài viết đã đăng</span>
    <strong class="admin-kpi__value"><?= (int) $postTotal ?></strong>
    <span class="admin-kpi__hint">Tin tức hiển thị ngoài website</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Tin nhắn liên hệ mới</span>
    <strong class="admin-kpi__value"><?= (int) $contactNew ?></strong>
    <span class="admin-kpi__hint">Chưa đọc trong hộp thư</span>
  </div>
</div>

<div class="admin-grid-2">
  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Hồ sơ mới nhất</h2>
      <span class="admin-muted">6 hồ sơ gần đây</span>
    </div>
    <?php if ($latestApps === []): ?>
      <p class="admin-empty">Chưa có hồ sơ ứng tuyển nào. Hồ sơ gửi từ website sẽ xuất hiện tại đây.</p>
    <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Ứng viên</th><th>Vị trí</th><th>Trạng thái</th><th>Nhận lúc</th></tr></thead>
        <tbody>
          <?php foreach ($latestApps as $app): ?>
          <tr>
            <td>
              <a href="<?= url('admin/applications/' . $app['id']) ?>" class="admin-link"><strong><?= e($app['full_name']) ?></strong></a>
              <span class="admin-muted"><?= e($app['phone']) ?></span>
            </td>
            <td><?= e($app['job_title'] ?? 'CV tự do') ?></td>
            <td><span class="admin-badge admin-badge--<?= e($app['status']) ?>"><?= e($statusLabels[$app['status']] ?? $app['status']) ?></span></td>
            <td class="admin-muted"><?= e(vn_date($app['created_at'], 'd/m/Y H:i')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="admin-card">
    <div class="admin-card__head">
      <h2 class="admin-h2">Tin theo trạng thái</h2>
    </div>
    <ul class="admin-list">
      <?php foreach (['published' => 'Đang đăng', 'paused' => 'Tạm dừng', 'draft' => 'Bản nháp', 'closed' => 'Đã đóng', 'expired' => 'Hết hạn'] as $key => $label): ?>
      <li>
        <span><?= e($label) ?></span>
        <strong><?= (int) $jobCounts[$key] ?></strong>
      </li>
      <?php endforeach; ?>
    </ul>
    <a href="<?= url('admin/jobs') ?>" class="btn btn-outline btn-block mt-4">Quản lý tin tuyển dụng</a>
  </section>
</div>
