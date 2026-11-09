<?php
/** Chi tiết tin nhắn liên hệ. */
$msg = $message;
$statusBadge = ['new' => 'admin-badge--new', 'read' => 'admin-badge--draft', 'replied' => 'admin-badge--published'];
?>
<div class="admin-page-head">
  <div>
    <p class="admin-breadcrumb"><a href="<?= url('admin/contact') ?>">Hộp thư liên hệ</a> / #<?= (int) $msg['id'] ?></p>
    <h1 class="admin-h1"><?= e($msg['full_name']) ?></h1>
  </div>
  <div class="admin-head-actions">
    <a href="tel:<?= e($msg['phone']) ?>" class="btn btn-outline">Gọi <?= e($msg['phone']) ?></a>
    <?php if (!empty($msg['email'])): ?>
    <a href="mailto:<?= e($msg['email']) ?>?subject=<?= rawurlencode('Phản hồi TEX-GIANG: ' . ($msg['subject'] ?? 'Câu hỏi tuyển dụng')) ?>" class="btn btn-outline">Trả lời email</a>
    <?php endif; ?>
    <a href="<?= url('admin/contact') ?>" class="btn btn-ghost">← Danh sách</a>
  </div>
</div>

<div class="admin-grid-2">
  <section class="admin-card">
    <h2 class="admin-h2">Nội dung tin nhắn</h2>
    <div class="admin-message"><?= nl2br(e((string) $msg['message'])) ?></div>
    <?php if (!empty($msg['subject'])): ?>
    <p class="admin-muted mt-4">Chủ đề: <?= e($msg['subject']) ?></p>
    <?php endif; ?>
  </section>

  <section class="admin-card">
    <h2 class="admin-h2">Thông tin người gửi</h2>
    <table class="admin-table admin-table--kv">
      <tbody>
        <tr><th>Họ và tên</th><td><?= e($msg['full_name']) ?></td></tr>
        <tr><th>Điện thoại</th><td><a href="tel:<?= e($msg['phone']) ?>"><?= e($msg['phone']) ?></a></td></tr>
        <tr><th>Email</th><td><?= $msg['email'] ? '<a href="mailto:' . e($msg['email']) . '">' . e($msg['email']) . '</a>' : '—' ?></td></tr>
      </tbody>
    </table>

    <h2 class="admin-h2 mt-4">Xử lý</h2>
    <form method="POST" action="<?= url('admin/contact/' . $msg['id'] . '/status') ?>">
      <?= csrf_field() ?>
      <div class="field-grid">
        <label class="field">
          <span class="field__label">Trạng thái</span>
          <select class="field__input" name="status">
            <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $msg['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="admin-form__actions mt-4">
        <button type="submit" class="btn btn-primary">Lưu trạng thái</button>
      </div>
    </form>

    <form method="POST" action="<?= url('admin/contact/' . $msg['id'] . '/delete') ?>" class="mt-4"
          data-confirm="Xoá tin nhắn của &quot;<?= e($msg['full_name']) ?>&quot;?">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger">Xoá tin nhắn</button>
    </form>
  </section>
</div>
