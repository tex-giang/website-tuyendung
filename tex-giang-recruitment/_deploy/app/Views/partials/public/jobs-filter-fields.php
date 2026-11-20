<?php
/**
 * Các ô nhập của bộ lọc /jobs.
 *
 * QUAN TRỌNG: đây là các phần tử CON TRỰC TIẾP của #jobs-filter-form.
 * CSS `#jobs-filter-form { display: grid; grid-template-columns: ... }` bố trí
 * chúng nằm NGANG. Không bọc thêm <div> bao ngoài — sẽ tạo 1 ô grid duy nhất
 * và toàn bộ bộ lọc bị xếp dọc.
 *
 * Dữ liệu vào: $filters, $branches, $blocks, $categories.
 * Không có bộ lọc mức lương (đã bỏ theo yêu cầu).
 */
?>
<div>
  <label class="form-label" for="filter-q">Từ khoá</label>
  <input type="search" id="filter-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Nhập tên vị trí hoặc từ khóa" class="form-input">
</div>

<div>
  <label class="form-label" for="filter-location">Địa điểm</label>
  <select id="filter-location" name="location" class="form-input">
    <option value="">Tất cả địa điểm</option>
    <?php foreach ($branches as $slug => $name): ?>
    <option value="<?= e($slug) ?>"<?= $filters['location'] === $slug ? ' selected' : '' ?>>Khu <?= e($name) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div>
  <label class="form-label" for="filter-khoi">Khối</label>
  <select id="filter-khoi" name="khoi" class="form-input" data-block-filter>
    <option value="">Tất cả Khối</option>
    <?php foreach ($blocks as $key => $label): ?>
    <option value="<?= e($key) ?>"<?= $filters['block'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div>
  <label class="form-label" for="filter-department">Bộ phận tuyển dụng</label>
  <select id="filter-department" name="department" class="form-input" data-department-filter>
    <option value="">Tất cả bộ phận</option>
    <?php foreach ($categories as $slug => $name): ?>
    <option value="<?= e($slug) ?>"<?= $filters['department'] === $slug ? ' selected' : '' ?>><?= e($name) ?></option>
    <?php endforeach; ?>
  </select>
</div>
