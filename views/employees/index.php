<?php
/**
 * فهرست کارمندان
 */
use App\Core\Auth;

$f = $filters;
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← سازمان ← کارمندان</div>
    <h1>👥 فهرست کارمندان</h1>
    <div class="sub"><?= to_fa((string)$total) ?> نتیجه با فیلترهای فعلی</div>
  </div>
  <div class="flex gap-1 wrap no-print">
    <?php if (Auth::can('employees.export') || Auth::isSuper()): ?>
      <a class="btn" href="<?= e(hrm_url('/employees/export')) . '?' . e(http_build_query(array_merge($f, ['page' => '']))) ?>">📤 خروجی CSV</a>
    <?php endif; ?>
    <?php if (Auth::can('employees.import')): ?>
      <a class="btn" href="<?= e(hrm_url('/employees/import')) ?>">📥 ورود توده‌ای</a>
    <?php endif; ?>
    <?php if (Auth::can('employees.create')): ?>
      <a class="btn btn-primary" href="<?= e(hrm_url('/employees/create')) ?>">➕ افزودن کارمند</a>
    <?php endif; ?>
  </div>
</div>

<!-- تب‌های وضعیت -->
<div class="tabs">
  <?php
  $tabs = [
      'active' => ['فعال', $statusCounts['active']],
      'inactive' => ['غیرفعال', $statusCounts['inactive']],
      'archived' => ['آرشیو (حذف‌شده)', $statusCounts['archived']],
      'all' => ['همه', $statusCounts['all']],
  ];
  foreach ($tabs as $key => $t):
      $qs = http_build_query(array_merge($f, ['status' => $key, 'page' => '']));
  ?>
    <a href="<?= e(hrm_url('/employees') . '?' . $qs) ?>" class="<?= $f['status'] === $key ? 'active' : '' ?>">
      <?= e($t[0]) ?> <span class="badge badge-muted"><?= to_fa((string)$t[1]) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<form class="filters no-print" method="get" action="<?= e(hrm_url('/employees')) ?>">
  <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="🔎 نام، کد پرسنلی، کد ملی، تلفن…" class="grow">
  <select name="dept">
    <option value="0">همه واحدها</option>
    <?php foreach ($depts as $d): ?>
      <option value="<?= (int)$d['id'] ?>" <?= $f['dept'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="gender">
    <option value="">هر جنسیت</option>
    <option value="male" <?= $f['gender'] === 'male' ? 'selected' : '' ?>>آقا</option>
    <option value="female" <?= $f['gender'] === 'female' ? 'selected' : '' ?>>خانم</option>
  </select>
  <input type="hidden" name="status" value="<?= e($f['status']) ?>">
  <button class="btn btn-primary">اعمال فیلتر</button>
  <a class="btn btn-ghost" href="<?= e(hrm_url('/employees')) ?>">پاک‌سازی</a>
</form>

<div class="card">
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead>
        <tr>
          <th>کد پرسنلی</th><th>نام و نام خانوادگی</th><th>واحد / پست</th>
          <th>تلفن</th><th>تاریخ استخدام</th><th>وضعیت</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="mono"><a class="row-link" href="<?= e(hrm_url('/employees/' . $r['id'])) ?>"><?= e($r['emp_no']) ?></a></td>
          <td>
            <div class="flex items-center gap-1">
              <div class="avatar" style="width:32px;height:32px;font-size:12px"><?= e(mb_substr($r['first_name'], 0, 1) . mb_substr($r['last_name'], 0, 1)) ?></div>
              <div>
                <a class="row-link" href="<?= e(hrm_url('/employees/' . $r['id'])) ?>"><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?></a>
                <div class="small muted"><?= $r['gender'] === 'female' ? 'خانم' : 'آقا' ?><?= $r['national_code'] ? ' · کدملی ' . e($r['national_code']) : '' ?></div>
              </div>
            </div>
          </td>
          <td class="small"><?= e($r['dept_name'] ?? '—') ?><div class="muted"><?= e($r['pos_title'] ?? '') ?></div></td>
          <td class="mono small" dir="ltr"><?= e($r['phone'] ?: '—') ?></td>
          <td class="small"><?= e(jdate($r['hire_date'])) ?></td>
          <td>
            <?= $r['deleted_at'] ? '<span class="badge badge-muted">حذف‌شده</span>' : status_badge($r['status']) ?>
          </td>
          <td class="actions">
            <a class="btn btn-sm" href="<?= e(hrm_url('/employees/' . $r['id'])) ?>" title="پرونده">👁</a>
            <?php if (Auth::can('employees.update') && !$r['deleted_at']): ?>
              <a class="btn btn-sm" href="<?= e(hrm_url('/employees/' . $r['id'] . '/edit')) ?>" title="ویرایش">✏️</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="7"><div class="empty"><div class="big">👥</div>کارمندی با این فیلترها یافت نشد</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $pager ?>
</div>
