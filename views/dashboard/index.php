<?php
/**
 * داشبورد اصلی
 * @var ?int $totalEmp @var ?int $presentNow ...
 */
use App\Core\Auth;

$canSeeOrg = isset($totalEmp);
$user = Auth::user();
?>
<div class="page-head">
  <div>
    <h1>داشبورد 👋</h1>
    <div class="sub">سلام <?= e(trim(($user['first_name'] ?? '') ?: ($user['username'] ?? ''))) ?>، روز خوبی داشته باشید · <?= to_fa(today_j()) ?> (<?= e(\App\Core\Jalali::weekday(date('Y-m-d'))) ?>)</div>
  </div>
  <div class="flex gap-1 wrap no-print">
    <?php if (can('employees.create')): ?><a class="btn btn-primary" href="<?= e(hrm_url('/employees/create')) ?>">➕ افزودن کارمند</a><?php endif; ?>
    <a class="btn" href="<?= e(hrm_url('/about')) ?>">📖 راهنما</a>
  </div>
</div>

<?php if ($canSeeOrg): ?>
<!-- KPI -->
<div class="grid grid-4 mb-2">
  <div class="kpi blue">
    <div class="ic">👥</div>
    <div><b><?= to_fa((string)$totalEmp) ?></b><span>کارمند فعال</span></div>
    <span class="trend up">+<?= to_fa((string)$newMonth) ?> این ماه</span>
  </div>
  <div class="kpi green">
    <div class="ic">✅</div>
    <div><b><?= to_fa((string)$presentNow) ?></b><span>حاضرر امروز</span></div>
  </div>
  <div class="kpi violet">
    <div class="ic">🌴</div>
    <div><b><?= to_fa((string)$leaveToday) ?></b><span>در مرخصی امروز</span></div>
  </div>
  <div class="kpi red">
    <div class="ic">❌</div>
    <div><b><?= to_fa((string)$absentToday) ?></b><span>غایب امروز</span></div>
  </div>
</div>

<div class="grid grid-4 mb-2">
  <div class="kpi orange">
    <div class="ic">⏳</div>
    <div><b><?= to_fa((string)$pendingLeave) ?></b><span>مرخصی در انتظار تأیید</span></div>
  </div>
  <div class="kpi cyan">
    <div class="ic">📨</div>
    <div><b><?= to_fa((string)$pendingReq) ?></b><span>درخواست در انتظار</span></div>
  </div>
  <div class="kpi blue">
    <div class="ic">📢</div>
    <div><b><?= to_fa((string)$openJobs) ?></b><span>آگهی شغلی باز</span></div>
  </div>
  <div class="kpi violet">
    <div class="ic">🧑‍💼</div>
    <div><b><?= to_fa((string)$applicants) ?></b><span>داوطلب فعال</span></div>
  </div>
</div>

<div class="grid grid-2 mb-2">
  <div class="card">
    <div class="card-header"><h3>📊 توزیع نیروی انسانی بر اساس واحد</h3></div>
    <div class="card-body"><?= $deptChart ?></div>
  </div>
  <div class="card">
    <div class="card-header"><h3>📈 روند حضور ۱۴ روز اخیر</h3></div>
    <div class="card-body"><?= $trendChart ?></div>
  </div>
</div>

<div class="grid grid-3 mb-2">
  <div class="card">
    <div class="card-header"><h3>⚤ ترکیب جنسیت</h3></div>
    <div class="card-body"><?= $genderChart ?></div>
  </div>

  <div class="card">
    <div class="card-header"><h3>📝 قراردادهای رو به اتمام</h3>
      <span class="badge badge-warn"><?= to_fa((string)count($expiring)) ?> مورد</span>
    </div>
    <div class="card-body flush">
      <?php if (!$expiring): ?>
        <div class="empty"><div class="big">🎉</div>قراردادی تا ۳۰ روز آینده تمام نمی‌شود</div>
      <?php else: ?>
        <div class="table-wrap"><table class="tbl">
          <thead><tr><th>کارمند</th><th>پایان قرارداد</th></tr></thead>
          <tbody>
          <?php foreach ($expiring as $x): ?>
            <tr>
              <td><a class="row-link" href="<?= e(hrm_url('/employees/' . $x['id'])) ?>"><?= e(trim($x['first_name'] . ' ' . $x['last_name'])) ?></a>
                  <div class="small muted"><?= e($x['emp_no']) ?></div></td>
              <td><span class="badge badge-warn"><?= e(jdate($x['contract_end'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>🎂 تولد امروز</h3></div>
    <div class="card-body flush">
      <?php if (!$birthdays): ?>
        <div class="empty"><div class="big">🎈</div>امروز تولدی نیست</div>
      <?php else: ?>
        <?php foreach ($birthdays as $b): ?>
          <div class="notif-item" style="display:flex;gap:10px;align-items:center;border-bottom:1px solid var(--border)">
            <div class="avatar" style="width:34px;height:34px"><?= e(mb_substr($b['first_name'], 0, 1)) ?></div>
            <div><b><?= e(trim($b['first_name'] . ' ' . $b['last_name'])) ?></b><span>تبریک صمیمی 🎁</span></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="grid grid-2 mb-2">
  <div class="card">
    <div class="card-header">
      <h3>🌴 مرخصی‌های در انتظار تأیید</h3>
      <?php if (can('leave.approve')): ?><a class="btn btn-sm" href="<?= e(hrm_url('/leave?tab=approve')) ?>">همه ←</a><?php endif; ?>
    </div>
    <div class="card-body flush">
      <?php if (!$pendingLeaves): ?>
        <div class="empty"><div class="big">✅</div>درخواست مرخصی معلقی نیست</div>
      <?php else: ?>
        <div class="table-wrap"><table class="tbl">
          <thead><tr><th>کارمند</th><th>نوع</th><th>بازه</th><th>روز</th></tr></thead>
          <tbody>
          <?php foreach ($pendingLeaves as $lv): ?>
            <tr>
              <td><a class="row-link" href="<?= e(hrm_url('/employees/' . $lv['employee_id'])) ?>"><?= e(trim($lv['first_name'] . ' ' . $lv['last_name'])) ?></a></td>
              <td><span class="badge" style="background:<?= e($lv['color']) ?>22;color:<?= e($lv['color']) ?>"><?= e($lv['type_name']) ?></span></td>
              <td class="small"><?= e(jdate($lv['from_date'])) ?> — <?= e(jdate($lv['to_date'])) ?></td>
              <td><?= to_fa((string)$lv['days']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>📜 آخرین فعالیت‌ها</h3></div>
    <div class="card-body">
      <div class="timeline">
        <?php foreach (array_slice($activities, 0, 7) as $a): ?>
          <div class="tl-item">
            <b><?= e($a['message'] ?: $a['action']) ?></b>
            <div class="d"><?= e($a['username'] ?? 'سیستم') ?> · <?= e(jdate(substr($a['created_at'], 0, 10))) ?> <?= e(substr($a['created_at'], 11, 5)) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$activities): ?><div class="empty">فعالیتی ثبت نشده</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (isset($myAttMonth)): ?>
<!-- خلاصه شخصی -->
<div class="grid grid-4 mb-2">
  <div class="kpi green"><div class="ic">🗓</div><div><b><?= to_fa((string)$myAttMonth) ?></b><span>حضور این ماه</span></div></div>
  <div class="kpi orange"><div class="ic">⏱</div><div><b><?= to_fa((string)$myLateMonth) ?></b><span>تأخیر این ماه</span></div></div>
  <div class="kpi blue"><div class="ic">🌴</div><div><b><?= to_fa(number_format($myLeaveLeft, 0)) ?></b><span>مرخصی باقی‌مانده</span></div></div>
  <div class="kpi violet"><div class="ic">📨</div><div><b><?= to_fa((string)$myPendingLeave) ?></b><span>درخواست معلق من</span></div></div>
</div>
<div class="flex gap-1 wrap mb-2 no-print">
  <a class="btn btn-primary" href="<?= e(hrm_url('/my')) ?>">🗂 پرونده کاری من</a>
  <a class="btn" href="<?= e(hrm_url('/leave')) ?>">درخواست مرخصی</a>
  <a class="btn" href="<?= e(hrm_url('/attendance/my')) ?>">حضور من</a>
  <a class="btn" href="<?= e(hrm_url('/requests')) ?>">درخواست‌های من</a>
</div>
<?php endif; ?>
