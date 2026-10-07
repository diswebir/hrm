<?php
/**
 * مرخصی — درخواست‌ها و تعادل
 */
use App\Core\Auth;

$canApprove = Auth::can('leave.approve');
$mine = $tab === 'mine';
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← مرخصی</div>
    <h1>🌴 مرخصی</h1>
    <div class="sub">ثبت و پیگیری درخواست‌های مرخصی</div>
  </div>
  <div class="flex gap-1 wrap no-print">
    <a class="btn" href="<?= e(hrm_url('/leave/calendar')) ?>">🗓 تقویم</a>
    <?php if ($canApprove): ?>
      <a class="btn" href="<?= e(hrm_url('/leave/balances')) ?>">📊 تعادل همه</a>
      <a class="btn" href="<?= e(hrm_url('/leave/types')) ?>">🏷 انواع</a>
      <a class="btn" href="<?= e(hrm_url('/leave/holidays')) ?>">🎉 تعطیلات</a>
    <?php endif; ?>
  </div>
</div>

<div class="tabs no-print">
  <a href="<?= e(hrm_url('/leave?tab=mine')) ?>" class="<?= $mine ? 'active' : '' ?>">درخواست‌های من</a>
  <?php if ($canApprove): ?>
    <a href="<?= e(hrm_url('/leave?tab=approve')) ?>" class="<?= $tab === 'approve' ? 'active' : '' ?>">
      تأیید درخواست‌ها <span class="badge badge-warn"><?= to_fa((string)$pendingCount) ?></span>
    </a>
    <a href="<?= e(hrm_url('/leave?tab=all')) ?>" class="<?= $tab === 'all' ? 'active' : '' ?>">همه</a>
  <?php endif; ?>
</div>

<div class="grid grid-3">
  <!-- فرم درخواست -->
  <div class="no-print">
    <div class="card">
      <div class="card-header"><h3>➕ درخواست جدید</h3></div>
      <div class="card-body">
        <form method="post" action="<?= e(hrm_url('/leave')) ?>">
          <?= csrf_field() ?>
          <div class="field mb-1"><label>نوع مرخصی <span class="req">*</span></label>
            <select name="type_id" required>
              <?php foreach ($types as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?><?= $t['paid'] ? '' : ' (بدون حقوق)' ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field mb-1"><label>از تاریخ (شمسی) <span class="req">*</span></label>
            <input name="from_date" dir="rtl" placeholder="1404/08/05" required></div>
          <div class="field mb-1"><label>تا تاریخ (شمسی) <span class="req">*</span></label>
            <input name="to_date" dir="rtl" placeholder="1404/08/07" required></div>
          <div class="field mb-1"><label>دلیل <span class="req">*</span></label>
            <textarea name="reason" rows="2" placeholder="شرح کوتاه…"></textarea></div>
          <div class="alert alert-info small">
            📅 روزهای کاری این بازه به‌صورت خودکار محاسبه می‌شوند (پنجشنبه/جمعه و تعطیلات رسمی لحاظ نمی‌شوند).
            برای مرخصی استحقاقلی حداقل <?= to_fa((string)$minYears) ?> سال سابقه لازم است.
          </div>
          <button class="btn btn-primary full">ارسال درخواست</button>
        </form>
      </div>
    </div>

    <?php if ($mine && $balances): ?>
      <div class="card mt-2">
        <div class="card-header"><h3>📦 موجودی <?= to_fa((string)(int)setting('leave_year', date('Y'))) ?></h3></div>
        <div class="card-body">
          <?php foreach ($balances as $b): ?>
            <div class="mb-1">
              <div class="flex justify-between small"><b style="color:<?= e($b['color']) ?>"><?= e($b['name']) ?></b>
                <span><?= to_fa(number_format(max(0, (float)$b['days_total'] - (float)$b['days_used']), 0)) ?> از <?= to_fa(number_format((float)$b['days_total'], 0)) ?> روز</span></div>
              <?= \App\Core\Charts::progress((float)$b['days_used'], (float)$b['days_total'], $b['color']) ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- فهرست درخواست‌ها -->
  <div style="grid-column: span 2">
    <div class="card">
      <div class="card-header">
        <h3><?= $tab === 'approve' ? '⏳ در انتظار تأیید شما' : ($mine ? 'درخواست‌های من' : 'همه درخواست‌ها') ?></h3>
        <form method="get" action="<?= e(hrm_url('/leave')) ?>" class="flex gap-1">
          <input type="hidden" name="tab" value="<?= e($tab) ?>">
          <select name="status" onchange="this.form.submit()">
            <?php foreach (['all' => 'همه', 'pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'ردشده', 'cancelled' => 'انصرافی'] as $k => $lbl): ?>
              <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="card-body table-wrap flush">
        <table class="tbl">
          <thead>
            <tr>
              <?php if (!$mine || $tab !== 'mine'): ?><th>کارمند</th><?php endif; ?>
              <th>نوع</th><th>بازه</th><th>روز</th><th>دلیل</th><th>وضعیت</th><th>اقدام</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php if (!$mine || $tab !== 'mine'): ?>
                <td class="small"><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?><div class="muted mono"><?= e($r['emp_no']) ?></div></td>
              <?php endif; ?>
              <td><span class="badge" style="background:<?= e($r['type_color']) ?>22;color:<?= e($r['type_color']) ?>;border-color:transparent"><?= e($r['type_name']) ?></span></td>
              <td class="small"><?= e($r['from_date']) ?><br>تا <?= e($r['to_date']) ?></td>
              <td class="num"><b><?= to_fa((string)$r['days']) ?></b></td>
              <td class="small" style="max-width:160px"><?= e(mb_strimwidth((string)$r['reason'], 0, 60, '…')) ?></td>
              <td><?= status_badge($r['status']) ?>
                  <?php if ($r['approver_name']): ?><div class="small muted"><?= e($r['approver_name']) ?></div><?php endif; ?></td>
              <td class="actions">
                <?php if ($r['status'] === 'pending' && $canApprove): ?>
                  <form method="post" action="<?= e(hrm_url('/leave/' . $r['id'] . '/approve')) ?>" data-confirm="تأیید این مرخصی؟ (از موجودی کسر می‌شود)">
                    <?= csrf_field() ?><button class="btn btn-sm btn-success">✅ تأیید</button>
                  </form>
                  <form method="post" action="<?= e(hrm_url('/leave/' . $r['id'] . '/reject')) ?>" data-confirm="رد شود؟">
                    <?= csrf_field() ?><button class="btn btn-sm btn-danger">❌</button>
                  </form>
                <?php elseif ($r['status'] === 'pending' && (int)$r['employee_id'] === (int)$myEmpId): ?>
                  <form method="post" action="<?= e(hrm_url('/leave/' . $r['id'] . '/cancel')) ?>" data-confirm="انصراف دهید؟">
                    <?= csrf_field() ?><button class="btn btn-sm">انصراف</button>
                  </form>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="7" class="empty"><div class="big">🌴</div>درخواستی نیست</div></td><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
