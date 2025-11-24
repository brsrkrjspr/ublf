<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $studentNo = $_SESSION['student']['StudentNo'];
    $reportId = $_POST['report_id'];
    $db = new Database();
    $conn = $db->getConnection();
    // Only delete if the report belongs to the logged-in student
    $stmt = $conn->prepare('DELETE FROM reportitem WHERE ReportID = :reportId AND StudentNo = :studentNo');
    $result = $stmt->execute(['reportId' => $reportId, 'studentNo' => $studentNo]);
    if ($result && $stmt->rowCount() > 0) {
        $_SESSION['dashboard_msg'] = 'Report deleted successfully.';
    } else {
        $_SESSION['dashboard_msg'] = 'Failed to delete report.';
    }
}
header('Location: dashboard.php');
exit; 