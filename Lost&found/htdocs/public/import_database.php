<?php
/**
 * Database Import Script
 * 
 * This script imports the database schema into Aiven MySQL.
 * Run this ONCE after deploying to Render, then delete this file for security.
 * 
 * IMPORTANT: Delete this file after successful import!
 */

// Security check - only allow if not in production or with a secret key
$import_key = isset($_GET['key']) ? $_GET['key'] : '';
$allowed_key = 'import-' . date('Y-m-d'); // Today's date as key

if ($import_key !== $allowed_key) {
    die('Access denied. Use: ?key=' . $allowed_key);
}

require_once __DIR__ . '/../includes/Database.php';

// Read the SQL file
$sql_file = __DIR__ . '/../../db/ub_lost_found_COMPLETE.sql';

if (!file_exists($sql_file)) {
    die('Error: SQL file not found at: ' . $sql_file);
}

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die('Error: Could not connect to database. Check your connection settings.');
}

echo "<h2>Database Import Script</h2>";
echo "<p>Connecting to database...</p>";

// Read SQL file
$sql = file_get_contents($sql_file);

if ($sql === false) {
    die('Error: Could not read SQL file.');
}

echo "<p>SQL file loaded (" . number_format(strlen($sql)) . " bytes)</p>";
echo "<p>Executing SQL statements...</p>";

// Split SQL into individual statements
// Remove comments and empty lines
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Split by semicolons
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && strlen($stmt) > 10;
    }
);

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($statements as $index => $statement) {
    if (empty(trim($statement))) {
        continue;
    }
    
    try {
        $conn->exec($statement);
        $success_count++;
        
        // Show progress for table creation
        if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
            echo "<p>✅ Created table: <strong>" . $matches[1] . "</strong></p>";
        }
    } catch (PDOException $e) {
        $error_count++;
        $error_msg = $e->getMessage();
        
        // Ignore "table already exists" errors
        if (strpos($error_msg, 'already exists') === false && 
            strpos($error_msg, 'Duplicate') === false) {
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $error_msg
            ];
            echo "<p>❌ Error: " . htmlspecialchars($error_msg) . "</p>";
        } else {
            // Table already exists - not a critical error
            echo "<p>⚠️ Table already exists (skipping)</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Import Summary</h3>";
echo "<p><strong>Successful statements:</strong> " . $success_count . "</p>";
echo "<p><strong>Errors:</strong> " . $error_count . "</p>";

if (count($errors) > 0) {
    echo "<h4>Errors encountered:</h4>";
    foreach ($errors as $error) {
        echo "<p><strong>Statement:</strong> " . htmlspecialchars($error['statement']) . "</p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($error['error']) . "</p><hr>";
    }
}

// Verify tables were created
echo "<h3>Verifying Tables</h3>";
try {
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Tables found:</strong> " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    $expected_tables = ['admin', 'student', 'item', 'reportitem', 'itemclass', 
                       'itemstatus', 'reportstatus', 'status', 'notifications', 
                       'profile_photo_history', 'reportitem_match'];
    
    $missing = array_diff($expected_tables, $tables);
    if (empty($missing)) {
        echo "<p style='color: green;'><strong>✅ All expected tables are present!</strong></p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ Missing tables:</strong> " . implode(', ', $missing) . "</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error verifying tables: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT: Delete this file (import_database.php) after successful import for security!</strong></p>";
echo "<p>Current date key: <code>" . $allowed_key . "</code></p>";
?>

