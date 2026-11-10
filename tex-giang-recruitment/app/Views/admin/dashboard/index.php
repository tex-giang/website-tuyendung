<?php
$page['ajax_fragment'] = true;
$statusLabels = [
    'new' => 'Mới', 'contacted' => 'Đã liên hệ', 'interview' => 'Hẹn phỏng vấn',
    'hired' => 'Trúng tuyển', 'rejected' => 'Không phù hợp',
];
$metrics = [
    ['Tin đang tuyển', $publishedNow, 'Đang hiển thị công khai', 'bi-briefcase'],
    ['Tổng hồ sơ', $appTotal, 'Tất cả hồ sơ ứng tuyển', 'bi-file-earmark-text'],
    ['Hẹn phỏng vấn', $appCounts['interview'] ?? 0, 'Hồ sơ đã có lịch', 'bi-calendar-event'],
    ['Đã tuyển', $appCounts['hired'] ?? 0, 'Ứng viên trúng tuyển', 'bi-person-check'],
    ['Nháp hoặc tạm dừng', (int) $jobCounts['draft'] + (int) $jobCounts['paused'], 'Tin chưa hiển thị', 'bi-pause-circle'],
];
$canContact = \App\Core\Permissions::currentCan('contact');
$canPosts = \App\Core\Permissions::currentCan('posts');
?>
<div class="admin-dashboard">
  <div class="admin-page-head">
    <div>
      <h1 class="admin-h1">Bảng điều khiển</h1>
      <p class="admin-sub">Tổng quan hoạt động tuyển dụng.</p>
    </div>
    <a href="<?= url('admin/jobs/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm tin tuyển dụng</a>
  </div>

  <section class="admin-dashboard__queue" aria-labelledby="new-applications-title">
    <div class="admin-dashboard__queue-info">
      <i class="bi bi-inbox admin-dashboard__queue-icon" aria-hidden="true"></i>
      <div>
        <h2 id="new-applications-title">Hồ sơ mới đang chờ xử lý</h2>
        <strong><?= (int) $newApps ?></strong>
        <p>Liên hệ sớm với ứng viên phù hợp.</p>
      </div>
    </div>
    <a href="<?= url('admin/applications') ?>?status=new" class="btn btn-primary">Xử lý hồ sơ mới <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </section>

  <dl class="admin-dashboard__metrics" aria-label="Chỉ số tuyển dụng">
    <?php foreach ($metrics as [$label, $value, $hint, $icon]): ?>
    <div class="admin-dashboard__metric">
      <dt><?= e($label) ?></dt>
      <dd><strong><?= (int) $value ?></strong><span><?= e($hint) ?></span></dd>
      <i class="bi <?= e($icon) ?> admin-dashboard__metric-icon" aria-hidden="true"></i>
    </div>
    <?php endforeach; ?>
  </dl>

  <div class="admin-dashboard__grid">
    <section class="admin-dashboard__panel" aria-labelledby="recent-applications-title">
      <div class="admin-dashboard__panel-head">
        <h2 class="admin-h2" id="recent-applications-title">Hồ sơ mới nhất</h2>
        <a href="<?= url('admin/applications') ?>" class="admin-link">Xem tất cả <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
      <?php if ($latestApps === []): ?>
      <p class="admin-dashboard__empty">Hồ sơ ứng viên gửi từ website sẽ xuất hiện tại đây.</p>
      <?php else: ?>
      <table class="admin-dashboard__recent-table" role="table" aria-labelledby="recent-applications-title">
        <thead role="rowgroup">
          <tr role="row">
            <th scope="col" role="columnheader">Ứng viên</th>
            <th scope="col" role="columnheader">Vị trí</th>
            <th scope="col" role="columnheader">Trạng thái</th>
            <th scope="col" role="columnheader">Nhận lúc</th>
            <th scope="col" role="columnheader">Chi tiết</th>
          </tr>
        </thead>
        <tbody role="rowgroup">
          <?php foreach ($latestApps as $app): ?>
          <tr role="row">
            <td role="cell" class="admin-dashboard__person"><?= e($app['full_name']) ?></td>
            <?php $position = $app['job_title'] ?? 'CV tự do'; ?>
            <td role="cell" class="admin-dashboard__position" title="<?= e($position) ?>"><?= e($position) ?></td>
            <td role="cell" class="admin-dashboard__status"><span class="admin-badge admin-badge--<?= e($app['status']) ?>"><?= e($statusLabels[$app['status']] ?? $app['status']) ?></span></td>
            <td role="cell" class="admin-dashboard__date"><?= e(vn_date($app['created_at'], 'H:i d/m/Y')) ?></td>
            <td role="cell" class="admin-dashboard__detail"><a href="<?= url('admin/applications/' . $app['id']) ?>" aria-label="Xem hồ sơ <?= e($app['full_name']) ?>">Xem</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>

    <div class="admin-dashboard__rail">
      <section class="admin-dashboard__panel" aria-labelledby="job-status-title">
        <div class="admin-dashboard__panel-head">
          <h2 class="admin-h2" id="job-status-title">Tin theo trạng thái</h2>
          <span class="admin-muted">Tổng&nbsp; <?= (int) $jobTotal ?></span>
        </div>
        <ul class="admin-dashboard__status-list">
          <?php foreach (['published' => 'Đang tuyển', 'draft' => 'Bản nháp', 'paused' => 'Tạm dừng', 'closed' => 'Đã đóng', 'expired' => 'Hết hạn'] as $key => $label): ?>
          <li>
            <a href="<?= url('admin/jobs') ?>?status=<?= e($key) ?>" class="admin-badge admin-badge--<?= e($key) ?>"><?= e($label) ?></a>
            <strong><?= (int) $jobCounts[$key] ?></strong>
          </li>
          <?php endforeach; ?>
        </ul>
      </section>

      <?php if ($canPosts || $canContact): ?>
      <section class="admin-dashboard__panel" aria-labelledby="dashboard-links-title">
        <div class="admin-dashboard__panel-head"><h2 class="admin-h2" id="dashboard-links-title">Lối tắt</h2></div>
        <ul class="admin-dashboard__links">
          <?php if ($canPosts): ?>
          <li><a href="<?= url('admin/posts') ?>"><i class="bi bi-newspaper admin-dashboard__metric-icon" aria-hidden="true"></i><span>Quản lý bài viết</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
          <?php endif; ?>
          <?php if ($canContact): ?>
          <li><a href="<?= url('admin/contact') ?>?status=new"><i class="bi bi-envelope admin-dashboard__metric-icon" aria-hidden="true"></i><span>Hộp thư liên hệ</span><?php if ($contactNew > 0): ?><small class="admin-badge admin-badge--new"><?= (int) $contactNew ?> chưa xem</small><?php endif; ?><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
          <?php endif; ?>
        </ul>
      </section>
      <?php endif; ?>
    </div>
  </div>
</div>
