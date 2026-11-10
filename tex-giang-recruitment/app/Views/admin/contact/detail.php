<?php
$page['ajax_fragment'] = true;
$msg = $message;
$listUrl = url('admin/contact');
$detailPath = 'admin/contact/' . (int) $msg['id'];
$status = $msg['status'];
$statusBadge = ['new' => 'admin-badge--new', 'read' => 'admin-badge--draft', 'replied' => 'admin-badge--published'];
$subject = trim((string) ($msg['subject'] ?? ''));
$title = $subject !== '' ? $subject : 'Không có chủ đề';
$phone = \App\Models\BranchContact::telHref((string) $msg['phone']);
$email = filter_var($msg['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
$received = vn_date($msg['created_at'], 'H:i d/m/Y') ?: '—';
?>
<div class="admin-page-head admin-cms-form-head">
  <div class="admin-contact-heading">
    <a href="<?= $listUrl ?>" class="admin-editor-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Hộp thư liên hệ</a>
    <div class="admin-contact-title">
      <h1 class="admin-h1"><?= e($title) ?></h1>
      <span class="admin-badge <?= $statusBadge[$status] ?? '' ?>"><?= e($statuses[$status] ?? $status) ?></span>
    </div>
    <p class="admin-sub">Nhận lúc <?= e($received) ?></p>
  </div>
  <div class="admin-head-actions">
    <?php if ($phone !== ''): ?>
    <a href="tel:<?= e($phone) ?>" class="btn btn-outline"><i class="bi bi-telephone" aria-hidden="true"></i> Gọi điện</a>
    <?php endif; ?>
    <?php if ($email !== ''): ?>
    <a href="mailto:<?= e($email) ?>?subject=<?= rawurlencode('Phản hồi TEX-GIANG: ' . ($subject !== '' ? $subject : 'Câu hỏi tuyển dụng')) ?>" class="btn btn-primary"><i class="bi bi-envelope" aria-hidden="true"></i> Trả lời email</a>
    <?php endif; ?>
  </div>
</div>

<div class="admin-contact-detail">
  <section class="admin-cms-card admin-contact-message" aria-labelledby="contact-message-title">
    <h2 id="contact-message-title">Nội dung tin nhắn</h2>
    <p><?= e($msg['message']) ?></p>
  </section>
  <div class="admin-contact-side">
    <section class="admin-cms-card admin-cms-fields" aria-labelledby="contact-sender-title">
      <h2 id="contact-sender-title">Thông tin người gửi</h2>
      <dl class="admin-information">
        <div><dt>Họ và tên</dt><dd><?= e($msg['full_name']) ?></dd></div>
        <div><dt>Email</dt><dd><?php if ($email !== ''): ?><a class="admin-link" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php else: ?>—<?php endif; ?></dd></div>
        <div><dt>Số điện thoại</dt><dd><?php if ($phone !== ''): ?><a class="admin-link" href="tel:<?= e($phone) ?>"><?= e($msg['phone']) ?></a><?php else: ?>—<?php endif; ?></dd></div>
        <div><dt>Thời gian nhận</dt><dd class="admin-mono"><?= e($received) ?></dd></div>
      </dl>
    </section>
    <section class="admin-cms-card admin-cms-fields" aria-labelledby="contact-status-title">
      <h2 id="contact-status-title">Trạng thái</h2>
      <form class="admin-cms-fields" data-ajax method="POST" action="<?= url($detailPath . '/status') ?>">
        <?= csrf_field() ?>
        <label class="field">
          <span class="visually-hidden">Trạng thái tin nhắn</span>
          <select class="field__input" name="status">
            <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit" class="btn btn-primary">Lưu trạng thái</button>
      </form>
      <form class="admin-contact-delete" data-ajax method="POST" action="<?= url($detailPath . '/delete') ?>" data-confirm="<?= e('Xóa tin nhắn của “' . $msg['full_name'] . '”?') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline admin-editor-delete"><i class="bi bi-trash" aria-hidden="true"></i> Xóa tin nhắn</button>
      </form>
    </section>
  </div>
</div>
