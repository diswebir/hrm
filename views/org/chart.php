<?php
/**
 * چارت سازمانی
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← سازمان ← چارت</div>
    <h1>🌳 چارت سازمانی</h1>
    <div class="sub">نمای درختی واحدها، مدیران و تعداد نیرو</div>
  </div>
  <button class="btn" data-print>🖨 چاپ</button>
</div>

<div class="card">
  <div class="card-body" style="overflow-x:auto">
    <div class="orgchart">
      <?= $tree ?: '<div class="empty"><div class="big">🏢</div>هنوز واحدی تعریف نشده است</div>' ?>
    </div>
  </div>
</div>
