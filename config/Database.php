<?php

namespace Config;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

/**
 * Database Configuration
 * 
 * Sets up the database connection using Eloquent ORM.
 */
class Database
{
    private static ?Capsule $capsule = null;
    private static bool $initialized = false;

    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$capsule = new Capsule;

        self::$capsule->addConnection([
            'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'form_builder',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ]);

        // Set the event dispatcher used by Eloquent models (optional for basic usage)
        if (class_exists('\Illuminate\Events\Dispatcher') && class_exists('\Illuminate\Container\Container')) {
            try {
                self::$capsule->setEventDispatcher(new Dispatcher(new Container()));
            } catch (\Exception $e) {
                // Events dispatcher is optional, continue without it for basic operations
                error_log('Warning: Could not set event dispatcher: ' . $e->getMessage());
            }
        }

        // Make this Capsule instance available globally via static methods
        self::$capsule->setAsGlobal();

        // Setup the Eloquent ORM
        self::$capsule->bootEloquent();

        self::$initialized = true;
    }

    public static function getCapsule(): Capsule
    {
        if (!self::$initialized) {
            self::initialize();
        }
        
        return self::$capsule;
    }

    /**
     * Get the database connection
     */
    public static function getConnection()
    {
        if (!self::$initialized) {
            self::initialize();
        }

        return self::$capsule->getConnection();
    }

    public static function testConnection(): bool
    {
        try {
            if (!self::$initialized) {
                self::initialize();
            }

            $connection = self::getConnection();
            $connection->getPdo();
            
            // Test with a simple query
            $connection->select('SELECT 1');
            
            return true;
        } catch (\Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database statistics
     */
    public static function getStats(): array
    {
        try {
            $connection = self::getConnection();
            
            $stats = [];
            
            // Get total record counts for main tables
            $tables = ['users', 'forms', 'submissions', 'settings'];
            
            foreach ($tables as $table) {
                try {
                    $result = $connection->select("SELECT COUNT(*) as count FROM `{$table}`");
                    $stats['table_counts'][$table] = $result[0]['count'] ?? 0;
                } catch (\Exception $e) {
                    $stats['table_counts'][$table] = 'N/A';
                }
            }
            
            // Database size
            try {
                $result = $connection->select("
                    SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
                    FROM 
                        information_schema.tables 
                    WHERE 
                        table_schema = ?
                ", [$_ENV['DB_DATABASE']]);
                
                $stats['database_size_mb'] = $result[0]['db_size_mb'] ?? 'N/A';
            } catch (\Exception $e) {
                $stats['database_size_mb'] = 'N/A';
            }
            
            // Connection info
            $stats['connection_info'] = [
                'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'database' => $_ENV['DB_DATABASE'] ?? 'form_builder',
                'charset' => 'utf8mb4'
            ];
            
            return $stats;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
