<?php
/**
 * جذب و استخدام
 */
$tab = (string)($_GET['tab'] ?? 'jobs');
$stageBadge = ['new' => 'badge-info', 'screening' => 'badge-info', 'interview' => 'badge-warn',
               'offer' => 'badge-primary', 'hired' => 'badge-success', 'rejected' => 'badge-danger'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← جذب و استخدام</div>
    <h1>🎯 جذب و استخدام (ATS)</h1>
    <div class="sub">مدیریت آگهی، متقاضیان و مصاحبه‌ها</div>
  </div>
</div>

<div class="grid grid-4 mb-2">
  <div class="kpi blue"><div class="ic">📢</div><div><b><?= to_fa((string)$stats['open']) ?></b><span>آگهی باز از <?= to_fa((string)$stats['all']) ?></span></div></div>
  <div class="kpi orange"><div class="ic">🧑‍💼</div><div><b><?= to_fa((string)$stats['applicants']) ?></b><span>متقاضی</span></div></div>
  <div class="kpi purple"><div class="ic">🗣</div><div><b><?= to_fa((string)$stats['interviews']) ?></b><span>مصاحبه در جریان</span></div></div>
  <div class="kpi green"><div class="ic">🎉</div><div><b><?= to_fa((string)$stats['hired']) ?></b><span>استخدام‌شده</span></div></div>
</div>

<div class="tabs">
  <a href="?tab=jobs" class="<?= $tab === 'jobs' ? 'active' : '' ?>">آگهی‌ها</a>
  <a href="?tab=applicants" class="<?= $tab === 'applicants' ? 'active' : '' ?>">متقاضیان و خط لوله</a>
  <a href="?tab=interviews" class="<?= $tab === 'interviews' ? 'active' : '' ?>">مصاحبه‌ها</a>
</div>

<?php if ($tab === 'jobs'): ?>
<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>➕ آگهی جدید</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/recruit/jobs')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>عنوان شغل <span class="req">*</span></label><input name="title" required placeholder="مثلاً کارشناس فروش"></div>
        <div class="field mb-1"><label>واحد</label>
          <select name="dept_id"><option value="0">—</option>
            <?php foreach (\App\Core\DB::all('SELECT id, name FROM departments ORDER BY name') as $d): ?>
              <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field mb-1"><label>شرح شغل</label><textarea name="description" rows="2"></textarea></div>
        <div class="field mb-1"><label>الزامات</label><textarea name="requirements" rows="2"></textarea></div>
        <div class="form-grid">
          <div class="field"><label>حداقل حقوق</label><input name="salary_min" dir="ltr" type="number" value="0"></div>
          <div class="field"><label>حداکثر حقوق</label><input name="salary_max" dir="ltr" type="number" value="0"></div>
          <div class="field"><label>تعداد نیرو</label><input name="openings" dir="ltr" type="number" min="1" value="1"></div>
        </div>
        <button class="btn btn-primary full mt-1">ایجاد آگهی</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3>آگهی‌ها</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>عنوان</th><th>واحد</th><th>متقاضیان</th><th>وضعیت</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($jobs as $j): ?>
            <tr>
              <td><b><?= e($j['title']) ?></b><div class="small muted" style="max-width:320px"><?= e(mb_strimwidth((string)$j['description'], 0, 70, '…')) ?></div></td>
              <td class="small"><?= e($j['dept_name'] ?? '—') ?></td>
              <td class="num"><?= to_fa((string)$j['app_count']) ?></td>
              <td><?= status_badge($j['status']) ?></td>
              <td class="actions">
                <form method="post" action="<?= e(hrm_url('/recruit/jobs/' . $j['id'] . '/toggle')) ?>" data-confirm="<?= $j['status'] === 'open' ? 'آگهی بسته شود؟' : 'آگهی منتشر شود؟' ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm <?= $j['status'] === 'open' ? '' : 'btn-success' ?>"><?= $j['status'] === 'open' ? '⏸ بستن' : '🚀 انتشار' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$jobs): ?><tr><td colspan="5" class="empty">آگهی‌ای نیست</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mt-2">
      <div class="card-header"><h3>🧑‍💼 ثبت متقاضی (فرم دستی)</h3></div>
      <div class="card-body">
        <form method="post" action="<?= e(hrm_url('/recruit/applicants')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="form-grid">
            <div class="field"><label>نام کامل <span class="req">*</span></label><input name="full_name" required></div>
            <div class="field"><label>تلفن <span class="req">*</span></label><input name="phone" dir="ltr" placeholder="0912..." required></div>
            <div class="field"><label>ایمیل</label><input name="email" dir="ltr"></div>
            <div class="field"><label>برای آگهی</label>
              <select name="job_id"><option value="0">— عمومی —</option>
                <?php foreach ($jobs as $j): ?><option value="<?= (int)$j['id'] ?>"><?= e($j['title']) ?></option><?php endforeach; ?>
              </select></div>
            <div class="field"><label>حقوق مورد انتظار</label><input name="expected_pay" dir="ltr" type="number"></div>
            <div class="field"><label>منبع</label><input name="source" placeholder="سایت، معرفی، …"></div>
            <div class="field"><label>رزومه (PDF/Word تا ۵MB)</label><input type="file" name="resume"></div>
            <div class="field"><label>یادداشت</label><input name="notes"></div>
          </div>
          <button class="btn btn-primary mt-1">ثبت متقاضی</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'applicants'): ?>
<div class="card">
  <div class="card-header"><h3>خط لوله متقاضیان — کارت را با دکمه‌ها جلو ببرید</h3></div>
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>نام</th><th>آگهی</th><th>تلفن</th><th>انتظار حقوق</th><th>مرحله</th><th>اقدام سریع</th><th>رزومه</th></tr></thead>
      <tbody>
      <?php foreach ($applicants as $a): ?>
        <tr>
          <td><b><?= e($a['full_name']) ?></b><div class="small muted"><?= e($a['source']) ?></div></td>
          <td class="small"><?= e($a['job_title'] ?: '—') ?></td>
          <td class="mono small" dir="ltr"><?= e($a['phone']) ?></td>
          <td class="num small"><?= $a['expected_pay'] ? money($a['expected_pay']) : '—' ?></td>
          <td><span class="badge <?= $stageBadge[$a['stage']] ?? 'badge-muted' ?>"><?= e($stages[$a['stage']] ?? $a['stage']) ?></span></td>
          <td class="actions">
            <?php if ($a['stage'] !== 'hired' && $a['stage'] !== 'rejected'): ?>
              <form method="post" action="<?= e(hrm_url('/recruit/applicants/' . $a['id'] . '/stage')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="stage" value="<?= e($next = array_values(array_keys($stages))[min(array_search($a['stage'], array_keys($stages), true) + 1, count($stages) - 1)]) ?>">
                <button class="btn btn-sm btn-primary">→ جلو</button>
              </form>
              <form method="post" action="<?= e(hrm_url('/recruit/applicants/' . $a['id'] . '/stage')) ?>" data-confirm="رد شود؟">
                <?= csrf_field() ?><input type="hidden" name="stage" value="rejected"><button class="btn btn-sm btn-danger">✕</button>
              </form>
            <?php elseif ($a['stage'] === 'hired'): ?>
              <a class="btn btn-sm btn-success" href="<?= e(hrm_url('/employees/create')) ?>">➕ پرونده کارمندی</a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?php if ($a['resume_path']): ?><a class="btn btn-sm" target="_blank" href="<?= e(hrm_url('/storage/uploads/' . $a['resume_path'])) ?>">📄</a><?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$applicants): ?><tr><td colspan="7" class="empty"><div class="big">🧑‍💼</div>متقاضی‌ای ثبت نشده</div></td><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>🗓 ثبت مصاحبه</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/recruit/interviews')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>متقاضی</label>
          <select name="applicant_id" required>
            <?php foreach ($applicants as $a): if ($a['stage'] === 'rejected') continue; ?>
              <option value="<?= (int)$a['id'] ?>"><?= e($a['full_name']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field mb-1"><label>زمان (شمسی) <span class="req">*</span></label><input name="interview_at" dir="rtl" placeholder="1404/08/20 10:30" required></div>
        <div class="field mb-1"><label>شیوه</label>
          <select name="mode"><option value="in_person">حضوری</option><option value="online">آنلاین</option><option value="phone">تلفنی</option></select></div>
        <div class="field mb-1"><label>امتیاز (۱–۵)</label>
          <select name="score"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= $i === 3 ? 'selected' : '' ?>><?= to_fa((string)$i) ?></option><?php endfor; ?></select></div>
        <div class="field mb-1"><label>نتیجه</label>
          <select name="result"><option value="">هنوز</option><option value="passed">قبول</option><option value="failed">رد</option></select></div>
        <div class="field mb-1"><label>یادداشت</label><textarea name="notes" rows="2"></textarea></div>
        <button class="btn btn-primary full">ثبت مصاحبه</button>
      </form>
    </div>
  </div>
  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3>مصاحبه‌ها</h3></div>
      <div class="card-body table-wrap">
        <table class="tbl">
          <thead><tr><th>متقاضی</th><th>زمان</th><th>مصاحبه‌کننده</th><th>شیوه</th><th>امتیاز</th><th>نتیجه</th></tr></thead>
          <tbody>
          <?php foreach ($interviews as $i): ?>
            <tr>
              <td><b><?= e($i['full_name']) ?></b></td>
              <td class="small mono" dir="ltr"><?= e($i['interview_at']) ?></td>
              <td class="small"><?= e($i['interviewer'] ?: '—') ?></td>
              <td class="small"><?= ['in_person' => 'حضوری', 'online' => 'آنلاین', 'phone' => 'تلفنی'][$i['mode']] ?? $i['mode'] ?></td>
              <td><?= str_repeat('⭐', (int)$i['score']) ?></td>
              <td><?= $i['result'] === 'passed' ? '<span class="badge badge-success">قبول</span>' : ($i['result'] === 'failed' ? '<span class="badge badge-danger">رد</span>' : '<span class="badge badge-muted">—</span>') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$interviews): ?><tr><td colspan="6" class="empty">مصاحبه‌ای ثبت نشده</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
