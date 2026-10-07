<?php
/**
 * نقش‌ها و ماتریس دسترسی
 */
use App\Core\Auth;

?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← نقش‌ها</div>
    <h1>🛡 نقش‌ها و دسترسی‌ها</h1>
    <div class="sub"><?= to_fa((string)count($roles)) ?> نقش · ماتریس امنیتی سامانه (کنترل دسترسی نقش‌محور RBAC)</div>
  </div>
</div>

<div class="grid grid-4 mb-2">
  <?php foreach ($roles as $r): ?>
    <div class="kpi <?= $r['slug'] === 'super-admin' ? 'purple' : 'blue' ?>">
      <div class="ic">🎭</div>
      <div>
        <b><?= e($r['name']) ?></b>
        <span><?= to_fa((string)$r['user_count']) ?> کاربر · <?= to_fa((string)$r['perm_count']) ?> دسترسی</span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<form method="post" action="<?= e(hrm_url('/roles/store')) ?>" class="filters no-print">
  <?= csrf_field() ?>
  <input name="name" placeholder="نام نقش جدید" required>
  <input name="slug" dir="ltr" placeholder="slug (اختیاری)">
  <input name="description" placeholder="توضیح">
  <button class="btn btn-primary">➕ ایجاد نقش</button>
</form>

<?php foreach ($roles as $r): ?>
<div class="card mb-2">
  <div class="card-header">
    <h3><?= e($r['name']) ?> <span class="badge badge-muted mono"><?= e($r['slug']) ?></span>
      <?php if ($r['is_system']): ?><span class="badge badge-info">سیستمی</span><?php endif; ?></h3>
    <div class="small muted"><?= e($r['description']) ?></div>
  </div>
  <form method="post" action="<?= e(hrm_url('/roles/' . $r['id'])) ?>">
    <?= csrf_field() ?>
    <div class="card-body">
      <?php foreach ($groups as $gname => $perms): ?>
        <div style="border:1px solid var(--border);border-radius:10px;margin-bottom:10px;overflow:hidden">
          <div style="background:var(--bg-2);padding:8px 12px;font-weight:700;font-size:13px"><?= e($gname) ?></div>
          <div style="padding:10px 12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:6px">
            <?php foreach ($perms as $p): $checked = in_array($p['slug'], $byRole[(int)$r['id']], true); ?>
              <label class="badge <?= $checked ? 'badge-primary' : 'badge-muted' ?>" style="cursor:pointer;justify-content:flex-start;padding:7px 10px;font-weight:400">
                <input type="checkbox" name="perm[]" value="<?= e($p['slug']) ?>" <?= $checked ? 'checked' : '' ?> style="margin-left:7px">
                <span><?= e($p['title']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card-footer no-print" style="justify-content:flex-end">
      <span class="small muted" style="margin-left:auto">تغییرات بلافاصله اعمال می‌شود</span>
      <button class="btn btn-primary" <?= $r['slug'] === 'super-admin' && !Auth::isSuper() ? 'disabled' : '' ?>>💾 ذخیره دسترسی‌های «<?= e($r['name']) ?>»</button>
    </div>
  </form>
</div>
<?php endforeach; ?>
