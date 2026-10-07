<?php
/**
 * پشتیبان‌گیری
 */
?>
<div class="page-head">
  <div>
    <div class="crumb"><a href="<?= e(hrm_url('/')) ?>">داشبورد</a> ← پشتیبان‌گیری</div>
    <h1>💾 پشتیبان‌گیری و بازیابی</h1>
    <div class="sub">دیتابیس فعلی: <?= number_format($dbSize / 1024, 1) ?> KB · فضای آزاد دیسک: <?= number_format($free / 1048576, 0) ?> MB</div>
  </div>
  <form method="post" action="<?= e(hrm_url('/backup/create')) ?>">
    <?= csrf_field() ?>
    <button class="btn btn-primary">🛡 ساخت پشتیبان الان</button>
  </form>
</div>

<div class="alert alert-warning">
  ⚠️ پشتیبان شامل همه داده‌ها (کارمندان، حقوق، تنظیمات) است. پس از هر تغییر مهم، فایل را دانلود و در جای امنی نگه‌داری کنید.
  حداکثر ۱۰ نسخه خودکار نگه داشته می‌شود.
</div>

<div class="card">
  <div class="card-header"><h3>نسخه‌ها (<?= to_fa((string)count($files)) ?>)</h3></div>
  <div class="card-body table-wrap flush">
    <table class="tbl">
      <thead><tr><th>فایل</th><th>حجم</th><th>زمان ساخت</th><th>اقدامات</th></tr></thead>
      <tbody>
      <?php foreach ($files as $f): ?>
        <tr>
          <td class="mono small"><?= e($f['name']) ?></td>
          <td class="num small"><?= number_format($f['size'] / 1024, 1) ?> KB</td>
          <td class="small"><?= e(date('Y/m/d H:i', $f['time'])) ?></td>
          <td class="actions">
            <a class="btn btn-sm btn-success" href="<?= e(hrm_url('/backup/download/' . $f['name'])) ?>">⬇ دانلود</a>
            <form method="post" action="<?= e(hrm_url('/backup/restore')) ?>" style="display:inline"
                  data-confirm="⚠️ دیتابیس فعلی با این پشتیبان جایگزین می‌شود! از وضعیت فعلی پشتیبان گرفته می‌شود اما تغییرات اخیر از بین می‌روند. ادامه می‌دهید؟">
              <?= csrf_field() ?>
              <input type="hidden" name="file" value="<?= e($f['name']) ?>">
              <button class="btn btn-sm btn-warn">♻ بازیابی</button>
            </form>
            <form method="post" action="<?= e(hrm_url('/backup/delete/' . $f['name'])) ?>" data-confirm="حذف شود؟" style="display:inline">
              <?= csrf_field() ?><button class="btn btn-sm btn-danger">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$files): ?><tr><td colspan="4" class="empty"><div class="big">💾</div>هنوز پشتیبانی ساخته نشده — همین حالا یکی بسازید</div></td><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt-2">
  <div class="card-header"><h3>🧠 راهنمای بازیابی دستی (برای مدیر هاست)</h3></div>
  <div class="card-body small muted" style="line-height:2.2">
    ۱) دانلود فایل <span class="mono">.sqlite</span> از جدول بالا<br>
    ۲) در File Manager هاست، فایل <span class="mono">storage/database/hrm.sqlite</span> را جایگزین کنید<br>
    ۳) در صورت لزوم مجوز فایل را <span class="mono">644</span> بگذارید<br>
    ۴) سامانه بدون نیاز به نصب مجدد بالا می‌آید
  </div>
</div>
