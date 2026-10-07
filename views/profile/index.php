<?php
/**
 * پروفایل من
 */
use App\Core\Auth;

$initial = mb_substr($user['username'], 0, 2);
if ($emp) $initial = mb_substr($emp['first_name'], 0, 1) . mb_substr($emp['last_name'], 0, 1);
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← پروفایل</div>
    <h1>👤 پروفایل من</h1>
    <div class="sub">کاربر <?= e($user['username']) ?> · نقش: <?= e($user['role_name'] ?? '—') ?></div>
  </div>
  <button class="btn" data-print>🖨 چاپ</button>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header"><h3>اطلاعات حساب</h3></div>
    <div class="card-body">
      <div class="flex gap-2 items-center mb-2">
        <div class="avatar" style="width:64px;height:64px;font-size:20px"><?= e($initial) ?></div>
        <div>
          <b style="font-size:16px"><?= $emp ? e(trim($emp['first_name'] . ' ' . $emp['last_name'])) : e($user['username']) ?></b>
          <div class="small muted">آخرین ورود: <?= $user['last_login_at'] ? e(jdate(substr($user['last_login_at'], 0, 10)) . ' ' . substr($user['last_login_at'], 11, 5)) : '—' ?></div>
        </div>
      </div>

      <form method="post" action="<?= e(hrm_url('/profile')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field">
            <label>ایمیل</label>
            <input name="email" type="email" dir="ltr" value="<?= e($user['email'] ?? '') ?>">
          </div>
          <?php if ($emp && can('employees.update')): ?>
          <div class="field">
            <label>تلفن</label>
            <input name="phone" dir="ltr" value="<?= e($emp['phone'] ?? '') ?>">
          </div>
          <div class="field span2">
            <label>آدرس</label>
            <textarea name="address"><?= e($emp['address'] ?? '') ?></textarea>
          </div>
          <?php endif; ?>
        </div>
        <button class="btn btn-primary mt-1">ذخیره اطلاعات</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>🔑 تغییر رمز عبور</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/profile/password')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1">
          <label>رمز عبور فعلی</label>
          <input name="current" type="password" dir="ltr" required>
        </div>
        <div class="field mb-1">
          <label>رمز عبور جدید (حداقل ۸ نویسه)</label>
          <input name="new" type="password" dir="ltr" minlength="8" required>
        </div>
        <div class="field mb-1">
          <label>تکرار رمز جدید</label>
          <input name="repeat" type="password" dir="ltr" minlength="8" required>
        </div>
        <button class="btn btn-primary">تغییر رمز</button>
      </form>
    </div>
  </div>
</div>

<div class="grid grid-2 mt-2">
  <div class="card">
    <div class="card-header"><h3>🖥 نشست‌های فعال من</h3><a class="btn btn-sm" href="<?= e(hrm_url('/profile/sessions')) ?>">مدیریت ←</a></div>
    <div class="card-body table-wrap">
      <table class="tbl">
        <thead><tr><th>IP</th><th>مرورگر</th><th>آخرین فعالیت</th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
          <tr class="<?= $s['revoked_at'] ? '' : '' ?>">
            <td class="mono"><?= e($s['ip_address']) ?> <?= $s['session_id'] === session_id() ? '<span class="badge badge-ok">فعلی</span>' : '' ?></td>
            <td class="small"><?= e(mb_substr((string)$s['user_agent'], 0, 45)) ?><?= $s['revoked_at'] ? ' <span class="badge badge-muted">قطع شده</span>' : '' ?></td>
            <td class="small"><?= e(jdate(substr($s['last_seen_at'], 0, 10))) ?> <?= e(substr($s['last_seen_at'], 11, 5)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>📜 آخرین اقدامات من</h3></div>
    <div class="card-body">
      <div class="timeline">
        <?php foreach ($logs as $l): ?>
          <div class="tl-item">
            <b><?= e($l['message'] ?: $l['action']) ?></b>
            <div class="d"><?= e($l['action']) ?> · <?= e(jdate(substr($l['created_at'], 0, 10))) ?> <?= e(substr($l['created_at'], 11, 5)) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$logs): ?><div class="empty">موردی ثبت نشده</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
