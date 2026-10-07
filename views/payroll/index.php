<?php
/**
 * حقوق و دستمزد
 */
use App\Core\Auth;
use App\Core\Jalali;

$monthNames = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
$tab = (string)($_GET['tab'] ?? 'period');
$canFinalize = Auth::can('payroll.finalize');
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← حقوق و دستمزد</div>
    <h1>💰 حقوق و دستمزد</h1>
    <div class="sub">دوره <?= to_fa((string)$year) ?>/<?= to_fa((string)$month) ?> — <?= e($monthNames[$month]) ?> <?= $periodRec ? '· ' . status_badge($periodRec['status'] === 'final' ? 'final' : 'draft') : '· ثبت‌نشده' ?></div>
  </div>
  <div class="flex gap-1 wrap no-print">
    <a class="btn" href="<?= e(hrm_url('/payroll/export')) . '?year=' . $year . '&month=' . $month ?>">📤 CSV</a>
    <button class="btn" data-print>🖨</button>
  </div>
</div>

<div class="grid grid-3 mb-2">
  <div class="kpi green"><div class="ic">📈</div><div><b><?= money($sumGross) ?></b><span>کل درآمد (تومان)</span></div></div>
  <div class="kpi red"><div class="ic">📉</div><div><b><?= money($sumDed) ?></b><span>کل کسورات (تومان)</span></div></div>
  <div class="kpi blue"><div class="ic">🧾</div><div><b><?= money($sumNet) ?></b><span>کل پرداختی خالص (تومان)</span></div></div>
</div>

<div class="tabs no-print">
  <a href="?tab=period" class="<?= $tab === 'period' ? 'active' : '' ?>">دوره و محاسبه</a>
  <a href="?tab=slips" class="<?= $tab === 'slips' ? 'active' : '' ?>">فیش‌ها (<?= to_fa((string)count($slips)) ?>)</a>
  <a href="?tab=components" class="<?= $tab === 'components' ? 'active' : '' ?>">اجزا و تخصیص</a>
  <a href="<?= e(hrm_url('/payroll/loans')) ?>">🏦 وام‌ها</a>
</div>

<form class="filters no-print" method="get" action="<?= e(hrm_url('/payroll')) ?>">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <select name="year">
    <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
      <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= to_fa((string)$y) ?></option>
    <?php endfor; ?>
  </select>
  <select name="month">
    <?php for ($mI = 1; $mI <= 12; $mI++): ?>
      <option value="<?= $mI ?>" <?= $month === $mI ? 'selected' : '' ?>><?= to_fa((string)$mI) ?> — <?= e($monthNames[$mI]) ?></option>
    <?php endfor; ?>
  </select>
  <button class="btn btn-primary">نمایش دوره</button>
</form>

<?php if ($tab === 'period'): ?>
<div class="grid grid-2">
  <div class="card no-print">
    <div class="card-header"><h3>۱) محاسبه دوره</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/payroll/run')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>سال</label><input name="year" dir="ltr" type="number" value="<?= (int)$year ?>"></div>
          <div class="field"><label>ماه</label><input name="month" dir="ltr" type="number" min="1" max="12" value="<?= (int)$month ?>"></div>
          <div class="field span2"><label>کارمند</label>
            <select name="employee_id">
              <option value="0">— همه کارمندان فعال —</option>
              <?php foreach ($emps as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_no']) ?>)</option>
              <?php endforeach; ?>
            </select></div>
        </div>
        <button class="btn btn-primary full mt-1">🧮 محاسبه فیش‌ها</button>
        <div class="small muted mt-1">شامل: حقوق پایه + افزایشی‌ها − بیمه (۹.۶۷٪) − مالیات مصوب − کسورات دیگر</div>
      </form>

      <?php if ($periodRec && $periodRec['status'] === 'draft' && $canFinalize): ?>
        <form method="post" action="<?= e(hrm_url('/payroll/' . $periodRec['id'] . '/finalize')) ?>" class="mt-2"
              data-confirm="دوره نهایی شود؟ پس از نهایی‌سازی فیش‌ها قفل انتشار می‌شوند.">
          <?= csrf_field() ?>
          <button class="btn btn-success full">🔒 نهایی‌سازی دوره</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card no-print">
    <div class="card-header"><h3>۲) کپی از دوره قبل</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/payroll/copy')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>از سال</label><input name="from_year" dir="ltr" type="number" value="<?= (int)$year ?>"></div>
          <div class="field"><label>از ماه</label><input name="from_month" dir="ltr" type="number" min="1" max="12" value="<?= max(1, (int)$month - 1) ?>"></div>
          <div class="field"><label>به سال</label><input name="to_year" dir="ltr" type="number" value="<?= (int)$year ?>"></div>
          <div class="field"><label>به ماه</label><input name="to_month" dir="ltr" type="number" min="1" max="12" value="<?= (int)$month ?>"></div>
        </div>
        <button class="btn full mt-1">📋 کپی فیش‌های دوره مبدأ</button>
        <div class="small muted mt-1">فیش‌های موجود مقصد پرش نمی‌شوند؛ ابتدا محاسبه کنید سپس تغییر دهید.</div>
      </form>
    </div>
  </div>
</div>

<?php elseif ($tab === 'slips'): ?>
<div class="card">
  <div class="card-header"><h3>فیش‌های دوره <?= to_fa((string)$year) ?>/<?= to_fa((string)$month) ?></h3></div>
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>کد</th><th>نام</th><th>درآمد</th><th>کسورات</th><th>خالص</th><th>وضعیت</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($slips as $s): ?>
        <tr>
          <td class="mono small"><?= e($s['emp_no']) ?></td>
          <td><?= e(trim($s['first_name'] . ' ' . $s['last_name'])) ?></td>
          <td class="num"><?= money($s['gross_earn']) ?></td>
          <td class="num"><?= money($s['total_deduct']) ?></td>
          <td class="num"><b><?= money($s['net_pay']) ?></b></td>
          <td><?= status_badge($s['period_status']) ?></td>
          <td class="actions">
            <a class="btn btn-sm" target="_blank" href="<?= e(hrm_url('/payroll/slip/' . $s['id'])) ?>">📄 فیش</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$slips): ?><tr><td colspan="7" class="empty"><div class="big">💰</div>هنوز فیشی محاسبه نشده — به تب «دوره و محاسبه» بروید</div></td><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<div class="grid grid-3">
  <div class="card no-print">
    <div class="card-header"><h3>افزودن جزو</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/payroll/components')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>عنوان <span class="req">*</span></label><input name="title" required placeholder="مثلاً بن کارکنان"></div>
        <div class="field mb-1"><label>نوع</label>
          <select name="kind"><option value="earning">افزایشی</option><option value="deduction">کاهشی</option></select></div>
        <div class="field mb-1"><label>نحوه محاسبه</label>
          <select name="calc_type"><option value="fixed">مبلغ ثابت</option><option value="percent">درصدی</option></select></div>
        <div class="field mb-1"><label>نرخ/مبلغ پیش‌فرض</label><input name="rate" dir="ltr" type="number" step="0.01" value="0"></div>
        <div class="field mb-1"><label>فرمول (اختیاری)</label><input name="formula" dir="ltr" placeholder="base * 0.10"></div>
        <button class="btn btn-primary full">افزودن جزو</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3>اجزای حقوقی</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>عنوان</th><th>نوع</th><th>نحوه</th><th>نرخ</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($components as $c): ?>
            <tr>
              <td><b><?= e($c['name']) ?></b> <span class="small muted mono"><?= e($c['code']) ?></span></td>
              <td><span class="badge <?= $c['type'] === 'earning' ? 'badge-success' : 'badge-warn' ?>"><?= ['earning' => 'افزایشی', 'deduction' => 'کاهشی'][$c['type']] ?? $c['type'] ?></span><?= (int)$c['is_system'] ? ' <span class="badge badge-muted">سیستمی</span>' : '' ?></td>
              <td class="small mono" dir="ltr"><?= e($c['calc_type']) ?></td>
              <td class="num mono"><?= money($c['default_amount']) ?></td>
              <td class="actions">
                <?php if (!(int)$c['is_system']): ?>
                  <form method="post" action="<?= e(hrm_url('/payroll/components/' . $c['id'] . '/delete')) ?>" data-confirm="حذف جزو؟">
                    <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
                  </form>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mt-2">
      <div class="card-header"><h3>تخصیص جزو به کارمند</h3></div>
      <div class="card-body">
        <form method="post" action="<?= e(hrm_url('/payroll/components/assign')) ?>" class="flex gap-1 wrap items-end">
          <?= csrf_field() ?>
          <div class="field" style="min-width:180px"><label>کارمند</label>
            <select name="employee_id" required>
              <?php foreach ($emps as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field" style="min-width:160px"><label>جزو</label>
            <select name="component_id" required>
              <?php foreach ($components as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field" style="width:130px"><label>مبلغ/درصد</label><input name="value" dir="ltr" type="number" step="0.01" value="0"></div>
          <button class="btn btn-primary">ثبت تخصیص</button>
        </form>

        <?php if (!empty($assignments)): ?>
          <div class="table-wrap mt-2">
            <table class="tbl">
              <thead><tr><th>کارمند</th><th>جزو</th><th>مبلغ/درصد</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($assignments as $as): ?>
                <tr>
                  <td class="small"><?= e(trim($as['first_name'] . ' ' . $as['last_name'])) ?> <span class="muted mono"><?= e($as['emp_no']) ?></span></td>
                  <td class="small"><?= e($as['name']) ?></td>
                  <td class="num mono"><?= money($as['amount']) ?></td>
                  <td class="actions">
                    <form method="post" action="<?= e(hrm_url('/payroll/assignments/' . $as['id'] . '/delete')) ?>" data-confirm="تخصیص حذف شود؟">
                      <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
