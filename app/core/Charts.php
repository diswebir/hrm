<?php
/**
 * نمودارهای آماری SVG — بدون هیچ کتابخانه جاوااسکریپتی
 */
declare(strict_types=1);

namespace App\Core;

final class Charts
{
    public const PALETTE = [
        '#2563eb', '#7c3aed', '#0891b2', '#059669',
        '#d97706', '#dc2626', '#db2777', '#4f46e5',
        '#0d9488', '#65a30d',
    ];

    /** نمودار میله‌ای افقی */
    public static function bar(array $data, int $width = 560, int $barH = 26, int $gap = 12): string
    {
        if (!$data) return '<div class="chart-empty">داده‌ای موجود نیست</div>';
        $max = max(1, max(array_map(fn($d) => (float)$d[1], $data)));
        $labelW = 130;
        $h = count($data) * ($barH + $gap) + 4;
        $svg = '<svg class="chart" viewBox="0 0 ' . $width . ' ' . $h . '" role="img" aria-label="نمودار میله‌ای">';
        $y = 4;
        foreach ($data as $i => $item) {
            [$label, $value] = [$item[0], (float)$item[1]];
            $color = self::PALETTE[$i % count(self::PALETTE)];
            $bw = (int)(($width - $labelW - 70) * ($value / $max));
            $bw = max($bw, 2);
            $svg .= '<text x="' . ($labelW - 8) . '" y="' . ($y + $barH * 0.68) . '" text-anchor="end" class="chart-label">' . e(mb_substr((string)$label, 0, 18)) . '</text>';
            $svg .= '<rect x="' . $labelW . '" y="' . $y . '" width="' . $bw . '" height="' . $barH . '" rx="7" fill="' . $color . '" opacity="0.9"><title>' . e((string)$label) . ': ' . to_fa(number_format($value)) . '</title></rect>';
            $svg .= '<text x="' . ($labelW + $bw + 8) . '" y="' . ($y + $barH * 0.68) . '" class="chart-value">' . to_fa(number_format($value)) . '</text>';
            $y += $barH + $gap;
        }
        $svg .= '</svg>';
        return $svg;
    }

    /** نمودار دایره‌ای (Donut) */
    public static function donut(array $data, int $size = 180, int $thickness = 26): string
    {
        if (!$data) return '<div class="chart-empty">داده‌ای موجود نیست</div>';
        $total = array_sum(array_map(fn($d) => (float)$d[1], $data));
        if ($total <= 0) return '<div class="chart-empty">داده‌ای موجود نیست</div>';
        $r = ($size - $thickness) / 2;
        $cx = $cy = $size / 2;
        $circ = 2 * M_PI * $r;
        $svg = '<svg class="chart donut" viewBox="0 0 ' . $size . ' ' . $size . '" role="img" aria-label="نمودار دایره‌ای">';
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="#e2e8f0" stroke-width="' . $thickness . '"/>';
        $offset = 0;
        foreach ($data as $i => $item) {
            $frac = (float)$item[1] / $total;
            $len = $frac * $circ;
            $color = self::PALETTE[$i % count(self::PALETTE)];
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . $color . '" stroke-width="' . $thickness . '"'
                . ' stroke-dasharray="' . round($len, 2) . ' ' . round($circ - $len, 2) . '"'
                . ' stroke-dashoffset="' . round(-$offset, 2) . '" transform="rotate(-90 ' . $cx . ' ' . $cy . ')">'
                . '<title>' . e((string)$item[0]) . ': ' . to_fa(number_format((float)$item[1])) . ' (' . to_fa(number_format($frac * 100, 1)) . '٪)</title></circle>';
            $offset += $len;
        }
        $svg .= '</svg>';
        // راهنمای رنگ‌ها
        $legend = '<ul class="donut-legend">';
        foreach ($data as $i => $item) {
            $color = self::PALETTE[$i % count(self::PALETTE)];
            $legend .= '<li><i style="background:' . $color . '"></i>' . e((string)$item[0]) . ' <b>' . to_fa(number_format((float)$item[1])) . '</b></li>';
        }
        $legend .= '</ul>';
        return '<div class="donut-wrap">' . $svg . $legend . '</div>';
    }

    /** نمودار خطی (روند) */
    public static function line(array $points, int $width = 560, int $height = 160, string $label = ''): string
    {
        if (count($points) < 2) return '<div class="chart-empty">داده کافی نیست</div>';
        $values = array_map(fn($p) => (float)$p[1], $points);
        $max = max(1, max($values));
        $min = min(0, min($values));
        $pad = 26;
        $w = $width - $pad * 2;
        $h = $height - $pad * 2;
        $step = count($points) > 1 ? $w / (count($points) - 1) : $w;

        $coords = [];
        foreach ($points as $i => $p) {
            $x = $pad + $i * $step;
            $y = $pad + $h - ((float)$p[1] - $min) / max(0.0001, ($max - $min)) * $h;
            $coords[] = [$x, $y, $p[0], $p[1]];
        }

        $svg = '<svg class="chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . e($label) . '">';
        // خطوط شبکه
        for ($g = 0; $g <= 3; $g++) {
            $gy = $pad + ($h / 3) * $g;
            $svg .= '<line x1="' . $pad . '" y1="' . $gy . '" x2="' . ($width - $pad) . '" y2="' . $gy . '" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4 4"/>';
        }
        $poly = implode(' ', array_map(fn($c) => $c[0] . ',' . $c[1], $coords));
        $area = $pad . ',' . ($pad + $h) . ' ' . $poly . ' ' . ($width - $pad) . ',' . ($pad + $h);
        $svg .= '<polygon points="' . $area . '" fill="#2563eb" opacity="0.08"/>';
        $svg .= '<polyline points="' . $poly . '" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';
        foreach ($coords as $c) {
            $svg .= '<circle cx="' . $c[0] . '" cy="' . $c[1] . '" r="4" fill="#2563eb"><title>' . e((string)$c[2]) . ': ' . to_fa(number_format((float)$c[3])) . '</title></circle>';
        }
        // برچسب اول و آخر
        $first = $coords[0];
        $last = $coords[count($coords) - 1];
        $svg .= '<text x="' . $first[0] . '" y="' . ($height - 6) . '" class="chart-label" text-anchor="middle">' . e((string)$first[2]) . '</text>';
        $svg .= '<text x="' . $last[0] . '" y="' . ($height - 6) . '" class="chart-label" text-anchor="middle">' . e((string)$last[2]) . '</text>';
        $svg .= '</svg>';
        return $svg;
    }

    /** نوار پیشرفت */
    public static function progress(float $value, float $max, string $color = '#2563eb'): string
    {
        $p = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;
        return '<div class="progress" title="' . to_fa(number_format($value)) . ' از ' . to_fa(number_format($max)) . '">'
            . '<div class="progress-bar" style="width:' . $p . '%;background:' . $color . '"></div></div>';
    }
}
