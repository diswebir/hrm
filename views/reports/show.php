<?php
/**
 * نمایش گزارش
 */
$d = $data;
$hasSecondary = !empty($d['secondary']);
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/reports')) ?>">گزارش‌ها</a> ← <?= e($meta[0]) ?></div>
    <h1><?= e($meta[1]) ?> گزارش <?= e($meta[0]) ?></h1>
    <div class="sub"><?= e($meta[2]) ?></div>
  </div>
  <div class="flex gap-1 no-print">
    <a class="btn" href="<?= e(hrm_url('/reports/' . $slug . '/export') . '?' . e(http_build_query($_GET))) ?>">📤 CSV</a>
    <button class="btn" data-print>🖨</button>
    <a class="btn" href="<?= e(hrm_url('/reports')) ?>">بازگشت</a>
  </div>
</div>

<form class="filters no-print" method="get">
  <label class="small muted">از</label><input type="date" name="from" value="<?= e($_GET['from'] ?? date('Y-m-01')) ?>">
  <label class="small muted">تا</label><input type="date" name="to" value="<?= e($_GET['to'] ?? date('Y-m-d')) ?>">
  <button class="btn btn-primary">بازه</button>
</form>

<?php if (!empty($d['kpis'])): ?>
<div class="grid grid-4 mb-2">
  <?php $i = 0; foreach ($d['kpis'] as $lbl => $val): $i++; ?>
    <div class="kpi <?= ['blue', 'green', 'orange', 'purple'][$i % 4] ?>">
      <div class="ic">📊</div><div><b><?= to_fa((string)$val) ?></b><span><?= e($lbl) ?></span></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card mb-2">
  <div class="card-header"><h3>جزئیات</h3></div>
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><?php foreach ($d['headers'] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($d['rows'] as $r): ?>
        <tr>
          <?php foreach ($r as $c): ?>
            <td class="<?= is_numeric($c) && !preg_match('/^\d{1,3}$/', (string)$c) ? 'num' : '' ?> small"><?= e((string)$c) ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$d['rows']): ?><tr><td colspan="<?= count($d['headers']) ?>" class="empty">داده‌ای برای این بازه نیست</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($hasSecondary): ?>
<div class="card">
  <div class="card-header"><h3><?= e($d['secondary']['title']) ?></h3></div>
  <div class="card-body table-wrap">
    <table class="tbl">
      <thead><tr><?php foreach ($d['secondary']['headers'] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($d['secondary']['rows'] as $r): ?>
        <tr><?php foreach ($r as $c): ?><td class="small"><?= e((string)$c) ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
      <?php if (!$d['secondary']['rows']): ?><tr><td colspan="<?= count($d['secondary']['headers']) ?>" class="empty">موردی نیست</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
