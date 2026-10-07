<?php
/**
 * دفترچه تلفن
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← دفترچه تلفن</div>
    <h1>📇 دفترچه تلفن سازمان</h1>
    <div class="sub">اطلاعات تماس همکاران فعال (<?= to_fa((string)count($rows)) ?> نفر)</div>
  </div>
</div>

<form class="filters no-print" method="get" action="<?= e(hrm_url('/directory')) ?>">
  <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="🔎 نام، تلفن یا ایمیل…" class="grow">
  <select name="dept">
    <option value="0">همه واحدها</option>
    <?php foreach ($depts as $d): ?>
      <option value="<?= (int)$d['id'] ?>" <?= $filters['dept'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary">جستجو</button>
</form>

<div class="grid grid-3">
  <?php foreach ($rows as $r): ?>
    <div class="card">
      <div class="card-body" style="display:flex;gap:12px;align-items:center">
        <div class="avatar" style="width:46px;height:46px"><?= e(mb_substr($r['first_name'], 0, 1) . mb_substr($r['last_name'], 0, 1)) ?></div>
        <div style="min-width:0;flex:1">
          <b><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?></b>
          <div class="small muted"><?= e($r['pos_name'] ?: 'کارمند') ?> · <?= e($r['dept_name'] ?: '—') ?></div>
          <div class="small mono" dir="ltr" style="color:var(--primary)"><?= e($r['phone'] ?: '—') ?></div>
          <?php if ($r['email']): ?><div class="small mono" dir="ltr" style="font-size:11px"><?= e($r['email']) ?></div><?php endif; ?>
        </div>
        <a class="btn btn-sm" href="<?= e(hrm_url('/employees/' . $r['id'])) ?>">👁</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!$rows): ?>
  <div class="card"><div class="empty"><div class="big">📇</div>نفری یافت نشد</div></div>
<?php endif; ?>
