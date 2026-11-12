<?php
$page['ajax_fragment'] = true;
$errors = \App\Core\Session::pull('_errors', []);
$values = array_merge($settings, \App\Core\Session::pull('_old', []));
$hints = [
    'jobs_per_page' => 'Từ 2 đến 50 tin.',
    'zalo' => 'Nhập đường dẫn Zalo OA hoặc số Zalo.',
];
$seoLimits = ['seo_title' => 70, 'seo_description' => 160];
?>
<div class="admin-content-editor admin-system-editor">
  <div class="admin-page-head admin-cms-form-head">
    <div>
      <h1 class="admin-h1">Cài đặt hệ thống</h1>
      <p class="admin-sub">Thông tin chung, kênh liên hệ và cấu hình hiển thị của website tuyển dụng.</p>
    </div>
  </div>

  <?php if ($errors !== []): ?>
  <div class="admin-alert admin-alert--error" role="alert">Có <?= count($errors) ?> trường cần kiểm tra lại.</div>
  <?php endif; ?>

  <form data-ajax method="POST" action="<?= url('admin/settings') ?>" class="admin-form admin-cms-form">
    <?= csrf_field() ?>
    <?php foreach ($groups as $groupKey => $group): ?>
    <section class="admin-cms-card admin-system-section" aria-labelledby="settings-<?= e($groupKey) ?>">
      <header class="admin-cms-section-intro">
        <h2 id="settings-<?= e($groupKey) ?>"><?= e($group['label']) ?></h2>
        <p><?= e($group['hint']) ?></p>
      </header>
      <div class="field-grid admin-cms-fields">
        <?php foreach ($group['fields'] as $key => [$label, $type, $max]):
            $value = (string) ($values[$key] ?? '');
            $error = (string) ($errors[$key] ?? '');
            $inputClass = 'field__input' . ($error !== '' ? ' is-invalid' : '');
            $required = in_array($key, ['site_name', 'legal_name', 'jobs_per_page'], true);
            $wide = in_array($key, ['website', 'email', 'youtube', 'seo_title', 'seo_description'], true);
        ?>
        <label class="field<?= $wide ? ' field--span2' : '' ?>">
          <span class="field__label"><?= e($label) ?><?= $required ? ' <em>*</em>' : '' ?></span>
          <?php if ($type === 'textarea'): ?>
          <textarea class="<?= $inputClass ?>" name="<?= e($key) ?>" rows="3" maxlength="<?= (int) $max ?>"><?= e($value) ?></textarea>
          <?php else: ?>
          <input class="<?= $inputClass ?>" type="<?= e($type) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>"
                 <?= $type === 'number' ? 'step="1"' : 'maxlength="' . (int) $max . '"' ?><?= $key === 'jobs_per_page' ? ' min="2" max="50"' : '' ?><?= $required ? ' required' : '' ?>>
          <?php endif; ?>
          <?php if (isset($hints[$key])): ?><span class="admin-muted"><?= e($hints[$key]) ?></span><?php endif; ?>
          <?php if (isset($seoLimits[$key])): ?>
          <span class="admin-muted"><span data-settings-length><?= mb_strlen($value) ?></span>/<?= $seoLimits[$key] ?> ký tự khuyến nghị</span>
          <?php endif; ?>
          <?php if ($error !== ''): ?><span class="field__error"><?= e($error) ?></span><?php endif; ?>
        </label>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>

    <?php $saveLabel = 'Lưu cài đặt'; require BASE_PATH . '/app/Views/partials/admin/editor-save.php'; ?>
  </form>
</div>
