<?php
/**
 * واحدها و پست‌ها
 */
$editDept = null;
$editPos = null;
$parentNames = [];
foreach ($depts as $dd) $parentNames[(int)$dd['id']] = $dd['name'];
if (isset($_GET['edit_dept'])) {
    foreach ($depts as $d) if ((int)$d['id'] === (int)$_GET['edit_dept']) $editDept = $d;
}
if (isset($_GET['edit_pos'])) {
    foreach ($positions as $p) if ((int)$p['id'] === (int)$_GET['edit_pos']) $editPos = $p;
}
$levelNames = [1 => 'کارشناس', 'کارشناس ارشد', 'سرپرست', 'مدیر', 'مدیرعامل'];
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← سازمان ← واحدها و پست‌ها</div>
    <h1>🗂 واحدها و پست‌های سازمانی</h1>
    <div class="sub">ساختار درختی سازمان و عناوین شغلی را مدیریت کنید</div>
  </div>
  <div class="flex gap-1">
    <a class="btn" href="<?= e(hrm_url('/org/chart')) ?>">🌳 چارت سازمانی</a>
    <button class="btn btn-primary" data-modal="deptModal" onclick="fillDept(null)">➕ واحد جدید</button>
    <button class="btn" data-modal="posModal" onclick="fillPos(null)">➕ پست جدید</button>
  </div>
</div>

<div class="card mb-2">
  <div class="card-header"><h3>🏢 واحدهای سازمانی (<?= to_fa((string)count($depts)) ?>)</h3></div>
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>نام واحد</th><th>کد</th><th>واحد والد</th><th>مدیر</th><th>نیرو</th><th>زیرواحد</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($depts as $d): ?>
        <tr>
          <td><b><?= e($d['name']) ?></b><?php if ($d['description']): ?><div class="small muted"><?= e(mb_substr($d['description'], 0, 60)) ?></div><?php endif; ?></td>
          <td class="mono"><?= e($d['code'] ?: '—') ?></td>
          <td class="small"><?= $d['parent_id'] ? e($parentNames[$d['parent_id']] ?? '—') : '<span class="badge badge-muted">ریشه</span>' ?></td>
          <td class="small"><?= e($d['manager_name'] ?? '—') ?></td>
          <td><span class="badge badge-primary"><?= to_fa((string)$d['members']) ?></span></td>
          <td><?= to_fa((string)$d['children']) ?></td>
          <td class="actions">
            <button class="btn btn-sm" data-modal="deptModal" onclick='fillDept(<?= json_encode($d, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
            <form method="post" action="<?= e(hrm_url('/org/departments/' . $d['id'] . '/delete')) ?>" style="display:inline" data-confirm="این واحد حذف شود؟">
              <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>🏷 پست‌های سازمانی (<?= to_fa((string)count($positions)) ?>)</h3></div>
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>عنوان پست</th><th>سطح</th><th>واحد</th><th>دارندگان</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($positions as $p): ?>
        <tr>
          <td><b><?= e($p['title']) ?></b> <span class="mono small muted"><?= e($p['code'] ?: '') ?></span></td>
          <td><span class="badge badge-info"><?= e($levelNames[$p['level']] ?? ('سطح ' . $p['level'])) ?></span></td>
          <td class="small"><?= e($p['dept_name'] ?? '—') ?></td>
          <td><?= to_fa((string)$p['holders']) ?></td>
          <td class="actions">
            <button class="btn btn-sm" data-modal="posModal" onclick='fillPos(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
            <form method="post" action="<?= e(hrm_url('/org/positions/' . $p['id'] . '/delete')) ?>" style="display:inline" data-confirm="این پست حذف شود؟">
              <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- مودال واحد -->
<div class="modal-back" id="deptModal">
  <div class="modal">
    <form method="post" action="<?= e(hrm_url('/org/departments')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="dept_id" value="0">
      <div class="modal-head"><h3 id="deptModalTitle">افزودن واحد سازمانی</h3><button type="button" class="x-btn" data-close-modal>×</button></div>
      <div class="modal-body form-grid">
        <div class="field span2"><label>نام واحد <span class="req">*</span></label><input name="name" id="dept_name" required></div>
        <div class="field"><label>کد</label><input name="code" id="dept_code" dir="ltr"></div>
        <div class="field"><label>واحد والد</label>
          <select name="parent_id" id="dept_parent">
            <option value="0">— (ریشه)</option>
            <?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>مدیر واحد (از میان کارمندان)</label>
          <select name="manager_id" id="dept_manager">
            <option value="0">—</option>
            <?php foreach ($employees as $emp): ?><option value="<?= (int)$emp['id'] ?>"><?= e(trim($emp['first_name'] . ' ' . $emp['last_name'])) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>توضیح</label><input name="description" id="dept_desc"></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>انصراف</button><button class="btn btn-primary">ذخیره</button></div>
    </form>
  </div>
</div>

<!-- مودال پست -->
<div class="modal-back" id="posModal">
  <div class="modal">
    <form method="post" action="<?= e(hrm_url('/org/positions')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="pos_id" value="0">
      <div class="modal-head"><h3 id="posModalTitle">افزودن پست</h3><button type="button" class="x-btn" data-close-modal>×</button></div>
      <div class="modal-body form-grid">
        <div class="field span2"><label>عنوان پست <span class="req">*</span></label><input name="title" id="pos_title" required></div>
        <div class="field"><label>کد</label><input name="code" id="pos_code" dir="ltr"></div>
        <div class="field"><label>سطح سلسله‌مراتبی</label>
          <select name="level" id="pos_level">
            <?php foreach ($levelNames as $lv => $ln): ?><option value="<?= $lv ?>"><?= to_fa((string)$lv) ?> — <?= e($ln) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>واحد</label>
          <select name="department_id" id="pos_dept">
            <option value="0">—</option>
            <?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>توضیح</label><input name="description" id="pos_desc"></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>انصراف</button><button class="btn btn-primary">ذخیره</button></div>
    </form>
  </div>
</div>

<script>
function fillDept(d){
  var t=document.getElementById('deptModalTitle');
  document.getElementById('dept_id').value = d?d.id:0;
  document.getElementById('dept_name').value = d?d.name:'';
  document.getElementById('dept_code').value = d?(d.code||''):'';
  document.getElementById('dept_parent').value = d&&d.parent_id?d.parent_id:0;
  document.getElementById('dept_manager').value = d&&d.manager_id?d.manager_id:0;
  document.getElementById('dept_desc').value = d?(d.description||''):'';
  t.textContent = d?'ویرایش واحد: '+d.name:'افزودن واحد سازمانی';
  if(d) document.getElementById('deptModal').classList.add('on');
}
function fillPos(p){
  document.getElementById('pos_id').value = p?p.id:0;
  document.getElementById('pos_title').value = p?p.title:'';
  document.getElementById('pos_code').value = p?(p.code||''):'';
  document.getElementById('pos_level').value = p?p.level:1;
  document.getElementById('pos_dept').value = p&&p.department_id?p.department_id:0;
  document.getElementById('pos_desc').value = p?(p.description||''):'';
  document.getElementById('posModalTitle').textContent = p?'ویرایش پست: '+p.title:'افزودن پست';
  if(p) document.getElementById('posModal').classList.add('on');
}
</script>
<?php if ($editDept): ?>
<script>document.addEventListener('DOMContentLoaded',function(){fillDept(<?= json_encode($editDept, JSON_UNESCAPED_UNICODE) ?>);document.getElementById('deptModal').classList.add('on');});</script>
<?php endif; ?>
