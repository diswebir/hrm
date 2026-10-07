<?php
/**
 * نقشه مسیرهای سامانه
 * auth=false → نیازمند ورود نیست
 * perm → الزام مجوز مشخص
 */

/** ---------- عمومی ---------- */
$router->get('/about', 'About@index', ['auth' => false]);
$router->get('/login', 'Auth@loginForm', ['auth' => false]);
$router->post('/login', 'Auth@login', ['auth' => false]);
$router->post('/logout', 'Auth@logout');
$router->get('/apply', 'Recruit@applyForm', ['auth' => false]);
$router->post('/apply', 'Recruit@apply', ['auth' => false]);

/** ---------- داشبورد و پروفایل ---------- */
$router->get('/', 'Dashboard@index');
$router->get('/my', 'Dashboard@self');
$router->get('/profile', 'Profile@index');
$router->post('/profile', 'Profile@update');
$router->post('/profile/password', 'Profile@password');
$router->get('/profile/sessions', 'Profile@sessions');
$router->post('/profile/sessions/revoke', 'Profile@revokeSession');
$router->post('/profile/sessions/revoke-others', 'Profile@revokeOthers');

/** ---------- جستجو و اعلان ---------- */
$router->get('/search', 'Search@index');
$router->get('/notifications', 'Notifications@index');
$router->post('/notifications/read', 'Notifications@read');
$router->post('/notifications/read-all', 'Notifications@readAll');

/** ---------- کارمندان ---------- */
$router->get('/employees', 'Employees@index', ['perm' => 'employees.view']);
$router->get('/employees/export', 'Employees@export', ['perm' => 'employees.export']);
$router->get('/employees/template', 'Employees@template', ['perm' => 'employees.import']);
$router->get('/employees/import', 'Employees@importForm', ['perm' => 'employees.import']);
$router->post('/employees/import', 'Employees@importRun', ['perm' => 'employees.import']);
$router->get('/employees/create', 'Employees@create', ['perm' => 'employees.create']);
$router->post('/employees', 'Employees@store', ['perm' => 'employees.create']);
$router->get('/employees/{id}', 'Employees@show');
$router->get('/employees/{id}/edit', 'Employees@edit', ['perm' => 'employees.update']);
$router->post('/employees/{id}', 'Employees@update', ['perm' => 'employees.update']);
$router->post('/employees/{id}/delete', 'Employees@delete', ['perm' => 'employees.delete']);
$router->post('/employees/{id}/archive', 'Employees@archive', ['perm' => 'employees.delete']);
$router->post('/employees/{id}/restore', 'Employees@restore', ['perm' => 'employees.delete']);
$router->post('/employees/{id}/photo', 'Employees@photo', ['perm' => 'employees.update']);
$router->post('/employees/{id}/note', 'Employees@addNote', ['perm' => 'employees.view']);
$router->post('/employees/{id}/history', 'Employees@addHistory', ['perm' => 'employees.update']);
$router->post('/documents', 'Employees@addDocument', ['perm' => 'employees.update']);
$router->post('/documents/{id}/delete', 'Employees@deleteDocument', ['perm' => 'employees.update']);
$router->get('/directory', 'Employees@directory', ['perm' => 'employees.view']);

/** ---------- ساختار سازمانی ---------- */
$router->get('/org', 'Org@index', ['perm' => 'org.manage']);
$router->get('/org/chart', 'Org@chart', ['perm' => 'org.view']);
$router->post('/org/departments', 'Org@saveDept', ['perm' => 'org.manage']);
$router->post('/org/departments/{id}/delete', 'Org@deleteDept', ['perm' => 'org.manage']);
$router->post('/org/positions', 'Org@savePos', ['perm' => 'org.manage']);
$router->post('/org/positions/{id}/delete', 'Org@deletePos', ['perm' => 'org.manage']);

/** ---------- کاربران و نقش‌ها ---------- */
$router->get('/users', 'Users@index', ['perm' => 'users.manage']);
$router->post('/users', 'Users@store', ['perm' => 'users.manage']);
$router->get('/users/{id}/edit', 'Users@edit', ['perm' => 'users.manage']);
$router->post('/users/{id}', 'Users@update', ['perm' => 'users.manage']);
$router->post('/users/{id}/delete', 'Users@delete', ['perm' => 'users.manage']);
$router->get('/roles', 'Roles@index', ['perm' => 'roles.manage']);
$router->post('/roles/store', 'Roles@store', ['perm' => 'roles.manage']);
$router->post('/roles/{id}', 'Roles@update', ['perm' => 'roles.manage']);

/** ---------- حضور و غیاب ---------- */
$router->get('/attendance', 'Attendance@index', ['perm' => 'attendance.view']);
$router->get('/attendance/my', 'Attendance@my');
$router->post('/attendance/check', 'Attendance@check');
$router->get('/attendance/export', 'Attendance@export', ['perm' => 'attendance.export']);
$router->post('/attendance/save', 'Attendance@save', ['perm' => 'attendance.manage']);
$router->post('/attendance/{id}/delete', 'Attendance@delete', ['perm' => 'attendance.manage']);

/** ---------- اضافه‌کاری ---------- */
$router->get('/overtime', 'Overtime@index');
$router->post('/overtime', 'Overtime@store');
$router->post('/overtime/{id}/approve', 'Overtime@approve', ['perm' => 'overtime.approve']);
$router->post('/overtime/{id}/reject', 'Overtime@reject', ['perm' => 'overtime.approve']);

/** ---------- مرخصی ---------- */
$router->get('/leave', 'Leave@index');
$router->post('/leave', 'Leave@store');
$router->get('/leave/calendar', 'Leave@calendar');
$router->get('/leave/balances', 'Leave@balances');
$router->post('/leave/balances', 'Leave@balancesSave', ['perm' => 'leave.manage']);
$router->get('/leave/types', 'Leave@types', ['perm' => 'leave.manage']);
$router->post('/leave/types', 'Leave@typesSave', ['perm' => 'leave.manage']);
$router->get('/leave/holidays', 'Leave@holidays', ['perm' => 'leave.manage']);
$router->post('/leave/holidays', 'Leave@holidaysSave', ['perm' => 'leave.manage']);
$router->post('/leave/holidays/{id}/delete', 'Leave@holidayDelete', ['perm' => 'leave.manage']);
$router->post('/leave/{id}/approve', 'Leave@approve', ['perm' => 'leave.approve']);
$router->post('/leave/{id}/reject', 'Leave@reject', ['perm' => 'leave.approve']);
$router->post('/leave/{id}/cancel', 'Leave@cancel');

/** ---------- حقوق و دستمزد ---------- */
$router->get('/payroll', 'Payroll@index', ['perm' => 'payroll.view']);
$router->get('/payroll/slip/{id}', 'Payroll@slip', ['perm' => 'payroll.view']);
$router->get('/payroll/export', 'Payroll@export', ['perm' => 'payroll.view']);
$router->post('/payroll/run', 'Payroll@run', ['perm' => 'payroll.run']);
$router->post('/payroll/{id}/finalize', 'Payroll@finalize', ['perm' => 'payroll.run']);
$router->post('/payroll/copy', 'Payroll@copy', ['perm' => 'payroll.run']);
$router->post('/payroll/components', 'Payroll@componentSave', ['perm' => 'payroll.manage']);
$router->post('/payroll/components/assign', 'Payroll@componentAssign', ['perm' => 'payroll.manage']);
$router->post('/payroll/components/{id}/delete', 'Payroll@componentDelete', ['perm' => 'payroll.manage']);
$router->post('/payroll/assignments/{id}/delete', 'Payroll@componentUnassign', ['perm' => 'payroll.manage']);
$router->get('/payroll/loans', 'Payroll@loans', ['perm' => 'payroll.manage']);
$router->post('/payroll/loans', 'Payroll@loanSave', ['perm' => 'payroll.manage']);
$router->post('/payroll/loans/{id}/pay', 'Payroll@loanPay', ['perm' => 'payroll.manage']);
$router->post('/payroll/loans/{id}/delete', 'Payroll@loanDelete', ['perm' => 'payroll.manage']);

/** ---------- درخواست‌ها ---------- */
$router->get('/requests', 'Requests@index');
$router->post('/requests', 'Requests@store');
$router->post('/requests/{id}/approve', 'Requests@approve', ['perm' => 'requests.approve']);
$router->post('/requests/{id}/reject', 'Requests@reject', ['perm' => 'requests.approve']);

/** ---------- جذب و استخدام ---------- */
$router->get('/recruit', 'Recruit@index', ['perm' => 'recruits.view']);
$router->post('/recruit/jobs', 'Recruit@jobStore', ['perm' => 'recruits.manage']);
$router->post('/recruit/jobs/{id}/toggle', 'Recruit@jobPublish', ['perm' => 'recruits.manage']);
$router->post('/recruit/applicants', 'Recruit@applicantStore', ['perm' => 'recruits.manage']);
$router->post('/recruit/applicants/{id}/stage', 'Recruit@stage', ['perm' => 'recruits.manage']);
$router->post('/recruit/interviews', 'Recruit@interviewStore', ['perm' => 'recruits.manage']);

/** ---------- آموزش ---------- */
$router->get('/training', 'Training@index', ['perm' => 'training.view']);
$router->post('/training/courses', 'Training@courseStore', ['perm' => 'training.manage']);
$router->post('/training/enroll', 'Training@enroll', ['perm' => 'training.manage']);
$router->post('/training/enrollments/{id}/complete', 'Training@complete', ['perm' => 'training.manage']);
$router->post('/training/enrollments/{id}/delete', 'Training@unenroll', ['perm' => 'training.manage']);

/** ---------- عملکرد ---------- */
$router->get('/performance', 'Performance@goals', ['perm' => 'performance.view']);
$router->get('/performance/goals', 'Performance@goals', ['perm' => 'performance.view']);
$router->post('/performance/goals', 'Performance@goalStore', ['perm' => 'performance.manage']);
$router->post('/performance/goals/{id}/score', 'Performance@goalScore', ['perm' => 'performance.manage']);
$router->get('/performance/reviews', 'Performance@reviews', ['perm' => 'performance.view']);
$router->post('/performance/reviews', 'Performance@reviewStore', ['perm' => 'performance.manage']);
$router->get('/performance/penalties', 'Performance@penalties', ['perm' => 'performance.view']);
$router->post('/performance/penalties', 'Performance@penalty', ['perm' => 'performance.manage']);
$router->post('/performance/discipline', 'Performance@disciplineStore', ['perm' => 'discipline.manage']);

/** ---------- گزارش‌ها ---------- */
$router->get('/reports', 'Reports@index', ['perm' => 'reports.view']);
$router->get('/reports/{slug}', 'Reports@run', ['perm' => 'reports.view']);
$router->get('/reports/{slug}/export', 'Reports@export', ['perm' => 'reports.export']);

/** ---------- سیستم ---------- */
$router->get('/settings', 'Settings@index', ['perm' => 'settings.manage']);
$router->post('/settings', 'Settings@save', ['perm' => 'settings.manage']);
$router->get('/backup', 'Backup@index', ['perm' => 'backup.manage']);
$router->post('/backup/create', 'Backup@create', ['perm' => 'backup.manage']);
$router->get('/backup/download/{file}', 'Backup@download', ['perm' => 'backup.manage']);
$router->post('/backup/restore', 'Backup@restore', ['perm' => 'backup.manage']);
$router->post('/backup/delete/{file}', 'Backup@delete', ['perm' => 'backup.manage']);
$router->get('/logs', 'Logs@index', ['perm' => 'logs.view']);
$router->get('/logs/export', 'Logs@export', ['perm' => 'logs.view']);
$router->get('/health', 'Health@index', ['perm' => 'health.view']);
