<?php
use App\Core\Auth;
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="logo-lg">HR</div>
    <h1>ورود به سامانه</h1>
    <p class="sub"><?= e(setting('app_name', '')) ?><br><?= e(setting('org_name', '')) ?></p>

    <form method="post" action="<?= e(hrm_url('/login')) ?>">
      <?= csrf_field() ?>
      <div class="field mb-2">
        <label>نام کاربری یا ایمیل</label>
        <input name="username" dir="ltr" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div class="field mb-2">
        <label>رمز عبور</label>
        <input name="password" type="password" dir="ltr" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary btn-lg full mt-1" type="submit">ورود ←</button>
    </form>

    <div class="auth-foot">
      <div>برای دسترسی، حساب کاربری خود را از مدیر سامانه دریافت کنید.</div>
      <div class="mt-1"><a href="<?= e(hrm_url('/about')) ?>">📖 آشنایی با قابلیت‌های سامانه</a></div>
    </div>
  </div>
</div>
