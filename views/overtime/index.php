<?php
/**
 * اضافه‌کاری
 */
use App\Core\Auth;

?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← اضافه‌کاری</div>
    <h1>⏱ اضافه‌کاری</h1>
    <div class="sub"><?= $canManage ? 'مدیریت و تأیید درخواست‌های اضافه‌کاری' : 'درخواست و پیگیری اضافه‌کاری خودتان' ?> — <?= to_fa((string)count($rows)) ?> رکورد</div>
  </div>
</div>

<div class="grid grid-3 mb-2">
  <div class="kpi green"><div class="ic">✅</div><div><b><?= to_fa(number_format($totalHours, 1)) ?></b><span>ساعت تأییدشده (این فهرست)</span></div></div>
  <div class="kpi orange"><div class="ic">⏳</div><div><b><?= to_fa((string)$pending) ?></b><span>در انتظار بررسی</span></div></div>
  <div class="kpi blue"><div class="ic">🧮</div><div><b><?= to_fa(number_format(1.4, 1)) ?>× تا <?= to_fa(number_format((float)setting('overtime_holiday_rate', 2), 1)) ?>×</b><span>ضریب شب و تعطیلات</span></div></div>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>➕ ثبت درخواست</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/overtime')) ?>">
        <?= csrf_field() ?>
        <?php if ($canManage): ?>
          <div class="field mb-1"><label>کارمند</label>
            <select name="employee_id">
              <?php foreach ($employees as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_no']) ?>)</option>
              <?php endforeach; ?>
            </select></div>
        <?php endif; ?>
        <div class="field mb-1"><label>تاریخ <span class="req">*</span></label><input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" required></div>
        <div class="field mb-1"><label>ساعات <span class="req">*</span></label><input type="number" name="hours" min="0.5" max="12" step="0.5" value="2" required></div>
        <div class="field mb-1"><label>دلیل</label><input name="reason" placeholder="مثلاً پشتیبانی شب (شبانه)…"></div>
        <div class="alert alert-info small">ضریب به‌صورت خودکار: روز عادی <?= to_fa('1.4') ?>× · جمعه/تعطیل <?= to_fa(number_format((float)setting('overtime_holiday_rate', 2), 1)) ?>×</div>
        <button class="btn btn-primary full">ارسال درخواست</button>
      </form>
    </div>
  </div>

  <div style="grid-column: span 2">
    <div class="card">
      <div class="card-header"><h3>فهرست درخواست‌ها</h3></div>
      <div class="card-body table-wrap flush">
        <table class="tbl">
          <thead>
            <tr>
              <th>تاریخ</th><th>کارمند</th><th>ساعات</th><th>ضریب</th><th>دلیل</th><th>وضعیت</th><th>اقدام</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="small"><?= e(jdate($r['date'])) ?></td>
              <td class="small"><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?><div class="muted mono"><?= e($r['emp_no']) ?></div></td>
              <td class="num"><b><?= to_fa(number_format((float)$r['hours'], 1)) ?></b></td>
              <td class="num mono"><?= to_fa(number_format((float)$r['multiplier'], 2)) ?>×</td>
              <td class="small" style="max-width:200px"><?= e($r['reason']) ?></td>
              <td><?= status_badge($r['status']) ?></td>
              <td class="actions">
                <?php if ($r['status'] === 'pending' && $canManage): ?>
                  <form method="post" action="<?= e(hrm_url('/overtime/' . $r['id'] . '/approve')) ?>" data-confirm="تأیید شود؟">
                    <?= csrf_field() ?><button class="btn btn-sm btn-success">✅</button>
                  </form>
                  <form method="post" action="<?= e(hrm_url('/overtime/' . $r['id'] . '/reject')) ?>" data-confirm="رد شود؟">
                    <?= csrf_field() ?><button class="btn btn-sm btn-danger">❌</button>
                  </form>
                <?php elseif ($r['status'] === 'approved'): ?>
                  <span class="small muted"><?= e($r['approver_name'] ?: '') ?></span>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="7" class="empty"><div class="big">⏱</div>درخواستی ثبت نشده</div></td><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
