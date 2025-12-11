<?php
/**
 * Test View - Sets up mock sessions to view pages without database
 * Use this to test the UI/design without database connection
 */

session_start();

// Set mock student session for dashboard viewing
if (!isset($_SESSION['student'])) {
    $_SESSION['student'] = [
        'StudentNo' => 'TEST001',
        'StudentName' => 'Test Student',
        'Email' => 'TEST001@ub.edu.ph',
        'PhoneNo' => '09123456789',
        'ProfilePhoto' => null,
        'PhotoConfirmed' => 0
    ];
}

// Set mock admin session for admin dashboard viewing
if (!isset($_SESSION['admin'])) {
    $_SESSION['admin'] = [
        'AdminID' => 1,
        'Username' => 'admin',
        'AdminName' => 'Test Admin',
        'Email' => 'admin@ub.edu.ph'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test View - UB Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #faf9f6; padding: 40px 20px; }
        .test-card { max-width: 800px; margin: 0 auto; }
        .btn-link { text-decoration: none; }
    </style>
</head>
<body>
    <div class="test-card card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Test View - Mock Sessions Active</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Mock sessions have been set!</strong> You can now view pages without database connection.
            </div>
            
            <h5 class="mb-3">📋 Available Test Pages:</h5>
            
            <div class="list-group mb-4">
                <a href="index.php" class="list-group-item list-group-item-action">
                    <h6 class="mb-1">🏠 Homepage / Login</h6>
                    <p class="mb-0 text-muted">View the main landing page</p>
                </a>
                
                <a href="dashboard.php" class="list-group-item list-group-item-action">
                    <h6 class="mb-1">👤 Student Dashboard</h6>
                    <p class="mb-0 text-muted">View student dashboard with chatbot panel</p>
                </a>
                
                <a href="admin_login.php" class="list-group-item list-group-item-action">
                    <h6 class="mb-1">🔐 Admin Login Page</h6>
                    <p class="mb-0 text-muted">View admin login page</p>
                </a>
                
                <a href="admin_dashboard.php" class="list-group-item list-group-item-action">
                    <h6 class="mb-1">⚙️ Admin Dashboard</h6>
                    <p class="mb-0 text-muted">View admin dashboard with analytics section</p>
                </a>
                
                <a href="admin_dashboard.php?section=analytics" class="list-group-item list-group-item-action">
                    <h6 class="mb-1">📊 Admin Analytics</h6>
                    <p class="mb-0 text-muted">View analytics dashboard section</p>
                </a>
            </div>
            
            <div class="alert alert-warning">
                <strong>Note:</strong> Database-dependent features will show empty data or warnings, but pages will load for design review.
            </div>
            
            <div class="mt-3">
                <a href="dashboard.php" class="btn btn-primary">Go to Student Dashboard</a>
                <a href="admin_dashboard.php" class="btn btn-danger">Go to Admin Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

