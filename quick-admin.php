<?php
/**
 * Quick Admin Creator
 * 
 * Simple script to quickly create admin accounts with predefined credentials.
 * Modify the credentials below and run this script.
 * 
 * Usage: php quick-admin.php
 */

// === CONFIGURE THESE CREDENTIALS ===
$adminCredentials = [
    'username' => 'admin2',
    'email' => 'admin2@bsmmupathalumni.org',
    'password' => 'SecurePass123!',
    'role' => 'super_admin'
];
// === END CONFIGURATION ===

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Quick Admin Creator ===\n";

// Load environment variables if available
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Database configuration
$config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'form_builder',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4'
];

try {
    // Create database connection
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "✓ Database connected successfully\n";
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$adminCredentials['username'], $adminCredentials['email']]);
    
    if ($stmt->fetch()) {
        echo "✗ User with username '{$adminCredentials['username']}' or email '{$adminCredentials['email']}' already exists\n";
        exit(1);
    }
    
    // Hash password
    $hashedPassword = password_hash($adminCredentials['password'], PASSWORD_BCRYPT);
    
    // Create admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role, isActive, createdAt, updatedAt) 
        VALUES (?, ?, ?, ?, 1, NOW(), NOW())
    ");
    
    $stmt->execute([
        $adminCredentials['username'],
        $adminCredentials['email'],
        $hashedPassword,
        $adminCredentials['role']
    ]);
    
    $userId = $pdo->lastInsertId();
    
    echo "\n✓ Admin account created successfully!\n";
    echo "User ID: $userId\n";
    echo "Username: {$adminCredentials['username']}\n";
    echo "Email: {$adminCredentials['email']}\n";
    echo "Role: {$adminCredentials['role']}\n";
    echo "Password: {$adminCredentials['password']}\n";
    echo "\nIMPORTANT: Please change the password after first login!\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
