<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isset($_SESSION['student']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Unauthorized');
}

$notificationID = $_POST['notification_id'] ?? null;
if (!$notificationID) {
    http_response_code(400);
    exit('Missing notification ID');
}

$db = new Database();
$conn = $db->getConnection();
$notification = new Notification($conn);

$result = $notification->markAsRead($notificationID, $_SESSION['student']['StudentNo']);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
}
?> 