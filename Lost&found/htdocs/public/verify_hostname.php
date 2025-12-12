<?php
/**
 * Verify Aiven Hostname
 * This helps check if the hostname in Render matches Aiven
 */

$test_key = isset($_GET['key']) ? $_GET['key'] : '';
if ($test_key !== 'verify-2025-12-12') {
    die('Access denied. Use: ?key=verify-2025-12-12');
}

echo "<h2>Aiven Hostname Verification</h2>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

echo "<h3>Current Configuration in Render:</h3>";
$current_host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'mysql-1bd0087e-dullajasperdave-5242.j.aivencloud.com');
echo "<p><strong>DB_HOST:</strong> <code>$current_host</code></p>";

echo "<h3>DNS Resolution Test:</h3>";
$ip = @gethostbyname($current_host);
if ($ip === $current_host) {
    echo "<p class='error'>❌ DNS Resolution FAILED for: <code>$current_host</code></p>";
    echo "<p class='error'><strong>This hostname cannot be resolved!</strong></p>";
} else {
    echo "<p class='success'>✅ DNS Resolution successful!</p>";
    echo "<p>Resolves to IP: <code>$ip</code></p>";
}

echo "<hr>";
echo "<h3>⚠️ Action Required:</h3>";
echo "<ol>";
echo "<li>Go to <strong>Aiven Console</strong> → Your MySQL service</li>";
echo "<li>Click on <strong>\"Connection information\"</strong> tab</li>";
echo "<li>Look at the <strong>\"Host\"</strong> field</li>";
echo "<li><strong>Copy the EXACT hostname</strong> shown there</li>";
echo "<li>Compare it with what's shown above</li>";
echo "<li>If different, update <code>DB_HOST</code> in Render Dashboard</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Common Aiven Hostname Formats:</h3>";
echo "<ul>";
echo "<li><code>mysql-XXXXX-XXXXX-XXXXX.j.aivencloud.com</code></li>";
echo "<li><code>mysql-XXXXX-XXXXX-XXXXX.a.aivencloud.com</code></li>";
echo "<li><code>mysql-XXXXX-XXXXX-XXXXX.e.aivencloud.com</code></li>";
echo "</ul>";
echo "<p class='info'>The hostname might have a different subdomain (.j, .a, .e) or different ID after resume.</p>";

echo "<hr>";
echo "<p><strong>⚠️ Delete this file after verification!</strong></p>";
?>

