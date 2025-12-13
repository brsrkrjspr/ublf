<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../includes/Database.php';
$db = new Database();
$conn = $db->getConnection();

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$adminID = $_SESSION['admin']['AdminID'];

if (!$currentPassword || !$newPassword || !$confirmPassword) {
    $_SESSION['admin_settings_msg'] = 'All fields are required.';
    header('Location: admin_dashboard.php#settings');
    exit;
}
if ($newPassword !== $confirmPassword) {
    $_SESSION['admin_settings_msg'] = 'New passwords do not match.';
    header('Location: admin_dashboard.php#settings');
    exit;
}
// Fetch current password hash
$stmt = $conn->prepare('SELECT PasswordHash FROM Admin WHERE AdminID = :adminID LIMIT 1');
$stmt->execute(['adminID' => $adminID]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || !password_verify($currentPassword, $row['PasswordHash'])) {
    $_SESSION['admin_settings_msg'] = 'Current password is incorrect.';
    header('Location: admin_dashboard.php#settings');
    exit;
}
// Update password
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $conn->prepare('UPDATE Admin SET PasswordHash = :newHash WHERE AdminID = :adminID');
$result = $stmt->execute(['newHash' => $newHash, 'adminID' => $adminID]);
if ($result) {
    $_SESSION['admin_settings_msg'] = 'Password changed successfully!';
} else {
    $_SESSION['admin_settings_msg'] = 'Failed to change password.';
}
header('Location: admin_dashboard.php#settings');
exit; 