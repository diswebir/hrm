<?php
/**
 * آموزش
 */
$tab = (string)($_GET['tab'] ?? 'courses');
$stStatus = ['planned' => 'برنامه‌ریزی‌شده', 'running' => 'در حال اجرا', 'done' => 'پایان‌یافته'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← آموزش</div>
    <h1>🎓 آموزش</h1>
    <div class="sub">دوره‌ها، ثبت‌نام‌ها و گواهینامه‌ها</div>
  </div>
</div>

<div class="grid grid-4 mb-2">
  <div class="kpi blue"><div class="ic">📚</div><div><b><?= to_fa((string)$stats['courses']) ?></b><span>کل دوره‌ها</span></div></div>
  <div class="kpi orange"><div class="ic">▶️</div><div><b><?= to_fa((string)$stats['active']) ?></b><span>در حال اجرا</span></div></div>
  <div class="kpi purple"><div class="ic">👥</div><div><b><?= to_fa((string)$stats['enrolled']) ?></b><span>ثبت‌نام</span></div></div>
  <div class="kpi green"><div class="ic">🏅</div><div><b><?= to_fa((string)$stats['passed']) ?></b><span>قبول‌شده با گواهی</span></div></div>
</div>

<div class="tabs">
  <a href="?tab=courses" class="<?= $tab === 'courses' ? 'active' : '' ?>">دوره‌ها</a>
  <a href="?tab=enrollments" class="<?= $tab === 'enrollments' ? 'active' : '' ?>">ثبت‌نام‌ها</a>
</div>

<?php if ($tab === 'courses'): ?>
<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>➕ دوره جدید</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/training/courses')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>عنوان <span class="req">*</span></label><input name="title" required placeholder="مثلاً دوره ایمنی اطلاعات"></div>
        <div class="field mb-1"><label>برگزارکننده</label><input name="provider" placeholder="داخلی یا آموزشگاه…"></div>
        <div class="form-grid">
          <div class="field"><label>شروع (شمسی) <span class="req">*</span></label><input name="start_date" dir="rtl" placeholder="1404/08/15" required></div>
          <div class="field"><label>پایان (شمسی)</label><input name="end_date" dir="rtl"></div>
          <div class="field"><label>ظرفیت</label><input name="seats" dir="ltr" type="number" min="1" value="20"></div>
          <div class="field"><label>هزینه (تومان)</label><input name="cost" dir="ltr" type="number" min="0" value="0"></div>
          <div class="field"><label>وضعیت</label>
            <select name="status"><?php foreach ($stStatus as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label>یادداشت</label><input name="note"></div>
        </div>
        <button class="btn btn-primary full mt-1">ایجاد دوره</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3>دوره‌ها</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>عنوان</th><th>برگزارکننده</th><th>بازه</th><th>ثبت‌نام</th><th>هزینه</th><th>وضعیت</th></tr></thead>
          <tbody>
          <?php foreach ($courses as $c): ?>
            <tr>
              <td><b><?= e($c['title']) ?></b><?= $c['note'] ? '<div class="small muted">' . e($c['note']) . '</div>' : '' ?></td>
              <td class="small"><?= e($c['provider'] ?: '—') ?></td>
              <td class="small mono" dir="rtl"><?= e(jdate($c['start_date'])) ?> — <?= e(jdate($c['end_date'])) ?></td>
              <td class="num"><?= to_fa((string)$c['enrolled']) ?> / <?= to_fa((string)$c['seats']) ?></td>
              <td class="num small"><?= $c['cost'] ? money($c['cost']) : '—' ?></td>
              <td><?= status_badge($c['status'] === 'running' ? 'active' : ($c['status'] === 'done' ? 'final' : 'draft')) ?><div class="small muted"><?= e($stStatus[$c['status']]) ?></div></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$courses): ?><tr><td colspan="6" class="empty">دوره‌ای نیست</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>📝 ثبت‌نام کارمند</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/training/enroll')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>دوره</label>
          <select name="course_id" required>
            <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?> (<?= to_fa((string)$c['enrolled']) ?>/<?= to_fa((string)$c['seats']) ?>)</option><?php endforeach; ?>
          </select></div>
        <div class="field mb-1"><label>کارمند</label>
          <select name="employee_id" required>
            <?php foreach ($emps as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
          </select></div>
        <button class="btn btn-primary full">ثبت‌نام</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3>ثبت‌نام‌ها</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>کارمند</th><th>دوره</th><th>وضعیت</th><th>نمره</th><th>گواهی</th><th>اقدام</th></tr></thead>
          <tbody>
          <?php foreach ($enrollments as $en): ?>
            <tr>
              <td class="small"><?= e(trim($en['first_name'] . ' ' . $en['last_name'])) ?><div class="muted mono"><?= e($en['emp_no']) ?></div></td>
              <td class="small"><?= e($en['course_title']) ?></td>
              <td><?= ['enrolled' => '<span class="badge badge-info">ثبت‌نام</span>',
                       'passed' => '<span class="badge badge-success">قبول</span>',
                       'failed' => '<span class="badge badge-danger">مردود</span>'][$en['status']] ?? e($en['status']) ?></td>
              <td class="num"><?= $en['score'] > 0 ? to_fa(number_format((float)$en['score'], 0)) : '—' ?></td>
              <td class="small mono"><?= e($en['cert_no'] ?: '—') ?></td>
              <td class="actions">
                <?php if ($en['status'] === 'enrolled'): ?>
                  <form method="post" action="<?= e(hrm_url('/training/enrollments/' . $en['id'] . '/complete')) ?>" class="flex gap-1">
                    <?= csrf_field() ?>
                    <input name="score" type="number" min="0" max="100" value="70" style="width:74px" dir="ltr">
                    <button class="btn btn-sm btn-success" name="result" value="passed">✅ قبول</button>
                    <button class="btn btn-sm btn-danger" name="result" value="failed">✗ مردودی</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="<?= e(hrm_url('/training/enrollments/' . $en['id'] . '/delete')) ?>" data-confirm="ثبت‌نام حذف شود؟">
                    <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$enrollments): ?><tr><td colspan="6" class="empty">ثبت‌نامی نیست</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
