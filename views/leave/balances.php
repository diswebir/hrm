<?php
/**
 * تعادل مرخصی همه کارمندان
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/leave')) ?>">مرخصی</a> ← تعادل</div>
    <h1>📊 تعادل مرخصی کارمندان</h1>
    <div class="sub">سال <?= to_fa((string)$year) ?></div>
  </div>
  <form method="get" class="flex gap-1">
    <select name="year" onchange="this.form.submit()">
      <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= to_fa((string)$y) ?></option>
      <?php endfor; ?>
    </select>
  </form>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header"><h3>فرم ویرایش/افزودن تعادل</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/leave/balances')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>کارمند</label>
            <select name="employee_id" required>
              <option value="">—</option>
              <?php foreach ($emps as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?> (<?= e($e['emp_no']) ?>)</option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>نوع مرخصی</label>
            <select name="type_id" required>
              <?php foreach ($types as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>سال</label>
            <input name="year" dir="ltr" value="<?= (int)$year ?>"></div>
          <div class="field"><label>سهمیه (روز)</label>
            <input name="days_total" dir="ltr" type="number" step="0.5" min="0" value="0"></div>
          <div class="field"><label>مصرف‌شده (روز)</label>
            <input name="days_used" dir="ltr" type="number" step="0.5" min="0" value="0"></div>
        </div>
        <button class="btn btn-primary mt-1">ذخیره تعادل</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>فهرست تعادل‌ها</h3></div>
    <div class="card-body table-wrap">
      <table class="tbl">
        <thead><tr><th>کارمند</th><th>نوع</th><th>سهمیه</th><th>مصرف</th><th>باقیمانده</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $left = (float)$r['days_total'] - (float)$r['days_used']; ?>
          <tr>
            <td class="small"><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?><div class="muted mono"><?= e($r['emp_no']) ?></div></td>
            <td><span class="badge" style="background:<?= e($r['color']) ?>22;color:<?= e($r['color']) ?>;border-color:transparent"><?= e($r['type_name']) ?></span></td>
            <td class="num"><?= to_fa(number_format((float)$r['days_total'], 0)) ?></td>
            <td class="num"><?= to_fa(number_format((float)$r['days_used'], 0)) ?></td>
            <td class="num"><b class="<?= $left < 0 ? 'text-error' : 'text-success' ?>"><?= to_fa(number_format($left, 0)) ?></b></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="empty">رکوردی نیست</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
