<?php
/**
 * HRM — ویزارد نصب آسان (نصب با یک کلیک روی هاست cPanel)
 *
 * کافیست پس از آپلود فایل‌ها، دامنه/آدرس را باز کنید؛ این صفحه به‌طور
 * خودکار اجرا می‌شود و سامانه را در چند گام ساده راه‌اندازی می‌کند.
 */
declare(strict_types=1);

define('HRM_ROOT', __DIR__);

require HRM_ROOT . '/app/core/Helpers.php';
require HRM_ROOT . '/app/core/Jalali.php';
require HRM_ROOT . '/app/core/DB.php';
require HRM_ROOT . '/app/core/Settings.php';
require HRM_ROOT . '/install/Seeder.php';

use App\Core\DB;

$INSTALLED = file_exists(HRM_ROOT . '/config/installed.lock');

// نشست باید پیش از هر خروجی HTML شروع شود تا کوکی ارسال گردد
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/** ذخیره پیکربندی */
function install_write_config(array $cfg): bool
{
    $export = var_export($cfg, true);
    $php = "<?php\n/**\n * فایل پیکربندی HRM — توسط نصب‌کننده ساخته شد.\n * (این فایل را ویرایش نکنید مگر اینکه بدانید چه می‌کنید)\n */\nreturn " . $export . ";\n";
    return @file_put_contents(HRM_ROOT . '/config/config.php', $php) !== false;
}

/** چک سازگاری محیط */
function install_requirements(): array
{
    $checks = [];
    $checks[] = [
        'ok'   => version_compare(PHP_VERSION, '7.4.0', '>='),
        'title'=> 'نسخه PHP',
        'need' => 'PHP 7.4 یا جدیدتر',
        'got'  => 'PHP ' . PHP_VERSION,
    ];
    $checks[] = [
        'ok'   => extension_loaded('pdo_sqlite'),
        'title'=> 'افزونه PDO SQLite',
        'need' => 'فعال (ضروری برای پایگاه داده)',
        'got'  => extension_loaded('pdo_sqlite') ? 'فعال ✓' : 'غیرفعال',
    ];
    $checks[] = [
        'ok'   => extension_loaded('json'),
        'title'=> 'افزونه JSON',
        'need' => 'فعال',
        'got'  => extension_loaded('json') ? 'فعال ✓' : 'غیرفعال',
    ];
    $checks[] = [
        'ok'   => extension_loaded('session'),
        'title'=> 'افزونه Session',
        'need' => 'فعال',
        'got'  => extension_loaded('session') ? 'فعال ✓' : 'غیرفعال',
    ];
    $checks[] = [
        'ok'   => extension_loaded('mbstring'),
        'title'=> 'افزونه Mbstring',
        'need' => 'فعال (پیشنهادی برای فارسی)',
        'got'  => extension_loaded('mbstring') ? 'فعال ✓' : 'غیرفعال',
    ];

    foreach (['/config', '/storage', '/storage/database', '/storage/uploads', '/storage/backups', '/storage/logs', '/storage/exports'] as $dir) {
        $path = HRM_ROOT . $dir;
        if (!is_dir($path)) @mkdir($path, 0775, true);
        $writable = is_dir($path) && is_writable($path);
        $checks[] = [
            'ok'   => $writable,
            'title'=> 'نوشتن در ' . $dir,
            'need' => 'پوشه قابل نوشتن باشد (755 یا 775)',
            'got'  => $writable ? 'قابل نوشتن ✓' : 'غیرقابل نوشتن ✗',
        ];
    }

    // قابلیت ساخت دیتابیس
    try {
        $tmpDb = HRM_ROOT . '/storage/database/.probe.sqlite';
        $pdo = new PDO('sqlite:' . $tmpDb);
        $pdo->exec('CREATE TABLE IF NOT EXISTS p (id INTEGER)');
        @unlink($tmpDb);
        $ok = true;
        $got = 'ساخت فایل دیتابیس ممکن است ✓';
    } catch (Throwable $e) {
        $ok = false;
        $got = 'خطا: ' . $e->getMessage();
    }
    $checks[] = ['ok' => $ok, 'title' => 'ساخت پایگاه داده SQLite', 'need' => 'قابلیت ایجاد فایل .sqlite', 'got' => $got];

    return $checks;
}

// ================= مدیریت درخواست‌ها =================
$errors = [];
$step = (int)($_REQUEST['step'] ?? 1);
$sampleData = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$INSTALLED) {
    $action = $_POST['action'] ?? '';

    // اعتبارسنجی توکن CSRF پیش از هر اقدامی
    $reqToken = $_POST['_token'] ?? '';
    if ($action !== '' && (empty($_SESSION['_inst_csrf']) || !is_string($reqToken) || !hash_equals((string)$_SESSION['_inst_csrf'], $reqToken))) {
        http_response_code(419);
        echo '<meta charset="utf-8"><p style="font-family:tahoma;text-align:center;padding:40px">جلسه منقضی شده است؛ لطفاً صفحه نصب را دوباره باز کنید.</p>';
        exit;
    }

    if ($action === 'base') {
        $appName = trim((string)post('app_name', 'سامانه مدیریت منابع انسانی'));
        $orgName = trim((string)post('org_name', ''));
        $timezone = trim((string)post('timezone', 'Asia/Tehran'));
        if ($orgName === '') $errors[] = 'نام سازمان الزامی است.';
        if (!in_array($timezone, ['Asia/Tehran', 'UTC', 'Asia/Dubai', 'Asia/Kabul'], true)) $timezone = 'Asia/Tehran';

        session_start();
        $_SESSION['install_base'] = ['app_name' => $appName, 'org_name' => $orgName, 'timezone' => $timezone];
        if (!$errors) $step = 3;
    }

    if ($action === 'admin') {
        session_start();
        $base = $_SESSION['install_base'] ?? [];
        $first = trim((string)post('first_name', ''));
        $last = trim((string)post('last_name', ''));
        $username = strtolower(trim((string)post('username', '')));
        $email = trim((string)post('email', ''));
        $password = (string)post('password', '');
        $password2 = (string)post('password2', '');
        $sampleData = post('sample') === '1';

        if ($first === '' || $last === '') $errors[] = 'نام و نام خانوادگی مدیر الزامی است.';
        if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)) $errors[] = 'نام کاربری باید فقط حروف انگلیسی، عدد، نقطه، خط تیره و زیرخط باشد (۳ تا ۳۰ نویسه).';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل معتبر نیست.';
        if (strlen($password) < 8) $errors[] = 'رمز عبور باید حداقل ۸ نویسه باشد.';
        if ($password !== $password2) $errors[] = 'تکرار رمز عبور مطابقت ندارد.';

        if (!$errors) {
            try {
                // ساخت دیتابیس و جداول
                $dbPath = HRM_ROOT . '/storage/database/hrm.sqlite';
                if (is_file($dbPath)) @unlink($dbPath);
                $GLOBALS['hrm_config'] = ['db_path' => $dbPath, 'app_name' => $base['app_name'] ?? 'HRM'];

                $schema = file_get_contents(HRM_ROOT . '/install/schema.sql');
                if ($schema === false) throw new RuntimeException('فایل schema.sql یافت نشد.');
                DB::execScript($schema);

                \App\Core\Seeder::run($GLOBALS['hrm_config']);
                \App\Core\Seeder::admin($first, $last, $username, $email, $password);
                if ($sampleData) \App\Core\Seeder::sample();

                $cfg = [
                    'app_name'     => $base['app_name'] ?? 'سامانه مدیریت منابع انسانی',
                    'org_name'     => $base['org_name'] ?? '',
                    'locale'       => 'fa',
                    'timezone'     => $base['timezone'] ?? 'Asia/Tehran',
                    'debug'        => false,
                    'base_url'     => '',
                    'db_path'      => $dbPath,
                    'version'      => '1.0.0',
                    'installed_at' => date('Y-m-d H:i:s'),
                ];
                if (!install_write_config($cfg)) {
                    throw new RuntimeException('امکان نوشتن config/config.php وجود ندارد. دسترسی پوشه config را 755 کنید.');
                }
                if (@file_put_contents(HRM_ROOT . '/config/installed.lock', date('Y-m-d H:i:s')) === false) {
                    throw new RuntimeException('امکان نوشتن installed.lock وجود ندارد.');
                }

                // ثبت رویداد نصب
                DB::insert('activity_log', [
                    'user_id' => null, 'level' => 'info', 'action' => 'install',
                    'message' => 'نصب سامانه با موفقیت انجام شد',
                    'context' => json_encode(['sample' => $sampleData], JSON_UNESCAPED_UNICODE),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                unset($_SESSION['install_base']);
                $step = 5;
            } catch (Throwable $e) {
                $errors[] = 'خطا در نصب: ' . $e->getMessage();
                $step = 3;
            }
        } else {
            $step = 3;
        }
    }
}

if ($step < 1 || $step > 5) $step = 1;
$checks = install_requirements();
$allOk = !in_array(false, array_column($checks, 'ok'), true);
$requireLogin = $step >= 3 && $step <= 4 && !$INSTALLED;

// هدر و خروجی
header('Content-Type: text/html; charset=utf-8');
$installedFlag = $INSTALLED;
$skinCss = is_file(HRM_ROOT . '/assets/css/app.css') ? file_get_contents(HRM_ROOT . '/assets/css/app.css') : '';
?>
<!doctype html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب سامانه منابع انسانی</title>
<style><?= preg_replace('/<\/?(html|head|body)[^>]*>/i', '', $skinCss) /* استایل مشترک */ ?></style>
<style>
.install-wrap{max-width:860px;margin:40px auto;padding:0 16px}
.install-hero{text-align:center;margin-bottom:28px}
.install-hero .logo{width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:34px;font-weight:800;box-shadow:0 12px 30px rgba(37,99,235,.35)}
.stepper{display:flex;gap:6px;justify-content:center;margin:26px 0}
.stepper .st{flex:1;max-width:150px;text-align:center;font-size:12px;color:#94a3b8}
.stepper .st i{display:block;height:6px;border-radius:99px;background:#e2e8f0;margin-bottom:8px}
.stepper .st.on{color:#2563eb;font-weight:700}
.stepper .st.on i{background:#2563eb}
.stepper .st.done i{background:#10b981}
.check-row{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px dashed #e2e8f0;gap:10px}
.check-row:last-child{border-bottom:0}
.ok{color:#059669;font-weight:700}.bad{color:#dc2626;font-weight:700}
.install-actions{display:flex;gap:10px;justify-content:space-between;align-items:center;margin-top:22px;flex-wrap:wrap}
.hint{font-size:13px;color:#64748b;line-height:2}
code.dir{background:#f1f5f9;padding:2px 8px;border-radius:6px;font-size:12px;direction:ltr;display:inline-block}
.spinner{width:46px;height:46px;border:4px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;margin:0 auto 14px;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.checkline{display:flex;gap:10px;align-items:flex-start;cursor:pointer;font-size:14px}
.btn-lg{padding:13px 34px;font-size:16px}
.demo-card{background:linear-gradient(135deg,#eef2ff 0%,#f0fdf4 100%);border:1.5px dashed #6366f1;border-radius:14px;padding:16px 18px}
.demo-card-head{font-size:15.5px;font-weight:800;color:#4338ca;margin-bottom:6px}
.demo-list{margin:4px 0 10px;padding-right:20px;font-size:13.5px;line-height:2.15;color:#334155}
.demo-list li::marker{content:"✔  ";color:#059669}
.install-actions button.btn{border-radius:12px;font-weight:700}
</style>
</head>
<body>
<div class="install-wrap">
  <div class="install-hero">
    <div class="logo">HR</div>
    <h1 style="margin:14px 0 4px">نصب سامانه مدیریت منابع انسانی</h1>
    <p style="color:#64748b">نصب آسان و سریع — بدون نیاز به MySQL، فقط SQLite</p>
  </div>

  <div class="stepper">
    <?php
    $titles = [1 => 'الزامات', 2 => 'اطلاعات سازمان', 3 => 'حساب مدیر', 4 => 'اجرا', 5 => 'پایان'];
    foreach ($titles as $i => $t):
        $cls = $i === $step ? 'on' : ($i < $step || $installedFlag ? 'done' : '');
    ?>
      <div class="st <?= $cls ?>"><i></i><?= $t ?></div>
    <?php endforeach; ?>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error" style="margin-bottom:16px">
      <?php foreach ($errors as $er): ?><div>• <?= e($er) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card">
  <?php if ($installedFlag && $step !== 5): ?>
    <div style="text-align:center;padding:30px">
      <div style="font-size:52px">✅</div>
      <h2>سامانه قبلاً نصب شده است</h2>
      <p class="hint">برای امنیت، نصب‌کننده غیرفعال شده است. اگر می‌خواهید دوباره نصب کنید، فایل
        <code class="dir">config/installed.lock</code> و <code class="dir">config/config.php</code> را حذف کنید.</p>
      <a class="btn btn-primary" href="<?= e(hrm_url('/login')) ?>">ورود به سامانه</a>
    </div>

  <?php elseif ($step === 1): ?>
    <div class="card-header"><h2>گام ۱ — بررسی سازگاری محیط</h2></div>
    <div class="card-body" style="padding:0 18px">
      <?php foreach ($checks as $c): ?>
        <div class="check-row">
          <div><b><?= e($c['title']) ?></b><div class="hint" style="font-size:12px">نیازمندی: <?= e($c['need']) ?></div></div>
          <div class="<?= $c['ok'] ? 'ok' : 'bad' ?>"><?= e($c['got']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card-footer install-actions">
      <span class="hint"><?= $allOk ? '✅ همه الزامات برآورده شده‌اند. می‌توانید ادامه دهید.' : '⛔ برخی الزامات برآورده نشده؛ تنظیمات هاست (cPanel → Select PHP Version) را بررسی کنید.' ?></span>
      <?php if ($allOk): ?>
        <a class="btn btn-primary" href="?step=2">ادامه نصب →</a>
      <?php else: ?>
        <a class="btn" href="?step=1">تلاش مجدد</a>
      <?php endif; ?>
    </div>

  <?php elseif ($step === 2): ?>
    <form method="post" action="?step=2">
      <?= csrf_field_installer() ?>
      <input type="hidden" name="action" value="base">
      <div class="card-header"><h2>گام ۲ — اطلاعات سازمان</h2></div>
      <div class="card-body form-grid">
        <div class="field span2">
          <label>نام سامانه <span class="req">*</span></label>
          <input name="app_name" value="<?= e($_POST['app_name'] ?? 'سامانه مدیریت منابع انسانی') ?>" required>
        </div>
        <div class="field span2">
          <label>نام سازمان / شرکت <span class="req">*</span></label>
          <input name="org_name" value="<?= e($_POST['org_name'] ?? '') ?>" placeholder="مثلاً: شرکت فناوری پارس" required>
        </div>
        <div class="field">
          <label>منطقه زمانی</label>
          <select name="timezone">
            <option value="Asia/Tehran">تهران (Asia/Tehran)</option>
            <option value="Asia/Dubai">دبی</option>
            <option value="UTC">UTC</option>
          </select>
        </div>
        <div class="field">
          <label>پایگاه داده</label>
          <input value="storage/database/hrm.sqlite (SQLite)" disabled>
          <small class="hint">هیچ نیازی به MySQL نیست؛ دیتابیس به‌صورت فایل روی هاست شما ذخیره می‌شود.</small>
        </div>
      </div>
      <div class="card-footer install-actions">
        <a class="btn" href="?step=1">→ بازگشت</a>
        <button class="btn btn-primary">ادامه →</button>
      </div>
    </form>

  <?php elseif ($step === 3): ?>
    <form method="post" action="?step=3" onsubmit="if(this._busy){return false;}this._busy=1;var bs=this.querySelectorAll('.install-actions button');for(var i=0;i<bs.length;i++){bs[i].style.pointerEvents='none';bs[i].style.opacity=.7;}var p=this.querySelector('button[name=&quot;sample&quot;][value=&quot;1&quot;]')||this.querySelector('button[name=sample][value=1]');if(p){p.textContent='⏳ در حال نصب…';}">
      <?= csrf_field_installer() ?>
      <input type="hidden" name="action" value="admin">
      <div class="card-header"><h2>گام ۳ — حساب مدیر سامانه</h2></div>
      <div class="card-body form-grid">
        <div class="field">
          <label>نام <span class="req">*</span></label>
          <input name="first_name" value="<?= e($_POST['first_name'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>نام خانوادگی <span class="req">*</span></label>
          <input name="last_name" value="<?= e($_POST['last_name'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>نام کاربری (انگلیسی) <span class="req">*</span></label>
          <input name="username" dir="ltr" value="<?= e($_POST['username'] ?? 'admin') ?>" pattern="[a-z0-9._-]{3,30}" required>
        </div>
        <div class="field">
          <label>ایمیل</label>
          <input name="email" type="email" dir="ltr" value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
          <label>رمز عبور (حداقل ۸ نویسه) <span class="req">*</span></label>
          <input name="password" type="password" dir="ltr" minlength="8" required>
        </div>
        <div class="field">
          <label>تکرار رمز عبور <span class="req">*</span></label>
          <input name="password2" type="password" dir="ltr" minlength="8" required>
        </div>
        <div class="field span2">
          <div class="demo-card">
            <div class="demo-card-head">🎲 دیتای دمو — پرکردن خودکار سامانه با یک کلیک</div>
            <ul class="demo-list">
              <li><b>👥 ۱۶ کارمند نمونه</b> در ۷ واحد سازمانی، با پست، شیفت، مدیر مستقیم و کاربر همراه</li>
              <li><b>🕐 حضور و غیاب</b> ماه جاری (حاضر، تأخیر، غیبت، مرخصی) + گزارش‌های آماده</li>
              <li><b>🌴 مرخصی‌ها</b>: درخواست‌ها، تعادل هر فرد و ۳۳ تعطیل رسمی سال</li>
              <li><b>💰 حقوق</b>: اجزای حقوق، فیش‌های دوره، وام نمونه و اضافه‌کاری</li>
              <li><b>🎯 عملکرد و آموزش</b>: اهداف، دوره آموزشی، آگهی شغلی و متقاضی</li>
            </ul>
            <small class="hint">با زدن دکمه <b>«شروع نصب با دیتای دمو»</b> در پایین همین فرم، همه موارد بالا خودکار ساخته می‌شود و بلافاصله داشبورد را پر از اطلاعات می‌بینید. اگر ترجیح می‌دهید از صفر شروع کنید، <b>«نصب خالی»</b> را بزنید. هر زمان بخواهید از بخش «تنظیمات ← بازنشانی» می‌توانید داده‌ها را پاک کنید.</small>
          </div>
          <input type="hidden" name="sample" value="1">
        </div>
      </div>
      <div class="card-footer install-actions">
        <a class="btn" href="?step=2">→ بازگشت</a>
        <span style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn btn-lg" name="sample" value="0">نصب خالی</button>
          <button class="btn btn-primary btn-lg" name="sample" value="1">🎲 شروع نصب با دیتای دمو</button>
        </span>
      </div>
    </form>

  <?php elseif ($step === 4 && !$installedFlag): ?>
    <div style="text-align:center;padding:40px">
      <div class="spinner"></div>
      <p>در حال نصب…</p>
    </div>

  <?php elseif ($step === 5 || $installedFlag): ?>
    <div style="text-align:center;padding:34px 20px">
      <div style="font-size:60px">🎉</div>
      <h2>نصب با موفقیت انجام شد!</h2>
      <p class="hint">پایگاه داده ساخته شد، حساب مدیر فعال است و سامانه آماده استفاده است.</p>
      <div class="install-actions" style="justify-content:center">
        <a class="btn btn-primary btn-lg" href="<?= e(hrm_url('/login')) ?>">ورود به سامانه →</a>
        <a class="btn btn-lg" href="<?= e(hrm_url('/about')) ?>">مشاهده «درباره سامانه»</a>
      </div>
      <div class="hint" style="margin-top:26px;text-align:right;background:#f8fafc;border-radius:12px;padding:14px 18px">
        <b>برای امنیت:</b> فایل‌های <code class="dir">install.php</code> و پوشه <code class="dir">install/</code> را
        پس از پایان نصب می‌توانید از هاست حذف کنید (اختیاری). برای ورود از نام کاربری و رمزی که ساختید استفاده کنید.
      </div>
    </div>
  <?php endif; ?>
  </div>

  <p class="hint" style="text-align:center;margin-top:18px">HRM v1.0.0 — نصب‌کننده رسمی · سازگار با هاست‌های cPanel · PHP 7.4+</p>
</div>

<?php if ($step === 4): ?>
<script>setTimeout(function(){ location.search = '?step=3'; }, 100);</script>
<?php endif; ?>
</body>
</html>
<?php
function csrf_field_installer(): string
{
    if (empty($_SESSION['_inst_csrf'])) {
        $_SESSION['_inst_csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="_token" value="' . e($_SESSION['_inst_csrf']) . '">';
}
