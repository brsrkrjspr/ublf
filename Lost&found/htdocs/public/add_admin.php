<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../includes/Database.php';
$db = new Database();
$conn = $db->getConnection();

$name = trim($_POST['admin_name'] ?? '');
$username = trim($_POST['admin_username'] ?? '');
$email = trim($_POST['admin_email'] ?? '');
$password = $_POST['admin_password'] ?? '';

if (!$name || !$username || !$email || !$password) {
    $_SESSION['admin_msg'] = 'All fields are required.';
    header('Location: admin_dashboard.php#adminmgmt');
    exit;
}
// Check for duplicate username/email
$stmt = $conn->prepare('SELECT AdminID FROM Admin WHERE Username = :username OR Email = :email LIMIT 1');
$stmt->execute(['username' => $username, 'email' => $email]);
if ($stmt->fetch()) {
    $_SESSION['admin_msg'] = 'Username or email already exists.';
    header('Location: admin_dashboard.php#adminmgmt');
    exit;
}
// Hash password
$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare('INSERT INTO Admin (AdminName, Username, Email, PasswordHash) VALUES (:name, :username, :email, :password)');
$result = $stmt->execute([
    'name' => $name,
    'username' => $username,
    'email' => $email,
    'password' => $passwordHash
]);
if ($result) {
    $_SESSION['admin_msg'] = 'Admin added successfully!';
} else {
    $_SESSION['admin_msg'] = 'Failed to add admin.';
}
header('Location: admin_dashboard.php#adminmgmt');
exit; 