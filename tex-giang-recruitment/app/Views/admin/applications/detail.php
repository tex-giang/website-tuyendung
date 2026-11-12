<?php
$page['ajax_fragment'] = true;
$app = $application;
$appUrl = url('admin/applications/' . $app['id']);
$updateUrl = $appUrl . '/status';
$appointmentValue = $app['appointment_at'] ? date('d/m/Y H:i', strtotime((string) $app['appointment_at'])) : '';
$receivedAt = vn_date($app['created_at'], 'H:i d/m/Y');
$gender = ['nu' => 'Nữ', 'nam' => 'Nam', 'khac' => 'Khác'][(string) ($app['gender'] ?? '')] ?? '';
$hasCv = !empty($app['cv_stored_name']);
$currentStatus = (string) old('status', $app['status']);
?>
<div class="admin-page-head admin-application-head">
  <div>
    <a href="<?= url('admin/applications') ?>" class="admin-job-heading__back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách hồ sơ</a>
    <h1 class="admin-h1"><?= e($app['full_name']) ?></h1>
    <p class="admin-sub">Nhận lúc <?= e($receivedAt) ?></p>
  </div>
  <div class="admin-head-actions">
    <span class="admin-badge admin-badge--<?= e($app['status']) ?>"><?= e($statuses[$app['status']] ?? $app['status']) ?></span>
    <a href="tel:<?= e($app['phone']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-telephone" aria-hidden="true"></i> Gọi điện</a>
    <?php if (!empty($app['email'])): ?>
    <a href="mailto:<?= e($app['email']) ?>" class="btn btn-outline btn-sm"><i class="bi bi-envelope" aria-hidden="true"></i> Gửi email</a>
    <?php endif; ?>
  </div>
</div>

<div class="admin-application-detail">
  <div class="admin-application-detail__column">
    <section class="admin-card" aria-labelledby="candidate-info">
      <h2 class="admin-h2" id="candidate-info">Thông tin ứng viên</h2>
      <dl class="admin-application-info">
        <div><dt>Họ và tên</dt><dd><?= e($app['full_name']) ?></dd></div>
        <div><dt>Số điện thoại</dt><dd><a href="tel:<?= e($app['phone']) ?>"><?= e($app['phone']) ?></a></dd></div>
        <div><dt>Email</dt><dd><?php if (!empty($app['email'])): ?><a href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a><?php else: ?>—<?php endif; ?></dd></div>
        <div><dt>Vị trí ứng tuyển</dt><dd>
          <?php if (!empty($app['job_slug'])): ?>
          <a href="<?= url('jobs/' . $app['job_slug']) ?>" target="_blank" rel="noopener"><?= e($app['job_title'] ?? 'Xem tin tuyển dụng') ?></a>
          <?php else: ?><?= e($app['job_title'] ?? 'CV tự do') ?><?php endif; ?>
        </dd></div>
        <div><dt>Nguồn</dt><dd><?= e($app['source'] ?? 'Website') ?></dd></div>
        <div><dt>Thời gian nhận</dt><dd><?= e($receivedAt) ?></dd></div>
        <?php if (!empty($app['birth_year'])): ?><div><dt>Năm sinh</dt><dd><?= (int) $app['birth_year'] ?></dd></div><?php endif; ?>
        <?php if ($gender !== ''): ?><div><dt>Giới tính</dt><dd><?= e($gender) ?></dd></div><?php endif; ?>
        <?php if (!empty($app['address'])): ?><div><dt>Địa chỉ</dt><dd><?= e($app['address']) ?></dd></div><?php endif; ?>
      </dl>
    </section>

    <section class="admin-card" aria-labelledby="candidate-message">
      <h2 class="admin-h2" id="candidate-message">Lời nhắn và CV</h2>
      <p class="admin-application-message<?= empty($app['message']) ? ' admin-muted' : '' ?>"><?= !empty($app['message']) ? e($app['message']) : 'Ứng viên chưa gửi lời nhắn.' ?></p>
      <div class="admin-application-cv">
        <?php if ($hasCv): ?>
        <a href="<?= $appUrl ?>/cv" class="btn btn-outline btn-sm"><i class="bi bi-download" aria-hidden="true"></i> Tải CV</a>
        <span class="admin-muted"><?= e($app['cv_original_name'] ?: 'Tệp đính kèm') ?><?= $app['cv_size'] ? ' · ' . number_format((int) $app['cv_size'] / 1024, 0) . ' KB' : '' ?></span>
        <?php else: ?>
        <p class="admin-muted">Ứng viên chưa đính kèm tệp CV.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="admin-card" aria-labelledby="candidate-history">
      <h2 class="admin-h2" id="candidate-history">Lịch sử xử lý</h2>
      <?php if ($history === []): ?>
      <p class="admin-muted">Chưa có lịch sử xử lý.</p>
      <?php else: ?>
      <ol class="admin-timeline">
        <?php foreach ($history as $item): ?>
        <li class="admin-timeline__item">
          <span class="admin-timeline__dot" aria-hidden="true"></span>
          <div>
            <strong class="admin-timeline__status"><?= e($statuses[$item['status']] ?? $item['status']) ?></strong>
            <span class="admin-muted"><?= e(vn_date($item['created_at'], 'H:i d/m/Y')) ?><?= $item['changed_by_name'] ? ' · ' . e($item['changed_by_name']) : '' ?></span>
            <?php if (!empty($item['note'])): ?><p class="admin-timeline__note"><?= nl2br(e((string) $item['note'])) ?></p><?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    </section>
  </div>

  <div class="admin-application-detail__column">
    <section class="admin-card" aria-labelledby="candidate-status">
      <h2 class="admin-h2" id="candidate-status">Cập nhật trạng thái</h2>
      <form id="application-status" class="admin-application-editor" data-ajax data-admin-draft method="POST" action="<?= $updateUrl ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="status">
        <label class="field">
          <span class="field__label">Trạng thái hồ sơ</span>
          <select class="field__input" name="status">
            <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $currentStatus === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($error = errors('status')): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
        </label>
        <button type="submit" class="btn btn-primary">Lưu trạng thái</button>
      </form>
    </section>

    <section class="admin-card" aria-labelledby="candidate-appointment">
      <h2 class="admin-h2" id="candidate-appointment">Lịch phỏng vấn</h2>
      <form id="application-appointment" class="admin-application-editor" data-ajax data-admin-draft method="POST" action="<?= $updateUrl ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="appointment">
        <label class="field">
          <span class="field__label">Ngày giờ phỏng vấn <span class="admin-muted">(dd/mm/yyyy HH:MM)</span></span>
          <?php
          $appointmentInput = (string) old('appointment_at', $appointmentValue);
          if ($appointmentInput !== '') {
              $parsedAppointment = vn_parse_datetime($appointmentInput);
              if ($parsedAppointment !== null) {
                  $appointmentInput = date('d/m/Y H:i', strtotime($parsedAppointment));
              }
          }
          ?>
          <input class="field__input" type="text" name="appointment_at" value="<?= e($appointmentInput) ?>"
                 placeholder="28/10/2026 08:00" autocomplete="off" data-datepicker="datetime" aria-describedby="appointment-hint">
          <?php if ($error = errors('appointment_at')): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
        </label>
        <label class="field">
          <span class="field__label">Địa điểm</span>
          <input class="field__input" type="text" name="appointment_location" maxlength="255" placeholder="Ví dụ: Phòng họp tầng 2"
                 value="<?= e(old('appointment_location', $app['appointment_location'] ?? '')) ?>" aria-describedby="appointment-hint">
          <?php if ($error = errors('appointment_location')): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
        </label>
        <p class="admin-muted" id="appointment-hint">Để trống cả ngày giờ và địa điểm để hủy lịch.</p>
        <button type="submit" class="btn btn-primary">Lưu lịch phỏng vấn</button>
      </form>
    </section>

    <section class="admin-card" aria-labelledby="candidate-note">
      <h2 class="admin-h2" id="candidate-note">Ghi chú nội bộ</h2>
      <form id="application-note" class="admin-application-editor" data-ajax data-admin-draft method="POST" action="<?= $updateUrl ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="note">
        <label class="field">
          <span class="field__label">Nội dung ghi chú</span>
          <textarea class="field__input" name="admin_note" rows="3" maxlength="2000" required
                    placeholder="Ví dụ: Ứng viên có kinh nghiệm may, hẹn gọi lại chiều mai." aria-describedby="note-hint"><?= e(old('admin_note', '')) ?></textarea>
          <?php if ($error = errors('admin_note')): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
        </label>
        <p class="admin-muted" id="note-hint">Chỉ nhân sự quản trị nhìn thấy.</p>
        <button type="submit" class="btn btn-primary">Thêm ghi chú</button>
      </form>
    </section>
  </div>
</div>
