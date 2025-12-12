<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';

// For demo, use AdminID=1. In a real app, use session for admin login.
$adminID = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemName = $_POST['foundItemName'] ?? '';
    $itemClass = $_POST['foundItemClass'] ?? '';
    $description = $_POST['foundDescription'] ?? '';
    $dateFound = $_POST['foundDate'] ?? '';
    $locationFound = $_POST['foundLocation'] ?? '';
    $photoURL = null;

    // Handle image upload
    if (isset($_FILES['foundPhoto']) && $_FILES['foundPhoto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foundPhoto']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('found_', true) . '.' . $ext;
        $target = __DIR__ . '/../assets/uploads/' . $filename;
        if (move_uploaded_file($_FILES['foundPhoto']['tmp_name'], $target)) {
            $photoURL = 'assets/uploads/' . $filename;
        }
    }

    // Get ItemClassID from ItemClass table or insert if not exists
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('SELECT ItemClassID FROM ItemClass WHERE ClassName = :className LIMIT 1');
    $stmt->execute(['className' => $itemClass]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $itemClassID = $row['ItemClassID'];
    } else {
        $stmt = $conn->prepare('INSERT INTO ItemClass (ClassName) VALUES (:className)');
        $stmt->execute(['className' => $itemClass]);
        $itemClassID = $conn->lastInsertId();
    }

    // Insert into Item
    $stmt = $conn->prepare('INSERT INTO Item (AdminID, ItemName, ItemClassID, Description, DateFound, LocationFound, PhotoURL, StatusConfirmed) VALUES (:adminID, :itemName, :itemClassID, :description, :dateFound, :locationFound, :photoURL, 0)');
    $result = $stmt->execute([
        'adminID' => $adminID,
        'itemName' => $itemName,
        'itemClassID' => $itemClassID,
        'description' => $description,
        'dateFound' => $dateFound,
        'locationFound' => $locationFound,
        'photoURL' => $photoURL
    ]);

    if ($result) {
        $_SESSION['dashboard_msg'] = 'Found item reported successfully.';
    } else {
        $_SESSION['dashboard_msg'] = 'Failed to report found item.';
    }
}
header('Location: dashboard.php#found');
exit; 