<?php
/**
 * ورود توده‌ای از CSV
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/employees')) ?>">فهرست کارمندان</a> ← ورود توده‌ای</div>
    <h1>📥 ورود توده‌ای کارمندان از CSV</h1>
    <div class="sub">با فایل اکسل/CSV صدها کارمند را در چند ثانیه وارد کنید</div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header"><h3>۱) قالب را دانلود کنید</h3></div>
    <div class="card-body">
      <p class="muted small">ستون‌ها: <span class="mono">first_name, last_name, national_code, gender, birth_date, hire_date, department, position, email, phone, salary_base</span></p>
      <ul class="small muted" style="line-height:2;padding-right:18px">
        <li>تاریخ‌ها به <b>شمسی</b> باشد (مثال: 1370/05/12)</li>
        <li><span class="mono">gender</span>: male یا female</li>
        <li><span class="mono">department</span> و <span class="mono">position</span> باید دقیقاً مانند سامانه باشند (وگرنه خالی می‌ماند)</li>
        <li>ردیف‌هایی که کد ملی تکراری داشته باشند رد می‌شوند</li>
        <li>نام ستون‌ها باید دقیقاً مانند قالب باشد (حروف کوچک انگلیسی)</li>
      </ul>
      <a class="btn btn-primary" href="<?= e(hrm_url('/employees/template')) ?>">⬇️ دانلود قالب CSV</a>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>۲) فایل را بارگذاری و اجرا کنید</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/employees/import')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field mb-1">
          <label>فایل CSV (با BOM برای فارسی)</label>
          <input type="file" name="csv" accept=".csv,.txt" required>
        </div>
        <div class="alert alert-warning">⚠️ عملیات غیرقابل بازگشت نیست اما ردیف‌های تکراری رد می‌شوند؛ ابتدا با تعداد کم آزمایش کنید.</div>
        <button class="btn btn-primary btn-lg full">🚀 شروع ورود اطلاعات</button>
      </form>
      <div class="mt-2 small muted">
        💡 در اکسل: File ← Save As ← CSV UTF-8
      </div>
    </div>
  </div>
</div>
