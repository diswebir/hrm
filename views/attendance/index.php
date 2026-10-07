<?php
/**
 * فهرست حضور و غیاب
 */
$fs = $filters;
$statLabels = ['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'leave' => 'مرخصی', 'remote' => 'دورکاری', 'trip' => 'مأموریت', 'holiday' => 'تعطیل'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← حضور و غیاب</div>
    <h1>🕐 حضور و غیاب</h1>
    <div class="sub">بازه: <?= e(jdate($fs['from'])) ?> تا <?= e(jdate($fs['to'])) ?> — <?= to_fa((string)$total) ?> رکورد</div>
  </div>
  <div class="flex gap-1 wrap no-print">
    <button class="btn" data-print>🖨 چاپ</button>
    <a class="btn" href="<?= e(hrm_url('/attendance/export')) . '?' . e(http_build_query($fs)) ?>">📤 خروجی CSV</a>
    <button class="btn btn-primary" data-modal-open="m-add">➕ ثبت رکورد</button>
  </div>
</div>

<div class="grid grid-4 mb-2">
  <?php
  $cards = [
      ['present' => ['حاضر', 'green', '✅'], 'late' => ['تأخیر', 'orange', '⏱']],
      ['absent' => ['غایب', 'red', '❌'], 'leave' => ['در مرخصی', 'blue', '🌴']],
      ['remote' => ['دورکاری', 'blue', '🏠'], 'trip' => ['مأموریت', 'orange', '🚗']],
      ['holiday' => ['تعطیل رسمی', 'muted', '🎉'], 'work' => ['کل رکوردها', 'muted', '📋']],
  ];
  foreach ($cards as $pair):
      $idx = 0;
      foreach ($pair as $k => $meta):
          $cnt = $k === 'work' ? $total : $statMap[$k];
  ?>
    <div class="kpi <?= e($meta[1]) ?>"><div class="ic"><?= e($meta[2]) ?></div><div><b><?= to_fa((string)$cnt) ?></b><span><?= e($meta[0]) ?></span></div></div>
  <?php endforeach; endforeach; ?>
</div>

<form class="filters no-print" method="get" action="<?= e(hrm_url('/attendance')) ?>">
  <label class="small muted">از</label>
  <input type="date" name="from" value="<?= e($fs['from']) ?>">
  <label class="small muted">تا</label>
  <input type="date" name="to" value="<?= e($fs['to']) ?>">
  <input type="search" name="q" value="<?= e($fs['q']) ?>" placeholder="🔎 نام / کد پرسنلی…">
  <select name="dept">
    <option value="0">همه واحدها</option>
    <?php foreach ($depts as $d): ?>
      <option value="<?= (int)$d['id'] ?>" <?= $fs['dept'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">همه وضعیت‌ها</option>
    <?php foreach ($statLabels as $k => $lbl): ?>
      <option value="<?= $k ?>" <?= $fs['status'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary">اعمال</button>
  <a class="btn btn-ghost" href="<?= e(hrm_url('/attendance')) ?>">پاک‌سازی</a>
</form>

<div class="card">
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead>
        <tr><th>تاریخ</th><th>کارمند</th><th>ورود</th><th>خروج</th><th>کارکرد</th><th>تأخیر</th><th>وضعیت</th><th>منبع</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="small"><?= e(jdate($r['date'])) ?><div class="muted"><?= e(\App\Core\Jalali::weekday($r['date'])) ?></div></td>
          <td>
            <a class="row-link" href="<?= e(hrm_url('/employees/' . $r['employee_id'])) ?>"><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?></a>
            <div class="small muted mono"><?= e($r['emp_no']) ?> · <?= e($r['dept_name'] ?? '') ?></div>
          </td>
          <td class="mono"><?= e($r['check_in'] ?: '—') ?></td>
          <td class="mono"><?= e($r['check_out'] ?: '—') ?></td>
          <td class="mono small"><?php
            if ($r['work_min']) {
                echo to_fa((string)intdiv((int)$r['work_min'], 60)) . ':' . to_fa(str_pad((string)(((int)$r['work_min']) % 60), 2, '0', STR_PAD_LEFT));
            } else echo '—';
          ?></td>
          <td><?= $r['late_min'] > 0 ? '<span class="badge badge-warn">' . to_fa((string)$r['late_min']) . '′</span>' : '—' ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td class="small muted"><?= ['manual' => 'دستی', 'check' => 'ساعت زن', 'auto' => 'خودکار'][$r['source']] ?? $r['source'] ?></td>
          <td class="actions">
            <?php if ($r['note']): ?><span title="<?= e($r['note']) ?>">📝</span><?php endif; ?>
            <form method="post" action="<?= e(hrm_url('/attendance/' . $r['id'] . '/delete')) . '?' . e(http_build_query($fs)) ?>" data-confirm="رکورد حذف شود؟">
              <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="9" class="empty"><div class="big">🕐</div>رکوردی در این بازه نیست</div></td><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $pager ?>
</div>

<!-- مودال ثبت -->
<div class="modal" id="m-add">
  <div class="box" style="max-width:620px">
    <div class="modal-head"><h3>➕ ثبت رکورد حضور</h3><button type="button" class="modal-close" data-modal-close>✕</button></div>
    <form method="post" action="<?= e(hrm_url('/attendance/save')) ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field span2"><label>کارمند <span class="req">*</span></label>
          <select name="employee_id" required>
            <option value="">— انتخاب —</option>
            <?php foreach ($employees as $emp): ?>
              <option value="<?= (int)$emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_no']) ?>)</option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>تاریخ (میلادی از تقویم) <span class="req">*</span></label>
          <input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" required></div>
        <div class="field"><label>وضعیت خاص (بدون ساعت)</label>
          <select name="status">
            <option value="">خودکار از روی ساعت</option>
            <option value="absent">غایب</option>
            <option value="leave">در مرخصی</option>
            <option value="trip">مأموریت</option>
            <option value="remote">دورکاری</option>
            <option value="holiday">تعطیل</option>
          </select></div>
        <div class="field"><label>ساعت ورود</label><input type="time" name="check_in"></div>
        <div class="field"><label>ساعت خروج</label><input type="time" name="check_out"></div>
        <div class="field span2"><label>توضیح</label><input name="note" placeholder="اختیاری"></div>
      </div>
      <button class="btn btn-primary full mt-1">ذخیره رکورد</button>
    </form>
  </div>
</div>
