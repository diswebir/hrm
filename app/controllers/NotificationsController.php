<?php
/**
 * اعلان‌ها
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Notify;

final class NotificationsController extends Controller
{
    public function index(): void
    {
        $uid = Auth::id();
        if (isset($_GET['count']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
            echo json_encode(['count' => Notify::unreadCount($uid)]);
            exit;
        }

        $page = $this->page();
        $perPage = $this->perPage();
        $total = (int)\App\Core\DB::val('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$uid]);
        $rows = \App\Core\DB::all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . $perPage .
            ' OFFSET ' . (($page - 1) * $perPage),
            [$uid]
        );
        $unread = Notify::unreadCount($uid);

        $this->render('notifications/index', [
            'title' => 'اعلان‌ها',
            'rows' => $rows, 'total' => $total, 'page' => $page,
            'pager' => $this->pager($total, $perPage, $page),
            'unread' => $unread,
        ]);
    }

    public function read(): void
    {
        $id = (int)post('id', 0);
        Notify::markRead($id, Auth::id());
        $back = post('back', '/notifications');
        redirect(is_string($back) && $back[0] === '/' ? $back : '/notifications');
    }

    public function readAll(): void
    {
        Notify::markAllRead(Auth::id());
        flash_set('success', 'همه اعلان‌ها خوانده شد.');
        redirect('/notifications');
    }
}
