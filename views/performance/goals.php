<?php
/**
 * اهداف عملکردی
 */
use App\Core\Auth;
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← عملکرد ← اهداف</div>
    <h1>🎯 اهداف عملکردی</h1>
    <div class="sub">میانگین نمره: <b><?= to_fa(number_format($avg, 1)) ?></b> از ۱۰۰ — <?= to_fa((string)count($rows)) ?> هدف</div>
  </div>
  <?php if ($canAll): ?>
    <a class="btn" href="?scope=<?= $scope === 'all' ? 'mine' : 'all' ?>"><?= $scope === 'all' ? '👁 فقط اهداف من' : '👥 اهداف همه' ?></a>
  <?php endif; ?>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>➕ هدف جدید</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/performance/goals')) ?>">
        <?= csrf_field() ?>
        <?php if ($canAll): ?>
          <div class="field mb-1"><label>کارمند</label>
            <select name="employee_id">
              <?php foreach ($emps as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
            </select></div>
        <?php endif; ?>
        <div class="field mb-1"><label>عنوان <span class="req">*</span></label><input name="title" required placeholder="مثلاً افزایش ۲۰٪ فروش فصل"></div>
        <div class="field mb-1"><label>شرح</label><textarea name="description" rows="2"></textarea></div>
        <div class="form-grid">
          <div class="field"><label>وزن (٪) <span class="req">*</span></label><input name="weight" dir="ltr" type="number" min="1" max="100" value="25"></div>
          <div class="field"><label>دوره</label><input name="period" dir="ltr" value="<?= (int)$period ?>"></div>
          <div class="field"><label>هدف کمی</label><input name="target_value" dir="ltr" type="number" step="0.01"></div>
          <div class="field"><label>مهلت (شمسی)</label><input name="due_date" dir="rtl" placeholder="1404/12/29"></div>
        </div>
        <button class="btn btn-primary full mt-1">ثبت هدف</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3><?= $scope === 'all' ? 'اهداف همه کارمندان' : 'اهداف من' ?></h3></div>
      <div class="card-body">
        <?php foreach ($rows as $g): ?>
          <div class="info-item mb-1" style="display:block">
            <div class="flex justify-between items-center">
              <b><?= e($g['title']) ?></b>
              <span class="badge badge-primary"><?= to_fa(number_format((float)$g['score'], 0)) ?>٪</span>
            </div>
            <div class="small muted">
              <?= e(trim($g['first_name'] . ' ' . $g['last_name'])) ?> ·
              وزن <?= to_fa((string)(int)$g['weight']) ?>٪ ·
              مهلت: <?= e(jdate($g['due_date'])) ?>
              <?php if ($g['target_value']): ?> · هدف کمی: <?= to_fa((string)(int)$g['target_value']) ?> / فعلی: <?= to_fa((string)(int)$g['actual_value']) ?><?php endif; ?>
            </div>
            <?php if ($g['description']): ?><div class="small"><?= e($g['description']) ?></div><?php endif; ?>
            <?= \App\Core\Charts::progress((float)$g['score'], 100) ?>
            <form method="post" action="<?= e(hrm_url('/performance/goals/' . $g['id'] . '/score')) . '?scope=' . e($scope) ?>" class="flex gap-1 mt-1">
              <?= csrf_field() ?>
              <input type="hidden" name="actual_value" value="<?= (float)$g['actual_value'] ?>">
              <input name="score" type="number" min="0" max="100" value="<?= (int)$g['score'] ?>" dir="ltr" style="width:90px">
              <button class="btn btn-sm btn-primary">به‌روزرسانی نمره</button>
              <?php if ((int)$g['employee_id'] === (int)$myEmpId || $canAll): ?>
                <span class="small muted" style="margin-right:auto">آخرین وضعیت: <?= e($g['status']) ?></span>
              <?php endif; ?>
            </form>
          </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?><div class="empty"><div class="big">🎯</div>هدفی ثبت نشده</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
