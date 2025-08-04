<?php

/**
 * Database Model Inspector
 * 
 * This script provides information about the database structure,
 * relationships, and statistics for the Form Builder application.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Config\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// ANSI color codes for console output
const RED = "\033[31m";
const GREEN = "\033[32m";
const YELLOW = "\033[33m";
const BLUE = "\033[34m";
const MAGENTA = "\033[35m";
const CYAN = "\033[36m";
const WHITE = "\033[37m";
const RESET = "\033[0m";

function colorize($text, $color) {
    return $color . $text . RESET;
}

function printHeader($title) {
    echo "\n" . colorize(str_repeat("=", 60), CYAN) . "\n";
    echo colorize(" " . strtoupper($title), WHITE) . "\n";
    echo colorize(str_repeat("=", 60), CYAN) . "\n\n";
}

function printSubHeader($title) {
    echo colorize("\n" . $title, YELLOW) . "\n";
    echo colorize(str_repeat("-", strlen($title)), YELLOW) . "\n";
}

try {
    // Initialize database connection
    Database::initialize();
    
    printHeader("Form Builder Database Model Inspector");
    
    // Test connection
    if (Database::testConnection()) {
        echo colorize("✅ Database connection successful!", GREEN) . "\n";
    } else {
        echo colorize("❌ Database connection failed!", RED) . "\n";
        exit(1);
    }
    
    // Get database statistics
    printSubHeader("Database Statistics");
    $stats = Database::getStats();
    
    if (isset($stats['error'])) {
        echo colorize("Error: " . $stats['error'], RED) . "\n";
    } else {
        echo sprintf("Database: %s\n", colorize($stats['connection_info']['database'], CYAN));
        echo sprintf("Host: %s\n", colorize($stats['connection_info']['host'], CYAN));
        echo sprintf("Driver: %s\n", colorize($stats['connection_info']['driver'], CYAN));
        echo sprintf("Size: %s MB\n", colorize($stats['database_size_mb'] ?? 'N/A', MAGENTA));
        
        echo "\nTable Record Counts:\n";
        foreach ($stats['table_counts'] as $table => $count) {
            $color = is_numeric($count) ? ($count > 0 ? GREEN : YELLOW) : RED;
            echo sprintf("  %s: %s\n", 
                colorize(ucfirst($table), WHITE), 
                colorize($count, $color)
            );
        }
    }
    
    // Table structures
    printSubHeader("Table Structures");
    
    $tables = [
        'users' => 'User accounts and authentication',
        'forms' => 'Dynamic form definitions',
        'submissions' => 'Form submission data',
        'bkash_tokens' => 'bKash payment service tokens',
        'settings' => 'Application configuration'
    ];
    
    $connection = Database::getConnection();
    
    foreach ($tables as $tableName => $description) {
        echo colorize("\n📋 Table: $tableName", BLUE) . " - $description\n";
        
        try {
            $columns = $connection->select("DESCRIBE `{$tableName}`");
            
            echo sprintf("%-20s %-20s %-8s %-8s %-10s %s\n",
                colorize("Column", WHITE),
                colorize("Type", WHITE), 
                colorize("Null", WHITE),
                colorize("Key", WHITE),
                colorize("Default", WHITE),
                colorize("Extra", WHITE)
            );
            echo str_repeat("-", 80) . "\n";
            
            foreach ($columns as $column) {
                $keyColor = match($column['Key']) {
                    'PRI' => RED,
                    'UNI' => MAGENTA,
                    'MUL' => YELLOW,
                    default => WHITE
                };
                
                echo sprintf("%-20s %-20s %-8s %-8s %-10s %s\n",
                    $column['Field'],
                    $column['Type'],
                    $column['Null'],
                    colorize($column['Key'], $keyColor),
                    $column['Default'] ?? 'NULL',
                    $column['Extra']
                );
            }
            
        } catch (Exception $e) {
            echo colorize("❌ Table not found or accessible", RED) . "\n";
        }
    }
    
    // Foreign Key Relationships
    printSubHeader("Foreign Key Relationships");
    
    try {
        $foreignKeys = $connection->select("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                CONSTRAINT_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                TABLE_SCHEMA = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME, COLUMN_NAME
        ", [$_ENV['DB_DATABASE']]);
        
        if (empty($foreignKeys)) {
            echo colorize("No foreign key constraints found.", YELLOW) . "\n";
        } else {
            foreach ($foreignKeys as $fk) {
                echo sprintf("%s.%s → %s.%s (%s)\n",
                    colorize($fk['TABLE_NAME'], CYAN),
                    $fk['COLUMN_NAME'],
                    colorize($fk['REFERENCED_TABLE_NAME'], CYAN),
                    $fk['REFERENCED_COLUMN_NAME'],
                    colorize($fk['CONSTRAINT_NAME'], MAGENTA)
                );
            }
        }
    } catch (Exception $e) {
        echo colorize("❌ Could not retrieve foreign key information", RED) . "\n";
    }
    
    // Indexes
    printSubHeader("Database Indexes");
    
    foreach (array_keys($tables) as $tableName) {
        try {
            $indexes = $connection->select("SHOW INDEX FROM `{$tableName}`");
            
            if (!empty($indexes)) {
                echo colorize("\n🔍 Indexes for: $tableName", BLUE) . "\n";
                
                $groupedIndexes = [];
                foreach ($indexes as $index) {
                    $groupedIndexes[$index['Key_name']][] = $index;
                }
                
                foreach ($groupedIndexes as $indexName => $columns) {
                    $indexType = $columns[0]['Non_unique'] == 0 ? 'UNIQUE' : 'INDEX';
                    $indexColor = $indexType === 'UNIQUE' ? MAGENTA : YELLOW;
                    
                    $columnNames = array_map(function($col) {
                        return $col['Column_name'];
                    }, $columns);
                    
                    echo sprintf("  %s %s (%s)\n",
                        colorize($indexType, $indexColor),
                        $indexName === 'PRIMARY' ? colorize($indexName, RED) : $indexName,
                        implode(', ', $columnNames)
                    );
                }
            }
        } catch (Exception $e) {
            // Skip if table doesn't exist
        }
    }
    
    // JSON Field Examples
    printSubHeader("JSON Field Examples");
    
    echo colorize("📄 Form Fields Structure:", BLUE) . "\n";
    echo json_encode([
        [
            "id" => "field_001",
            "type" => "text", 
            "label" => "Full Name",
            "required" => true,
            "validation" => ["minLength" => 2, "maxLength" => 100]
        ],
        [
            "id" => "field_002",
            "type" => "email",
            "label" => "Email Address", 
            "required" => true
        ]
    ], JSON_PRETTY_PRINT) . "\n";
    
    echo colorize("\n💳 Payment Info Structure:", BLUE) . "\n";
    echo json_encode([
        "amount" => 1000.00,
        "currency" => "BDT",
        "method" => "bkash",
        "transactionId" => "TXN123456789",
        "status" => "completed",
        "gateway" => [
            "provider" => "bkash",
            "paymentID" => "TR001234567890"
        ]
    ], JSON_PRETTY_PRINT) . "\n";
    
    // Sample Queries
    printSubHeader("Common Query Patterns");
    
    $queries = [
        "Get user's active forms" => "SELECT * FROM forms WHERE createdBy = ? AND isActive = 1",
        "Find submission by unique ID" => "SELECT * FROM submissions WHERE uniqueId = ?",
        "Get pending submissions" => "SELECT * FROM submissions WHERE status = 'pending'",
        "Form submissions by date range" => "SELECT * FROM submissions WHERE formId = ? AND createdAt BETWEEN ? AND ?",
        "User authentication" => "SELECT * FROM users WHERE (email = ? OR username = ?) AND isActive = 1"
    ];
    
    foreach ($queries as $description => $query) {
        echo colorize("$description:", YELLOW) . "\n";
        echo "  $query\n\n";
    }
    
    echo colorize("\n🎉 Database model inspection completed successfully!", GREEN) . "\n";
    echo colorize("For detailed documentation, see DATABASE_MODEL.md and DATABASE_ERD.md", CYAN) . "\n\n";
    
} catch (Exception $e) {
    echo colorize("\n❌ Error: " . $e->getMessage(), RED) . "\n";
    echo colorize("Stack trace:\n" . $e->getTraceAsString(), RED) . "\n";
    exit(1);
}
