<?php
/**
 * پرونده کاری من (خودخدمت)
 */
$photos = ['الهام','کارمند'];
$initial = mb_substr($emp['first_name'], 0, 1) . mb_substr($emp['last_name'], 0, 1);
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← پرونده کاری من</div>
    <h1>🗂 پرونده کاری من</h1>
    <div class="sub">کد پرسنلی <?= e($emp['emp_no']) ?> · <?= e($pos['title'] ?? '—') ?> · <?= e($dept['name'] ?? '—') ?></div>
  </div>
  <div class="flex gap-1 no-print">
    <button class="btn" data-print>🖨 چاپ</button>
    <a class="btn btn-primary" href="<?= e(hrm_url('/profile')) ?>">ویرایش اطلاعات تماس</a>
  </div>
</div>

<div class="card mb-2">
  <div class="card-body">
    <div class="flex gap-2 wrap items-center">
      <div class="avatar" style="width:74px;height:74px;font-size:24px"><?= e($initial) ?></div>
      <div style="flex:1;min-width:200px">
        <h2 style="margin-bottom:2px"><?= e(trim($emp['first_name'] . ' ' . $emp['last_name'])) ?></h2>
        <div class="muted small"><?= status_badge($emp['status']) ?> · تاریخ استخدام: <?= e(jdate($emp['hire_date'])) ?> · مدیر مستقیم: <?= e($manager ? trim($manager['first_name'] . ' ' . $manager['last_name']) : '—') ?></div>
      </div>
      <?php if ($emp['photo']): ?>
        <img src="<?= e(hrm_url('/storage/uploads/' . $emp['photo'])) ?>" alt="" style="width:74px;height:74px;object-fit:cover;border-radius:18px">
      <?php endif; ?>
    </div>

    <div class="info-list mt-2">
      <div class="info-item"><span>کد ملی</span><b class="mono"><?= e($emp['national_code'] ?: '—') ?></b></div>
      <div class="info-item"><span>تاریخ تولد</span><b><?= e(jdate($emp['birth_date'])) ?></b></div>
      <div class="info-item"><span>جنسیت</span><b><?= $emp['gender'] === 'female' ? 'خانم' : 'آقا' ?></b></div>
      <div class="info-item"><span>تلفن</span><b class="mono" dir="ltr"><?= e($emp['phone'] ?: '—') ?></b></div>
      <div class="info-item"><span>ایمیل</span><b class="mono" dir="ltr"><?= e($emp['email'] ?: '—') ?></b></div>
      <div class="info-item"><span>نوع قرارداد</span><b><?= e(['permanent' => 'دائم', 'temporary' => 'موقت', 'probation' => 'آزمایشی', 'project' => 'پروژه‌ای'][$emp['contract_type']] ?? $emp['contract_type']) ?></b></div>
      <div class="info-item"><span>بازه قرارداد</span><b><?= e(jdate($emp['contract_start'])) ?> — <?= e(jdate($emp['contract_end'])) ?></b></div>
      <div class="info-item"><span>واحد</span><b><?= e($dept['name'] ?? '—') ?></b></div>
      <div class="info-item"><span>وضعیت تأهل</span><b><?= $emp['marital_status'] === 'married' ? 'متاهل' : 'مجرد' ?></b></div>
      <div class="info-item"><span>تحصیلات</span><b><?= e($emp['education'] ?: '—') ?></b></div>
    </div>
  </div>
</div>

<div class="tabs">
  <a href="#t-att" class="active" data-tab="t-att">🕐 حضور (۳۰ روز)</a>
  <a href="#t-leave" data-tab="t-leave">🌴 مرخصی‌ها</a>
  <a href="#t-slip" data-tab="t-slip">🧾 فیش‌ها</a>
  <a href="#t-goal" data-tab="t-goal">🎯 اهداف</a>
  <a href="#t-req" data-tab="t-req">📨 درخواست‌ها</a>
  <a href="#t-hist" data-tab="t-hist">🧵 سوابق</a>
  <a href="#t-doc" data-tab="t-doc">📎 مدارک</a>
  <a href="#t-loan" data-tab="t-loan">🏦 وام</a>
</div>

<div id="t-att" class="tab-pane">
  <div class="card"><div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>تاریخ</th><th>ورود</th><th>خروج</th><th>کارکرد (دقیقه)</th><th>وضعیت</th></tr></thead>
      <tbody>
      <?php foreach ($att30 as $a): ?>
        <tr>
          <td><?= e(jdate($a['date'])) ?> <span class="muted small"><?= e(\App\Core\Jalali::weekday($a['date'])) ?></span></td>
          <td class="mono"><?= e($a['check_in'] ?: '—') ?></td>
          <td class="mono"><?= e($a['check_out'] ?: '—') ?></td>
          <td><?= $a['work_min'] ? to_fa((string)intdiv((int)$a['work_min'], 60)) . 'ساعت ' . to_fa((string)(((int)$a['work_min']) % 60)) . 'دقیقه' : '—' ?></td>
          <td><?= status_badge($a['status']) ?> <?php if ($a['late_min'] > 0): ?><span class="badge badge-warn"><?= to_fa((string)$a['late_min']) ?> دقیقه تأخیر</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$att30): ?><tr><td colspan="5" class="empty">داده‌ای ثبت نشده</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>

<div id="t-leave" class="tab-pane hide">
  <div class="grid grid-2">
    <div class="card">
      <div class="card-header"><h3>موجودی سال جاری</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>نوع</th><th>سقف</th><th>مصرف</th><th>باقی</th></tr></thead>
          <tbody>
          <?php foreach ($balances as $b): ?>
            <tr>
              <td><span class="badge" style="background:<?= e($b['color']) ?>22;color:<?= e($b['color']) ?>"><?= e($b['name']) ?></span></td>
              <td><?= to_fa(number_format((float)$b['days_total'], 0)) ?></td>
              <td><?= to_fa(number_format((float)$b['days_used'], 0)) ?></td>
              <td><b><?= to_fa(number_format((float)$b['days_total'] - (float)$b['days_used'], 0)) ?></b></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h3>درخواست‌های اخیر</h3><a class="btn btn-sm btn-primary" href="<?= e(hrm_url('/leave')) ?>">درخواست جدید</a></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>بازه</th><th>نوع</th><th>روز</th><th>وضعیت</th></tr></thead>
          <tbody>
          <?php foreach ($leaves as $l): ?>
            <tr>
              <td class="small"><?= e(jdate($l['from_date'])) ?> — <?= e(jdate($l['to_date'])) ?></td>
              <td><?= e($l['type_name']) ?></td>
              <td><?= to_fa((string)$l['days']) ?></td>
              <td><?= status_badge($l['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$leaves): ?><tr><td colspan="4" class="empty">درخواستی نیست</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div id="t-slip" class="tab-pane hide">
  <div class="card"><div class="card-body table-wrap">
    <table class="tbl">
      <thead><tr><th>دوره</th><th>درآمد</th><th>کسورات</th><th>خالص پرداختی</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($slips as $s): ?>
        <tr>
          <td><?= to_fa((string)$s['year']) ?> / <?= to_fa((string)$s['month']) ?></td>
          <td class="num"><?= money($s['gross_earn']) ?></td>
          <td class="num"><?= money($s['total_deduct']) ?></td>
          <td class="num"><b><?= money($s['net_pay']) ?></b></td>
          <td><a class="btn btn-sm" href="<?= e(hrm_url('/payroll/slip/' . $s['id'])) ?>" target="_blank">مشاهده فیش</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$slips): ?><tr><td colspan="5" class="empty">هنوز فیشی صادر نشده</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>

<div id="t-goal" class="tab-pane hide">
  <div class="card"><div class="card-body">
    <?php if (!$goals): ?><div class="empty"><div class="big">🎯</div>هدف فعالی ثبت نشده</div><?php endif; ?>
    <?php foreach ($goals as $g): ?>
      <div class="info-item mb-1">
        <b><?= e($g['title']) ?></b>
        <div class="small muted"><?= e($g['description']) ?></div>
        <div class="flex items-center gap-1 mt-1">
          <?= \App\Core\Charts::progress((float)$g['score'], 100) ?>
          <span class="small"><?= to_fa(number_format((float)$g['score'], 0)) ?>٪</span>
          <span class="small muted">مهلت: <?= e(jdate($g['due_date'])) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div></div>
</div>

<div id="t-req" class="tab-pane hide">
  <div class="card">
    <div class="card-header"><h3>درخواست‌های من</h3><a class="btn btn-sm btn-primary" href="<?= e(hrm_url('/requests')) ?>">درخواست جدید</a></div>
    <div class="card-body table-wrap">
      <table class="tbl">
        <thead><tr><th>عنوان</th><th>نوع</th><th>تاریخ</th><th>وضعیت</th></tr></thead>
        <tbody>
        <?php foreach ($reqs as $r): ?>
          <tr><td><?= e($r['title']) ?></td><td class="small"><?= e($r['type']) ?></td>
              <td class="small"><?= e(jdate(substr($r['created_at'], 0, 10))) ?></td><td><?= status_badge($r['status']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$reqs): ?><tr><td colspan="4" class="empty">درخواستی ثبت نشده</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="t-hist" class="tab-pane hide">
  <div class="card"><div class="card-body">
    <div class="timeline">
      <?php foreach ($history as $h): ?>
        <div class="tl-item">
          <b><?= e($h['title']) ?></b>
          <div class="d"><?= e($h['department'] ?: '') ?> · <?= e(jdate($h['from_date'])) ?> تا <?= e($h['to_date'] ? jdate($h['to_date']) : 'اکنون') ?></div>
          <?php if ($h['note']): ?><div class="small"><?= e($h['note']) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <div class="tl-item">
        <b> استخدام در سازمان</b>
        <div class="d"><?= e(jdate($emp['hire_date'])) ?></div>
      </div>
    </div>
  </div></div>
</div>

<div id="t-doc" class="tab-pane hide">
  <div class="card"><div class="card-body table-wrap">
    <table class="tbl">
      <thead><tr><th>عنوان</th><th>دسته</th><th>تاریخ ثبت</th></tr></thead>
      <tbody>
      <?php foreach ($docs as $d): ?>
        <tr><td><?= e($d['title']) ?></td><td class="small"><?= e($d['category']) ?></td><td class="small"><?= e(jdate(substr($d['created_at'], 0, 10))) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$docs): ?><tr><td colspan="3" class="empty">مدرکی ثبت نشده</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>

<div id="t-loan" class="tab-pane hide">
  <div class="card"><div class="card-body">
    <?php if (!$loans): ?><div class="empty"><div class="big">🏦</div>وام فعالی ندارید</div><?php endif; ?>
    <?php foreach ($loans as $l): ?>
      <div class="info-item mb-1">
        <b>وام <?= money($l['amount']) ?> تومان</b>
        <div class="small muted">قسط ماهانه: <?= money($l['installment']) ?> · پرداخت‌شده: <?= to_fa((string)$l['paid_installments']) ?> از <?= to_fa((string)$l['months']) ?> قسط · دلیل: <?= e($l['reason'] ?: '—') ?></div>
        <?= \App\Core\Charts::progress((float)$l['paid_installments'], (float)$l['months'], '#7c3aed') ?>
      </div>
    <?php endforeach; ?>
  </div></div>
</div>

<script>
(function(){
  document.querySelectorAll('[data-tab]').forEach(function(a){
    a.addEventListener('click', function(ev){
      ev.preventDefault();
      document.querySelectorAll('[data-tab]').forEach(function(x){x.classList.remove('active')});
      a.classList.add('active');
      document.querySelectorAll('.tab-pane').forEach(function(p){p.classList.add('hide')});
      var pane = document.getElementById(a.getAttribute('data-tab'));
      if(pane) pane.classList.remove('hide');
      history.replaceState(null,'',a.getAttribute('href'));
    });
  });
  var initial = location.hash.replace('#t-','t-');
  if(initial){ var el = document.querySelector('[data-tab="'+initial+'"]'); if(el) el.click(); }
})();
</script>
