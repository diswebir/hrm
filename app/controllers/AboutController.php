<?php
/**
 * صفحه «درباره سامانه» — آموزش کامل و شماره‌دار همه قابلیت‌ها
 * (قابل مشاهده بدون ورود)
 */
declare(strict_types=1);

namespace App\Controllers;

final class AboutController extends Controller
{
    public function index(): void
    {
        $features = require HRM_ROOT . '/views/about/features.php';

        $sections = [];
        foreach ($features as $f) {
            $sections[$f['section']][] = $f;
        }

        $this->render('about/index', [
            'title'    => 'درباره سامانه',
            'sections' => $sections,
            'count'    => count($features),
        ], 'auth_layout');
    }
}
