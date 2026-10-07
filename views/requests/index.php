<?php
/**
 * درخواست‌ها
 */
use App\Core\Auth;
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← درخواست‌ها</div>
    <h1>📮 درخواست‌ها و انتقادات</h1>
    <div class="sub">ثبت درخواست‌های عمومی و پیگیری وضعیت</div>
  </div>
</div>

<div class="tabs">
  <a href="?tab=mine" class="<?= $tab === 'mine' ? 'active' : '' ?>">درخواست‌های من</a>
  <?php if ($canSeeAll): ?>
    <a href="?tab=approve" class="<?= $tab === 'approve' ? 'active' : '' ?>">در انتظار بررسی <span class="badge badge-warn"><?= to_fa((string)$pending) ?></span></a>
    <a href="?tab=all" class="<?= $tab === 'all' ? 'active' : '' ?>">همه</a>
  <?php endif; ?>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="card-header"><h3>➕ ثبت درخواست جدید</h3></div>
    <div class="card-body">
      <form method="post" action="<?= e(hrm_url('/requests')) ?>">
        <?= csrf_field() ?>
        <div class="field mb-1"><label>نوع</label>
          <select name="type">
            <?php foreach ($types as $k => $lbl): ?>
              <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field mb-1"><label>عنوان <span class="req">*</span></label>
          <input name="title" maxlength="140" required placeholder="خلاصه درخواست…"></div>
        <div class="field mb-1"><label>شرح <span class="req">*</span></label>
          <textarea name="body" rows="4" required placeholder="شرح کامل…"></textarea></div>
        <button class="btn btn-primary full">ارسال</button>
      </form>
    </div>
  </div>

  <div style="grid-column:span 2">
    <div class="card">
      <div class="card-header"><h3><?= $tab === 'approve' ? 'در انتظار' : ($tab === 'all' ? 'همه درخواست‌ها' : 'درخواست‌های من') ?></h3></div>
      <div class="card-body">
        <?php foreach ($rows as $r): ?>
          <div class="info-item mb-1" style="display:block">
            <div class="flex justify-between items-center">
              <b><?= e($r['title']) ?></b>
              <span><?= status_badge($r['status']) ?></span>
            </div>
            <div class="small muted">
              <?= ['general' => 'عمومی', 'facilities' => 'امکانات', 'complaint' => 'شکایت', 'suggestion' => 'پیشنهاد', 'it' => 'فاوا', 'other' => 'سایر'][$r['type']] ?? $r['type'] ?>
              · <?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?>
              · <?= e(jdate(substr($r['created_at'], 0, 10))) ?>
            </div>
            <div class="small" style="margin-top:4px"><?= nl2br(e($r['body'])) ?></div>
            <?php if ($r['decide_note']): ?>
              <div class="small" style="margin-top:4px;color:var(--primary)">💬 <?= e($r['decide_note']) ?> <span class="muted">(<?= e($r['decider']) ?>)</span></div>
            <?php endif; ?>
            <?php if ($r['status'] === 'pending' && $canSeeAll): ?>
              <div class="flex gap-1 mt-1">
                <form method="post" action="<?= e(hrm_url('/requests/' . $r['id'] . '/approve')) ?>" class="flex gap-1 grow">
                  <?= csrf_field() ?>
                  <input name="note" placeholder="پاسخ/توضیح (اختیاری)" style="flex:1" class="grow">
                  <button class="btn btn-sm btn-success">✅ تأیید</button>
                </form>
                <form method="post" action="<?= e(hrm_url('/requests/' . $r['id'] . '/reject')) ?>" data-confirm="رد شود؟">
                  <?= csrf_field() ?><button class="btn btn-sm btn-danger">❌ رد</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?><div class="empty"><div class="big">📮</div>درخواستی نیست</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
