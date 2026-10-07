<?php
/**
 * ارزیابی عملکرد
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← عملکرد ← ارزیابی</div>
    <h1>📊 ارزیابی عملکرد</h1>
    <div class="sub">میانگین کل: <b><?= to_fa(number_format($avgAll, 1)) ?></b> از ۱۰۰ — <?= to_fa((string)count($rows)) ?> ارزیابی</div>
  </div>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>✍️ ثبت ارزیابی جدید</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/performance/reviews')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>کارمند <span class="req">*</span></label>
          <select name="employee_id" required>
            <?php foreach ($emps as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="field mb-1"><label>دوره (سال/فصل) <span class="req">*</span></label>
          <input name="period" dir="ltr" value="<?= (int)$period ?>" required></div>

        <div class="small muted mb-1">هر شاخص از ۱ تا ۵:</div>
        <?php
        $qs = ['q1' => 'کیفیت کار', 'q2' => 'مسئولیت‌پذیری', 'q3' => 'کار تیمی', 'q4' => 'حضور و انضباط'];
        foreach ($qs as $k => $lbl):
        ?>
          <div class="field mb-1">
            <label><?= e($lbl) ?></label>
            <select name="<?= $k ?>">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?= $i ?>" <?= $i === 4 ? 'selected' : '' ?>><?= to_fa((string)$i) ?> — <?= ['خیلی ضعیف', 'ضعیف', 'متوسط', 'خوب', 'عالی'][$i - 1] ?></option>
              <?php endfor; ?>
            </select>
          </div>
        <?php endforeach; ?>

        <div class="field mb-1"><label>نقاط قوت</label><textarea name="strengths" rows="2"></textarea></div>
        <div class="field mb-1"><label>زمینه بهبود</label><textarea name="improvements" rows="2"></textarea></div>
        <div class="alert alert-info small">نمره نهایی = میانگین چهار شاخص × ۲۰ (از ۱۰۰) و برای کارمند اعلان می‌شود.</div>
        <button class="btn btn-primary full">ثبت ارزیابی</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3>سوابق ارزیابی</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>کارمند</th><th>دوره</th><th>نمره</th><th>نقاط قوت</th><th>بهبود</th><th>ارزیاب</th><th>تاریخ</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="small"><b><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?></b><div class="muted mono"><?= e($r['emp_no']) ?></div></td>
              <td class="mono small"><?= e($r['period']) ?></td>
              <td>
                <span class="badge <?= (float)$r['overall_score'] >= 70 ? 'badge-success' : ((float)$r['overall_score'] >= 50 ? 'badge-warn' : 'badge-danger') ?>">
                  <?= to_fa(number_format((float)$r['overall_score'], 1)) ?>
                </span>
              </td>
              <td class="small" style="max-width:150px"><?= e(mb_strimwidth((string)$r['strengths'], 0, 50, '…')) ?></td>
              <td class="small" style="max-width:150px"><?= e(mb_strimwidth((string)$r['improvements'], 0, 50, '…')) ?></td>
              <td class="small muted"><?= e($r['reviewer'] ?: '—') ?></td>
              <td class="small muted"><?= e(jdate(substr((string)$r['submitted_at'], 0, 10))) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="7" class="empty"><div class="big">📊</div>ارزیابی‌ای ثبت نشده</div></td><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
