<?php
/** PDO singleton + helper that binds ints as ints (needed for LIMIT/OFFSET). */
class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $c = require ROOT_PATH . '/config/database.php';
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset={$c['charset']}";
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Keep MySQL and PHP on Philippine time so "5m ago" labels are correct
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+08:00'",
            ]);
        }
        return self::$pdo;
    }

    /** Prepare + execute with positional params. */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $i = 1;
        foreach ($params as $value) {
            if (is_int($value)) {
                $stmt->bindValue($i, $value, PDO::PARAM_INT);
            } elseif ($value === null) {
                $stmt->bindValue($i, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($i, (string)$value, PDO::PARAM_STR);
            }
            $i++;
        }
        $stmt->execute();
        return $stmt;
    }

    public static function lastId(): int
    {
        return (int)self::connection()->lastInsertId();
    }
}
