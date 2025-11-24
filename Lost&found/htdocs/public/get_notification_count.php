<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['student'])) {
    require_once __DIR__ . '/../includes/Database.php';
    require_once __DIR__ . '/../classes/Notification.php';
    $db = new Database();
    $conn = $db->getConnection();
    $notification = new Notification($conn);
    $count = $notification->getUnreadCount($_SESSION['student']['StudentNo']);
    echo json_encode(['count' => $count]);
    exit;
} elseif (isset($_SESSION['admin'])) {
    // Optionally, implement admin notifications in the future
    echo json_encode(['count' => 0]);
    exit;
} else {
    echo json_encode(['count' => 0]);
    exit;
} 