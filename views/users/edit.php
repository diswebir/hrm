<?php
/**
 * ویرایش کاربر
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/users')) ?>">کاربران</a> ← <?= e($user['username']) ?></div>
    <h1>✏️ ویرایش کاربر: <span class="mono"><?= e($user['username']) ?></span></h1>
    <div class="sub">نقش فعلی: <?= e($user['role_name']) ?> · آخرین ورود: <?= e($user['last_login_at'] ? jdate(substr($user['last_login_at'], 0, 10)) : 'هرگز') ?></div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header"><h3>مشخصات</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/users/' . $user['id'])) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>ایمیل</label><input name="email" dir="ltr" value="<?= e($user['email'] ?? '') ?>"></div>
          <div class="field"><label>نقش</label>
            <select name="role_id">
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>وضعیت</label>
            <select name="status">
              <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>فعال</option>
              <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
            </select></div>
          <div class="field"><label>رمز جدید (خالی = بدون تغییر)</label>
            <input name="new_password" type="password" dir="ltr" minlength="8" autocomplete="new-password"></div>
        </div>
        <div class="alert alert-warning small">💡 تغییر رمز، همه نشست‌های فعال کاربر را بلافاصله خاتمه می‌دهد.</div>
        <button class="btn btn-primary full">ذخیره</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>نشست‌های اخیر</h3></div>
    <div class="card-body">
      <table class="tbl">
        <thead><tr><th>IP</th><th>مرورگر</th><th>آخرین فعالیت</th><th>وضعیت</th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
          <tr>
            <td class="mono small" dir="ltr"><?= e($s['ip_address']) ?></td>
            <td class="small" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($s['user_agent']) ?>"><?= e(substr((string)$s['user_agent'], 0, 40)) ?></td>
            <td class="small"><?= e($s['last_seen_at'] ? jdate(substr($s['last_seen_at'], 0, 10)) . ' ' . substr($s['last_seen_at'], 11, 5) : '—') ?></td>
            <td><?= $s['revoked_at'] ? '<span class="badge badge-muted">خاتمه‌یافته</span>' : '<span class="badge badge-success">فعال</span>' ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$sessions): ?><tr><td colspan="4" class="empty">نشستی نیست</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
