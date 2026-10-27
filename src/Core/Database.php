<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Lazily-created PDO connection to PostgreSQL.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                Config::get('DB_HOST', '127.0.0.1'),
                Config::get('DB_PORT', '5432'),
                Config::get('DB_NAME', 'hardware_store'),
            );
            self::$pdo = new PDO($dsn, (string) Config::get('DB_USER'), (string) Config::get('DB_PASSWORD', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }

    /**
     * Runs $work inside a transaction, committing on success and rolling back on any exception.
     *
     * @template T
     * @param callable(PDO): T $work
     * @return T
     */
    public static function transaction(callable $work): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $work($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
