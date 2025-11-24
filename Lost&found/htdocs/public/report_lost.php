<?php
session_start();
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../includes/Database.php';

if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNo = $_SESSION['student']['StudentNo'];
    $itemClass = $_POST['itemClass'] ?? '';
    $description = $_POST['description'] ?? '';
    $dateOfLoss = $_POST['dateOfLoss'] ?? '';
    $photoURL = null;

    // Handle image upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('lost_', true) . '.' . $ext;
        $target = __DIR__ . '/../assets/uploads/' . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
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

    // Get default ReportStatusID for 'Open'
    $stmt = $conn->prepare('SELECT ReportStatusID FROM ReportStatus WHERE StatusName = :status LIMIT 1');
    $stmt->execute(['status' => 'Open']);
    $statusRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $reportStatusID = $statusRow ? $statusRow['ReportStatusID'] : 1; // fallback to 1 if not found

            // Insert into reportitem
        $stmt = $conn->prepare('INSERT INTO reportitem (StudentNo, ItemClassID, Description, DateOfLoss, PhotoURL, StatusConfirmed, ReportStatusID) VALUES (:studentNo, :itemClassID, :description, :dateOfLoss, :photoURL, 0, :reportStatusID)');
    $result = $stmt->execute([
        'studentNo' => $studentNo,
        'itemClassID' => $itemClassID,
        'description' => $description,
        'dateOfLoss' => $dateOfLoss,
        'photoURL' => $photoURL,
        'reportStatusID' => $reportStatusID
    ]);

    if ($result) {
        $_SESSION['dashboard_msg'] = 'Lost item report submitted successfully.';
    } else {
        $_SESSION['dashboard_msg'] = 'Failed to submit lost item report.';
    }
}
header('Location: dashboard.php');
exit; 