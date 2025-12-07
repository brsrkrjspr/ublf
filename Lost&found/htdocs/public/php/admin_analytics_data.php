<?php
/**
 * Admin Analytics Data Endpoint
 * 
 * Returns JSON data for the admin analytics dashboard
 * Requires admin authentication
 */

session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Admin login required.']);
    exit;
}

require_once __DIR__ . '/../../includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Initialize response data
$data = [
    'total_users' => 0,
    'total_reports' => 0,
    'active_today' => 0,
    'top_actions' => [],
    'usage' => [
        'labels' => [],
        'values' => []
    ]
];

try {
    if (!$conn) {
        // Return default data if database unavailable
        echo json_encode($data);
        exit;
    }

    // 1. Total Users (students)
    $stmt = $conn->query('SELECT COUNT(*) as count FROM student');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['total_users'] = (int)($result['count'] ?? 0);

    // 2. Total Reports (lost + found items)
    // Lost items (reportitem)
    $stmt = $conn->query('SELECT COUNT(*) as count FROM reportitem');
    $lostCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    
    // Found items (item)
    $stmt = $conn->query('SELECT COUNT(*) as count FROM item');
    $foundCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    
    $data['total_reports'] = $lostCount + $foundCount;

    // 3. Active Today (students who created reports/items today or updated profile today)
    $today = date('Y-m-d');
    
    // Students who created lost reports today
    $stmt = $conn->prepare('SELECT COUNT(DISTINCT StudentNo) as count FROM reportitem WHERE DATE(CreatedAt) = :today');
    $stmt->execute(['today' => $today]);
    $activeFromReports = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    
    // Students who updated profile today (proxy for login activity)
    $stmt = $conn->prepare('SELECT COUNT(DISTINCT StudentNo) as count FROM student WHERE DATE(UpdatedAt) = :today');
    $stmt->execute(['today' => $today]);
    $activeFromUpdates = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    
    // Combine (using MAX to avoid double counting if same student did both)
    $data['active_today'] = max($activeFromReports, $activeFromUpdates);

    // 4. Top Actions (most common user actions)
    $topActions = [];
    
    // Count lost item reports
    $stmt = $conn->query('SELECT COUNT(*) as count FROM reportitem');
    $lostReportsCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    if ($lostReportsCount > 0) {
        $topActions[] = ['label' => 'Report Lost Item', 'count' => $lostReportsCount];
    }
    
    // Count found item reports
    $stmt = $conn->query('SELECT COUNT(*) as count FROM item');
    $foundReportsCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    if ($foundReportsCount > 0) {
        $topActions[] = ['label' => 'Report Found Item', 'count' => $foundReportsCount];
    }
    
    // Count approved reports (visible to users)
    $stmt = $conn->query('SELECT COUNT(*) as count FROM reportitem WHERE StatusConfirmed = 1');
    $approvedLostCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    if ($approvedLostCount > 0) {
        $topActions[] = ['label' => 'Approved Lost Reports', 'count' => $approvedLostCount];
    }
    
    $stmt = $conn->query('SELECT COUNT(*) as count FROM item WHERE StatusConfirmed = 1');
    $approvedFoundCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    if ($approvedFoundCount > 0) {
        $topActions[] = ['label' => 'Approved Found Items', 'count' => $approvedFoundCount];
    }
    
    // Count profile photo submissions
    $stmt = $conn->query('SELECT COUNT(*) as count FROM profile_photo_history');
    $photoCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    if ($photoCount > 0) {
        $topActions[] = ['label' => 'Profile Photo Submissions', 'count' => $photoCount];
    }
    
    // Sort by count descending and limit to top 5
    usort($topActions, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    $data['top_actions'] = array_slice($topActions, 0, 5);

    // 5. Usage Trends (last 7 days of activity)
    $usageLabels = [];
    $usageValues = [];
    
    // Get last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('M j', strtotime("-$i days"));
        $usageLabels[] = $dayLabel;
        
        // Count reports created on this day (lost + found)
        $stmt = $conn->prepare('SELECT COUNT(*) as count FROM reportitem WHERE DATE(CreatedAt) = :date');
        $stmt->execute(['date' => $date]);
        $lostDayCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        
        $stmt = $conn->prepare('SELECT COUNT(*) as count FROM item WHERE DATE(CreatedAt) = :date');
        $stmt->execute(['date' => $date]);
        $foundDayCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        
        $usageValues[] = $lostDayCount + $foundDayCount;
    }
    
    $data['usage'] = [
        'labels' => $usageLabels,
        'values' => $usageValues
    ];

} catch (PDOException $e) {
    // Log error but return default data
    error_log("Analytics data error: " . $e->getMessage());
    // Return partial data if available
} catch (Exception $e) {
    // Log error but return default data
    error_log("Analytics data error: " . $e->getMessage());
    // Return partial data if available
}

// Return JSON response
echo json_encode($data, JSON_PRETTY_PRINT);
?>

