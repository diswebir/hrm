<?php
/**
 * فهرست کاربران
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← کاربران</div>
    <h1>🔐 کاربران سامانه</h1>
    <div class="sub"><?= to_fa((string)count($rows)) ?> کاربر</div>
  </div>
  <div class="flex gap-1">
    <a class="btn" href="<?= e(hrm_url('/roles')) ?>">🛡 نقش‌ها</a>
    <button class="btn btn-primary" data-modal-open="m-new">➕ کاربر جدید</button>
  </div>
</div>

<form class="filters no-print" method="get" action="<?= e(hrm_url('/users')) ?>">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="🔎 نام کاربری، ایمیل یا نام…" class="grow">
  <button class="btn btn-primary">جستجو</button>
</form>

<div class="card">
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>#</th><th>نام کاربری</th><th>کارمند مرتبط</th><th>نقش</th><th>آخرین ورود</th><th>وضعیت</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="mono small"><?= to_fa((string)$r['id']) ?></td>
          <td>
            <b class="mono"><?= e($r['username']) ?></b>
            <?php if ((int)$r['id'] === (int)\App\Core\Auth::id()): ?><span class="badge badge-primary">شما</span><?php endif; ?>
            <div class="small muted mono" dir="ltr"><?= e($r['email'] ?: '') ?></div>
          </td>
          <td class="small"><?= $r['emp_no'] ? e(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) . ' <span class="muted mono">' . e($r['emp_no']) . '</span>' : '<span class="muted">—</span>' ?></td>
          <td><span class="badge badge-info"><?= e($r['role_name'] ?: '—') ?></span></td>
          <td class="small muted"><?= e($r['last_login_at'] ? jdate(substr($r['last_login_at'], 0, 10)) . ' ' . substr($r['last_login_at'], 11, 5) : 'هرگز') ?></td>
          <td><?= status_badge($r['status'] === 'active' ? 'active' : 'inactive') ?></td>
          <td class="actions">
            <a class="btn btn-sm" href="<?= e(hrm_url('/users/' . $r['id'] . '/edit')) ?>">✏️</a>
            <?php if ((int)$r['id'] !== (int)\App\Core\Auth::id()): ?>
              <form method="post" action="<?= e(hrm_url('/users/' . $r['id'] . '/delete')) ?>" data-confirm="کاربر غیرفعال شود؟ (امکان فعال‌سازی مجدد از ویرایش)">
                <?= csrf_field() ?><button class="btn btn-sm btn-danger">⏸</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal" id="m-new">
  <div class="box" style="max-width:560px">
    <div class="modal-head"><h3>➕ کاربر جدید</h3><button type="button" class="modal-close" data-modal-close>✕</button></div>
    <form method="post" action="<?= e(hrm_url('/users')) ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field"><label>نام کاربری (لاتین) <span class="req">*</span></label>
          <input name="username" dir="ltr" pattern="[a-z0-9._\-]{3,32}" required placeholder="e.g. s.ahmadi"></div>
        <div class="field"><label>رمز عبور (حداقل ۸) <span class="req">*</span></label>
          <input name="password" type="password" dir="ltr" minlength="8" required></div>
        <div class="field"><label>ایمیل</label><input name="email" type="email" dir="ltr"></div>
        <div class="field"><label>نقش <span class="req">*</span></label>
          <select name="role_id" required>
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field span2"><label>کارمند مرتبط (اختیاری)</label>
          <select name="employee_id">
            <option value="0">— بدون پیوند —</option>
            <?php foreach ($emps as $emp): ?>
              <option value="<?= (int)$emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_no']) ?>)</option>
            <?php endforeach; ?>
          </select></div>
      </div>
      <button class="btn btn-primary full mt-1">ایجاد کاربر</button>
    </form>
  </div>
</div>
