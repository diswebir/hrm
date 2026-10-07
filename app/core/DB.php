<?php
/**
 * اتصال و مدیریت پایگاه داده SQLite (PDO)
 */
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

final class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $path = $GLOBALS['hrm_config']['db_path'] ?? (HRM_ROOT . '/storage/database/hrm.sqlite');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            self::$pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }
        return self::$pdo;
    }

    /** اجرای کوئری SELECT و برگرداندن همه ردیف‌ها */
    public static function all(string $sql, array $params = []): array
    {
        $st = self::run($sql, $params);
        return $st->fetchAll();
    }

    /** اولین ردیف */
    public static function row(string $sql, array $params = []): ?array
    {
        $st = self::run($sql, $params);
        $r = $st->fetch();
        return $r === false ? null : $r;
    }

    /** یک مقدار تکی */
    public static function val(string $sql, array $params = [])
    {
        $st = self::run($sql, $params);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    }

    /** شمارش ساده */
    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        return (int)self::val("SELECT COUNT(*) FROM {$table} WHERE {$where}", $params);
    }

    /** INSERT — برگرداندن شناسه */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES ('
            . implode(', ', array_map(fn($c) => ':' . $c, $cols)) . ')';
        self::run($sql, $data);
        return (int)self::conn()->lastInsertId();
    }

    /** UPDATE بر اساس id */
    public static function update(string $table, array $data, int $id): bool
    {
        if (!$data) return false;
        $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $sql = "UPDATE {$table} SET {$sets} WHERE id = :__id";
        $data['__id'] = $id;
        self::run($sql, $data);
        return true;
    }

    /** UPDATE با شرط دلخواه */
    public static function updateWhere(string $table, array $data, string $where, array $params = []): int
    {
        if (!$data) return 0;
        $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $sql = "UPDATE {$table} SET {$sets} WHERE {$where}";
        self::run($sql, $data + $params);
        return self::conn()->rowCount();
    }

    /** DELETE */
    public static function delete(string $table, int $id): bool
    {
        self::run("DELETE FROM {$table} WHERE id = ?", [$id]);
        return true;
    }

    /** بستن اتصال (برای بازیابی پشتیبان) */
    public static function close(): void
    {
        self::$pdo = null;
    }

    /** اجرای هر کوئری */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /** اجرای دسته‌ای از دستورات SQL (نصب) */
    public static function execScript(string $sql): void
    {
        self::conn()->exec($sql);
    }

    /** شروع تراکنش */
    public static function begin(): void
    {
        self::conn()->beginTransaction();
    }

    public static function commit(): void
    {
        if (self::conn()->inTransaction()) self::conn()->commit();
    }

    public static function rollBack(): void
    {
        if (self::conn()->inTransaction()) self::conn()->rollBack();
    }

    /** تراکنش امن */
    public static function transaction(callable $fn)
    {
        self::begin();
        try {
            $result = $fn();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }

    /** اطلاعات دیتابیس برای صفحه سلامت */
    public static function info(): array
    {
        $path = $GLOBALS['hrm_config']['db_path'] ?? '';
        return [
            'path'   => $path,
            'size'   => is_file($path) ? filesize($path) : 0,
            'tables' => (int)self::val("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"),
        ];
    }

    public static function optimize(): void
    {
        self::conn()->exec('VACUUM');
        self::conn()->exec('PRAGMA optimize');
    }
}
