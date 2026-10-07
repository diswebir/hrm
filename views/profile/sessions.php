<?php
/**
 * نشست‌های فعال
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/profile')) ?>">پروفایل</a> ← نشست‌ها</div>
    <h1>🖥 نشست‌های فعال</h1>
    <div class="sub">هر ردیف یک ورود شما به سامانه است. اگر ناشناس بود فوراً قطعش کنید.</div>
  </div>
  <form method="post" action="<?= e(hrm_url('/profile/sessions/revoke-others')) ?>" data-confirm="همه نشست‌های دیگر قطع شوند؟">
    <?= csrf_field() ?>
    <button class="btn btn-danger">قطع همه به‌جز این دستگاه</button>
  </form>
</div>

<div class="card">
  <div class="card-body table-wrap">
    <table class="tbl">
      <thead>
        <tr><th>IP</th><th>دستگاه / مرورگر</th><th>شروع</th><th>آخرین فعالیت</th><th>وضعیت</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($sessions as $s): ?>
        <tr>
          <td class="mono"><?= e($s['ip_address']) ?></td>
          <td class="small" style="max-width:280px"><?= e(mb_substr((string)$s['user_agent'], 0, 90)) ?></td>
          <td class="small"><?= e(jdate(substr($s['created_at'], 0, 10))) ?> <?= e(substr($s['created_at'], 11, 5)) ?></td>
          <td class="small"><?= e(jdate(substr($s['last_seen_at'], 0, 10))) ?> <?= e(substr($s['last_seen_at'], 11, 5)) ?></td>
          <td>
            <?php if ($s['revoked_at']): ?>
              <span class="badge badge-muted">قطع شده</span>
            <?php elseif ($s['session_id'] === $current): ?>
              <span class="badge badge-ok">این دستگاه</span>
            <?php else: ?>
              <span class="badge badge-info">فعال</span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <?php if (!$s['revoked_at'] && $s['session_id'] !== $current): ?>
              <form method="post" action="<?= e(hrm_url('/profile/sessions/revoke')) ?>" data-confirm="این نشست قطع شود؟">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                <button class="btn btn-sm btn-danger">قطع</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$sessions): ?><tr><td colspan="6" class="empty">نشستی یافت نشد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
