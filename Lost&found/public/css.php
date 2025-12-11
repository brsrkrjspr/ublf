<?php
/**
 * CSS Router - Serves CSS files from assets directory
 * Usage: <link href="css.php?file=ub.css" rel="stylesheet">
 */

$allowedFiles = ['ub.css', 'UB.css', 'dashboard.css', 'dash.css', 'notifications.css', 'admin_dashboard.css', 'admin.css', 'profile.css'];
$file = $_GET['file'] ?? '';

if (empty($file) || !in_array($file, $allowedFiles)) {
    http_response_code(404);
    die('CSS file not found');
}

$cssPath = __DIR__ . '/../assets/' . $file;

if (!file_exists($cssPath)) {
    http_response_code(404);
    die('CSS file not found');
}

header('Content-Type: text/css');
header('Cache-Control: public, max-age=3600');
readfile($cssPath);

