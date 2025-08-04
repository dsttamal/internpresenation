<?php

namespace Config;

use PDO;
use PDOException;

/**
 * Simple Database Configuration
 * 
 * A lightweight database configuration that works without Eloquent ORM
 * for basic database operations.
 */
class SimpleDatabase
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }

        return self::$connection;
    }

    private static function connect(): void
    {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $database = $_ENV['DB_DATABASE'] ?? 'form_builder';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function testConnection(): bool
    {
        try {
            $pdo = self::getConnection();
            $pdo->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function query(string $sql, array $params = []): array
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    public static function getStats(): array
    {
        try {
            $pdo = self::getConnection();
            
            // Get database info
            $version = $pdo->query('SELECT VERSION() as version')->fetch()['version'];
            $charset = $pdo->query('SELECT @@character_set_database as charset')->fetch()['charset'];
            $collation = $pdo->query('SELECT @@collation_database as collation')->fetch()['collation'];
            
            // Get table count
            $database = $_ENV['DB_DATABASE'] ?? 'form_builder';
            $tableCount = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '{$database}'")->fetch()['count'];

            return [
                'version' => $version,
                'charset' => $charset,
                'collation' => $collation,
                'tables' => (int) $tableCount,
                'connection_status' => 'Connected'
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'connection_status' => 'Failed'
            ];
        }
    }
}
