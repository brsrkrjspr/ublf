<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student = $_SESSION['student'];
$reportID = $_POST['report_id'] ?? $_GET['report_id'] ?? null;

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable']);
    exit;
}

// If no report_id provided, get all approved reports for this student
if (!$reportID) {
    $stmt = $conn->prepare('
        SELECT r.ReportID 
        FROM reportitem r 
        WHERE r.StudentNo = :studentNo AND r.StatusConfirmed = 1
        ORDER BY r.ReportID DESC
    ');
    $stmt->execute(['studentNo' => $student['StudentNo']]);
    $reportIDs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($reportIDs)) {
        echo json_encode(['success' => false, 'message' => 'No approved lost items to check']);
        exit;
    }
    
    // Process all reports and return summary
    $totalMatches = 0;
    $completed = 0;
    $errors = [];
    
    foreach ($reportIDs as $id) {
        $result = processMatchCheck($conn, $id, $student);
        if ($result['success']) {
            $totalMatches += $result['matchesFound'] ?? 0;
            $completed++;
        } else {
            $errors[] = $result['message'] ?? 'Unknown error';
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Checked {$completed} item(s)",
        'matchesFound' => $totalMatches,
        'totalChecked' => $completed,
        'errors' => $errors
    ]);
    exit;
}

// Process single report
$result = processMatchCheck($conn, $reportID, $student);
echo json_encode($result);

function processMatchCheck($conn, $reportID, $student) {
    // Get full report details
    $stmt = $conn->prepare('
        SELECT r.*, ic.ClassName, s.StudentName, s.Email 
        FROM `reportitem` r 
        LEFT JOIN `itemclass` ic ON r.ItemClassID = ic.ItemClassID 
        LEFT JOIN `student` s ON r.StudentNo = s.StudentNo 
        WHERE r.ReportID = :id AND r.StudentNo = :studentNo AND r.StatusConfirmed = 1
    ');
    $stmt->execute(['id' => $reportID, 'studentNo' => $student['StudentNo']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        return ['success' => false, 'message' => 'Report not found or not approved'];
    }
    
    // Trigger match detection webhook
    $webhookUrl = Config::get('N8N_MATCH_DETECTION_LOST_WEBHOOK_URL');
    
    if (empty($webhookUrl) || strpos($webhookUrl, 'your-n8n-instance.com') !== false) {
        return ['success' => false, 'message' => 'Match detection service not configured'];
    }
    
    $payload = [
        'reportID' => $report['ReportID'],
        'itemName' => $report['ItemName'] ?? '',
        'itemClass' => $report['ClassName'] ?? '',
        'description' => $report['Description'] ?? '',
        'lostLocation' => $report['LostLocation'] ?? '',
        'dateOfLoss' => $report['DateOfLoss'] ?? '',
        'studentNo' => $student['StudentNo'],
        'studentName' => $student['StudentName'] ?? '',
        'studentEmail' => $student['Email'] ?? ($student['StudentNo'] . '@ub.edu.ph')
    ];
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'message' => 'Failed to connect to match detection service'];
    }
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        return ['success' => false, 'message' => 'Match detection service returned an error'];
    }
    
    $responseData = json_decode($response, true);
    return [
        'success' => true,
        'message' => $responseData['message'] ?? 'Match detection completed',
        'matchesFound' => $responseData['matchesFound'] ?? 0,
        'data' => $responseData
    ];
}
?>

