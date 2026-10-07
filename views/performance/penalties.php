<?php
/**
 * تشویق و تنبیه
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← عملکرد ← تشویق و تنبیه</div>
    <h1>⚖️ تشویق و تنبیه</h1>
    <div class="sub">کل پاداش‌ها: <?= money($sumR) ?> تومان · کل جریمه‌ها: <?= money($sumP) ?> تومان</div>
  </div>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>➕ ثبت پاداش / جریمه</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/performance/penalties')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>کارمند</label>
          <select name="employee_id" required>
            <?php foreach ($emps as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="field mb-1"><label>نوع</label>
          <select name="kind"><option value="reward">🏆 پاداش</option><option value="penalty">⚠️ جریمه</option></select></div>
        <div class="form-grid">
          <div class="field"><label>مبلغ (تومان) <span class="req">*</span></label><input name="amount" dir="ltr" type="number" min="0" required></div>
          <div class="field"><label>تاریخ (شمسی)</label><input name="date" dir="rtl" placeholder="1404/08/15"></div>
        </div>
        <div class="field mb-1"><label>دلیل <span class="req">*</span></label><input name="reason" required placeholder="مثلاً کسب رتبه برتر فروش مهر"></div>
        <button class="btn btn-primary full">ثبت</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>📜 سوابق پاداش/جریمه</h3></div>
    <div class="card-body table-wrap">
      <table class="tbl">
        <thead><tr><th>کارمند</th><th>نوع</th><th>مبلغ</th><th>دلیل</th><th>تاریخ</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="small"><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?></td>
            <td><?= $r['kind'] === 'reward' ? '<span class="badge badge-success">پاداش</span>' : '<span class="badge badge-warn">جریمه</span>' ?></td>
            <td class="num"><?= money($r['amount']) ?></td>
            <td class="small"><?= e($r['reason']) ?></td>
            <td class="small"><?= e(jdate($r['date'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="empty">رکوردی نیست</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>🚨 سوابق انضباطی</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/performance/discipline')) ?>" class="mb-1">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>کارمند</label>
          <select name="employee_id" required>
            <?php foreach ($emps as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-grid">
          <div class="field"><label>نوع</label>
            <select name="kind">
              <option value="warning">تذکر شفاهی</option>
              <option value="reprimand">تذکر کتبی</option>
              <option value="suspension">تعلیق</option>
              <option value="dismissal">اخراج</option>
            </select></div>
          <div class="field"><label>تاریخ (شمسی)</label><input name="date" dir="rtl"></div>
        </div>
        <div class="field mb-1"><label>عنوان <span class="req">*</span></label><input name="title" required></div>
        <div class="field mb-1"><label>شرح</label><textarea name="description" rows="2"></textarea></div>
        <div class="field mb-1"><label>اقدام انجام‌شده</label><input name="action"></div>
        <button class="btn btn-danger full">ثبت سوءسابقه</button>
      </form>

      <div class="table-wrap mt-1">
        <table class="tbl">
          <thead><tr><th>کارمند</th><th>عنوان</th><th>نوع</th><th>تاریخ</th></tr></thead>
          <tbody>
          <?php foreach ($disc as $d): ?>
            <tr>
              <td class="small"><?= e(trim($d['first_name'] . ' ' . $d['last_name'])) ?></td>
              <td class="small"><b><?= e($d['title']) ?></b><?= $d['description'] ? '<div class="muted">' . e($d['description']) . '</div>' : '' ?></td>
              <td><?= ['warning' => 'تذکر شفاهی', 'reprimand' => 'تذکر کتبی', 'suspension' => 'تعلیق', 'dismissal' => 'اخراج'][$d['kind']] ?? e($d['kind']) ?></td>
              <td class="small"><?= e(jdate($d['date'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$disc): ?><tr><td colspan="4" class="empty">بدون سوءسابقه ✅</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
