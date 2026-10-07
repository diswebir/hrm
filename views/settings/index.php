<?php
/**
 * تنظیمات سامانه
 */
$g = fn(string $k, $d = '') => e((string)($settings[$k] ?? $d));
$wd = explode(',', (string)($settings['work_days'] ?? '6,0,1,2,3,4'));
$dayNames = ['6' => 'شنبه', '0' => 'یکشنبه', '1' => 'دوشنبه', '2' => 'سه‌شنبه', '3' => 'چهارشنبه', '4' => 'پنجشنبه', '5' => 'جمعه'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← تنظیمات</div>
    <h1>⚙️ تنظیمات سامانه</h1>
    <div class="sub">نسخه <?= to_fa($version) ?> · PHP <?= e($php) ?></div>
  </div>
</div>

<div class="grid grid-4 mb-2">
  <div class="kpi blue"><div class="ic">👥</div><div><b><?= to_fa((string)$counts['employees']) ?></b><span>کارمند</span></div></div>
  <div class="kpi purple"><div class="ic">🔐</div><div><b><?= to_fa((string)$counts['users']) ?></b><span>کاربر</span></div></div>
  <div class="kpi orange"><div class="ic">🕐</div><div><b><?= to_fa((string)$counts['attendance']) ?></b><span>رکورد حضور</span></div></div>
  <div class="kpi muted"><div class="ic">📜</div><div><b><?= to_fa((string)$counts['logs']) ?></b><span>رویداد ثبت‌شده</span></div></div>
</div>

<form method="post" action="<?= e(hrm_url('/settings')) ?>">
  <?= csrf_field() ?>
  <div class="grid grid-2">
    <div class="card">
      <div class="card-header"><h3>🏢 هویت سازمان</h3></div>
      <div class="card-body form-grid">
        <div class="field"><label>نام سامانه</label><input name="app_name" value="<?= $g('app_name', 'سامانه منابع انسانی') ?>"></div>
        <div class="field"><label>نام سازمان</label><input name="org_name" value="<?= $g('org_name') ?>"></div>
        <div class="field"><label>متن سربرگ/لوگو</label><input name="logo_text" value="<?= $g('logo_text') ?>"></div>
        <div class="field"><label>منطقه زمانی</label>
          <select name="timezone">
            <option value="Asia/Tehran" <?= ($settings['timezone'] ?? '') === 'Asia/Tehran' ? 'selected' : '' ?>>تهران (Asia/Tehran)</option>
            <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
          </select></div>
        <div class="field"><label>واحد پول</label><input name="currency" value="<?= $g('currency', 'تومان') ?>"></div>
        <div class="field"><label>رنگ پوسته (CSS متغیر)</label><input name="theme" value="<?= $g('theme', '#4f46e5') ?>"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>🕐 کارکرد</h3></div>
      <div class="card-body form-grid">
        <div class="field"><label>شروع کار</label><input name="work_start" dir="ltr" placeholder="08:00" value="<?= $g('work_start', '08:00') ?>"></div>
        <div class="field"><label>پایان کار</label><input name="work_end" dir="ltr" placeholder="17:00" value="<?= $g('work_end', '17:00') ?>"></div>
        <div class="field"><label>مهلت تأخیر (دقیقه)</label><input name="late_grace_min" dir="ltr" type="number" min="0" max="120" value="<?= $g('late_grace_min', '10') ?>"></div>
        <div class="field"><label>مهلت نشست بیکار (دقیقه)</label><input name="session_ttl_minutes" dir="ltr" type="number" min="5" value="<?= $g('session_ttl_minutes', '120') ?>"></div>
        <div class="field span2"><label>روزهای کاری</label>
          <div class="flex gap-1 wrap">
            <?php foreach ($dayNames as $num => $lbl): ?>
              <label class="badge <?= in_array($num, $wd, true) ? 'badge-primary' : 'badge-muted' ?>" style="cursor:pointer;padding:7px 11px">
                <input type="checkbox" name="work_days[]" value="<?= $num ?>" <?= in_array($num, $wd, true) ? 'checked' : '' ?> style="margin-left:6px" class="wd-box" data-val="<?= $num ?>">
                <?= e($lbl) ?>
              </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="work_days" id="work_days_field" value="<?= e((string)($settings['work_days'] ?? '6,0,1,2,3,4')) ?>">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>🔢 کد پرسنلی و اضافه‌کاری</h3></div>
      <div class="card-body form-grid">
        <div class="field"><label>پیشوند کد</label><input name="emp_no_prefix" dir="ltr" maxlength="5" value="<?= $g('emp_no_prefix', 'EMP') ?>"></div>
        <div class="field"><label>تعداد رقم</label><input name="emp_no_digits" dir="ltr" type="number" min="1" max="9" value="<?= $g('emp_no_digits', '5') ?>"></div>
        <div class="field"><label>ضریب عادی</label><input name="overtime_rate" dir="ltr" type="number" step="0.05" min="1" value="<?= $g('overtime_rate', '1.4') ?>"></div>
        <div class="field"><label>ضریب شب</label><input name="overtime_night_rate" dir="ltr" type="number" step="0.05" min="1" value="<?= $g('overtime_night_rate', '1.75') ?>"></div>
        <div class="field"><label>ضریب تعطیل</label><input name="overtime_holiday_rate" dir="ltr" type="number" step="0.05" min="1" value="<?= $g('overtime_holiday_rate', '2') ?>"></div>
        <div class="field"><label>معافیت مالیاتی (ریال)</label><input name="tax_exempt_personal" dir="ltr" type="number" value="<?= $g('tax_exempt_personal', '140000000') ?>"></div>
        <div class="field"><label>هشدار قرارداد (روز)</label><input name="contract_alert_days" dir="ltr" type="number" value="<?= $g('contract_alert_days', '30') ?>"></div>
        <div class="field"><label>هشدار دوره آزمایشی (روز)</label><input name="probation_alert_days" dir="ltr" type="number" value="<?= $g('probation_alert_days', '7') ?>"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>💾 ذخیره</h3></div>
      <div class="card-body">
        <div class="alert alert-info small">تغییرات بلافاصله در سراسر سامانه اعمال می‌شوند. رمز عبور و اطلاعات ورود دست‌نخورده می‌ماند.</div>
        <button class="btn btn-primary btn-lg full">💾 ذخیره تنظیمات</button>
        <div class="small muted mt-2">
          💡 توصیه: پس از هر تغییر مهم، از بخش <a href="<?= e(hrm_url('/backup')) ?>">پشتیبان‌گیری</a> نسخه تهیه کنید.
        </div>
      </div>
    </div>
  </div>
</form>

<script>
(function(){
  // ترکیب چک‌باکس‌ها به مقدار پنهان work_days
  var field = document.getElementById('work_days_field');
  document.querySelectorAll('.wd-box').forEach(function(cb){
    cb.addEventListener('change', function(){
      var vals = [];
      document.querySelectorAll('.wd-box:checked').forEach(function(x){ vals.push(x.value); });
      field.value = vals.join(',');
    });
  });
})();
</script>
