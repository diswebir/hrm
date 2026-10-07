<?php
/**
 * وام کارمندان
 */
$statusMap = ['active' => 'در حال بازپرداخت', 'closed' => 'تسویه‌شده', 'rejected' => 'رد شده'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/payroll')) ?>">حقوق و دستمزد</a> ← وام‌ها</div>
    <h1>🏦 وام کارمندان</h1>
    <div class="sub">اقساط فعال به‌صورت خودکار در کسورات فیش ماهانه لحاظ می‌شوند</div>
  </div>
  <a class="btn" href="<?= e(hrm_url('/payroll')) ?>">بازگشت به حقوق</a>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>➕ ثبت وام</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/payroll/loans')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>کارمند</label>
          <select name="employee_id" required>
            <?php foreach ($emps as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-grid">
          <div class="field"><label>مبلغ کل (تومان) <span class="req">*</span></label><input name="amount" dir="ltr" type="number" min="10000" required></div>
          <div class="field"><label>تعداد اقساط <span class="req">*</span></label><input name="months" dir="ltr" type="number" min="1" max="60" value="12"></div>
          <div class="field"><label>شروع (شمسی)</label><input name="start_date" dir="rtl" placeholder="1404/08/01"></div>
          <div class="field"><label>دلیل</label><input name="reason" placeholder="وام ضروری…"></div>
        </div>
        <button class="btn btn-primary full mt-1">ثبت وام</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3>فهرست وام‌ها</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>کارمند</th><th>مبلغ</th><th>قسط ماهانه</th><th>پیشرفت</th><th>شروع</th><th>وضعیت</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($rows as $l): $paidCnt = (int)$l['paid_installments']; ?>
            <tr>
              <td class="small"><b><?= e(trim($l['first_name'] . ' ' . $l['last_name'])) ?></b><div class="muted mono"><?= e($l['emp_no']) ?></div></td>
              <td class="num"><?= money($l['amount']) ?></td>
              <td class="num"><?= money($l['installment']) ?></td>
              <td style="min-width:140px">
                <?= \App\Core\Charts::progress($paidCnt, (int)$l['months']) ?>
                <div class="small muted"><?= to_fa((string)$paidCnt) ?> از <?= to_fa((string)$l['months']) ?> قسط</div>
              </td>
              <td class="small"><?= e(jdate($l['start_date'])) ?></td>
              <td><?= status_badge($l['status'] === 'active' ? 'active' : ($l['status'] === 'closed' ? 'final' : 'rejected')) ?><div class="small muted"><?= e($statusMap[$l['status']] ?? $l['status']) ?></div></td>
              <td class="actions">
                <?php if ($l['status'] === 'active'): ?>
                  <form method="post" action="<?= e(hrm_url('/payroll/loans/' . $l['id'] . '/pay')) ?>" data-confirm="ثبت پرداخت قسط بعدی؟">
                    <?= csrf_field() ?><button class="btn btn-sm btn-success">💵 پرداخت قسط</button>
                  </form>
                <?php endif; ?>
                <form method="post" action="<?= e(hrm_url('/payroll/loans/' . $l['id'] . '/delete')) ?>" data-confirm="وام و همه اقساطش حذف شود؟">
                  <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="7" class="empty"><div class="big">🏦</div>وامی ثبت نشده</div></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
