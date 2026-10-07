<?php
/**
 * HRM — سامانه مدیریت منابع انسانی
 * Front Controller (کنترل‌کننده اصلی درخواست‌ها)
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = new App\Core\Router();
require __DIR__ . '/app/routes.php';

$router->dispatch();
