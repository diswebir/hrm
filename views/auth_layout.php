<?php
/**
 * لایوت صفحات بدون ورود (ورود، درباره سامانه، خطاها)
 * @var string $content_for_layout
 */
use App\Core\Auth;

$theme = setting('theme', 'light');
if (isset($_COOKIE['hrm_theme'])) $theme = $_COOKIE['hrm_theme'] === 'dark' ? 'dark' : 'light';
$appName = setting('app_name', 'سامانه منابع انسانی');
$pageTitle = $page_title ?? '';
?>
<!doctype html>
<html lang="fa" dir="rtl" data-theme="<?= e($theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $pageTitle ? e($pageTitle) . ' — ' : '' ?><?= e($appName) ?></title>
<link rel="icon" href="data:image/svg+xml,<?php echo rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#2563eb"/><text x="32" y="42" font-size="28" font-family="Arial" font-weight="bold" fill="#fff" text-anchor="middle">HR</text></svg>'); ?>">
<link rel="stylesheet" href="<?= e(hrm_url('/assets/css/app.css')) ?>">
<script>window.HRM_BASE = <?= json_encode(hrm_base(), JSON_UNESCAPED_SLASHES) ?>;</script>
</head>
<body>
<?php foreach (\App\Core\Flash::pull() as $fl): ?>
  <div class="toast toast-<?= e($fl['type']) ?> <?= e($fl['type']) ?>" style="position:fixed;top:16px;left:16px;z-index:300"><?= e($fl['message']) ?></div>
<?php endforeach; ?>

<?= $content_for_layout ?? '' ?>

<script src="<?= e(hrm_url('/assets/js/app.js')) ?>"></script>
</body>
</html>
