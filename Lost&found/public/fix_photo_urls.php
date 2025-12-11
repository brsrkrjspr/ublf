<?php
/**
 * Database PhotoURL Cleanup Script
 * 
 * This script fixes PhotoURL entries in the database that have spaces in filenames.
 * It replaces spaces with underscores to match the actual file naming convention.
 * 
 * WARNING: Run this script only once. Delete it after use for security.
 * 
 * Access: https://yourdomain.com/public/fix_photo_urls.php
 */

session_start();
require_once __DIR__ . '/../includes/Database.php';

// Optional: Add admin check
// if (!isset($_SESSION['admin'])) {
//     die('Access denied. Admin login required.');
// }

$db = new Database();
$conn = $db->getConnection();

$results = [
    'fixed' => 0,
    'errors' => 0,
    'messages' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
    try {
        // Fix reportitem table
        $stmt = $conn->query("SELECT ReportID, PhotoURL FROM reportitem WHERE PhotoURL IS NOT NULL AND PhotoURL != ''");
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($reports as $report) {
            $originalUrl = $report['PhotoURL'];
            $fixedUrl = str_replace(' ', '_', $originalUrl);
            
            if ($originalUrl !== $fixedUrl) {
                $updateStmt = $conn->prepare("UPDATE reportitem SET PhotoURL = :newUrl WHERE ReportID = :id");
                if ($updateStmt->execute(['newUrl' => $fixedUrl, 'id' => $report['ReportID']])) {
                    $results['fixed']++;
                    $results['messages'][] = "Fixed ReportID {$report['ReportID']}: '$originalUrl' → '$fixedUrl'";
                } else {
                    $results['errors']++;
                    $results['messages'][] = "Error fixing ReportID {$report['ReportID']}";
                }
            }
        }
        
        // Fix item table (found items)
        $stmt = $conn->query("SELECT ItemID, PhotoURL FROM item WHERE PhotoURL IS NOT NULL AND PhotoURL != ''");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $originalUrl = $item['PhotoURL'];
            $fixedUrl = str_replace(' ', '_', $originalUrl);
            
            if ($originalUrl !== $fixedUrl) {
                $updateStmt = $conn->prepare("UPDATE item SET PhotoURL = :newUrl WHERE ItemID = :id");
                if ($updateStmt->execute(['newUrl' => $fixedUrl, 'id' => $item['ItemID']])) {
                    $results['fixed']++;
                    $results['messages'][] = "Fixed ItemID {$item['ItemID']}: '$originalUrl' → '$fixedUrl'";
                } else {
                    $results['errors']++;
                    $results['messages'][] = "Error fixing ItemID {$item['ItemID']}";
                }
            }
        }
        
        // Fix student table (profile photos)
        $stmt = $conn->query("SELECT StudentNo, ProfilePhoto FROM student WHERE ProfilePhoto IS NOT NULL AND ProfilePhoto != ''");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($students as $student) {
            $originalUrl = $student['ProfilePhoto'];
            $fixedUrl = str_replace(' ', '_', $originalUrl);
            
            if ($originalUrl !== $fixedUrl) {
                $updateStmt = $conn->prepare("UPDATE student SET ProfilePhoto = :newUrl WHERE StudentNo = :studentNo");
                if ($updateStmt->execute(['newUrl' => $fixedUrl, 'studentNo' => $student['StudentNo']])) {
                    $results['fixed']++;
                    $results['messages'][] = "Fixed StudentNo {$student['StudentNo']}: '$originalUrl' → '$fixedUrl'";
                } else {
                    $results['errors']++;
                    $results['messages'][] = "Error fixing StudentNo {$student['StudentNo']}";
                }
            }
        }
        
    } catch (Exception $e) {
        $results['errors']++;
        $results['messages'][] = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Photo URLs - UB Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; }
        .alert { margin-top: 20px; }
        .log { background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-warning">
                <h4 class="mb-0">⚠️ PhotoURL Cleanup Script</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    This script fixes PhotoURL entries in the database that have spaces in filenames.
                    It replaces spaces with underscores to match the actual file naming convention.
                </p>
                
                <?php if ($results['fixed'] > 0 || $results['errors'] > 0): ?>
                    <div class="alert alert-<?php echo $results['errors'] > 0 ? 'danger' : 'success'; ?>">
                        <strong>Results:</strong><br>
                        Fixed: <?php echo $results['fixed']; ?><br>
                        Errors: <?php echo $results['errors']; ?>
                    </div>
                    
                    <?php if (!empty($results['messages'])): ?>
                        <div class="log">
                            <?php foreach ($results['messages'] as $msg): ?>
                                <?php echo htmlspecialchars($msg); ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="fix" value="1">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('This will update PhotoURL entries in the database. Continue?')">
                            Fix Photo URLs
                        </button>
                    </form>
                <?php endif; ?>
                
                <div class="alert alert-info mt-4">
                    <strong>⚠️ Security Note:</strong> Delete this file after running it!
                </div>
            </div>
        </div>
    </div>
</body>
</html>

