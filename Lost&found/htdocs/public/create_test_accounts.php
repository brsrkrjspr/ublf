<?php
/**
 * Test Account Creation Script
 * Run this once to create test login credentials
 * DELETE THIS FILE after creating accounts for security
 */

require_once __DIR__ . '/../includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

$messages = [];

// Create Test Student Account
$testStudentNo = 'TEST001';
$testStudentPassword = 'test123';
$testStudentName = 'Test Student';
$testStudentEmail = 'TEST001@ub.edu.ph';
$testStudentPhone = '09123456789';

try {
    // Check if student already exists
    $stmt = $conn->prepare('SELECT StudentNo FROM student WHERE StudentNo = :studentNo LIMIT 1');
    $stmt->execute(['studentNo' => $testStudentNo]);
    
    if ($stmt->fetch()) {
        $messages[] = "⚠️ Student account already exists. Updating password...";
        // Update password
        $passwordHash = password_hash($testStudentPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('UPDATE student SET PasswordHash = :hash, StudentName = :name, Email = :email, PhoneNo = :phone WHERE StudentNo = :studentNo');
        $stmt->execute([
            'hash' => $passwordHash,
            'name' => $testStudentName,
            'email' => $testStudentEmail,
            'phone' => $testStudentPhone,
            'studentNo' => $testStudentNo
        ]);
        $messages[] = "✅ Student account updated successfully!";
    } else {
        // Create new student
        $passwordHash = password_hash($testStudentPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('INSERT INTO student (StudentNo, PasswordHash, StudentName, Email, PhoneNo) VALUES (:studentNo, :hash, :name, :email, :phone)');
        $stmt->execute([
            'studentNo' => $testStudentNo,
            'hash' => $passwordHash,
            'name' => $testStudentName,
            'email' => $testStudentEmail,
            'phone' => $testStudentPhone
        ]);
        $messages[] = "✅ Student account created successfully!";
    }
} catch (Exception $e) {
    $messages[] = "❌ Error creating student account: " . $e->getMessage();
}

// Create Test Admin Account
$testAdminUsername = 'admin';
$testAdminPassword = 'admin123';
$testAdminName = 'Test Admin';
$testAdminEmail = 'admin@ub.edu.ph';

try {
    // Check if admin already exists
    $stmt = $conn->prepare('SELECT AdminID FROM admin WHERE Username = :username LIMIT 1');
    $stmt->execute(['username' => $testAdminUsername]);
    
    if ($stmt->fetch()) {
        $messages[] = "⚠️ Admin account already exists. Updating password...";
        // Update password
        $passwordHash = password_hash($testAdminPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('UPDATE admin SET PasswordHash = :hash, AdminName = :name, Email = :email WHERE Username = :username');
        $stmt->execute([
            'hash' => $passwordHash,
            'name' => $testAdminName,
            'email' => $testAdminEmail,
            'username' => $testAdminUsername
        ]);
        $messages[] = "✅ Admin account updated successfully!";
    } else {
        // Create new admin
        $passwordHash = password_hash($testAdminPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('INSERT INTO admin (Username, PasswordHash, AdminName, Email) VALUES (:username, :hash, :name, :email)');
        $stmt->execute([
            'username' => $testAdminUsername,
            'hash' => $passwordHash,
            'name' => $testAdminName,
            'email' => $testAdminEmail
        ]);
        $messages[] = "✅ Admin account created successfully!";
    }
} catch (Exception $e) {
    $messages[] = "❌ Error creating admin account: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Accounts Created</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #faf9f6; padding: 40px 20px; }
        .credentials-card { max-width: 600px; margin: 0 auto; }
        .credential-box { background: #f8f9fa; border-left: 4px solid #800000; padding: 15px; margin: 15px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="credentials-card card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Test Accounts Created</h3>
        </div>
        <div class="card-body">
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
            
            <hr>
            
            <h5 class="mb-3">📋 Test Credentials:</h5>
            
            <div class="credential-box">
                <h6 class="text-primary">👤 Student Dashboard Login</h6>
                <p class="mb-1"><strong>Student No:</strong> <code><?php echo htmlspecialchars($testStudentNo); ?></code></p>
                <p class="mb-1"><strong>Password:</strong> <code><?php echo htmlspecialchars($testStudentPassword); ?></code></p>
                <p class="mb-0"><small class="text-muted">Login at: <a href="index.php">index.php</a></small></p>
            </div>
            
            <div class="credential-box">
                <h6 class="text-danger">🔐 Admin Dashboard Login</h6>
                <p class="mb-1"><strong>Username:</strong> <code><?php echo htmlspecialchars($testAdminUsername); ?></code></p>
                <p class="mb-1"><strong>Password:</strong> <code><?php echo htmlspecialchars($testAdminPassword); ?></code></p>
                <p class="mb-0"><small class="text-muted">Login at: <a href="admin_login.php">admin_login.php</a></small></p>
            </div>
            
            <div class="alert alert-warning mt-4">
                <strong>⚠️ Security Notice:</strong> Delete this file (<code>create_test_accounts.php</code>) after creating accounts for security purposes.
            </div>
            
            <div class="mt-3">
                <a href="index.php" class="btn btn-primary">Go to Student Login</a>
                <a href="admin_login.php" class="btn btn-danger">Go to Admin Login</a>
            </div>
        </div>
    </div>
</body>
</html>

