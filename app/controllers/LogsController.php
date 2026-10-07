<?php
/**
 * گزارش رویدادها (لاگ)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;

final class LogsController extends Controller
{
    public function index(): void
    {
        $level = (string)($_GET['level'] ?? '');
        $action = trim((string)($_GET['action'] ?? ''));
        $page = $this->page();
        $perPage = 50;

        $where = ['1=1'];
        $params = [];
        if ($level !== '' && in_array($level, ['info', 'warning', 'error'], true)) {
            $where[] = 'l.level = ?';
            $params[] = $level;
        }
        if ($action !== '') {
            $where[] = 'l.action LIKE ?';
            $params[] = '%' . $action . '%';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int)DB::val("SELECT COUNT(*) FROM activity_log l WHERE {$whereSql}", $params);
        $rows = DB::all(
            "SELECT l.*, u.username
             FROM activity_log l LEFT JOIN users u ON u.id = l.user_id
             WHERE {$whereSql}
             ORDER BY l.id DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $params
        );
        $actions = DB::all('SELECT DISTINCT action FROM activity_log ORDER BY action LIMIT 60');

        $this->render('logs/index', [
            'title' => 'گزارش رویدادها',
            'rows' => $rows, 'total' => $total, 'page' => $page,
            'pager' => $this->pager($total, $perPage, $page),
            'actions' => array_column($actions, 'action'),
            'filters' => ['level' => $level, 'action' => $action],
            'counts' => [
                'error' => (int)DB::val("SELECT COUNT(*) FROM activity_log WHERE level='error'"),
                'warning' => (int)DB::val("SELECT COUNT(*) FROM activity_log WHERE level='warning'"),
                'total' => (int)DB::val('SELECT COUNT(*) FROM activity_log'),
            ],
        ]);
    }

    public function export(): void
    {
        $rows = DB::all(
            "SELECT l.created_at, l.level, l.action, l.message, l.context, l.ip_address, u.username
             FROM activity_log l LEFT JOIN users u ON u.id = l.user_id
             ORDER BY l.id DESC LIMIT 5000"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['created_at'], $r['level'], $r['action'], (string)$r['message'],
                      (string)$r['context'], (string)$r['username'], (string)$r['ip_address']];
        }
        csv_download('logs-' . date('Ymd-His'),
            ['زمان', 'سطح', 'عملیات', 'پیام', 'زمینه', 'کاربر', 'IP'], $out);
    }
}
