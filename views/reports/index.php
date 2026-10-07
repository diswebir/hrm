<?php
/**
 * فهرست گزارش‌ها
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← گزارش‌ها</div>
    <h1>📈 گزارش‌های مدیریتی</h1>
    <div class="sub"><?= to_fa((string)count($catalog)) ?> گزارش آماده با خروجی CSV</div>
  </div>
</div>

<div class="grid grid-3">
  <?php foreach ($catalog as $slug => $m): ?>
    <a href="<?= e(hrm_url('/reports/' . $slug)) ?>" class="card" style="text-decoration:none;color:inherit">
      <div class="card-body text-center" style="padding:26px">
        <div style="font-size:38px"><?= e($m[1]) ?></div>
        <h3 class="mt-1">گزارش <?= e($m[0]) ?></h3>
        <div class="small muted"><?= e($m[2]) ?></div>
        <span class="btn btn-primary mt-1" style="display:inline-block">اجرای گزارش →</span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
