<div class="christmas-decoration tg-container no-print" aria-hidden="true">
  <span>🎄</span>
  <span>⛄ 🦌</span>
</div>

<div class="christmas-overlay no-print" aria-hidden="true">
  <?php foreach ([7, 17, 29, 39, 53, 66, 77, 92, 12, 23, 34, 47, 58, 73, 84, 97] as $index => $left): ?>
  <span class="christmas-overlay__snow" style="--snow-left: <?= $left ?>%; --snow-size: <?= 3 + $index % 4 ?>px; --snow-duration: <?= 11 + ($index * 5) % 9 ?>s; --snow-delay: -<?= ($index * 7) % 17 ?>s"></span>
  <?php endforeach; ?>
</div>
