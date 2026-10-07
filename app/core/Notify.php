<?php
/**
 * اعلان‌های درون‌برنامه‌ای
 */
declare(strict_types=1);

namespace App\Core;

final class Notify
{
    /** اعلان برای یک کاربر */
    public static function toUser(int $userId, string $title, string $body = '', string $link = ''): void
    {
        if ($userId <= 0) return;
        DB::insert('notifications', [
            'user_id'    => $userId,
            'title'      => mb_substr($title, 0, 150),
            'body'       => mb_substr($body, 0, 500),
            'link'       => $link,
            'read_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** اعلان برای همه کاربران با یک مجوز مشخص */
    public static function toPermission(string $permission, string $title, string $body = '', string $link = ''): void
    {
        $rows = DB::all('SELECT DISTINCT u.id FROM users u
                         JOIN roles r ON r.id = u.role_id
                         LEFT JOIN role_permissions rp ON rp.role_id = r.id
                         WHERE u.status = ? AND (rp.permission = ? OR r.slug = ?)',
            ['active', $permission, 'super']);
        foreach ($rows as $r) {
            self::toUser((int)$r['id'], $title, $body, $link);
        }
    }

    /** اعلان عمومی مدیران (HR و بالاتر) */
    public static function toManagers(string $title, string $body = '', string $link = ''): void
    {
        self::toPermission('requests.approve', $title, $body, $link);
    }

    public static function unread(int $userId): array
    {
        return DB::all('SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL
                        ORDER BY created_at DESC LIMIT 20', [$userId]);
    }

    public static function unreadCount(int $userId): int
    {
        return (int)DB::val('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public static function recent(int $userId, int $limit = 8): array
    {
        return DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . $limit, [$userId]);
    }

    public static function markRead(int $id, int $userId): void
    {
        DB::run('UPDATE notifications SET read_at = ? WHERE id = ? AND user_id = ?',
            [date('Y-m-d H:i:s'), $id, $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        DB::run('UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL',
            [date('Y-m-d H:i:s'), $userId]);
    }
}
