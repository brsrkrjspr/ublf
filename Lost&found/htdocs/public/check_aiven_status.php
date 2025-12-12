<?php
/**
 * Check Aiven Service Status
 * This helps diagnose if Aiven service is paused
 */

$test_key = isset($_GET['key']) ? $_GET['key'] : '';
if ($test_key !== 'check-2025-12-12') {
    die('Access denied. Use: ?key=check-2025-12-12');
}

echo "<h2>Aiven Service Status Check</h2>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

$host = 'mysql-1bd0087e-dullajasperdave-5242.j.aivencloud.com';

echo "<h3>DNS Resolution Test:</h3>";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "<p class='error'><strong>❌ DNS Resolution FAILED</strong></p>";
    echo "<p>The hostname <code>$host</code> cannot be resolved.</p>";
    echo "<p><strong>Possible causes:</strong></p>";
    echo "<ul>";
    echo "<li>⚠️ <strong>Aiven service is PAUSED</strong> (free tier pauses after inactivity)</li>";
    echo "<li>The hostname has changed</li>";
    echo "<li>Network/DNS issue</li>";
    echo "</ul>";
    echo "<p class='warning'><strong>Action Required:</strong></p>";
    echo "<ol>";
    echo "<li>Go to Aiven Console → Your MySQL service</li>";
    echo "<li>Check if service status shows 'Paused' or 'Stopped'</li>";
    echo "<li>If paused, click 'Resume' or 'Start' to wake it up</li>";
    echo "<li>Wait 1-2 minutes for service to start</li>";
    echo "<li>Verify the hostname in Connection Information (it might have changed)</li>";
    echo "<li>Update DB_HOST in Render if hostname changed</li>";
    echo "</ol>";
} else {
    echo "<p class='success'>✅ DNS Resolution successful!</p>";
    echo "<p>Hostname resolves to: <code>$ip</code></p>";
}

echo "<hr>";
echo "<p><strong>⚠️ Delete this file after checking!</strong></p>";
?>

