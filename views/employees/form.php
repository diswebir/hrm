<?php
/**
 * فرم افزودن/ویرایش کارمند
 * @var ?array $emp
 */
$isEdit = $emp !== null;
$val = fn(string $k, $default = '') => $isEdit ? ($emp[$k] ?? $default) : $default;
// نمایش تاریخ‌ها به شمسی
$j = fn($g) => $g ? \App\Core\Jalali::format($g) : '';
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/employees')) ?>">فهرست کارمندان</a> ← <?= $isEdit ? 'ویرایش' : 'افزودن' ?></div>
    <h1><?= $isEdit ? '✏️ ویرایش پرونده کارمند' : '➕ افزودن کارمند جدید' ?></h1>
    <?php if (!$isEdit): ?>
      <div class="sub">کد پرسنلی خودکار صادر می‌شود: <b class="mono"><?= e($nextNo) ?></b></div>
    <?php else: ?>
      <div class="sub">کد پرسنلی: <b class="mono"><?= e($emp['emp_no']) ?></b></div>
    <?php endif; ?>
  </div>
</div>

<form method="post" action="<?= $isEdit ? e(hrm_url('/employees/' . $emp['id'])) : e(hrm_url('/employees')) ?>">
  <?= csrf_field() ?>

  <div class="grid grid-2">
    <div class="card">
      <div class="card-header"><h3>🪪 اطلاعات هویتی</h3></div>
      <div class="card-body form-grid">
        <div class="field"><label>نام <span class="req">*</span></label>
          <input name="first_name" value="<?= e($val('first_name')) ?>" required></div>
        <div class="field"><label>نام خانوادگی <span class="req">*</span></label>
          <input name="last_name" value="<?= e($val('last_name')) ?>" required></div>
        <div class="field"><label>کد ملی (۱۰ رقمی)</label>
          <input name="national_code" dir="ltr" maxlength="10" pattern="\d{10}" value="<?= e($val('national_code')) ?>"></div>
        <div class="field"><label>جنسیت</label>
          <select name="gender">
            <option value="male" <?= $val('gender') === 'male' ? 'selected' : '' ?>>آقا</option>
            <option value="female" <?= $val('gender') === 'female' ? 'selected' : '' ?>>خانم</option>
          </select></div>
        <div class="field"><label>تاریخ تولد (شمسی)</label>
          <input name="birth_date" dir="rtl" placeholder="مثال: 1370/05/12" value="<?= e($j($val('birth_date'))) ?>"></div>
        <div class="field"><label>وضعیت تأهل</label>
          <select name="marital_status">
            <option value="single" <?= $val('marital_status') === 'single' ? 'selected' : '' ?>>مجرد</option>
            <option value="married" <?= $val('marital_status') === 'married' ? 'selected' : '' ?>>متاهل</option>
          </select></div>
        <div class="field"><label>تعداد فرزندان</label>
          <input name="dependents" type="number" min="0" max="15" value="<?= (int)$val('dependents') ?>"></div>
        <div class="field"><label>تحصیلات</label>
          <input name="education" placeholder="مثلاً کارشناسی ارشد نرم‌افزار" value="<?= e($val('education')) ?>"></div>
        <div class="field span2"><label>آدرس</label>
          <textarea name="address" rows="2"><?= e($val('address')) ?></textarea></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>🏢 اطلاعات سازمانی</h3></div>
      <div class="card-body form-grid">
        <div class="field"><label>واحد سازمانی</label>
          <select name="dept_id" id="f_dept">
            <option value="0">—</option>
            <?php foreach ($depts as $d): ?>
              <option value="<?= (int)$d['id'] ?>" <?= $val('dept_id') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>پست / عنوان شغلی</label>
          <select name="position_id" id="f_pos">
            <option value="0">—</option>
            <?php foreach ($positions as $p): ?>
              <option value="<?= (int)$p['id'] ?>" data-dept="<?= (int)($p['department_id'] ?? 0) ?>" <?= $val('position_id') == $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>مدیر مستقیم</label>
          <select name="manager_id">
            <option value="0">—</option>
            <?php foreach ($peers as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= $val('manager_id') == $p['id'] ? 'selected' : '' ?>><?= e(trim($p['first_name'] . ' ' . $p['last_name'])) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>شیفت کاری</label>
          <select name="shift_id">
            <option value="0">پیش‌فرض سازمان</option>
            <?php foreach ($shifts as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $val('shift_id') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['start_time']) ?> تا <?= e($s['end_time']) ?>)</option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>تاریخ استخدام (شمسی) <span class="req">*</span></label>
          <input name="hire_date" dir="rtl" placeholder="1403/07/01" value="<?= e($isEdit ? $j($emp['hire_date']) : \App\Core\Jalali::g2j(date('Y-m-d'))) ?>"></div>
        <div class="field"><label>وضعیت</label>
          <select name="status">
            <option value="active" <?= $val('status') === 'active' ? 'selected' : '' ?>>فعال</option>
            <option value="inactive" <?= $val('status') === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
          </select></div>
        <div class="field span2"><label>روزهای کاری</label>
          <?php
          $wd = $val('work_days', setting('work_days', '6,0,1,2,3,4'));
          $wdArr = array_map('intval', explode(',', (string)$wd));
          $dayNames = [6 => 'شنبه', 0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه'];
          ?>
          <div class="flex gap-1 wrap">
            <?php foreach ($dayNames as $dNum => $dName): ?>
              <label class="badge <?= in_array($dNum, $wdArr, true) ? 'badge-primary' : 'badge-muted' ?>" style="cursor:pointer;padding:7px 12px">
                <input type="checkbox" name="work_days[]" value="<?= $dNum ?>" <?= in_array($dNum, $wdArr, true) ? 'checked' : '' ?> style="margin-left:5px">
                <?= e($dName) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>📝 قرارداد و حقوق</h3></div>
      <div class="card-body form-grid">
        <div class="field"><label>نوع قرارداد</label>
          <select name="contract_type">
            <?php foreach (['permanent' => 'دائم', 'temporary' => 'موقت', 'probation' => 'آزمایشی', 'project' => 'پروژه‌ای'] as $k => $lbl): ?>
              <option value="<?= $k ?>" <?= $val('contract_type') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>حقوق پایه (تومان)</label>
          <input name="salary_base" dir="ltr" type="number" min="0" step="100000" value="<?= e((string)$val('salary_base')) ?>"></div>
        <div class="field"><label>شروع قرارداد (شمسی)</label>
          <input name="contract_start" dir="rtl" value="<?= e($j($val('contract_start'))) ?>"></div>
        <div class="field"><label>پایان قرارداد (شمسی)</label>
          <input name="contract_end" dir="rtl" placeholder="برای دائم خالی بگذارید" value="<?= e($j($val('contract_end'))) ?>"></div>
        <div class="field"><label>پایان دوره آزمایشی (شمسی)</label>
          <input name="probation_end" dir="rtl" value="<?= e($j($val('probation_end'))) ?>"></div>
        <div class="field"><label>شماره بانک</label>
          <input name="bank_account" dir="ltr" value="<?= e($val('bank_account')) ?>"></div>
        <div class="field"><label>شماره بیمه</label>
          <input name="insurance_no" dir="ltr" value="<?= e($val('insurance_no')) ?>"></div>
        <div class="field"><label>تلفن</label>
          <input name="phone" dir="ltr" placeholder="0912..." value="<?= e($val('phone')) ?>"></div>
        <div class="field"><label>ایمیل</label>
          <input name="email" type="email" dir="ltr" value="<?= e($val('email')) ?>"></div>
        <div class="field span2"><label>یادداشت داخلی</label>
          <textarea name="notes" rows="2"><?= e($val('notes')) ?></textarea></div>
      </div>
    </div>

    <?php if ($isEdit): ?>
    <div class="card">
      <div class="card-header"><h3>🖼 عکس پرونده</h3></div>
      <div class="card-body text-center">
        <?php if ($emp['photo']): ?>
          <img src="<?= e(hrm_url('/storage/uploads/' . $emp['photo'])) ?>" style="width:110px;height:110px;object-fit:cover;border-radius:22px" alt="">
        <?php else: ?>
          <div class="avatar" style="width:110px;height:110px;font-size:34px;margin:auto"><?= e(mb_substr($emp['first_name'], 0, 1) . mb_substr($emp['last_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div class="mt-2">
          <input type="file" name="photo" accept="image/*" class="full">
          <small class="hint">JPG/PNG تا ۳ مگابایت — این بخش پس از «ذخیره» فعال می‌شود؛ یا از صفحه پرونده آپلود کنید.</small>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="flex gap-1 mt-2 no-print">
    <button class="btn btn-primary btn-lg">💾 ذخیره</button>
    <a class="btn btn-lg" href="<?= $isEdit ? e(hrm_url('/employees/' . $emp['id'])) : e(hrm_url('/employees')) ?>">انصراف</a>
  </div>
</form>

<script>
// فیلتر پست‌ها بر اساس واحد انتخابی
(function(){
  var dept = document.getElementById('f_dept');
  var pos = document.getElementById('f_pos');
  if(!dept||!pos) return;
  function filter(){
    var d = dept.value;
    pos.querySelectorAll('option').forEach(function(o){
      if(!o.value) return;
      var od = o.getAttribute('data-dept');
      o.style.display = (d==='0' || od==='0' || od===d) ? '' : 'none';
    });
  }
  dept.addEventListener('change', filter); filter();
})();
</script>
