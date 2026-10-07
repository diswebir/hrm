<?php
/**
 * تبدیل تاریخ میلادی ↔ شمسی (الگوریتم استاندارد jalaali)
 */
declare(strict_types=1);

namespace App\Core;

final class Jalali
{
    private const DATES = [
        [1403, 1, 1, 1796, 3, 21], [1404, 1, 1, 1797, 3, 21], [1405, 1, 1, 1798, 3, 21],
        [1406, 1, 1, 1799, 3, 21], [1407, 1, 1, 1800, 3, 21], [1408, 1, 1, 1801, 3, 21],
        [1409, 1, 1, 1802, 3, 21], [1410, 1, 1, 1803, 3, 21], [1411, 1, 1, 1804, 3, 20],
        [1412, 1, 1, 1805, 3, 21], [1413, 1, 1, 1806, 3, 21], [1414, 1, 1, 1807, 3, 21],
        [1415, 1, 1, 1808, 3, 20], [1416, 1, 1, 1809, 3, 21], [1417, 1, 1, 1810, 3, 21],
        [1418, 1, 1, 1811, 3, 21], [1419, 1, 1, 1812, 3, 21], [1420, 1, 1, 1813, 3, 20],
        [1398, 1, 1, 1793, 3, 21], [1399, 1, 1, 1794, 3, 20], [1400, 1, 1, 1795, 3, 21],
        [1401, 1, 1, 1796, 3, 21], [1402, 1, 1, 1796, 3, 21],
        [1395, 1, 1, 1789, 3, 21], [1396, 1, 1, 1790, 3, 21], [1397, 1, 1, 1791, 3, 21],
    ];

    public static function isLeap(int $jy): bool
    {
        return self::jalCal($jy)['leap'] === 1;
    }

    public static function daysInMonth(int $jy, int $jm): int
    {
        if ($jm <= 6) return 31;
        if ($jm <= 11) return 30;
        return self::isLeap($jy) ? 30 : 29;
    }

    /** تقسیم صحیح با گرد کردن به سمت صفر (مثل ~~ در جاوااسکریپت) */
    private static function fdiv(int $a, int $b): int
    {
        return intdiv($a, $b);
    }

    private static function fmod(int $a, int $b): int
    {
        return $a % $b;
    }

    /**
     * اطلاعات سال شمسی: روز نوروز در تقویم میلادی و کبیسه بودن
     * پورت الگوریتم استاندارد jalaali-js (MIT)
     */
    private static function jalCal(int $jy): array
    {
        $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635,
                   2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        $bl = count($breaks);
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];
        $jump = 0;
        if ($jy < $jp || $jy >= $breaks[$bl - 1]) {
            return ['leap' => 0, 'gy' => $gy, 'march' => 21];
        }
        for ($i = 1; $i < $bl; $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) break;
            $leapJ = $leapJ + self::fdiv($jump, 33) * 8 + self::fdiv(self::fmod($jump, 33), 4);
            $jp = $jm;
        }
        $n = $jy - $jp;
        $leapJ = $leapJ + self::fdiv($n, 33) * 8 + self::fdiv(self::fmod($n, 33) + 3, 4);
        if (self::fmod($jump, 33) === 4 && $jump - $n === 4) $leapJ++;
        $leapG = self::fdiv($gy, 4) - self::fdiv((self::fdiv($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;
        if ($jump - $n < 6) $n = $n - $jump + self::fdiv($jump + 4, 33) * 33;
        $leap = self::fmod(self::fmod($n + 1, 33) - 1, 4);
        if ($leap === -1) $leap = 4;
        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }

    private static function g2d(int $gy, int $gm, int $gd): int
    {
        $d = self::fdiv(($gy + self::fdiv($gm - 8, 6) + 100100) * 1461, 4)
           + self::fdiv(153 * self::fmod($gm + 9, 12) + 2, 5)
           + $gd - 34840408;
        $d = $d - self::fdiv(self::fdiv($gy + 100100 + self::fdiv($gm - 8, 6), 100) * 3, 4) + 752;
        return $d;
    }

    private static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j = $j + self::fdiv(self::fdiv(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::fdiv(self::fmod($j, 1461), 4) * 5 + 308;
        $gd = self::fdiv(self::fmod($i, 153), 5) + 1;
        $gm = self::fmod(self::fdiv($i, 153), 12) + 1;
        $gy = self::fdiv($j, 1461) - 100100 + self::fdiv(8 - $gm, 6);
        return [$gy, $gm, $gd];
    }

    private static function j2d(int $jy, int $jm, int $jd): int
    {
        $r = self::jalCal($jy);
        return self::g2d($r['gy'], 3, $r['march']) + ($jm - 1) * 31
             - self::fdiv($jm, 7) * ($jm - 7) + $jd - 1;
    }

    private static function d2j(int $jdn): array
    {
        $g = self::d2g($jdn);
        $gy = $g[0];
        $jy = $gy - 621;
        $r = self::jalCal($jy);
        $jdn1f = self::g2d($gy, 3, $r['march']);
        $k = $jdn - $jdn1f;
        if ($k >= 0) {
            if ($k <= 185) {
                return [$jy, 1 + self::fdiv($k, 31), self::fmod($k, 31) + 1];
            }
            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;
            if ($r['leap'] === 1) $k += 1;
        }
        return [$jy, 7 + self::fdiv($k, 30), self::fmod($k, 30) + 1];
    }

    /** میلادی → شمسی */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        if ($gm < 1 || $gm > 12 || $gd < 1 || $gd > 31) return [0, 0, 0];
        return self::d2j(self::g2d($gy, $gm, $gd));
    }

    /** شمسی → میلادی */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return self::d2g(self::j2d($jy, $jm, $jd));
    }

    /** 'Y-m-d' میلادی → 'Y-m-d' شمسی */
    public static function g2j(string $gDate): string
    {
        $gDate = trim($gDate);
        if ($gDate === '' || $gDate === '0000-00-00') return '';
        $p = explode('-', str_replace('/', '-', substr($gDate, 0, 10)));
        if (count($p) < 3) return '';
        [$jy, $jm, $jd] = self::toJalali((int)$p[0], (int)$p[1], (int)$p[2]);
        return sprintf('%04d-%02d-%02d', $jy, $jm, $jd);
    }

    /** 'Y-m-d' شمسی → 'Y-m-d' میلادی */
    public static function j2g(string $jDate): string
    {
        $jDate = trim($jDate);
        if ($jDate === '') return '';
        $p = explode('-', str_replace('/', '-', substr($jDate, 0, 10)));
        if (count($p) < 3) return '';
        [$gy, $gm, $gd] = self::toGregorian((int)$p[0], (int)$p[1], (int)$p[2]);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /** نمایش زیبای تاریخ میلادی ذخیره‌شده به شمسی: «۱۴۰۳/۰۱/۰۱» */
    public static function format(?string $gDate, string $sep = '/'): string
    {
        if ($gDate === null || trim($gDate) === '') return '—';
        $j = self::g2j($gDate);
        if ($j === '') return '—';
        return str_replace('-', $sep, $j);
    }

    public static function now(): string
    {
        return self::g2j(date('Y-m-d'));
    }

    /** نام ماه شمسی */
    public static function monthName(int $jm): string
    {
        $names = [1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        return $names[$jm] ?? '';
    }

    public static function weekday(string $gDate): string
    {
        $ts = strtotime($gDate);
        if ($ts === false) return '';
        $names = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        return $names[(int)date('w', $ts)];
    }

    /** اعتبارسنجی تاریخ شمسی ورودی کاربر */
    public static function validJalali(string $j): bool
    {
        $p = explode('-', str_replace('/', '-', trim($j)));
        if (count($p) !== 3) return false;
        [$jy, $jm, $jd] = [(int)$p[0], (int)$p[1], (int)$p[2]];
        if ($jy < 1200 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1) return false;
        return $jd <= self::daysInMonth($jy, $jm);
    }

    /** اختلاف دو تاریخ میلادی به روز */
    public static function diffDays(string $from, string $to): int
    {
        $a = strtotime($from);
        $b = strtotime($to);
        if (!$a || !$b) return 0;
        return (int)round(($b - $a) / 86400);
    }

    /** امروز (میلادی، برای ذخیره در دیتابیس) */
    public static function today(): string
    {
        return date('Y-m-d');
    }

    /** تعداد روزهای کاری (شنبه تا چهارشنبه/پنجشنبه طبق تنظیم) بین دو تاریخ */
    public static function businessDays(string $from, string $to, array $workDays = [0, 1, 2, 3, 4, 5]): int
    {
        // workDays: شماره روز هفته میلادی (0=یکشنبه … 6=شنبه)
        $count = 0;
        $cur = strtotime($from);
        $end = strtotime($to);
        if (!$cur || !$end || $end < $cur) return 0;
        while ($cur <= $end) {
            if (in_array((int)date('w', $cur), $workDays, true)) $count++;
            $cur = strtotime('+1 day', $cur);
        }
        return $count;
    }
}
