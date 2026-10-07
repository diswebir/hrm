<?php
/**
 * انواع مرخصی
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/leave')) ?>">مرخصی</a> ← انواع</div>
    <h1>🏷 انواع مرخصی</h1>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header"><h3>افزودن نوع</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/leave/types')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>نام <span class="req">*</span></label><input name="name" required placeholder="مثلاً مرخصی بدون حقوق"></div>
          <div class="field"><label>کد</label>
            <select name="code">
              <option value="custom">سفارشی</option>
              <option value="annual">استحقاقلی (annual)</option>
              <option value="sick">استعلاجی (sick)</option>
              <option value="unpaid">بدون حقوق (unpaid)</option>
              <option value="special">استثنایی (special)</option>
              <option value="marriage">ازدواج</option>
              <option value="maternity">زایمان</option>
              <option value="parental">والدین</option>
            </select></div>
          <div class="field"><label>رنگ</label><input type="color" name="color" value="#4f46e5"></div>
          <div class="field"><label>پرداخت حقوق</label>
            <select name="paid"><option value="1">بله</option><option value="0">خیر</option></select></div>
        </div>
        <button class="btn btn-primary mt-1">افزودن</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>انواع فعلی</h3></div>
    <div class="card-body">
      <?php foreach ($rows as $r): ?>
        <div class="info-item mb-1">
          <div class="flex justify-between">
            <b style="color:<?= e($r['color']) ?>"><?= e($r['name']) ?></b>
            <span class="small">
              <?= $r['paid'] ? '<span class="badge badge-success">پرداختی</span>' : '<span class="badge badge-muted">بدون حقوق</span>' ?>
              <span class="badge badge-muted mono"><?= e($r['slug']) ?></span>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
