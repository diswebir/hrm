<?php
/**
 * تعطیلات رسمی
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/leave')) ?>">مرخصی</a> ← تعطیلات</div>
    <h1>🎉 تعطیلات رسمی</h1>
    <div class="sub">این روزها در محاسبه روزهای کاری مرخصی و وضعیت حضور لحاظ می‌شوند (<?= to_fa((string)count($rows)) ?> روز)</div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header"><h3>افزودن بازه تعطیلی</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/leave/holidays')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field span2"><label>عنوان <span class="req">*</span></label>
            <input name="title" required placeholder="مثلاً تعطیلات نوروز یا روز ملی فناوری"></div>
          <div class="field"><label>از (شمسی) <span class="req">*</span></label><input name="from_date" dir="rtl" placeholder="1404/01/01" required></div>
          <div class="field"><label>تا (شمسی، خالی = فقط همان روز)</label><input name="to_date" dir="rtl" placeholder="1404/01/04"></div>
        </div>
        <button class="btn btn-primary mt-1">افزودن تعطیلات</button>
      </form>
      <div class="alert alert-info small mt-1">💡 بازه‌های چندروزه (مثل نوروز) را می‌توانید در یک مرحله ثبت کنید؛ روزهای تکراری نادیده گرفته می‌شوند.</div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>فهرست</h3></div>
    <div class="card-body table-wrap">
      <table class="tbl">
        <thead><tr><th>تاریخ</th><th>عنوان</th><th>روز هفته</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="small"><?= e(jdate($r['date'])) ?></td>
            <td><?= e($r['title']) ?></td>
            <td class="small muted"><?= e(\App\Core\Jalali::weekday($r['date'])) ?></td>
            <td class="actions">
              <form method="post" action="<?= e(hrm_url('/leave/holidays/' . $r['id'] . '/delete')) ?>" data-confirm="حذف شود؟">
                <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="4" class="empty">تعطیلی ثبت نشده</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
