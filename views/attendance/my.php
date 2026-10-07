<?php
/**
 * حضور من (خودخدمت)
 */
use App\Core\Jalali;

$canPunch = !$todayRec || !$todayRec['check_out'];
$state = 'idle';
if ($todayRec) {
    if (!$todayRec['check_out']) $state = 'in';
    else $state = 'out';
}
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← حضور من</div>
    <h1>🕐 حضور و غیاب من</h1>
    <div class="sub">امروز: <?= e(Jalali::format(date('Y-m-d'))) ?> — <?= e(Jalali::weekday(date('Y-m-d'))) ?></div>
  </div>
</div>

<div class="grid grid-2 mb-2">
  <div class="card">
    <div class="card-body text-center" style="padding:28px">
      <div style="font-size:44px;font-weight:800;direction:ltr" id="live-clock">--:--:--</div>
      <div class="muted small">ساعت سرور — ثبت ورود/خروج با دکمه پایین</div>

      <?php if ($state === 'idle'): ?>
        <form method="post" action="<?= e(hrm_url('/attendance/check')) ?>" class="mt-2">
          <?= csrf_field() ?>
          <button class="btn btn-success btn-lg full" style="padding:16px;font-size:18px">🟢 ثبت ورود</button>
        </form>
        <div class="small muted mt-1">هنوز امروز ورودی ثبت نکرده‌اید.</div>
      <?php elseif ($state === 'in'): ?>
        <div class="mt-2 badge badge-success" style="font-size:15px;padding:10px 18px">
          ورود ثبت شد: <?= to_fa($todayRec['check_in']) ?>
          <?= $todayRec['late_min'] > 0 ? ' · تأخیر ' . to_fa((string)$todayRec['late_min']) . ' دقیقه' : '' ?>
        </div>
        <form method="post" action="<?= e(hrm_url('/attendance/check')) ?>" class="mt-2">
          <?= csrf_field() ?>
          <button class="btn btn-danger btn-lg full" style="padding:16px;font-size:18px">🔴 ثبت خروج</button>
        </form>
      <?php else: ?>
        <div class="mt-2">
          <span class="badge badge-success" style="font-size:14px;padding:8px 14px">ورود <?= to_fa($todayRec['check_in']) ?></span>
          <span class="badge badge-warn" style="font-size:14px;padding:8px 14px">خروج <?= to_fa($todayRec['check_out']) ?></span>
        </div>
        <div class="mt-1"><b>کارکرد امروز:</b> <?= to_fa((string)intdiv((int)$todayRec['work_min'], 60)) ?> ساعت و <?= to_fa((string)((int)$todayRec['work_min'] % 60)) ?> دقیقه</div>
        <div class="small muted mt-1">امروز کامل شد ✅ — فردا دوباره!</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>📊 خلاصه این ماه</h3></div>
    <div class="card-body">
      <div class="grid grid-2">
        <div class="kpi green"><div class="ic">✅</div><div><b><?= to_fa((string)$month['present']) ?></b><span>روز حضور</span></div></div>
        <div class="kpi orange"><div class="ic">⏱</div><div><b><?= to_fa((string)$month['late']) ?></b><span>روز تأخیر</span></div></div>
        <div class="kpi red"><div class="ic">❌</div><div><b><?= to_fa((string)$month['absent']) ?></b><span>غیبت</span></div></div>
        <div class="kpi blue"><div class="ic">🌴</div><div><b><?= to_fa((string)$month['leave']) ?></b><span>مرخصی</span></div></div>
      </div>
      <div class="info-item mt-1">
        <div class="flex justify-between"><b>کارکرد ماهانه</b><span><?= to_fa((string)intdiv($month['work_min'], 60)) ?> ساعت</span></div>
        <?php
        $expected = (int)setting('standard_work_hours', 176);
        echo \App\Core\Charts::progress($month['work_min'] / 60, $expected);
        ?>
        <div class="small muted">استاندارد ماهانه: <?= to_fa((string)$expected) ?> ساعت</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>۳۰ روز گذشته</h3></div>
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>تاریخ</th><th>ورود</th><th>خروج</th><th>کارکرد</th><th>تأخیر</th><th>وضعیت</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e(Jalali::format($r['date'])) ?> <span class="small muted"><?= e(Jalali::weekday($r['date'])) ?></span></td>
          <td class="mono"><?= e($r['check_in'] ?: '—') ?></td>
          <td class="mono"><?= e($r['check_out'] ?: '—') ?></td>
          <td class="mono small"><?= $r['work_min'] ? to_fa((string)intdiv((int)$r['work_min'], 60)) . 'h ' . to_fa((string)((int)$r['work_min'] % 60)) . 'm' : '—' ?></td>
          <td><?= $r['late_min'] > 0 ? '<span class="badge badge-warn">' . to_fa((string)$r['late_min']) . '′</span>' : '—' ?></td>
          <td><?= status_badge($r['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="empty">رکوردی نیست</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function(){
  function tick(){
    var el = document.getElementById('live-clock');
    if(el){ var d = new Date(); el.textContent = d.toTimeString().slice(0,8); }
  }
  tick(); setInterval(tick, 1000);
})();
</script>
