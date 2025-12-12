<?php
/**
 * Check Admin Account Script
 * This script checks if the admin account exists and verifies the password
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
    die("<h2>❌ Database Connection Failed</h2><p>Cannot connect to database. Check Database.php configuration.</p>");
}

echo "<h2>Admin Account Diagnostic</h2>";
echo "<hr>";

try {
    // Check if admin table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'admin'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        die("<h3>❌ Error</h3><p>The 'admin' table does not exist in the database. Please import the database schema first.</p>");
    }
    
    echo "<h3>✅ Admin table exists</h3>";
    
    // Check admin users
    $stmt = $conn->query("SELECT AdminID, Username, Email, AdminName, CreatedAt FROM admin");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Admin Users Found: " . count($admins) . "</h3>";
    
    if (count($admins) === 0) {
        echo "<p style='color: red;'><strong>⚠️ No admin users found!</strong></p>";
        echo "<p>You need to create an admin user. Use fix_admin_password.php to create one.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>AdminID</th><th>Username</th><th>Email</th><th>AdminName</th><th>CreatedAt</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($admin['AdminID']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['Username']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['Email']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['AdminName']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['CreatedAt']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check specific admin user
    echo "<hr>";
    echo "<h3>Checking 'admin' user:</h3>";
    
    $stmt = $conn->prepare("SELECT * FROM admin WHERE Username = :username LIMIT 1");
    $stmt->execute(['username' => 'admin']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>✅ Admin user 'admin' found</p>";
        echo "<p><strong>AdminID:</strong> " . htmlspecialchars($admin['AdminID']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['Email']) . "</p>";
        echo "<p><strong>AdminName:</strong> " . htmlspecialchars($admin['AdminName']) . "</p>";
        echo "<p><strong>PasswordHash:</strong> " . htmlspecialchars(substr($admin['PasswordHash'], 0, 20)) . "...</p>";
        
        // Test password verification
        echo "<hr>";
        echo "<h3>Password Verification Test:</h3>";
        
        $testPassword = 'admin123';
        $verify = password_verify($testPassword, $admin['PasswordHash']);
        
        if ($verify) {
            echo "<p style='color: green;'><strong>✅ Password 'admin123' VERIFIED!</strong></p>";
            echo "<p>Login should work with username: <strong>admin</strong> and password: <strong>admin123</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Password 'admin123' does NOT match!</strong></p>";
            echo "<p>The password hash in the database does not match 'admin123'.</p>";
            echo "<p><strong>Solution:</strong> Run <a href='fix_admin_password.php?key=" . $securityKey . "'>fix_admin_password.php</a> to update the password.</p>";
        }
        
        // Generate new hash for comparison
        $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
        echo "<p><strong>New hash for 'admin123':</strong> " . htmlspecialchars($newHash) . "</p>";
        
    } else {
        echo "<p style='color: red;'><strong>❌ Admin user 'admin' NOT FOUND!</strong></p>";
        echo "<p>You need to create the admin user. Use <a href='fix_admin_password.php?key=" . $securityKey . "'>fix_admin_password.php</a> to create it.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h3>❌ Database Error</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>SQL State: " . htmlspecialchars($e->getCode()) . "</p>";
}

echo "<hr>";
echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT:</strong> Delete this file (check_admin_account.php) after use for security!</p>";
?>

