<?php
/**
 * سلامت سامانه
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← سلامت</div>
    <h1>🩺 سلامت سامانه</h1>
    <div class="sub">بررسی خودکار محیط اجرا و داده‌ها</div>
  </div>
  <span class="badge <?= $allOk ? 'badge-success' : 'badge-warn' ?>" style="font-size:16px;padding:12px 20px">
    <?= $allOk ? '✅ همه بررسی‌ها موفق' : '⚠️ برخی بررسی‌ها ناموفق' ?>
  </span>
</div>

<div class="grid grid-3 mb-2">
  <div class="kpi blue"><div class="ic">🐘</div><div><b>PHP <?= e($php) ?></b><span>محیط اجرا</span></div></div>
  <div class="kpi green"><div class="ic">🗄</div><div><b>SQLite <?= e($sqlite) ?></b><span>موتور دیتابیس</span></div></div>
  <div class="kpi purple"><div class="ic">📦</div><div><b>نسخه <?= to_fa($installed) ?></b><span>سامانه</span></div></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header"><h3>🔍 بررسی‌ها</h3></div>
    <div class="card-body">
      <?php foreach ($checks as $c): ?>
        <div class="info-item">
          <span><?= e($c[0]) ?></span>
          <b class="<?= $c[1] ? 'text-success' : 'text-error' ?>" style="text-align:left;max-width:55%">
            <?= $c[1] ? '✅' : '❌' ?> <span class="small" dir="auto"><?= e((string)$c[2]) ?></span>
          </b>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <div class="card mb-2">
      <div class="card-header"><h3>📊 آمار جداول</h3></div>
      <div class="card-body">
        <div class="info-list" style="grid-template-columns:1fr 1fr">
          <?php foreach ($stats as $t => $c): ?>
            <div class="info-item"><span class="mono"><?= e($t) ?></span><b class="<?= $c < 0 ? 'text-error' : '' ?>"><?= $c < 0 ? '❌' : to_fa((string)$c) ?></b></div>
          <?php endforeach; ?>
        </div>
        <div class="info-list mt-1" style="grid-template-columns:1fr">
          <div class="info-item"><span>حجم دیتابیس</span><b><?= number_format($dbSize / 1024, 1) ?> KB</b></div>
          <div class="info-item"><span>حجم لاگ‌ها</span><b><?= number_format($logSize / 1024, 1) ?> KB</b></div>
          <div class="info-item"><span>نشست‌های فعال (فایل)</span><b><?= to_fa((string)$sessCount) ?></b></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>⚠️ آخرین خطاها/هشدارها</h3></div>
      <div class="card-body">
        <?php foreach ($recentErrors as $e2): ?>
          <div class="info-item">
            <span class="small mono"><?= e($e2['action']) ?></span>
            <b class="small" style="text-align:left;max-width:65%">
              <span class="<?= $e2['action'] === 'exception' ? 'text-error' : '' ?>"><?= e(mb_strimwidth((string)$e2['message'], 0, 80, '…')) ?></span>
              <div class="muted mono" style="font-size:11px"><?= e($e2['created_at']) ?></div>
            </b>
          </div>
        <?php endforeach; ?>
        <?php if (!$recentErrors): ?><div class="empty">🎉 هیچ خطای اخیری ثبت نشده</div><?php endif; ?>
        <a class="btn full mt-1" href="<?= e(hrm_url('/logs?level=error')) ?>">مشاهده همه خطاها →</a>
      </div>
    </div>
  </div>
</div>
