<?php
/**
 * نمایش قالب‌ها (View) با لایوت اصلی
 */
declare(strict_types=1);

namespace App\Core;

final class View
{
    private static array $shared = [];

    /** داده مشترک همه قالب‌ها */
    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    /** رندر قالب داخل لایوت */
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $content = self::partial($template, $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutData = self::$shared + [
            'content_for_layout' => $content,
            'page_title'         => $data['title'] ?? '',
        ] + $data;

        echo self::partial($layout, $layoutData);
    }

    /** رندر فقط قالب (بدون لایوت) */
    public static function partial(string $template, array $data = []): string
    {
        $file = HRM_ROOT . '/views/' . $template . '.php';
        if (!is_file($file)) {
            return '<!-- missing view: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . ' -->';
        }
        extract(self::$shared, EXTR_SKIP);
        extract($data, EXTR_OVERWRITE);
        $v = static function (string $__t, array $__d = []) {
            return self::partial($__t, $__d);
        };
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }

    /** فقط اینکد کردن رشته */
    public static function e($s): string
    {
        return htmlspecialchars((string)$s ?? '', ENT_QUOTES, 'UTF-8');
    }
}
