<?php
/** Chi tiết hồ sơ ứng viên: thông tin, CV, đổi trạng thái, lịch sử xử lý. */
$statusBadge = [
    'new' => 'admin-badge--new', 'contacted' => 'admin-badge--contacted',
    'interview' => 'admin-badge--interview', 'hired' => 'admin-badge--hired',
    'rejected' => 'admin-badge--rejected',
];
$app = $application;
$appointmentValue = $app['appointment_at'] ? date('Y-m-d\TH:i', strtotime((string) $app['appointment_at'])) : '';
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= url('admin/applications') ?>">Hồ sơ ứng tuyển</a> / HS-<?= str_pad((string) (int) $app['id'], 5, '0', STR_PAD_LEFT) ?></p>
    <h1 class="admin-h1"><?= e($app['full_name']) ?></h1>
  </div>
  <div class="admin-head-actions">
    <?php if (!empty($app['cv_stored_name'])): ?>
    <a href="<?= url('admin/applications/' . $app['id'] . '/cv') ?>" class="btn btn-primary"><i class="bi bi-download me-1" aria-hidden="true"></i> Tải CV (<?= e((string) ($app['cv_original_name'] ?: 'tệp đính kèm')) ?>)</a>
    <?php endif; ?>
    <a href="tel:<?= e($app['phone']) ?>" class="btn btn-outline"><i class="bi bi-telephone me-1" aria-hidden="true"></i> Gọi <?= e($app['phone']) ?></a>
    <a href="<?= url('admin/applications') ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Danh sách</a>
  </div>
</div>

<div class="admin-grid-2">
  <div>
    <section class="admin-card">
      <h2 class="admin-h2">Thông tin ứng viên</h2>
      <table class="admin-table admin-table--kv">
        <tbody>
          <tr><th>Họ và tên</th><td><?= e($app['full_name']) ?></td></tr>
          <tr><th>Điện thoại</th><td><a href="tel:<?= e($app['phone']) ?>"><?= e($app['phone']) ?></a></td></tr>
          <tr><th>Email</th><td><?= $app['email'] ? '<a href="mailto:' . e($app['email']) . '">' . e($app['email']) . '</a>' : '—' ?></td></tr>
          <tr><th>Năm sinh</th><td><?= $app['birth_year'] ? (int) $app['birth_year'] : '—' ?></td></tr>
          <tr><th>Giới tính</th><td><?= $app['gender'] ? e(['nu' => 'Nữ', 'nam' => 'Nam', 'khac' => 'Khác'][$app['gender']] ?? $app['gender']) : '—' ?></td></tr>
          <tr><th>Địa chỉ</th><td><?= e($app['address'] ?? '—') ?></td></tr>
          <tr><th>Vị trí ứng tuyển</th><td>
            <?= e($app['job_title'] ?? 'CV tự do — chưa chọn vị trí') ?>
            <?php if (!empty($app['job_slug'])): ?>
            · <a href="<?= url('jobs/' . $app['job_slug']) ?>" target="_blank" rel="noopener">xem tin <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i></a>
            <?php endif; ?>
          </td></tr>
          <tr><th>CV</th><td>
            <?php if (!empty($app['cv_stored_name'])): ?>
              <a href="<?= url('admin/applications/' . $app['id'] . '/cv') ?>"><?= e($app['cv_original_name'] ?: 'Tải CV') ?></a>
              <span class="admin-muted"><?= e($app['cv_mime'] ?? '') ?> · <?= $app['cv_size'] ? number_format((int) $app['cv_size'] / 1024, 0) . ' KB' : '' ?></span>
            <?php else: ?>
              <span class="admin-muted">Ứng viên không đính kèm CV.</span>
            <?php endif; ?>
          </td></tr>
        </tbody>
      </table>

      <?php if (!empty($app['message'])): ?>
      <h2 class="admin-h2 mt-4">Nội dung ứng viên gửi</h2>
      <div class="admin-message"><?= nl2br(e((string) $app['message'])) ?></div>
      <?php endif; ?>
    </section>

    <section class="admin-card">
      <h2 class="admin-h2">Cập nhật trạng thái xử lý</h2>
      <form method="POST" action="<?= url('admin/applications/' . $app['id'] . '/status') ?>">
        <?= csrf_field() ?>
        <div class="field-grid">
          <label class="field">
            <span class="field__label">Trạng thái</span>
            <select class="field__input" name="status">
              <?php foreach ($statuses as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $app['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="field">
            <span class="field__label">Lịch phỏng vấn</span>
            <input class="field__input" type="datetime-local" name="appointment_at" value="<?= e($appointmentValue) ?>">
          </label>
          <label class="field field--span2">
            <span class="field__label">Ghi chú nội bộ</span>
            <textarea class="field__input" name="admin_note" rows="3" maxlength="2000"
                      placeholder="Ví dụ: Đã gọi xác nhận, hẹn phỏng vấn 14:00 ngày 12/11."><?= e($app['admin_note'] ?? '') ?></textarea>
          </label>
        </div>
        <button type="submit" class="btn btn-primary mt-4"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> Lưu cập nhật</button>
      </form>
    </section>
  </div>

  <section class="admin-card">
    <h2 class="admin-h2">Lịch sử xử lý</h2>
    <?php if ($history === []): ?>
    <p class="admin-empty">Chưa có lịch sử.</p>
    <?php else: ?>
    <ol class="admin-timeline">
      <?php foreach ($history as $item): ?>
      <li class="admin-timeline__item">
        <span class="admin-timeline__dot"></span>
        <div>
          <strong class="admin-timeline__status"><?= e($statuses[$item['status']] ?? $item['status']) ?></strong>
          <span class="admin-muted"><?= e(vn_date($item['created_at'], 'd/m/Y H:i')) ?><?= $item['changed_by_name'] ? ' · ' . e($item['changed_by_name']) : '' ?></span>
          <?php if (!empty($item['note'])): ?><p class="admin-timeline__note"><?= nl2br(e((string) $item['note'])) ?></p><?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>

    <?php if (!empty($app['appointment_at'])): ?>
    <div class="admin-alert admin-alert--success mt-4">
      Lịch phỏng vấn đã hẹn: <strong><?= e(vn_date($app['appointment_at'], 'H:i d/m/Y')) ?></strong>
    </div>
    <?php endif; ?>
  </section>
</div>
