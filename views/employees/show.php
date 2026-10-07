<?php
/**
 * پرونده کامل کارمند
 */
use App\Core\Auth;

$initial = mb_substr($emp['first_name'], 0, 1) . mb_substr($emp['last_name'], 0, 1);
$canEdit = Auth::can('employees.update');
$canDocs = Auth::can('employees.update');
$contractTypes = ['permanent' => 'دائم', 'temporary' => 'موقت', 'probation' => 'آزمایشی', 'project' => 'پروژه‌ای'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/employees')) ?>">فهرست کارمندان</a> ← <?= e($emp['emp_no']) ?></div>
    <h1><?= e(trim($emp['first_name'] . ' ' . $emp['last_name'])) ?></h1>
    <div class="sub">
      <span class="mono"><?= e($emp['emp_no']) ?></span> ·
      <?= e($pos['title'] ?? 'بدون پست') ?> · <?= e($dept['name'] ?? 'بدون واحد') ?> ·
      <?= status_badge($emp['deleted_at'] ? 'archived' : $emp['status']) ?>
      <?php if ($user): ?> · <span class="badge badge-info">کاربر: <?= e($user['username']) ?></span><?php endif; ?>
    </div>
  </div>
  <div class="flex gap-1 wrap no-print">
    <button class="btn" data-print>🖨 چاپ</button>
    <?php if ($canEdit && !$emp['deleted_at']): ?>
      <a class="btn btn-primary" href="<?= e(hrm_url('/employees/' . $emp['id'] . '/edit')) ?>">✏️ ویرایش</a>
    <?php endif; ?>
    <?php if (Auth::can('employees.delete')): ?>
      <?php if ($emp['deleted_at']): ?>
        <form method="post" action="<?= e(hrm_url('/employees/' . $emp['id'] . '/restore')) ?>" data-confirm="کارمند بازگردانی شود؟">
          <?= csrf_field() ?><button class="btn btn-success">♻️ بازگردانی</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= e(hrm_url('/employees/' . $emp['id'] . '/archive')) ?>" data-confirm="کارمند غیرفعال شود؟">
          <?= csrf_field() ?><button class="btn">⏸ غیرفعال‌سازی</button>
        </form>
        <form method="post" action="<?= e(hrm_url('/employees/' . $emp['id'] . '/delete')) ?>" data-confirm="کارمند حذف (بایگانی) شود؟ قابل بازیابی است.">
          <?= csrf_field() ?><button class="btn btn-danger">🗑 حذف</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- خلاصه KPI -->
<div class="grid grid-4 mb-2">
  <div class="kpi green"><div class="ic">🗓</div><div><b><?= to_fa((string)$summary['att_days']) ?></b><span>حضور این ماه</span></div></div>
  <div class="kpi orange"><div class="ic">⏱</div><div><b><?= to_fa((string)$summary['late']) ?></b><span>تأخیر این ماه</span></div></div>
  <div class="kpi red"><div class="ic">❌</div><div><b><?= to_fa((string)$summary['absent']) ?></b><span>غیبت این ماه</span></div></div>
  <div class="kpi blue"><div class="ic">📅</div><div><b><?= e(jdate($emp['hire_date'])) ?></b><span>تاریخ استخدام</span></div></div>
</div>

<div class="grid grid-3 mb-2">
  <!-- کارت هویتی -->
  <div class="card">
    <div class="card-body text-center">
      <?php if ($emp['photo']): ?>
        <img src="<?= e(hrm_url('/storage/uploads/' . $emp['photo'])) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:24px" alt="">
      <?php else: ?>
        <div class="avatar" style="width:100px;height:100px;font-size:30px;margin:auto"><?= e($initial) ?></div>
      <?php endif; ?>
      <h3 class="mt-1"><?= e(trim($emp['first_name'] . ' ' . $emp['last_name'])) ?></h3>
      <div class="small muted"><?= e($emp['gender'] === 'female' ? 'خانم' : 'آقا') ?> · <?= e(jdate($emp['birth_date'])) ?></div>

      <?php if ($canDocs && !$emp['deleted_at']): ?>
        <form method="post" action="<?= e(hrm_url('/employees/' . $emp['id'] . '/photo')) ?>" enctype="multipart/form-data" class="mt-2 text-right">
          <?= csrf_field() ?>
          <input type="file" name="photo" accept="image/*" required>
          <button class="btn btn-sm btn-primary mt-1 full">📤 بارگذاری عکس</button>
        </form>
      <?php endif; ?>

      <div class="info-list mt-2" style="grid-template-columns:1fr">
        <div class="info-item"><span>کد ملی</span><b class="mono"><?= e($emp['national_code'] ?: '—') ?></b></div>
        <div class="info-item"><span>تلفن</span><b class="mono" dir="ltr"><?= e($emp['phone'] ?: '—') ?></b></div>
        <div class="info-item"><span>ایمیل</span><b class="mono" dir="ltr" style="font-size:12px"><?= e($emp['email'] ?: '—') ?></b></div>
        <div class="info-item"><span>مدیر مستقیم</span><b><?= $manager ? e(trim($manager['first_name'] . ' ' . $manager['last_name'])) : '—' ?></b></div>
      </div>
    </div>
  </div>

  <!-- اطلاعات سازمانی -->
  <div class="card">
    <div class="card-header"><h3>🏢 اطلاعات سازمانی</h3></div>
    <div class="card-body">
      <div class="info-list" style="grid-template-columns:1fr">
        <div class="info-item"><span>واحد</span><b><?= e($dept['name'] ?? '—') ?></b></div>
        <div class="info-item"><span>پست</span><b><?= e($pos['title'] ?? '—') ?></b></div>
        <div class="info-item"><span>شیفت</span><b><?php
          if ($emp['shift_id']) {
              $sh = \App\Core\DB::row('SELECT name, start_time, end_time FROM shifts WHERE id = ?', [$emp['shift_id']]);
              echo $sh ? e($sh['name'] . ' (' . $sh['start_time'] . '-' . $sh['end_time'] . ')') : '—';
          } else echo 'پیش‌فرض سازمان';
        ?></b></div>
        <div class="info-item"><span>تاریخ استخدام</span><b><?= e(jdate($emp['hire_date'])) ?></b></div>
        <div class="info-item"><span>وضعیت</span><b><?= status_badge($emp['status']) ?></b></div>
        <div class="info-item"><span>حقوق پایه</span><b><?= money($emp['salary_base']) ?> تومان</b></div>
      </div>
      <?php if ($emp['notes']): ?>
        <div class="alert alert-info mt-1" style="margin-top:12px"><?= nl2br(e($emp['notes'])) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- قرارداد -->
  <div class="card">
    <div class="card-header"><h3>📝 قرارداد</h3></div>
    <div class="card-body">
      <div class="info-list" style="grid-template-columns:1fr">
        <div class="info-item"><span>نوع</span><b><?= e($contractTypes[$emp['contract_type']] ?? $emp['contract_type']) ?></b></div>
        <div class="info-item"><span>بازه</span><b><?= e(jdate($emp['contract_start'])) ?> — <?= e(jdate($emp['contract_end'])) ?></b></div>
        <div class="info-item"><span>پایان دوره آزمایشی</span><b><?= e(jdate($emp['probation_end'])) ?></b></div>
        <div class="info-item"><span>شماره بانک</span><b class="mono" dir="ltr"><?= e($emp['bank_account'] ?: '—') ?></b></div>
        <div class="info-item"><span>شماره بیمه</span><b class="mono" dir="ltr"><?= e($emp['insurance_no'] ?: '—') ?></b></div>
      </div>
      <?php
      if ($emp['contract_end']) {
          $daysLeft = \App\Core\Jalali::diffDays(date('Y-m-d'), $emp['contract_end']);
          if ($daysLeft <= (int)setting('contract_alert_days', 30) && $daysLeft >= 0) {
              echo '<div class="alert alert-warning" style="margin-top:12px">⏳ قرارداد تا ' . to_fa((string)$daysLeft) . ' روز دیگر پایان می‌یابد.</div>';
          } elseif ($daysLeft < 0) {
              echo '<div class="alert alert-error" style="margin-top:12px">⛔ قرارداد منقضی شده است (' . to_fa((string)abs($daysLeft)) . ' روز پیش).</div>';
          }
      }
      ?>
    </div>
  </div>
</div>

<div class="tabs">
  <a href="#t-att" class="active" data-tab="t-att">🕐 حضور و غیاب</a>
  <a href="#t-leave" data-tab="t-leave">🌴 مرخصی‌ها</a>
  <a href="#t-hist" data-tab="t-hist">🧵 سوابق شغلی</a>
  <a href="#t-doc" data-tab="t-doc">📎 مدارک (<?= to_fa((string)count($docs)) ?>)</a>
  <a href="#t-goal" data-tab="t-goal">🎯 اهداف</a>
  <?php if (Auth::can('payroll.view')): ?><a href="#t-pay" data-tab="t-pay">🧾 حقوق</a><?php endif; ?>
  <a href="#t-note" data-tab="t-note">📝 یادداشت‌ها</a>
</div>

<div id="t-att" class="tab-pane">
  <div class="card"><div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>تاریخ</th><th>ورود</th><th>خروج</th><th>کارکرد</th><th>تأخیر</th><th>وضعیت</th></tr></thead>
      <tbody>
      <?php foreach ($att as $a): ?>
        <tr>
          <td><?= e(jdate($a['date'])) ?> <span class="small muted"><?= e(\App\Core\Jalali::weekday($a['date'])) ?></span></td>
          <td class="mono"><?= e($a['check_in'] ?: '—') ?></td>
          <td class="mono"><?= e($a['check_out'] ?: '—') ?></td>
          <td><?= $a['work_min'] ? to_fa((string)((int)$a['work_min'] / 60 | 0)) . 'h' : '—' ?></td>
          <td><?= $a['late_min'] > 0 ? '<span class="badge badge-warn">' . to_fa((string)$a['late_min']) . ' دقیقه</span>' : '—' ?></td>
          <td><?= status_badge($a['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$att): ?><tr><td colspan="6" class="empty">رکوردی ثبت نشده</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>

<div id="t-leave" class="tab-pane hide">
  <div class="grid grid-2">
    <div class="card">
      <div class="card-header"><h3>موجودی مرخصی (<?= to_fa((string)(int)date('Y')) ?>)</h3></div>
      <div class="card-body">
        <?php foreach ($balances as $b): ?>
          <div class="info-item mb-1">
            <div class="flex justify-between"><b style="color:<?= e($b['color']) ?>"><?= e($b['name']) ?></b>
              <span class="small"><?= to_fa(number_format((float)$b['days_total'] - (float)$b['days_used'], 0)) ?> روز باقی</span></div>
            <?= \App\Core\Charts::progress((float)$b['days_used'], (float)$b['days_total'], $b['color']) ?>
          </div>
        <?php endforeach; ?>
        <?php if (!$balances): ?><div class="empty">موجودی ثبت نشده</div><?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h3>درخواست‌ها</h3></div>
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

<div id="t-hist" class="tab-pane hide">
  <div class="card">
    <div class="card-header"><h3>تایم‌لاین سوابق</h3></div>
    <div class="card-body">
      <div class="timeline">
        <?php foreach ($history as $h): ?>
          <div class="tl-item">
            <b><?= e($h['title']) ?></b>
            <div class="d"><?= e($h['department'] ?: '') ?> · <?= e(jdate($h['from_date'])) ?> تا <?= e($h['to_date'] ? jdate($h['to_date']) : 'اکنون') ?></div>
            <?php if ($h['note']): ?><div class="small"><?= e($h['note']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
        <div class="tl-item"><b>ورود به سازمان</b><div class="d"><?= e(jdate($emp['hire_date'])) ?></div></div>
      </div>

      <?php if ($canEdit && !$emp['deleted_at']): ?>
        <form method="post" action="<?= e(hrm_url('/employees/' . $emp['id'] . '/history')) ?>" class="mt-2">
          <?= csrf_field() ?>
          <div class="form-grid">
            <div class="field"><label>عنوان رویداد <span class="req">*</span></label><input name="title" required placeholder="مثلاً ارتقا به کارشناس ارشد"></div>
            <div class="field"><label>واحد</label><input name="department" placeholder="مثلاً فناوری اطلاعات"></div>
            <div class="field"><label>از تاریخ (شمسی)</label><input name="from_date" dir="rtl"></div>
            <div class="field"><label>تا تاریخ (شمسی، خالی = اکنون)</label><input name="to_date" dir="rtl"></div>
            <div class="field span2"><label>توضیح</label><input name="note"></div>
          </div>
          <button class="btn btn-primary mt-1">➕ ثبت سابقه</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="t-doc" class="tab-pane hide">
  <div class="card">
    <div class="card-header"><h3>مدارک</h3></div>
    <div class="card-body">
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>عنوان</th><th>دسته</th><th>تاریخ انقضا</th><th>فایل</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($docs as $d): ?>
            <tr>
              <td><b><?= e($d['title']) ?></b><?php if ($d['note']): ?><div class="small muted"><?= e($d['note']) ?></div><?php endif; ?></td>
              <td><span class="badge badge-muted"><?= e(['id' => 'هویتی', 'contract' => 'قرارداد', 'education' => 'آموزشی', 'medical' => 'پزشکی', 'other' => 'سایر'][$d['category']] ?? $d['category']) ?></span></td>
              <td class="small"><?= e(jdate($d['expiry_date'])) ?></td>
              <td><?php if ($d['file_path']): ?><a class="btn btn-sm" target="_blank" href="<?= e(hrm_url('/storage/uploads/' . $d['file_path'])) ?>">دانلود</a><?php else: '—' ?><?php endif; ?></td>
              <td class="actions">
                <?php if ($canDocs): ?>
                  <form method="post" action="<?= e(hrm_url('/documents/' . $d['id'] . '/delete')) ?>" data-confirm="مدرک حذف شود؟">
                    <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$docs): ?><tr><td colspan="5" class="empty">مدرکی ثبت نشده</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($canDocs && !$emp['deleted_at']): ?>
        <form method="post" action="<?= e(hrm_url('/documents')) ?>" enctype="multipart/form-data" class="mt-2">
          <?= csrf_field() ?>
          <input type="hidden" name="employee_id" value="<?= (int)$emp['id'] ?>">
          <div class="form-grid">
            <div class="field"><label>عنوان مدرک <span class="req">*</span></label><input name="title" required placeholder="مثلاً گواهی پایان خدمت"></div>
            <div class="field"><label>دسته</label>
              <select name="category">
                <option value="id">هویتی</option><option value="contract">قرارداد</option>
                <option value="education">آموزشی</option><option value="medical">پزشکی</option>
                <option value="other">سایر</option>
              </select></div>
            <div class="field"><label>تاریخ انقضا (شمسی)</label><input name="expiry_date" dir="rtl"></div>
            <div class="field"><label>فایل (PDF/تصویر/Word تا ۸MB)</label><input type="file" name="file"></div>
            <div class="field span2"><label>توضیح</label><input name="note"></div>
          </div>
          <button class="btn btn-primary mt-1">📎 ثبت مدرک</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="t-goal" class="tab-pane hide">
  <div class="card"><div class="card-body">
    <?php if (!$goals): ?><div class="empty"><div class="big">🎯</div>هدفی ثبت نشده — از بخش «عملکرد ← اهداف» اضافه کنید</div><?php endif; ?>
    <?php foreach ($goals as $g): ?>
      <div class="info-item mb-1">
        <div class="flex justify-between"><b><?= e($g['title']) ?></b><span class="badge badge-primary"><?= to_fa(number_format((float)$g['score'], 0)) ?>٪</span></div>
        <div class="small muted"><?= e($g['description']) ?> · وزن: <?= to_fa((string)(int)$g['weight']) ?>٪ · مهلت: <?= e(jdate($g['due_date'])) ?></div>
        <?= \App\Core\Charts::progress((float)$g['score'], 100) ?>
      </div>
    <?php endforeach; ?>
  </div></div>
</div>

<?php if (Auth::can('payroll.view')): ?>
<div id="t-pay" class="tab-pane hide">
  <div class="card">
    <div class="card-header"><h3>فیش‌های اخیر</h3></div>
    <div class="card-body table-wrap">
      <table class="tbl">
        <thead><tr><th>دوره</th><th>درآمد</th><th>کسورات</th><th>خالص</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($slips as $s): ?>
          <tr>
            <td><?= to_fa((string)$s['year']) ?> / <?= to_fa((string)$s['month']) ?></td>
            <td class="num"><?= money($s['gross_earn']) ?></td>
            <td class="num"><?= money($s['total_deduct']) ?></td>
            <td class="num"><b><?= money($s['net_pay']) ?></b></td>
            <td><a class="btn btn-sm" target="_blank" href="<?= e(hrm_url('/payroll/slip/' . $s['id'])) ?>">مشاهده فیش</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$slips): ?><tr><td colspan="5" class="empty">فیشی صادر نشده</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="t-note" class="tab-pane hide">
  <div class="card">
    <div class="card-header"><h3>یادداشت‌های منابع انسانی</h3></div>
    <div class="card-body">
      <?php foreach ($notes as $n): ?>
        <div class="info-item mb-1">
          <div class="flex justify-between"><b><?= e($n['username'] ?? '—') ?></b><span class="small muted"><?= e(jdate(substr($n['created_at'], 0, 10))) ?> <?= e(substr($n['created_at'], 11, 5)) ?></span></div>
          <div class="small"><?= nl2br(e($n['note'])) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$notes): ?><div class="empty">یادداشتی نیست</div><?php endif; ?>

      <?php if (Auth::can('employees.view') && !$emp['deleted_at']): ?>
        <form method="post" action="<?= e(hrm_url('/employees/' . $emp['id'] . '/note')) ?>" class="mt-1">
          <?= csrf_field() ?>
          <div class="field"><label>یادداشت جدید</label><textarea name="note" required rows="2" placeholder="متن یادداشت داخلی…"></textarea></div>
          <button class="btn btn-primary mt-1">ثبت یادداشت</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
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
