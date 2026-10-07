<?php
/**
 * لایوت اصلی سامانه (کناری + نوار بالا)
 * @var string $content_for_layout
 */
use App\Core\Auth;
use App\Core\Notify;

$user = Auth::user();
$unread = $user ? Notify::unreadCount((int)$user['id']) : 0;
$theme = setting('theme', 'light');
if (isset($_COOKIE['hrm_theme'])) $theme = $_COOKIE['hrm_theme'] === 'dark' ? 'dark' : 'light';
$pageTitle = $page_title ?? '';
$appName = setting('app_name', 'سامانه منابع انسانی');
$orgName = setting('org_name', '');
$initials = '';
if ($user) {
    $emp = $user['employee_id'] ? \App\Core\DB::row('SELECT first_name, last_name FROM employees WHERE id = ?', [$user['employee_id']]) : null;
    $initials = mb_substr($emp['first_name'] ?? $user['username'], 0, 1) . mb_substr($emp['last_name'] ?? '', 0, 1);
    if (trim($initials) === '') $initials = mb_substr($user['username'], 0, 2);
}
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/** آیا مسیر جاری از زیرمجموعه است؟ */
function nav_active(string $path): bool
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = hrm_base();
    if ($base !== '' && strncmp($p, $base, strlen($base)) === 0) $p = substr($p, strlen($base));
    $p = '/' . ltrim($p, '/');
    return $p === $path || ($path !== '/' && strpos($p, $path) === 0);
}
$notifList = $user ? Notify::recent((int)$user['id'], 6) : [];
?>
<!doctype html>
<html lang="fa" dir="rtl" data-theme="<?= e($theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $pageTitle ? e($pageTitle) . ' — ' : '' ?><?= e($appName) ?></title>
<link rel="icon" href="data:image/svg+xml,<?php echo rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#2563eb"/><text x="32" y="42" font-size="28" font-family="Arial" font-weight="bold" fill="#fff" text-anchor="middle">HR</text></svg>'); ?>">
<link rel="stylesheet" href="<?= e(hrm_url('/assets/css/app.css')) ?>">
<script>
  window.HRM_BASE = <?= json_encode(hrm_base(), JSON_UNESCAPED_SLASHES) ?>;
  (function(){try{var t=localStorage.getItem('hrm_theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
</head>
<body>
<div class="app">
  <!-- ===== کناری ===== -->
  <aside class="sidebar">
    <div class="brand">
      <div class="logo">HR</div>
      <div>
        <b><?= e($appName) ?></b>
        <span><?= e($orgName) ?></span>
      </div>
    </div>
    <nav class="side-scroll">
      <div class="menu">
        <?php if (can('dashboard.view') || Auth::isSuper()): ?>
        <a href="<?= e(hrm_url('/')) ?>" class="<?= nav_active('/') && strlen(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/') <= strlen(hrm_base()) + 1 ? 'active' : '' ?>"><span class="ic">🏠</span> داشبورد</a>
        <?php endif; ?>
        <a href="<?= e(hrm_url('/about')) ?>" class="<?= nav_active('/about') ? 'active' : '' ?>"><span class="ic">📖</span> درباره سامانه</a>
      </div>

      <?php
      $groups = [];
      if (can('employees.view') || can('org.view')) $groups['سازمان و پرسنل'] = [];
      $g = [];
      if (can('org.view')) $g[] = ['/org/chart', 'chart', 'چارت سازمانی'];
      if (can('employees.view')) {
          $g[] = ['/employees', '👥', 'فهرست کارمندان'];
          $g[] = ['/directory', '📇', 'دفترچه تلفن'];
      }
      if (can('org.manage')) {
          $g[] = ['/org', '🗂', 'واحدها و پست‌ها'];
          $g[] = ['/employees/import', '📥', 'ورود توده‌ای'];
      }
      if ($g) $groups['سازمان و پرسنل'] = $g;

      $g = [];
      if (can('attendance.view')) $g[] = ['/attendance', '🕐', 'حضور و غیاب'];
      if (can('attendance.view')) $g[] = ['/overtime', '⏱', 'اضافه‌کاری'];
      $g[] = ['/leave', '🌴', 'مرخصی‌ها'];
      if (can('leave.manage')) $g[] = ['/leave/holidays', '📅', 'تعطیلات رسمی'];
      if (can('leave.manage')) $g[] = ['/leave/types', '🏷', 'انواع مرخصی'];
      if ($g) $groups['حضور و غیاب و مرخصی'] = $g;

      $g = [];
      if (can('payroll.view')) $g[] = ['/payroll', '💰', 'حقوق و دستمزد'];
      if (can('payroll.view')) $g[] = ['/payroll/mine', '🧾', 'فیش حقوقی من'];
      if (can('payroll.manage')) $g[] = ['/payroll/components', '🧩', 'اجزای حقوق'];
      if (can('payroll.manage')) $g[] = ['/payroll/loans', '🏦', 'وام‌ها و مساعده'];
      if (can('payroll.manage')) $g[] = ['/payroll/rewards', '🎁', 'پاداش و جریمه'];
      if ($g) $groups['حقوق و دستمزد'] = $g;

      $g = [];
      $g[] = ['/requests', '📨', 'درخواست‌ها'];
      if (can('requests.approve') ) $g[] = ['/requests?tab=approve', '✅', 'تأیید درخواست‌ها'];
      if (can('recruits.view')) $g[] = ['/recruit', '🧑‍💼', 'استخدام و داوطلبان'];
      if (can('training.view')) $g[] = ['/training', '🎓', 'آموزش'];
      if (can('performance.view') || can('performance.manage')) $g[] = ['/performance', '📈', 'عملکرد و اهداف'];
      if ($g) $groups['فرآیندهای منابع انسانی'] = $g;

      $g = [];
      if (can('reports.view')) $g[] = ['/reports', '📊', 'گزارش‌ها'];
      if ($g) $groups['گزارش و تحلیل'] = $g;

      $g = [];
      if (can('users.manage')) $g[] = ['/users', '🪪', 'کاربران'];
      if (can('roles.manage')) $g[] = ['/roles', '🔐', 'نقش‌ها و مجوزها'];
      if (can('settings.manage')) $g[] = ['/settings', '⚙️', 'تنظیمات سامانه'];
      if (can('backup.manage')) $g[] = ['/backup', '💾', 'پشتیبان‌گیری'];
      if (can('logs.view')) $g[] = ['/logs', '📜', 'گزارش رویدادها'];
      if (can('health.view')) $g[] = ['/health', '🩺', 'سلامت سامانه'];
      if ($g) $groups['مدیریت سیستم'] = $g;

      foreach ($groups as $title => $items):
          if (!$items) continue;
      ?>
        <div class="side-group">
          <span><?= e($title) ?></span>
          <?php foreach ($items as $it): ?>
            <a href="<?= e(hrm_url($it[0])) ?>" class="<?= nav_active(explode('?', $it[0])[0]) ? 'active' : '' ?>">
              <span class="ic"><?= $it[1] ?></span> <?= e($it[2]) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">HRM v<?= e(setting('installed_version', '1.0.0')) ?> · ساخته‌شده با PHP + SQLite</div>
  </aside>

  <!-- ===== محتوا ===== -->
  <div class="main">
    <header class="topbar">
      <button class="burger" data-burger aria-label="منو">☰</button>

      <?php if (can('search.global') || Auth::isSuper()): ?>
      <div class="search-box">
        <input id="globalSearch" type="search" placeholder="جستجوی کارمند، کد پرسنلی، کاربر…" autocomplete="off">
        <span class="s-ic">🔍</span>
        <div class="search-results" id="searchResults"></div>
      </div>
      <?php endif; ?>

      <div class="top-actions">
        <button class="icon-btn" data-toggle-theme title="تغییر تم" data-toggle-theme>🌓</button>

        <div style="position:relative">
          <button class="icon-btn" data-dd="notifBox" title="اعلان‌ها">
            🔔
            <?php if ($unread > 0): ?><span class="dot"><?= $unread > 99 ? '99+' : to_fa((string)$unread) ?></span><?php endif; ?>
          </button>
          <div class="dropdown" id="notifBox" style="left:0;right:auto;min-width:310px">
            <div style="padding:8px 12px;font-weight:700;border-bottom:1px solid var(--border)">اعلان‌ها</div>
            <div style="max-height:300px;overflow:auto">
              <?php if (!$notifList): ?>
                <div class="notif-item muted">اعلان جدیدی نیست 🎉</div>
              <?php endif; ?>
              <?php foreach ($notifList as $n): ?>
                <a class="notif-item" href="<?= e(hrm_url($n['link'] ?: '/notifications')) ?>">
                  <b><?= e($n['title']) ?></b>
                  <span><?= e(mb_substr((string)$n['body'], 0, 70)) ?> · <?= e(jdate($n['created_at'])) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
            <?php if ($unread > 0): ?>
              <form method="post" action="<?= e(hrm_url('/notifications/read-all')) ?>" style="padding:6px">
                <?= csrf_field() ?>
                <button class="btn btn-sm full">خواندن همه اعلان‌ها</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($user): ?>
        <div class="user-chip" data-dd="userBox">
          <div class="avatar"><?= e($initials) ?></div>
          <div class="meta">
            <b><?= e(($emp['first_name'] ?? '') ? trim($emp['first_name'] . ' ' . $emp['last_name']) : $user['username']) ?></b>
            <span><?= e($user['role_name'] ?? '') ?></span>
          </div>
          <div class="dropdown" id="userBox">
            <a href="<?= e(hrm_url('/profile')) ?>">👤 پروفایل من</a>
            <a href="<?= e(hrm_url('/my')) ?>">🗂 پرونده کاری من</a>
            <a href="<?= e(hrm_url('/profile/sessions')) ?>">🖥 نشست‌های فعال</a>
            <hr>
            <form method="post" action="<?= e(hrm_url('/logout')) ?>">
              <?= csrf_field() ?>
              <button type="submit" style="color:var(--danger)">🚪 خروج از حساب</button>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </header>

    <main class="page">
      <?php foreach (\App\Core\Flash::pull() as $fl): ?>
        <div class="toast toast-<?= e($fl['type']) ?> <?= e($fl['type']) ?>"><?= e($fl['message']) ?></div>
      <?php endforeach; ?>

      <?= $content_for_layout ?? '' ?>
    </main>

    <footer class="page no-print" style="padding-top:0;text-align:center;color:var(--muted);font-size:12px">
      <?= e($appName) ?> — <?= e($orgName) ?> · تاریخ امروز: <?= to_fa(today_j()) ?>
    </footer>
  </div>
</div>
<div class="toast-zone" id="toastZone"></div>
<script src="<?= e(hrm_url('/assets/js/app.js')) ?>"></script>
</body>
</html>
