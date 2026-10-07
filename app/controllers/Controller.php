<?php
/**
 * کنترلر پایه — قالب‌بندی عمومی کنترلرها
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

abstract class Controller
{
    protected function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        // داده‌های مشترک همه صفحات
        $data += [
            'pending_leave' => $this->pendingCount('leave_requests'),
            'pending_req'   => $this->pendingCount('requests'),
        ];
        View::render($template, $data, $layout);
    }

    private function pendingCount(string $table): int
    {
        try {
            if ($table === 'leave_requests') {
                if (!Auth::can('leave.approve')) return 0;
                return (int)\App\Core\DB::val("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
            }
            if (!Auth::can('requests.approve')) return 0;
            return (int)\App\Core\DB::val("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** شماره صفحه فعلی */
    protected function page(): int
    {
        $p = (int)($_GET['page'] ?? 1);
        return max(1, $p);
    }

    protected function perPage(): int
    {
        $p = (int)($_GET['per'] ?? 15);
        return min(100, max(5, $p));
    }

    /** لینک صفحه‌بندی */
    protected function pager(int $total, int $perPage, int $page): string
    {
        $pages = (int)ceil($total / max(1, $perPage));
        if ($pages <= 1) return '';
        $qs = $_GET;
        $link = function (int $p) use ($qs): string {
            $qs['page'] = $p;
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            return e($path . '?' . http_build_query($qs));
        };
        $html = '<div class="pager">';
        $html .= '<span class="info">کل: ' . to_fa((string)$total) . '</span>';
        if ($page > 1) $html .= '<a href="' . $link($page - 1) . '">›</a>';
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        if ($start > 1) $html .= '<a href="' . $link(1) . '">۱</a>' . ($start > 2 ? '<span class="info">…</span>' : '');
        for ($i = $start; $i <= $end; $i++) {
            $html .= $i === $page
                ? '<span class="cur">' . to_fa((string)$i) . '</span>'
                : '<a href="' . $link($i) . '">' . to_fa((string)$i) . '</a>';
        }
        if ($end < $pages) $html .= ($end < $pages - 1 ? '<span class="info">…</span>' : '') . '<a href="' . $link($pages) . '">' . to_fa((string)$pages) . '</a>';
        if ($page < $pages) $html .= '<a href="' . $link($page + 1) . '">‹</a>';
        $html .= '</div>';
        return $html;
    }
}
