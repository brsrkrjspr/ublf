<?php
/**
 * Test Image Paths Script
 * This script helps diagnose image path issues
 * 
 * SECURITY: Delete this file after use!
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ImageHelper.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Image Path Diagnostic</h2>";
echo "<hr>";

// Test 1: Check DocumentRoot
echo "<h3>1. Server Configuration</h3>";
echo "<p><strong>DocumentRoot:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Script Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Expected Image Directory:</strong> " . realpath(__DIR__ . '/../assets/uploads') . "</p>";
echo "<p><strong>Image Directory Exists:</strong> " . (is_dir(__DIR__ . '/../assets/uploads') ? 'YES ✅' : 'NO ❌') . "</p>";

// Test 2: Check database PhotoURLs
echo "<hr><h3>2. Database PhotoURLs</h3>";
try {
    $stmt = $conn->query("SELECT ReportID, PhotoURL FROM reportitem WHERE PhotoURL IS NOT NULL AND PhotoURL != '' LIMIT 5");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Found " . count($reports) . " reports with photos</strong></p>";
    
    if (count($reports) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ReportID</th><th>PhotoURL (Database)</th><th>Generated Path</th><th>File Exists</th><th>Test Image</th></tr>";
        
        foreach ($reports as $report) {
            $dbPath = $report['PhotoURL'];
            $generatedPath = getImagePath($dbPath);
            $fullPath = realpath(__DIR__ . '/../' . $dbPath);
            $fileExists = file_exists($fullPath);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($report['ReportID']) . "</td>";
            echo "<td>" . htmlspecialchars($dbPath) . "</td>";
            echo "<td>" . htmlspecialchars($generatedPath) . "</td>";
            echo "<td>" . ($fileExists ? 'YES ✅' : 'NO ❌') . "</td>";
            echo "<td>";
            if ($fileExists) {
                echo "<img src='" . htmlspecialchars($generatedPath) . "' style='max-width:100px;max-height:100px;' onerror=\"this.style.border='2px solid red'; this.alt='FAILED TO LOAD';\">";
            } else {
                echo "<span style='color:red;'>File not found at: " . htmlspecialchars($fullPath) . "</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Test different path formats
echo "<hr><h3>3. Path Format Tests</h3>";
$testPaths = [
    'assets/uploads/test.jpg',
    '../assets/uploads/test.jpg',
    '/assets/uploads/test.jpg',
    'Lost&found/htdocs/assets/uploads/test.jpg'
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Path Format</th><th>getImagePath() Result</th></tr>";
foreach ($testPaths as $testPath) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($testPath) . "</td>";
    echo "<td>" . htmlspecialchars(getImagePath($testPath)) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 4: List actual files in uploads directory
echo "<hr><h3>4. Actual Files in Uploads Directory</h3>";
$uploadsDir = __DIR__ . '/../assets/uploads';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    $imageFiles = array_filter($files, function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
    });
    
    echo "<p><strong>Found " . count($imageFiles) . " image files:</strong></p>";
    echo "<ul>";
    foreach ($imageFiles as $file) {
        $filePath = $uploadsDir . '/' . $file;
        $relativePath = 'assets/uploads/' . $file;
        $webPath = getImagePath($relativePath);
        echo "<li>";
        echo htmlspecialchars($file) . " → ";
        echo "<a href='" . htmlspecialchars($webPath) . "' target='_blank'>" . htmlspecialchars($webPath) . "</a>";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>Uploads directory does not exist!</p>";
}

echo "<hr>";
echo "<p><a href='all_lost.php'>Go to All Lost Items</a></p>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT:</strong> Delete this file (test_image_paths.php) after use for security!</p>";
?>

