<?php
/**
 * صفحه درباره سامانه — آموزش شماره‌دار قابلیت‌ها
 * @var array $sections  @var int $count
 */
$n = 0;
$appName = setting('app_name', 'سامانه مدیریت منابع انسانی');
$orgName = setting('org_name', '');
?>
<style>
.about-hero{
  background:linear-gradient(135deg,#1e3a8a,#4c1d95 55%,#7c3aed);
  color:#fff;border-radius:22px;padding:44px 34px;text-align:center;margin-bottom:26px;
  box-shadow:0 24px 60px -30px rgba(76,29,149,.9);position:relative;overflow:hidden;
}
.about-hero::after{content:"";position:absolute;inset:0;background:
  radial-gradient(500px 200px at 15% 0%,rgba(255,255,255,.18),transparent),
  radial-gradient(400px 240px at 90% 100%,rgba(56,189,248,.25),transparent);}
.about-hero>*{position:relative;z-index:1}
.about-hero .num{
  display:inline-block;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.35);
  padding:7px 20px;border-radius:99px;font-size:14px;margin-top:12px;backdrop-filter:blur(4px);
}
.about-hero h1{font-size:30px;margin:14px 0 8px}
.about-hero p{opacity:.9;max-width:680px;margin:0 auto;font-size:14.5px;line-height:2}
.about-toc{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-bottom:28px}
.about-toc a{
  background:var(--surface);border:1px solid var(--border);color:var(--text);
  padding:7px 15px;border-radius:99px;font-size:12.5px;font-weight:600;
}
.about-toc a:hover{border-color:var(--primary);color:var(--primary)}
.about-search{max-width:520px;margin:0 auto 26px;position:relative}
.about-search input{
  width:100%;padding:13px 46px 13px 18px;border-radius:99px;border:1px solid var(--border);
  background:var(--surface);color:var(--text);font-family:inherit;font-size:14px;box-shadow:var(--shadow);
}
.about-search input:focus{outline:none;border-color:var(--primary)}
.about-search span{position:absolute;right:17px;top:50%;transform:translateY(-50%);opacity:.55}
.about-sec{margin-bottom:34px}
.about-sec>h2{
  display:flex;align-items:center;gap:10px;font-size:19px;margin-bottom:14px;
  padding-bottom:10px;border-bottom:2px dashed var(--border);
}
.about-sec>h2 .pill{
  background:var(--primary-soft);color:var(--primary);border-radius:99px;
  font-size:12px;padding:3px 12px;font-weight:700;
}
.feat{
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  padding:18px 18px 16px;margin-bottom:12px;box-shadow:var(--shadow);
  display:grid;grid-template-columns:56px 1fr;gap:14px;scroll-margin-top:80px;
}
.feat:hover{border-color:color-mix(in srgb,var(--primary) 40%,var(--border))}
.feat .fno{
  width:56px;height:56px;border-radius:16px;background:var(--primary-soft);
  display:flex;align-items:center;justify-content:center;font-size:24px;
}
.feat .fno b{
  position:absolute;font-size:11px;background:var(--primary);color:#fff;
  border-radius:99px;padding:1px 8px;transform:translate(26px,-26px);
}
.feat h3{font-size:15.5px;margin-bottom:6px;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.feat .what{font-size:13.5px;color:var(--muted);margin-bottom:10px;line-height:1.9}
.feat .how{
  background:var(--surface-2);border:1px dashed var(--border);border-radius:12px;
  padding:11px 14px;font-size:13px;
}
.feat .how b{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--primary);margin-bottom:6px}
.feat .how ol{margin:0;padding-right:20px}
.feat .how li{margin:4px 0;line-height:1.85}
.feat .path{
  margin-top:9px;font-size:12px;color:var(--muted);display:flex;gap:6px;align-items:center;flex-wrap:wrap;
}
.feat .path code{
  background:var(--primary-soft);color:var(--primary);padding:2px 9px;border-radius:7px;
  font-size:11.5px;direction:ltr;
}
.about-guide{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin:22px 0}
.guide-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:18px}
.guide-card b{display:block;margin-bottom:6px;font-size:14px}
.guide-card p{margin:0;font-size:12.5px;color:var(--muted);line-height:1.9}
@media print{
  .about-toc,.about-search,.no-print{display:none}
  .feat{break-inside:avoid;box-shadow:none}
  .about-hero{background:#1e3a8a !important;-webkit-print-color-adjust:exact}
}
</style>

<div class="about-hero">
  <div style="font-size:44px">📖</div>
  <h1>درباره سامانه <?= e($appName) ?></h1>
  <p>سامانه‌ای کامل و حرفه‌ای برای مدیریت منابع انسانی سازمان شما؛ ساخته‌شده با PHP و پایگاه داده SQLite،
     قابل نصب روی هاست‌های cPanel با نصب آسان. در این صفحه <b>همه <?= to_fa((string)$count) ?> قابلیت</b>
     به‌صورت شمرده و آموزشی معرفی شده‌اند.</p>
  <div class="num">✅ <?= to_fa((string)$count) ?> قابلیت · <?= to_fa((string)count($sections)) ?> بخش · نسخه 1.0.0</div>
</div>

<div class="about-toc no-print">
  <?php $i = 0; foreach ($sections as $title => $list): $i++; ?>
    <a href="#sec-<?= $i ?>"><?= e($title) ?> (<?= to_fa((string)count($list)) ?>)</a>
  <?php endforeach; ?>
</div>

<div class="about-search no-print">
  <input type="search" id="featSearch" placeholder="جستجو در قابلیت‌ها… (مثلاً: مرخصی، پشتیبان، مالیات)">
  <span>🔍</span>
</div>

<div class="about-guide">
  <div class="guide-card"><b>🧭 از کجا شروع کنم؟</b><p>پس از نصب، ابتدا تنظیمات سازمان را کامل کنید، سپس واحدها و کارمندان را بسازید و در پایان حضور و غیاب را فعال کنید.</p></div>
  <div class="guide-card"><b>🧭 نقش من چیست؟</b><p>مدیر کل همه‌کاره است؛ مدیر تیم، کارمندان خودش را می‌بیند و درخواست‌ها را تأیید می‌کند؛ کارمند فقط پرونده خودش را.</p></div>
  <div class="guide-card"><b>🧭 امنیت چگونه است؟</b><p>رمزها هش، فرم‌ها توکن CSRF، کوئری‌ها آماده‌شده، نشست‌ها مدیریت‌شده و همه اقدامات مهم ثبت رویداد می‌شوند.</p></div>
  <div class="guide-card"><b>🧭 پشتیبان‌گیری؟</b><p>هر هفته از بخش پشتیبان‌گیری، یک نسخه بگیرید و دانلود کنید؛ بازیابی هم فقط با یک فایل انجام می‌شود.</p></div>
</div>

<?php $secNo = 0; foreach ($sections as $title => $list): $secNo++; ?>
<section class="about-sec" id="sec-<?= $secNo ?>">
  <h2><span class="pill">بخش <?= to_fa((string)$secNo) ?></span> <?= e($title) ?>
    <span class="muted" style="font-size:13px;font-weight:400">(<?= to_fa((string)count($list)) ?> قابلیت)</span>
  </h2>

  <?php foreach ($list as $item): $n++; ?>
    <article class="feat" id="f<?= $n ?>" data-search="<?= e(mb_strtolower($item['title'] . ' ' . $item['what'] . ' ' . $item['section'], 'UTF-8')) ?>">
      <div class="fno" style="position:relative">
        <span style="font-size:22px"><?= $item['icon'] ?></span>
        <b><?= to_fa((string)$n) ?></b>
      </div>
      <div>
        <h3><a href="#f<?= $n ?>" style="color:inherit"><?= e($item['title']) ?></a></h3>
        <div class="what"><?= e($item['what']) ?></div>
        <div class="how">
          <b>💡 چطور کار می‌کند؟</b>
          <ol>
            <?php foreach ($item['how'] as $step): ?>
              <li><?= e($step) ?></li>
            <?php endforeach; ?>
          </ol>
        </div>
        <div class="path">📍 مسیر: <code><?= e($item['path']) ?></code></div>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>

<?php if ($n < 100): ?>
<div class="alert alert-warning">تعداد قابلیت‌ها باید حداقل ۱۰۰ باشد؛ فعلی: <?= to_fa((string)$n) ?></div>
<?php endif; ?>

<div class="card mt-3 no-print">
  <div class="card-body text-center">
    <h2>همین حالا شروع کنید 🚀</h2>
    <p class="muted">با ورود به سامانه، همه این قابلیت‌ها را به‌صورت عملی در منوی کناری خواهید دید.</p>
    <div class="flex gap-1 justify-between" style="justify-content:center">
      <a class="btn btn-primary" href="<?= e(hrm_url('/login')) ?>">ورود به سامانه</a>
      <a class="btn" href="<?= e(hrm_url('/')) ?>">بازگشت</a>
    </div>
  </div>
</div>

<script>
(function(){
  var box = document.getElementById('featSearch');
  if(!box) return;
  box.addEventListener('input', function(){
    var q = box.value.trim().toLowerCase();
    document.querySelectorAll('.feat').forEach(function(el){
      var hit = !q || (el.getAttribute('data-search')||'').indexOf(q) !== -1;
      el.style.display = hit ? '' : 'none';
    });
    document.querySelectorAll('.about-sec').forEach(function(sec){
      var any = sec.querySelector('.feat:not([style*="display: none"])');
      var visible = sec.querySelectorAll('.feat');
      var shown = 0;
      visible.forEach(function(v){ if(v.style.display !== 'none') shown++; });
      sec.style.display = shown ? '' : 'none';
    });
  });
})();
</script>
