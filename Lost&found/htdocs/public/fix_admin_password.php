<?php
/**
 * Fix Admin Password Script
 * This script updates the admin user password to 'admin123'
 * 
 * SECURITY: Delete this file after use!
 */

require_once __DIR__ . '/../includes/Database.php';

// Simple security check - use today's date as key (YYYY-MM-DD format)
$securityKey = date('Y-m-d');
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $securityKey) {
    die("Access denied. Invalid security key. Use: ?key=" . $securityKey);
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed!");
}

$username = 'admin';
$newPassword = 'admin123';

// Generate password hash
$passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    // Check if admin exists
    $stmt = $conn->prepare("SELECT AdminID, Username FROM admin WHERE Username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Update existing admin
        $stmt = $conn->prepare("UPDATE admin SET PasswordHash = :passwordHash WHERE Username = :username");
        $result = $stmt->execute([
            'passwordHash' => $passwordHash,
            'username' => $username
        ]);
        
        if ($result) {
            echo "<h2>✅ Success!</h2>";
            echo "<p>Admin password has been updated successfully.</p>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
            echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
        } else {
            echo "<h2>❌ Error</h2>";
            echo "<p>Failed to update admin password.</p>";
        }
    } else {
        // Create new admin if doesn't exist
        $stmt = $conn->prepare("INSERT INTO admin (Username, PasswordHash, Email, AdminName) VALUES (:username, :passwordHash, :email, :adminName)");
        $result = $stmt->execute([
            'username' => $username,
            'passwordHash' => $passwordHash,
            'email' => 'admin@ub.edu.ph',
            'adminName' => 'System Admin'
        ]);
        
        if ($result) {
            echo "<h2>✅ Success!</h2>";
            echo "<p>Admin account has been created successfully.</p>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
            echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
        } else {
            echo "<h2>❌ Error</h2>";
            echo "<p>Failed to create admin account.</p>";
        }
    }
    
    // Verify the password works
    echo "<hr>";
    echo "<h3>Verification:</h3>";
    $testHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $verify = password_verify($newPassword, $passwordHash);
    echo "<p>Password verification test: " . ($verify ? "✅ PASSED" : "❌ FAILED") . "</p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT:</strong> Delete this file (fix_admin_password.php) after use for security!</p>";
?>

