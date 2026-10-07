<?php
/**
 * Seeder — داده‌های اولیه و داده‌های نمونه سامانه
 */
declare(strict_types=1);

namespace App\Core;

use App\Core\DB;

final class Seeder
{
    /** کاتالوگ کامل مجوزها — مبنای نقش‌های سفارشی */
    public const PERMISSIONS = [
        // slug, title, group
        ['dashboard.view', 'مشاهده داشبورد', 'عمومی'],
        ['dashboard.analytics', 'مشاهده نمودارها و تحلیل‌ها', 'عمومی'],
        ['profile.update', 'ویرایش پروفایل و رمز عبور', 'عمومی'],
        ['search.global', 'جستجوی سراسری', 'عمومی'],

        ['employees.view', 'مشاهده فهرست کارمندان', 'کارمندان'],
        ['employees.create', 'افزودن کارمند جدید', 'کارمندان'],
        ['employees.update', 'ویرایش اطلاعات کارمند', 'کارمندان'],
        ['employees.delete', 'حذف/بایگانی کارمند', 'کارمندان'],
        ['employees.import', 'ورود توده‌ای از CSV', 'کارمندان'],
        ['employees.export', 'خروجی گرفتن از فهرست‌ها', 'کارمندان'],
        ['employees.photo', 'بارگذاری عکس و مدارک', 'کارمندان'],

        ['org.manage', 'مدیریت واحدها و پست‌ها', 'سازمان'],
        ['org.view', 'مشاهده چارت سازمانی', 'سازمان'],

        ['users.manage', 'مدیریت کاربران و دسترسی‌ها', 'کاربران'],
        ['roles.manage', 'مدیریت نقش‌ها و مجوزها', 'کاربران'],

        ['attendance.view', 'مشاهده حضور و غیاب', 'حضور و غیاب'],
        ['attendance.manage', 'ثبت و ویرایش حضور و غیاب', 'حضور و غیاب'],
        ['attendance.export', 'خروجی حضور و غیاب', 'حضور و غیاب'],

        ['leave.manage', 'مدیریت انواع مرخصی و تعطیلات', 'مرخصی'],
        ['leave.approve', 'تأیید/رد درخواست مرخصی', 'مرخصی'],
        ['leave.view', 'مشاهده همه درخواست‌های مرخصی', 'مرخصی'],

        ['overtime.manage', 'مدیریت اضافه‌کاری', 'اضافه‌کاری'],
        ['overtime.approve', 'تأیید/رد اضافه‌کاری', 'اضافه‌کاری'],

        ['payroll.view', 'مشاهده فهرست حقوق و دستمزد', 'حقوق و دستمزد'],
        ['payroll.manage', 'مدیریت اجزای حقوق، وام و پاداش', 'حقوق و دستمزد'],
        ['payroll.run', 'اجرای محاسبه و صدور فیش', 'حقوق و دستمزد'],

        ['requests.view', 'مشاهده درخواست‌های کارمندان', 'درخواست‌ها'],
        ['requests.approve', 'تأیید/رد درخواست‌ها', 'درخواست‌ها'],

        ['recruits.manage', 'مدیریت استخدام و آگهی‌ها', 'استخدام'],
        ['recruits.view', 'مشاهده داوطلبان', 'استخدام'],

        ['training.manage', 'مدیریت دوره‌های آموزشی', 'آموزش'],
        ['training.view', 'مشاهده دوره‌های آموزشی', 'آموزش'],

        ['performance.manage', 'مدیریت اهداف و ارزیابی عملکرد', 'عملکرد'],
        ['performance.view', 'مشاهده ارزیابی‌ها', 'عملکرد'],
        ['discipline.manage', 'مدیریت انضباطی', 'عملکرد'],

        ['reports.view', 'مشاهده گزارش‌ها', 'گزارش‌ها'],
        ['reports.export', 'خروجی گزارش‌ها', 'گزارش‌ها'],

        ['settings.manage', 'تنظیمات سامانه', 'سیستم'],
        ['backup.manage', 'پشتیبان‌گیری و بازیابی', 'سیستم'],
        ['logs.view', 'مشاهده گزارش رویدادها', 'سیستم'],
        ['health.view', 'مشاهده سلامت سامانه', 'سیستم'],
        ['notifications.view', 'اعلان‌ها', 'عمومی'],
    ];

    public static function run(array $config): void
    {
        // 1) مجوزها
        $sort = 0;
        foreach (self::PERMISSIONS as $p) {
            $sort += 10;
            $exists = DB::val('SELECT id FROM permissions WHERE slug = ?', [$p[0]]);
            if (!$exists) {
                DB::insert('permissions', ['slug' => $p[0], 'title' => $p[1], 'grp' => $p[2], 'sort' => $sort]);
            }
        }

        // 2) نقش‌ها
        $roles = [
            ['super',   'مدیر کل سامانه', 'دسترسی کامل و بدون محدودیت', 1],
            ['admin',   'مدیر',           'مدیریت بخش‌های اصلی سامانه', 1],
            ['hr',      'کارشناس منابع انسانی', 'مدیریت پرسنل، مرخصی، حضور و غیاب و استخدام', 1],
            ['manager', 'مدیر مستقیم',    'مشاهده تیم و تأیید درخواست‌های زیرمجموعه', 1],
            ['employee', 'کارمند',        'دسترسی خودخدمت به پرونده، مرخصی و درخواست‌ها', 1],
        ];
        $roleIds = [];
        foreach ($roles as $r) {
            $id = DB::val('SELECT id FROM roles WHERE slug = ?', [$r[0]]);
            if (!$id) {
                $id = DB::insert('roles', ['slug' => $r[0], 'name' => $r[1], 'description' => $r[2], 'is_system' => $r[3]]);
            }
            $roleIds[$r[0]] = (int)$id;
        }

        // 3) مجوزهای هر نقش
        $assign = function (string $role, array $perms) use ($roleIds): void {
            $roleId = $roleIds[$role];
            foreach ($perms as $perm) {
                $has = DB::val('SELECT 1 FROM role_permissions WHERE role_id = ? AND permission = ?', [$roleId, $perm]);
                if (!$has) {
                    DB::insert('role_permissions', ['role_id' => $roleId, 'permission' => $perm]);
                }
            }
        };

        $all = array_column(self::PERMISSIONS, 0);
        $assign('admin', $all);
        $assign('hr', [
            'dashboard.view', 'dashboard.analytics', 'profile.update', 'search.global', 'notifications.view',
            'employees.view', 'employees.create', 'employees.update', 'employees.export', 'employees.import', 'employees.photo',
            'org.manage', 'org.view',
            'attendance.view', 'attendance.manage', 'attendance.export',
            'leave.manage', 'leave.approve', 'leave.view',
            'overtime.manage', 'overtime.approve',
            'payroll.view',
            'requests.view', 'requests.approve',
            'recruits.manage', 'recruits.view',
            'training.manage', 'training.view',
            'performance.manage', 'performance.view', 'discipline.manage',
            'reports.view', 'reports.export',
        ]);
        $assign('manager', [
            'dashboard.view', 'profile.update', 'search.global', 'notifications.view',
            'employees.view', 'org.view',
            'attendance.view',
            'leave.view', 'leave.approve',
            'overtime.approve',
            'requests.view', 'requests.approve',
            'training.view', 'performance.view',
            'reports.view',
        ]);
        $assign('employee', [
            'profile.update', 'search.global', 'notifications.view', 'org.view',
        ]);

        // 4) تنظیمات پیش‌فرض
        $defaults = [
            'app_name'        => $config['app_name'] ?? 'سامانه مدیریت منابع انسانی',
            'org_name'        => $config['org_name'] ?? 'سازمان نمونه',
            'logo_text'       => 'HR',
            'timezone'        => $config['timezone'] ?? 'Asia/Tehran',
            'date_format'     => 'jalali',           // jalali|gregorian
            'theme'           => 'light',            // light|dark
            'work_start'      => '08:00',
            'work_end'        => '17:00',
            'late_grace_min'  => '10',
            'work_days'       => '6,0,1,2,3,4',      // شنبه تا چهارشنبه + پنجشنبه
            'weekend_days'    => '5',                // جمعه
            'session_ttl_minutes' => '120',
            'emp_no_prefix'   => 'EMP-',
            'emp_no_digits'    => '4',
            'contract_alert_days' => '30',
            'probation_alert_days' => '7',
            'overtime_rate'   => '1.4',
            'overtime_night_rate' => '1.75',
            'overtime_holiday_rate' => '2',
            'tax_exempt_personal' => '140000000',    # معافیت مالیات سالانه (ریال تومان؟ تومان)
            'tax_brackets'    => serialize([
                ['from' => 0,      'rate' => 0],
                ['from' => 1200,   'rate' => 10],
                ['from' => 1800,   'rate' => 15],
                ['from' => 2400,   'rate' => 20],
                ['from' => 3200,   'rate' => 25],
                ['from' => 5000,   'rate' => 30],
            ]),
            'currency'        => 'تومان',
            'sample_seeded'    => '0',
            'installed_version' => '1.0.0',
        ];
        foreach ($defaults as $k => $v) {
            $has = DB::val('SELECT id FROM settings WHERE k = ?', [$k]);
            if (!$has) DB::insert('settings', ['k' => $k, 'v' => (string)$v]);
        }

        // 5) انواع مرخصی
        $leaveTypes = [
            ['استحقاقی', 'annual', 1, 0, '#2563eb', 30],
            ['استعلاجی', 'sick', 1, 0, '#dc2626', 15],
            ['بدون حقوق', 'unpaid', 0, 1, '#f59e0b', 30],
            ['ازدواج', 'marriage', 1, 0, '#7c3aed', 3],
            ['فوت خانواده', 'bereavement', 1, 0, '#64748b', 3],
            ['زایمان', 'maternity', 1, 0, '#db2777', 90],
            ['تشویقی', 'reward', 1, 0, '#059669', 5],
        ];
        foreach ($leaveTypes as $i => $lt) {
            $has = DB::val('SELECT id FROM leave_types WHERE slug = ?', [$lt[1]]);
            if (!$has) {
                DB::insert('leave_types', [
                    'name' => $lt[0], 'slug' => $lt[1], 'paid' => $lt[2], 'deduct_pay' => $lt[3],
                    'color' => $lt[4], 'annual_days' => $lt[5], 'sort' => $i,
                ]);
            }
        }

        // 6) تعطیلات رسمی (تکرار سالانه بر اساس ماه/روز شمسی → میلادی ذخیره می‌شود)
        $fixedHolidays = [
            ['01-01', 'جشن نوروز'],
            ['01-02', 'عیدنوروز'],
            ['01-03', 'عیدنوروز'],
            ['01-04', 'عیدنوروز'],
            ['01-12', 'روز جمهوری اسلامی'],
            ['01-13', 'روز طبیعت (سیزده‌به‌در)'],
            ['03-14', 'رحلت امام خمینی'],
            ['03-15', 'قیام ۱۵ خرداد'],
            ['09-22', 'پیروزی انقلاب اسلامی'],
            ['11-22', 'پیروزی انقلاب اسلامی'],
            ['12-29', 'ملی شدن صنعت نفت'],
        ];
        $year = (int)date('Y');
        foreach ($fixedHolidays as $fh) {
            [$m, $d] = array_map('intval', explode('-', $fh[0]));
            foreach ([$year - 1, $year, $year + 1] as $gy) {
                $g = \App\Core\Jalali::j2g(sprintf('%04d-%02d-%02d', $gy, $m, $d));
                $has = DB::val('SELECT id FROM holidays WHERE date = ?', [$g]);
                if (!$has && $g !== '') {
                    DB::insert('holidays', ['date' => $g, 'title' => $fh[1], 'national' => 1, 'recurring' => 1]);
                }
            }
        }

        // 7) شیفت‌ها
        $shifts = [
            ['شیفت اداری', '08:00', '17:00', 10, 0],
            ['شیفت صبح', '06:00', '14:00', 10, 0],
            ['شیفت عصر', '14:00', '22:00', 10, 0],
            ['شیفت شب', '22:00', '06:00', 15, 1],
        ];
        foreach ($shifts as $s) {
            $has = DB::val('SELECT id FROM shifts WHERE name = ?', [$s[0]]);
            if (!$has) {
                DB::insert('shifts', ['name' => $s[0], 'start_time' => $s[1], 'end_time' => $s[2], 'grace_min' => $s[3], 'overnight' => $s[4]]);
            }
        }

        // 8) اجزای حقوق
        $components = [
            // code, name, type, calc_type, formula, default, taxable, sort
            ['BASE',     'حق پایه',        'earning', 'base',        '',                 0, 1, 10],
            ['HOUSING',  'حق مسکن',        'earning', 'fixed',       '',                 8000000, 0, 20],
            ['FOOD',     'حق خواربار',     'earning', 'fixed',       '',                 6000000, 0, 30],
            ['CHILD',    'حق اولاد',        'earning', 'per_child',   '2000000',          0, 0, 40],
            ['SHIFT',    'حق شیفت',        'earning', 'fixed',       '',                 0, 0, 50],
            ['OT',       'اضافه‌کاری',      'earning', 'overtime',    '',                 0, 1, 60],
            ['BONUS',    'پاداش',          'earning', 'bonus',       '',                 0, 1, 70],
            ['BIME',     'بیمه (سهم کارمند)', 'deduction', 'percent_base', '7',          0, 0, 100],
            ['TAX',      'مالیات',         'deduction', 'tax',        '',                 0, 0, 110],
            ['LOAN',     'اقساط وام',      'deduction', 'loans',      '',                 0, 0, 120],
            ['PENALTY',  'جریمه',          'deduction', 'penalty',    '',                 0, 0, 130],
            ['UNPAID',   'مرخصی بدون حقوق', 'deduction', 'leave_deduct', '',              0, 0, 140],
        ];
        foreach ($components as $c) {
            $has = DB::val('SELECT id FROM salary_components WHERE code = ?', [$c[0]]);
            if (!$has) {
                DB::insert('salary_components', [
                    'code' => $c[0], 'name' => $c[1], 'type' => $c[2], 'calc_type' => $c[3],
                    'formula' => $c[4], 'default_amount' => $c[5], 'taxable' => $c[6],
                    'sort' => $c[7], 'is_system' => 1, 'active' => 1,
                ]);
            }
        }
    }

    /** ساخت حساب مدیر اولیه */
    public static function admin(string $first, string $last, string $username, string $email, string $password): int
    {
        $superId = (int)DB::val('SELECT id FROM roles WHERE slug = ?', ['super']);

        // واحد سازمانی پیش‌فرض
        $deptId = (int)DB::val('SELECT id FROM departments WHERE name = ?', ['مدیریت عامل']);
        if (!$deptId) {
            $deptId = DB::insert('departments', ['name' => 'مدیریت عامل', 'code' => 'DIR', 'description' => 'واحد مرکزی سازمان']);
        }
        $posId = (int)DB::val('SELECT id FROM positions WHERE title = ?', ['مدیرعامل']);
        if (!$posId) {
            $posId = DB::insert('positions', ['title' => 'مدیرعامل', 'level' => 5, 'department_id' => $deptId, 'code' => 'CEO']);
        }

        // پرونده کارمندی مدیر
        $empNo = 'EMP-0001';
        $empId = DB::insert('employees', [
            'emp_no' => $empNo, 'first_name' => $first, 'last_name' => $last,
            'gender' => 'male', 'hire_date' => date('Y-m-d'),
            'dept_id' => $deptId, 'position_id' => $posId,
            'email' => $email !== '' ? $email : null,
            'contract_type' => 'permanent', 'contract_start' => date('Y-m-d'),
            'salary_base' => 0, 'status' => 'active',
            'work_days' => '6,0,1,2,3,4',
        ]);
        DB::update('departments', ['manager_id' => $empId], $deptId);

        $userId = DB::insert('users', [
            'employee_id' => $empId,
            'username'    => $username,
            'email'       => $email !== '' ? $email : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role_id'     => $superId,
            'status'      => 'active',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return $userId;
    }

    /** داده‌های نمونه کامل برای آشنایی با سامانه */
    public static function sample(): void
    {
        $password = password_hash('Demo@12345', PASSWORD_DEFAULT);
        $today = date('Y-m-d');
        $year = (int)date('Y');

        // ---- واحدها ----
        $depts = [];
        $deptDefs = [
            ['مدیریت عامل', null, 'DIR'],
            ['منابع انسانی', 1, 'HR'],
            ['امور مالی', 1, 'FIN'],
            ['فناوری اطلاعات', 1, 'IT'],
            ['فروش و بازاریابی', 1, 'SLS'],
            ['توالید', 1, 'PRD'],
            ['پشتیبانی', 1, 'SUP'],
        ];
        foreach ($deptDefs as $i => $dd) {
            $parentId = $dd[1] !== null ? ($depts[$dd[1] - 1] ?? null) : null;
            $exists = DB::val('SELECT id FROM departments WHERE code = ?', [$dd[2]]);
            $depts[$i] = (int)($exists ?: DB::insert('departments', [
                'name' => $dd[0], 'code' => $dd[2],
                'parent_id' => $parentId === null ? null : (int)$parentId,
            ]));
        }

        // ---- پست‌ها ----
        $positions = [];
        $posDefs = [
            ['مدیرعامل', 5, 1], ['معاون اجرایی', 4, 1],
            ['کارشناس ارشد منابع انسانی', 3, 2], ['کارشناس منابع انسانی', 2, 2],
            ['مدیر مالی', 4, 3], ['حسابدار', 2, 3],
            ['مدیر فناوری اطلاعات', 4, 4], ['توسعه‌دهنده نرم‌افزار', 3, 4], ['کارشناس پشتیبانی فنی', 2, 4],
            ['مدیر فروش', 4, 5], ['کارشناس فروش', 2, 5],
            ['سرپرست تولید', 4, 6], ['اپراتور تولید', 1, 6],
            ['کارشناس پشتیبانی', 2, 7],
        ];
        foreach ($posDefs as $pd) {
            $exists = DB::val('SELECT id FROM positions WHERE title = ?', [$pd[0]]);
            $positions[] = (int)($exists ?: DB::insert('positions', [
                'title' => $pd[0], 'level' => $pd[1], 'department_id' => $depts[$pd[2] - 1],
            ]));
        }

        // ---- شیفت اولیه ----
        $shiftId = (int)DB::val('SELECT id FROM shifts LIMIT 1');

        // ---- کارمندان ----
        $empDefs = [
            // first, last, gender, dept_idx, pos_idx, salary, birth, hired, manager(emp idx or null)
            ['سارا', 'محمدی', 'female', 2, 3, 28000000, '1988-04-12', '-6 years', null],
            ['رضا', 'احمدی', 'male', 3, 5, 42000000, '1983-11-02', '-7 years', null],
            ['مریم', 'کریمی', 'female', 4, 7, 38000000, '1986-02-20', '-5 years', null],
            ['حسین', 'رضایی', 'male', 4, 8, 32000000, '1992-07-15', '-3 years', 3],
            ['نگار', 'صورتی', 'female', 4, 9, 22000000, '1996-09-01', '-1 year', 3],
            ['امیر', 'موسوی', 'male', 5, 10, 40000000, '1985-01-30', '-6 years', null],
            ['زهرا', 'هاشمی', 'female', 5, 11, 24000000, '1994-05-18', '-2 years', 6],
            ['بهزاد', 'نوری', 'male', 5, 11, 23000000, '1993-08-25', '-2 years', 6],
            ['الهام', 'عزیزی', 'female', 2, 4, 20000000, '1997-03-08', '-1 year', 2],
            ['مهدی', 'صادقی', 'male', 6, 12, 35000000, '1987-12-11', '-4 years', null],
            ['فرشاد', 'کاظمی', 'male', 6, 13, 16000000, '1998-06-05', '-8 months', 10],
            ['آرمان', 'شریفی', 'male', 6, 13, 15500000, '1999-10-22', '-6 months', 10],
            ['شیرین', 'قاسمی', 'female', 7, 14, 21000000, '1991-04-27', '-3 years', 1],
            ['کامران', 'یزدانی', 'male', 4, 8, 30000000, '1990-10-14', '-4 years', 3],
            ['مهسا', 'angelo', 'female', 3, 6, 26000000, '1995-12-03', '-2 years', 2],
        ];
        $empIds = [];
        $seq = 2;
        foreach ($empDefs as $ed) {
            $empNo = sprintf('EMP-%04d', $seq++);
            $exists = DB::val('SELECT id FROM employees WHERE emp_no = ?', [$empNo]);
            if ($exists) { $empIds[] = (int)$exists; continue; }
            $managerId = $ed[8] !== null ? ($empIds[$ed[8]] ?? null) : null;
            $hireDate = date('Y-m-d', strtotime($ed[7]));
            $id = DB::insert('employees', [
                'emp_no' => $empNo,
                'first_name' => $ed[0], 'last_name' => $ed[1], 'gender' => $ed[2],
                'national_code' => sprintf('%010d', random_int(1000000000, 4999999999)),
                'birth_date' => $ed[6],
                'hire_date' => $hireDate,
                'dept_id' => $depts[$ed[3] - 1], 'position_id' => $positions[$ed[4] - 1] ?? $positions[0],
                'manager_id' => $managerId,
                'shift_id' => $shiftId,
                'email' => null, 'phone' => '09' . random_int(1000000000, 9999999999),
                'contract_type' => 'permanent',
                'contract_start' => $hireDate,
                'contract_end' => date('Y-m-d', strtotime($ed[7] . ' +3 years')),
                'salary_base' => (float)$ed[5],
                'work_days' => '6,0,1,2,3,4',
                'status' => 'active',
            ]);
            $empIds[] = $id;

            // حساب کاربری
            $uname = 'user' . ($seq - 1);
            $hasUser = DB::val('SELECT id FROM users WHERE employee_id = ?', [$id]);
            if (!$hasUser) {
                DB::insert('users', [
                    'employee_id' => $id, 'username' => $uname,
                    'password_hash' => $password,
                    'role_id' => (int)DB::val('SELECT id FROM roles WHERE slug = ?', ['employee']),
                    'status' => 'active', 'created_at' => $today . ' 10:00:00',
                ]);
            }
            // سقف مرخصی
            foreach ([1 => 30, 2 => 15, 3 => 30, 7 => 5] as $tid => $days) {
                DB::insert('leave_balances', [
                    'employee_id' => $id, 'type_id' => $tid, 'year' => $year,
                    'days_total' => $days, 'days_used' => 0,
                ]);
            }
        }
        // ایمیل کارمندان
        foreach ($empIds as $id) {
            DB::update('employees', ['email' => 'user' . $id . '@example.com'], $id);
        }
        // مدیران واحدها
        DB::update('departments', ['manager_id' => $empIds[0]], $depts[1]);
        DB::update('departments', ['manager_id' => $empIds[1]], $depts[2]);
        DB::update('departments', ['manager_id' => $empIds[2]], $depts[3]);
        DB::update('departments', ['manager_id' => $empIds[5]], $depts[4]);
        DB::update('departments', ['manager_id' => $empIds[9]], $depts[5]);

        // نقش مدیر برای چند کاربر
        foreach ([0, 2, 3, 5, 9] as $idx) {
            $uid = DB::val('SELECT id FROM users WHERE employee_id = ?', [$empIds[$idx]]);
            if ($uid) {
                DB::run('UPDATE users SET role_id = (SELECT id FROM roles WHERE slug = ?) WHERE id = ?', ['manager', $uid]);
            }
        }
        // نقش HR برای الهام و مهسا
        foreach ([8, 14] as $idx) {
            if (!isset($empIds[$idx])) continue;
            $uid = DB::val('SELECT id FROM users WHERE employee_id = ?', [$empIds[$idx]]);
            if ($uid) {
                DB::run('UPDATE users SET role_id = (SELECT id FROM roles WHERE slug = ?) WHERE id = ?', ['hr', $uid]);
            }
        }

        // ---- حضور و غیاب ماه جاری ----
        $attStatuses = ['present', 'present', 'present', 'present', 'present', 'late', 'present', 'absent', 'present', 'remote'];
        $start = strtotime(date('Y-m-01'));
        $nowTs = time();
        for ($t = $start; $t <= $nowTs; $t = strtotime('+1 day', $t)) {
            $d = date('Y-m-d', $t);
            $dow = (int)date('w', $t);
            if (in_array($dow, [5], true)) continue; // جمعه
            if (in_array($d, ['1404-01-01'], true)) continue;
            foreach ($empIds as $i => $eid) {
                $status = $attStatuses[($i + (int)date('j', $t)) % count($attStatuses)];
                if ($status === 'absent' && ($i + (int)date('j', $t)) % 7 !== 0) $status = 'present';
                $exists = DB::val('SELECT id FROM attendance WHERE employee_id = ? AND date = ?', [$eid, $d]);
                if ($exists) continue;
                if ($status === 'absent') {
                    DB::insert('attendance', ['employee_id' => $eid, 'date' => $d, 'status' => 'absent', 'source' => 'manual']);
                    continue;
                }
                if ($status === 'leave') {
                    DB::insert('attendance', ['employee_id' => $eid, 'date' => $d, 'status' => 'leave', 'source' => 'manual']);
                    continue;
                }
                $inMin = 8 * 60 + (($i * 3) % 45) - 15;   // بین 7:45 تا 8:30
                if ($status === 'late') $inMin = 8 * 60 + 10 + ($i % 25);
                $outMin = 17 * 60 + (($i * 5) % 40);
                $in = sprintf('%02d:%02d', intdiv($inMin, 60), $inMin % 60);
                $out = sprintf('%02d:%02d', intdiv($outMin, 60), $outMin % 60);
                $late = max(0, $inMin - (8 * 60 + 10));
                $workMin = max(0, $outMin - $inMin - 60);
                DB::insert('attendance', [
                    'employee_id' => $eid, 'date' => $d,
                    'check_in' => $in, 'check_out' => $out,
                    'status' => $late > 15 ? 'late' : $status,
                    'late_min' => $late, 'work_min' => $workMin,
                    'source' => 'manual',
                ]);
            }
        }

        // ---- درخواست‌های مرخصی ----
        $ltAnnual = (int)DB::val('SELECT id FROM leave_types WHERE slug = ?', ['annual']);
        $ltSick = (int)DB::val('SELECT id FROM leave_types WHERE slug = ?', ['sick']);
        $leaveDefs = [
            [$empIds[1], $ltAnnual, '-10 days', '-8 days', 'approved', 'سفر خانوادگی'],
            [$empIds[4], $ltSick, '-5 days', '-4 days', 'approved', 'استراحت پزشکی'],
            [$empIds[7], $ltAnnual, '+3 days', '+5 days', 'pending', 'مرخصی برنامه‌ریزی‌شده'],
            [$empIds[12], $ltAnnual, '+7 days', '+8 days', 'pending', 'مهمانی'],
            [$empIds[6], $ltSick, '-20 days', '-19 days', 'rejected', '—'],
        ];
        foreach ($leaveDefs as $ld) {
            $from = date('Y-m-d', strtotime($ld[2]));
            $to = date('Y-m-d', strtotime($ld[3]));
            $exists = DB::val('SELECT id FROM leave_requests WHERE employee_id = ? AND from_date = ?', [$ld[0], $from]);
            if ($exists) continue;
            $days = \App\Core\Jalali::diffDays($from, $to) + 1;
            DB::insert('leave_requests', [
                'employee_id' => $ld[0], 'type_id' => $ld[1],
                'from_date' => $from, 'to_date' => $to, 'days' => $days,
                'reason' => $ld[5], 'status' => $ld[4],
                'approver_id' => $ld[4] === 'pending' ? null : $empIds[8],
                'decided_at' => $ld[4] === 'pending' ? null : date('Y-m-d H:i:s'),
            ]);
            if ($ld[4] === 'approved') {
                DB::run('UPDATE leave_balances SET days_used = days_used + ? WHERE employee_id = ? AND type_id = ? AND year = ?',
                    [$days, $ld[0], $ld[1], $year]);
            }
        }

        // ---- درخواست‌های عمومی ----
        $reqDefs = [
            [$empIds[4], 'equipment', 'درخواست مانیتور دوم', 'به دلیل کار برنامه‌نویسی، درخواست یک مانیتور اضافه دارم.', 'pending'],
            [$empIds[10], 'general', 'درخواست انتقال شیفت', 'آیا امکان انتقال به شیفت صبح وجود دارد؟', 'pending'],
            [$empIds[6], 'suggestion', 'پیشنهاد بهبود فرآیند فروش', 'پیشنهاد می‌کنم جلسه هفتگی هماهنگی فروش برگزار شود.', 'approved'],
        ];
        foreach ($reqDefs as $rd) {
            $exists = DB::val('SELECT id FROM requests WHERE employee_id = ? AND title = ?', [$rd[0], $rd[2]]);
            if ($exists) continue;
            DB::insert('requests', [
                'employee_id' => $rd[0], 'type' => $rd[1], 'title' => $rd[2],
                'body' => $rd[3], 'status' => $rd[4],
                'approver_id' => $rd[4] === 'pending' ? null : $empIds[0],
                'decided_at' => $rd[4] === 'pending' ? null : date('Y-m-d H:i:s'),
            ]);
        }

        // ---- آگهی و داوطلب ----
        $jobId = DB::val('SELECT id FROM jobs LIMIT 1');
        if (!$jobId) {
            $jobId = DB::insert('jobs', [
                'title' => 'توسعه‌دهنده ارشد PHP', 'dept_id' => $depts[3],
                'description' => 'توسعه و نگهداری سامانه‌های تحت وب سازمان',
                'requirements' => 'PHP 8، SQLite/MySQL، تجربه حداقل ۳ سال',
                'salary_min' => 35000000, 'salary_max' => 50000000,
                'openings' => 1, 'status' => 'open', 'published_at' => $today,
            ]);
        }
        $appDefs = [
            ['علی رحیمی', 'ali@example.com', '09121112233', 'screening'],
            ['نازنین امیری', 'naz@example.com', '09354445566', 'interview'],
            ['پارسا نیک‌خواه', 'parsa@example.com', '09197778899', 'new'],
        ];
        foreach ($appDefs as $ad) {
            $exists = DB::val('SELECT id FROM applicants WHERE email = ?', [$ad[1]]);
            if ($exists) continue;
            DB::insert('applicants', [
                'job_id' => $jobId, 'full_name' => $ad[0], 'email' => $ad[1],
                'phone' => $ad[2], 'stage' => $ad[3], 'source' => 'سایت سازمان',
            ]);
        }
        $appIds = DB::all('SELECT id FROM applicants');
        if ($appIds) {
            $ivExists = DB::val('SELECT id FROM interviews LIMIT 1');
            if (!$ivExists) {
                DB::insert('interviews', [
                    'applicant_id' => (int)$appIds[0]['id'], 'interviewer_id' => $empIds[3],
                    'interview_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
                    'mode' => 'in_person', 'result' => 'pending',
                ]);
            }
        }

        // ---- دوره آموزشی ----
        $courseId = DB::val('SELECT id FROM courses LIMIT 1');
        if (!$courseId) {
            $courseId = DB::insert('courses', [
                'title' => 'مدیریت منابع انسانی مدرن', 'provider' => 'آموزشگاه توسعه',
                'start_date' => date('Y-m-d', strtotime('+10 days')),
                'end_date' => date('Y-m-d', strtotime('+13 days')),
                'seats' => 15, 'cost' => 5000000, 'status' => 'planned',
            ]);
            foreach ([$empIds[8], $empIds[13]] as $eid) {
                DB::insert('enrollments', ['course_id' => $courseId, 'employee_id' => $eid, 'status' => 'enrolled']);
            }
        }

        // ---- اهداف و ارزیابی ----
        $goalDefs = [
            [$empIds[3], 'کاهش زمان پاسخ‌گویی پشتیبانی', 'کاهش میانگین تیکت‌ها به زیر ۴ ساعت', 40, 70],
            [$empIds[6], 'افزایش فروش فصل', 'رسیدن به ۱۲۰٪ هدف فروش', 50, 95],
            [$empIds[8], 'مستندسازی فرآیندهای HR', 'تکمیل ۱۰ فرآیند کلیدی', 30, 60],
        ];
        foreach ($goalDefs as $gd) {
            $exists = DB::val('SELECT id FROM goals WHERE employee_id = ? AND title = ?', [$gd[0], $gd[1]]);
            if ($exists) continue;
            DB::insert('goals', [
                'employee_id' => $gd[0], 'title' => $gd[1], 'description' => $gd[2],
                'weight' => $gd[3], 'score' => $gd[4], 'period' => date('Y') . '-نیمه اول',
                'status' => 'active', 'due_date' => date('Y-m-d', strtotime('+60 days')),
            ]);
        }
        $revExists = DB::val('SELECT id FROM reviews LIMIT 1');
        if (!$revExists && isset($empIds[3])) {
            DB::insert('reviews', [
                'employee_id' => $empIds[3], 'reviewer_id' => $empIds[2],
                'period' => date('Y') . '-نیمه اول', 'overall_score' => 82,
                'strengths' => 'پاسخ‌گویی سریع، نظم بالا',
                'improvements' => 'بهبود مهارت‌های ارتباطی با مشتری',
                'status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // ---- پاداش/جریمه ----
        $rpExists = DB::val('SELECT id FROM rewards_penalties LIMIT 1');
        if (!$rpExists && isset($empIds[6])) {
            DB::insert('rewards_penalties', [
                'employee_id' => $empIds[6], 'kind' => 'bonus', 'amount' => 8000000,
                'date' => $today, 'reason' => 'دستیابی به ۱۵۰٪ هدف فروش فصل گذشته',
            ]);
        }

        // ---- وام ----
        $loanExists = DB::val('SELECT id FROM loans LIMIT 1');
        if (!$loanExists && isset($empIds[12])) {
            DB::insert('loans', [
                'employee_id' => $empIds[12], 'amount' => 60000000, 'installment' => 5000000,
                'months' => 12, 'paid_installments' => 3, 'start_date' => date('Y-m-d', strtotime('-3 months')),
                'reason' => 'وام ضروری', 'status' => 'active',
            ]);
        }

        // ---- سوابق شغلی چند نفر ----
        foreach ([[$empIds[3], 'توسعه‌دهنده', 'فناوری اطلاعات', '-3 years', '-1 year'], [$empIds[6], 'کارشناس فروش', 'فروش', '-2 years', null]] as $wh) {
            $exists = DB::val('SELECT id FROM work_history WHERE employee_id = ? AND title = ?', [$wh[0], $wh[1]]);
            if ($exists) continue;
            DB::insert('work_history', [
                'employee_id' => $wh[0], 'title' => $wh[1], 'department' => $wh[2],
                'from_date' => date('Y-m-d', strtotime($wh[3])),
                'to_date' => $wh[4] ? date('Y-m-d', strtotime($wh[4])) : null,
            ]);
        }

        Settings::set('sample_seeded', '1');
    }
}
