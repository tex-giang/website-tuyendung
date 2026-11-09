<?php
$page['ajax_fragment'] = true;
/** Bảng điều khiển: số liệu thật từ DB. */
$statusLabels = [
    'new' => 'Mới nhận', 'contacted' => 'Đã liên hệ', 'interview' => 'Hẹn phỏng vấn',
    'hired' => 'Trúng tuyển', 'rejected' => 'Không phù hợp',
];
$canContact = \App\Core\Permissions::currentCan('contact');
$canPosts = \App\Core\Permissions::currentCan('posts');
?>
<div class="admin-page-head">
  <div>
    <h1 class="admin-h1">Bảng điều khiển</h1>
    <p class="admin-sub">Theo dõi hồ sơ ứng viên và tình trạng các tin tuyển dụng.</p>
  </div>
  <a href="<?= url('admin/jobs/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm tin tuyển dụng</a>
</div>

<section class="admin-inbox" aria-labelledby="new-applications-title">
  <div>
    <h2 class="admin-h2" id="new-applications-title">Hồ sơ mới chờ xử lý</h2>
    <p class="admin-inbox__summary"><strong><?= (int) $newApps ?></strong> hồ sơ</p>
  </div>
  <a href="<?= url('admin/applications') ?>?status=new" class="btn btn-outline">Mở danh sách hồ sơ mới <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
</section>

<div class="admin-kpis admin-kpis--overview">
  <div class="admin-kpi">
    <span class="admin-kpi__label">Tin đang đăng</span>
    <strong class="admin-kpi__value"><?= (int) $publishedNow ?></strong>
    <span class="admin-kpi__hint">Trong <?= (int) $jobTotal ?> tin tuyển dụng</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Hồ sơ ứng tuyển</span>
    <strong class="admin-kpi__value"><?= (int) $appTotal ?></strong>
    <span class="admin-kpi__hint">Tất cả trạng thái</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Hẹn phỏng vấn</span>
    <strong class="admin-kpi__value"><?= (int) ($appCounts['interview'] ?? 0) ?></strong>
    <span class="admin-kpi__hint"><?= (int) ($appCounts['hired'] ?? 0) ?> trúng tuyển</span>
  </div>
  <div class="admin-kpi">
    <span class="admin-kpi__label">Tin nháp / tạm dừng</span>
    <strong class="admin-kpi__value"><?= (int) $jobCounts['draft'] + (int) $jobCounts['paused'] ?></strong>
    <span class="admin-kpi__hint"><?= (int) $jobCounts['closed'] ?> tin đã đóng</span>
  </div>
</div>

<div class="admin-grid-2 admin-dashboard-grid">
  <section class="admin-card" aria-labelledby="recent-applications-title">
    <div class="admin-card__head">
      <h2 class="admin-h2" id="recent-applications-title">Hồ sơ gần đây</h2>
      <a href="<?= url('admin/applications') ?>" class="admin-link">Xem tất cả <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
    <?php if ($latestApps === []): ?>
      <p class="admin-empty">Hồ sơ ứng viên gửi từ website sẽ xuất hiện tại đây.</p>
    <?php else: ?>
    <ul class="admin-recent">
      <?php foreach ($latestApps as $app): ?>
      <li class="admin-recent__row">
        <div class="admin-recent__person">
          <a href="<?= url('admin/applications/' . $app['id']) ?>" class="admin-link"><strong><?= e($app['full_name']) ?></strong></a>
          <span class="admin-muted"><?= e($app['phone']) ?></span>
        </div>
        <span class="admin-recent__job"><?= e($app['job_title'] ?? 'CV tự do') ?></span>
        <div class="admin-recent__status">
          <span class="admin-badge admin-badge--<?= e($app['status']) ?>"><?= e($statusLabels[$app['status']] ?? $app['status']) ?></span>
          <span class="admin-muted"><?= e(vn_date($app['created_at'], 'd/m/Y H:i')) ?></span>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <section class="admin-card" aria-labelledby="job-status-title">
    <div class="admin-card__head">
      <h2 class="admin-h2" id="job-status-title">Tin theo trạng thái</h2>
    </div>
    <ul class="admin-list">
      <?php foreach (['published' => 'Đang đăng', 'paused' => 'Tạm dừng', 'draft' => 'Bản nháp', 'closed' => 'Đã đóng', 'expired' => 'Hết hạn'] as $key => $label): ?>
      <li>
        <a href="<?= url('admin/jobs') ?>?status=<?= e($key) ?>" class="admin-link"><?= e($label) ?></a>
        <strong><?= (int) $jobCounts[$key] ?></strong>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<?php if ($canPosts || $canContact): ?>
<div class="admin-dashboard-links">
  <?php if ($canPosts): ?>
  <a href="<?= url('admin/posts') ?>?status=published" class="admin-link"><i class="bi bi-newspaper" aria-hidden="true"></i> <?= (int) $postTotal ?> bài viết đã đăng</a>
  <?php endif; ?>
  <?php if ($canContact): ?>
  <a href="<?= url('admin/contact') ?>?status=new" class="admin-link"><i class="bi bi-envelope" aria-hidden="true"></i> <?= (int) $contactNew ?> tin nhắn mới</a>
  <?php endif; ?>
</div>
<?php endif; ?>
