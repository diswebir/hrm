<?php
/**
 * تقویم ماهانه مرخصی‌ها
 */
$dows = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
$order = [6, 0, 1, 2, 3, 4, 5]; // شنبه اول
$monthNames = [1 => 'ژانویه', 2 => 'فوریه', 3 => 'مارس', 4 => 'آوریل', 5 => 'مه', 6 => 'ژوئن',
               7 => 'ژوئیه', 8 => 'اوت', 9 => 'سپتامبر', 10 => 'اکتبر', 11 => 'نوامبر', 12 => 'دسامبر'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/leave')) ?>">مرخصی</a> ← تقویم</div>
    <h1>🗓 تقویم مرخصی‌های تأییدشده</h1>
    <div class="sub"><?= e($jalaliHead) ?> · <?= e($monthNames[$m] . ' ' . $y) ?></div>
  </div>
  <div class="flex gap-1">
    <a class="btn" href="?m=<?= (int)$prev['m'] ?>&y=<?= (int)$prev['y'] ?>">→ ماه قبل</a>
    <a class="btn" href="?m=<?= (int)$next['m'] ?>&y=<?= (int)$next['y'] ?>">ماه بعد ←</a>
    <button class="btn" data-print>🖨</button>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:14px">
    <div class="grid" style="grid-template-columns:repeat(7,1fr);gap:8px">
      <?php foreach ($order as $dow): ?>
        <div class="text-center small" style="font-weight:700;padding:8px;background:var(--bg-2);border-radius:8px">
          <?= e($dows[$dow]) ?>
        </div>
      <?php endforeach; ?>

      <?php foreach ($cells as $c): ?>
        <?php if ($c === null): ?><div></div><?php continue; endif; ?>
        <div style="min-height:96px;border:1px solid var(--border);border-radius:10px;padding:7px;background:<?= $c['isToday'] ? '#eef2ff' : ($c['isFriday'] || $c['holiday'] ? '#fff7ed' : '#fff') ?>;<?= $c['isToday'] ? 'border-color:#4f46e5;' : '' ?>">
          <div class="flex justify-between">
            <b style="<?= $c['isFriday'] ? 'color:#dc2626' : '' ?>"><?= to_fa((string)$c['day']) ?></b>
            <span class="small muted" dir="ltr"><?= e(substr($c['date'], 5)) ?></span>
          </div>
          <div class="small muted"><?= e($c['jalali']) ?></div>
          <?php if ($c['holiday']): ?>
            <div class="small" style="color:#dc2626">🎉 <?= e($c['holiday']) ?></div>
          <?php endif; ?>
          <?php foreach (array_slice($c['leaves'], 0, 4) as $lv): ?>
            <div class="small" style="background:<?= e($lv['color']) ?>22;color:<?= e($lv['color']) ?>;border-radius:6px;padding:2px 5px;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= e($lv['name']) ?> · <?= e($lv['type']) ?>
            </div>
          <?php endforeach; ?>
          <?php if (count($c['leaves']) > 4): ?>
            <div class="small muted">+<?= to_fa((string)(count($c['leaves']) - 4)) ?> نفر…</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
