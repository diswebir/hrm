<?php
/**
 * فیش حقوق
 */
$s = $slip;
$monthNames = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
?>
<style>
  .slip { max-width:820px; margin:0 auto; background:#fff; color:#111827; }
  .slip table { width:100%; border-collapse:collapse; font-size:14px; }
  .slip th, .slip td { border:1px solid #d1d5db; padding:9px 12px; text-align:right; }
  .slip th { background:#f3f4f6; }
  .slip .l { font-weight:700; }
  @media print { body>*:not(.slip-wrap) { display:none !important; } .no-print{display:none!important;} }
</style>

<div class="no-print page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/payroll?tab=slips')) ?>">حقوق و دستمزد</a> ← فیش</div>
    <h1>📄 فیش حقوق</h1>
  </div>
  <div class="flex gap-1">
    <button class="btn" data-print>🖨 چاپ / ذخیره PDF</button>
    <a class="btn" href="<?= e(hrm_url('/payroll?tab=slips')) ?>">بازگشت</a>
  </div>
</div>

<div class="card slip-wrap">
  <div class="card-body slip" style="padding:26px">
    <div class="flex justify-between items-center" style="border-bottom:3px solid #4f46e5;padding-bottom:14px">
      <div>
        <div style="font-size:20px;font-weight:800;color:#4f46e5"><?= e(setting('app_name', 'سازمان')) ?></div>
        <div class="small" style="color:#6b7280">فیش حقوق و مزایا — دوره <?= to_fa((string)($s['year'] ?? 0)) ?>/<?= to_fa((string)($s['month'] ?? 0)) ?> (<?= e($monthNames[(int)$s['month']]) ?>)</div>
      </div>
      <div class="text-left small" style="color:#6b7280">
        شماره فیش: <b class="mono">#<?= (int)$s['id'] ?></b><br>
        وضعیت: <?= ['draft' => 'پیش‌نویس', 'final' => 'نهایی', 'paid' => 'پرداخت‌شده'][$s['period_status'] ?? 'draft'] ?? '—' ?><br>
        تاریخ صدور: <?= e(jdate(date('Y-m-d'))) ?>
      </div>
    </div>

    <table style="margin-top:16px;border:none">
      <tr>
        <th>نام و نام خانوادگی</th><td><?= e(trim($s['first_name'] . ' ' . $s['last_name'])) ?></td>
        <th>کد پرسنلی</th><td class="mono"><?= e($s['emp_no']) ?></td>
      </tr>
      <tr>
        <th>کد ملی</th><td class="mono"><?= e($s['national_code'] ?: '—') ?></td>
        <th>شبای بانکی</th><td class="mono"><?= e($s['bank_account'] ?: '—') ?></td>
      </tr>
    </table>

    <div style="margin-top:18px" class="grid grid-2">
      <div>
        <div class="l" style="background:#ecfdf5;padding:8px 12px;border:1px solid #a7f3d0">درآمدها (ریال)</div>
        <table>
          <?php foreach ($earnings as $label => $val): ?>
            <tr><td><?= e($label) ?></td><td class="text-left mono"><?= number_format((float)$val) ?></td></tr>
          <?php endforeach; ?>
          <tr class="l"><td>جمع درآمدها</td><td class="text-left mono"><?= number_format((float)$s['gross_earn']) ?></td></tr>
        </table>
      </div>
      <div>
        <div class="l" style="background:#fef2f2;padding:8px 12px;border:1px solid #fecaca">کسورات (ریال)</div>
        <table>
          <?php foreach ($deductions as $label => $val): ?>
            <tr><td><?= e($label) ?></td><td class="text-left mono"><?= number_format((float)$val) ?></td></tr>
          <?php endforeach; ?>
          <tr class="l"><td>جمع کسورات</td><td class="text-left mono"><?= number_format((float)$s['total_deduct']) ?></td></tr>
        </table>
      </div>
    </div>

    <table style="margin-top:16px">
      <tr>
        <th style="background:#4f46e5;color:#fff;font-size:16px">خالص قابل پرداخت (ریال)</th>
        <td class="text-left mono" style="font-size:18px;font-weight:800;background:#eef2ff"><?= number_format((float)$s['net_pay']) ?></td>
      </tr>
    </table>

    <div class="small" style="margin-top:22px;color:#6b7280;display:flex;justify-content:space-between">
      <span>مهر و امضای کارفرما: …………………………</span>
      <span>امضای کارمند: …………………………</span>
    </div>
    <div class="small text-center" style="margin-top:14px;color:#9ca3af">
      این فیش در <?= e(setting('app_name', '')) ?> صادر شده است — تاریخ صدور <?= e(jdate(date('Y-m-d'))) ?>
    </div>
  </div>
</div>
