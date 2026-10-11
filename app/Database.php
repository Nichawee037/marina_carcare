<?php
declare(strict_types=1);
require_once __DIR__ . '/Config.php';
class Database {
    private static ?PDO $connection = null;
    public static function connection(): PDO {
        if (self::$connection === null) {
            $dsn = 'mysql:host=' . Config::DB_HOST . ';dbname=' . Config::DB_NAME . ';charset=utf8mb4';
            self::$connection = new PDO($dsn, Config::DB_USER, Config::DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        }
        return self::$connection;
    }
}
