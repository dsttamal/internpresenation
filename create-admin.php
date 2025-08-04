<?php
/**
 * Create Admin Account Script
 * 
 * Independent PHP script to create admin accounts for the form builder system.
 * This script can be run from the command line or browser.
 * 
 * Usage:
 * php create-admin.php
 * 
 * Or via browser: http://yourdomain.com/create-admin.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

/**
 * Create database connection
 */
function createConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage() . "\n");
    }
}

/**
 * Hash password using PHP's password_hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if user exists
 */
function userExists($pdo, $username, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    return $stmt->fetch() !== false;
}

/**
 * Create admin user
 */
function createAdmin($pdo, $username, $email, $password, $role = 'admin') {
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        throw new Exception("All fields are required");
    }
    
    if (!isValidEmail($email)) {
        throw new Exception("Invalid email format");
    }
    
    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters long");
    }
    
    if (strlen($username) < 3 || strlen($username) > 30) {
        throw new Exception("Username must be between 3 and 30 characters");
    }
    
    // Valid roles
    $validRoles = ['user', 'admin', 'super_admin', 'form_manager', 'payment_approver', 'submission_viewer', 'submission_editor', 'notification_manager'];
    if (!in_array($role, $validRoles)) {
        throw new Exception("Invalid role. Valid roles: " . implode(', ', $validRoles));
    }
    
    // Check if user already exists
    if (userExists($pdo, $username, $email)) {
        throw new Exception("User with this username or email already exists");
    }
    
    // Hash password
    $hashedPassword = hashPassword($password);
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role, isActive, createdAt, updatedAt) 
        VALUES (?, ?, ?, ?, 1, NOW(), NOW())
    ");
    
    $stmt->execute([$username, $email, $hashedPassword, $role]);
    
    return $pdo->lastInsertId();
}

/**
 * Interactive mode for command line
 */
function interactiveMode($pdo) {
    echo "=== Create Admin Account ===\n";
    
    echo "Enter username: ";
    $username = trim(fgets(STDIN));
    
    echo "Enter email: ";
    $email = trim(fgets(STDIN));
    
    echo "Enter password: ";
    $password = trim(fgets(STDIN));
    
    echo "Enter role (admin, super_admin, form_manager, etc.) [default: admin]: ";
    $role = trim(fgets(STDIN));
    if (empty($role)) {
        $role = 'admin';
    }
    
    try {
        $userId = createAdmin($pdo, $username, $email, $password, $role);
        echo "\n✓ Admin account created successfully!\n";
        echo "User ID: $userId\n";
        echo "Username: $username\n";
        echo "Email: $email\n";
        echo "Role: $role\n";
    } catch (Exception $e) {
        echo "\n✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
    
    return true;
}

/**
 * Web interface
 */
function webInterface($pdo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'admin';
            
            $userId = createAdmin($pdo, $username, $email, $password, $role);
            
            echo "<div style='color: green; margin: 20px; padding: 20px; border: 1px solid green; border-radius: 5px;'>";
            echo "<h3>✓ Admin account created successfully!</h3>";
            echo "<p><strong>User ID:</strong> $userId</p>";
            echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
            echo "<p><strong>Role:</strong> " . htmlspecialchars($role) . "</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 20px; padding: 20px; border: 1px solid red; border-radius: 5px;'>";
            echo "<h3>✗ Error</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    
    // HTML Form
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - BSMMU Alumni Form Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #005a87; }
        .header { text-align: center; margin-bottom: 30px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Create Admin Account</h1>
        <p>BSMMU Alumni Form Builder</p>
    </div>
    
    <div class="warning">
        <strong>Security Warning:</strong> This script should be removed from production servers after use. 
        Only use this in development or secure environments.
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required minlength="3" maxlength="30" 
                   value="' . htmlspecialchars($_POST['username'] ?? '') . '">
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required 
                   value="' . htmlspecialchars($_POST['email'] ?? '') . '">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="admin"' . (($_POST['role'] ?? 'admin') === 'admin' ? ' selected' : '') . '>Admin</option>
                <option value="super_admin"' . (($_POST['role'] ?? '') === 'super_admin' ? ' selected' : '') . '>Super Admin</option>
                <option value="form_manager"' . (($_POST['role'] ?? '') === 'form_manager' ? ' selected' : '') . '>Form Manager</option>
                <option value="payment_approver"' . (($_POST['role'] ?? '') === 'payment_approver' ? ' selected' : '') . '>Payment Approver</option>
                <option value="submission_viewer"' . (($_POST['role'] ?? '') === 'submission_viewer' ? ' selected' : '') . '>Submission Viewer</option>
                <option value="submission_editor"' . (($_POST['role'] ?? '') === 'submission_editor' ? ' selected' : '') . '>Submission Editor</option>
                <option value="notification_manager"' . (($_POST['role'] ?? '') === 'notification_manager' ? ' selected' : '') . '>Notification Manager</option>
            </select>
        </div>
        
        <div class="form-group">
            <button type="submit">Create Admin Account</button>
        </div>
    </form>
    
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 14px;">
        <h4>Role Descriptions:</h4>
        <ul>
            <li><strong>Admin:</strong> General administrative access</li>
            <li><strong>Super Admin:</strong> Full system access</li>
            <li><strong>Form Manager:</strong> Can create and manage forms</li>
            <li><strong>Payment Approver:</strong> Can approve payment submissions</li>
            <li><strong>Submission Viewer:</strong> Can view form submissions</li>
            <li><strong>Submission Editor:</strong> Can edit form submissions</li>
            <li><strong>Notification Manager:</strong> Can manage notifications</li>
        </ul>
    </div>
</body>
</html>';
}

// Main execution
try {
    $pdo = createConnection($config);
    echo "Database connected successfully.\n";
    
    // Check if running from command line or web
    if (php_sapi_name() === 'cli') {
        // Command line mode
        interactiveMode($pdo);
    } else {
        // Web mode
        webInterface($pdo);
    }
    
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        echo "<div style='color: red; margin: 20px; padding: 20px; border: 1px solid red; border-radius: 5px;'>";
        echo "<h3>Error</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}
