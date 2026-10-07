<?php
/**
 * گزارش رویدادها
 */
$f = $filters;
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← رویدادها</div>
    <h1>📜 گزارش رویدادها (Logs)</h1>
    <div class="sub"><?= to_fa((string)$counts['total']) ?> رویداد · <?= to_fa((string)$counts['error']) ?> خطا · <?= to_fa((string)$counts['warning']) ?> هشدار</div>
  </div>
  <a class="btn" href="<?= e(hrm_url('/logs/export')) ?>">📤 خروجی CSV (۵۰۰۰ رکورد آخر)</a>
</div>

<div class="grid grid-3 mb-2">
  <div class="kpi red"><div class="ic">❌</div><div><b><?= to_fa((string)$counts['error']) ?></b><span>خطا</span></div></div>
  <div class="kpi orange"><div class="ic">⚠️</div><div><b><?= to_fa((string)$counts['warning']) ?></b><span>هشدار</span></div></div>
  <div class="kpi blue"><div class="ic">ℹ️</div><div><b><?= to_fa((string)$counts['total']) ?></b><span>کل رویدادها</span></div></div>
</div>

<form class="filters no-print" method="get" action="<?= e(hrm_url('/logs')) ?>">
  <select name="level">
    <option value="">همه سطوح</option>
    <option value="error" <?= $f['level'] === 'error' ? 'selected' : '' ?>>خطا</option>
    <option value="warning" <?= $f['level'] === 'warning' ? 'selected' : '' ?>>هشدار</option>
    <option value="info" <?= $f['level'] === 'info' ? 'selected' : '' ?>>اطلاعات</option>
  </select>
  <select name="action">
    <option value="">همه عملیات</option>
    <?php foreach ($actions as $a): ?>
      <option value="<?= e($a) ?>" <?= $f['action'] === $a ? 'selected' : '' ?>><?= e($a) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary">اعمال</button>
</form>

<div class="card">
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>زمان</th><th>سطح</th><th>عملیات</th><th>پیام</th><th>کاربر</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="small mono" dir="ltr"><?= e($r['created_at']) ?></td>
          <td><?= ['error' => '<span class="badge badge-danger">خطا</span>',
                   'warning' => '<span class="badge badge-warn">هشدار</span>',
                   'info' => '<span class="badge badge-info">اطلاعات</span>'][$r['level']] ?? e($r['level']) ?></td>
          <td class="small mono"><?= e($r['action']) ?></td>
          <td class="small" style="max-width:380px">
            <?= e(mb_strimwidth((string)$r['message'], 0, 110, '…')) ?>
            <?php if ($r['context']): ?><div class="muted mono" style="font-size:11px"><?= e(mb_strimwidth((string)$r['context'], 0, 90, '…')) ?></div><?php endif; ?>
          </td>
          <td class="small"><?= e($r['username'] ?? 'سیستم') ?></td>
          <td class="small mono" dir="ltr"><?= e($r['ip_address'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="empty"><div class="big">📜</div>رویدادی با این فیلتر نیست</div></td><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $pager ?>
</div>
