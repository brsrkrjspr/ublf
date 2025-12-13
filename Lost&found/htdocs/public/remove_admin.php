<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../includes/Database.php';
$db = new Database();
$conn = $db->getConnection();

$adminID = $_POST['admin_id'] ?? null;
$currentAdminID = $_SESSION['admin']['AdminID'];

if (!$adminID) {
    $_SESSION['admin_msg'] = 'Invalid admin selected.';
    header('Location: admin_dashboard.php#adminmgmt');
    exit;
}
if ($adminID == $currentAdminID) {
    $_SESSION['admin_msg'] = 'You cannot remove yourself.';
    header('Location: admin_dashboard.php#adminmgmt');
    exit;
}
// Check if this is the last admin
$stmt = $conn->query('SELECT COUNT(*) FROM Admin');
$totalAdmins = $stmt->fetchColumn();
if ($totalAdmins <= 1) {
    $_SESSION['admin_msg'] = 'Cannot remove the last admin.';
    header('Location: admin_dashboard.php#adminmgmt');
    exit;
}
// Remove admin
$stmt = $conn->prepare('DELETE FROM Admin WHERE AdminID = :adminID');
$result = $stmt->execute(['adminID' => $adminID]);
if ($result) {
    $_SESSION['admin_msg'] = 'Admin removed successfully!';
} else {
    $_SESSION['admin_msg'] = 'Failed to remove admin.';
}
header('Location: admin_dashboard.php#adminmgmt');
exit; 