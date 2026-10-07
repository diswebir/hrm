<?php
/**
 * اعلان‌ها
 * @var array $rows @var string $pager @var int $unread
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← اعلان‌ها</div>
    <h1>🔔 اعلان‌ها</h1>
    <div class="sub"><?= to_fa((string)$unread) ?> اعلان خوانده‌نشده از کل <?= to_fa((string)$total) ?></div>
  </div>
  <?php if ($unread > 0): ?>
    <form method="post" action="<?= e(hrm_url('/notifications/read-all')) ?>">
      <?= csrf_field() ?>
      <button class="btn">خواندن همه</button>
    </form>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body flush">
    <?php if (!$rows): ?>
      <div class="empty"><div class="big">🔕</div>اعلانی ندارید</div>
    <?php endif; ?>
    <?php foreach ($rows as $n): ?>
      <div class="notif-item" style="border-bottom:1px solid var(--border);<?= $n['read_at'] ? 'opacity:.65' : '' ?>">
        <div class="flex justify-between gap-1">
          <b><?= e($n['title']) ?> <?= $n['read_at'] ? '' : '<span class="badge badge-primary">جدید</span>' ?></b>
          <span class="small muted"><?= e(jdate(substr($n['created_at'], 0, 10))) ?> <?= e(substr($n['created_at'], 11, 5)) ?></span>
        </div>
        <div class="small muted"><?= e($n['body']) ?></div>
        <div class="flex gap-1 mt-1">
          <?php if ($n['link']): ?><a class="btn btn-sm" href="<?= e(hrm_url($n['link'])) ?>">مشاهده ←</a><?php endif; ?>
          <?php if (!$n['read_at']): ?>
            <form method="post" action="<?= e(hrm_url('/notifications/read')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
              <input type="hidden" name="back" value="/notifications">
              <button class="btn btn-sm btn-ghost">علامت خوانده شد</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?= $pager ?>
</div>
