<?php
session_start();
require_once __DIR__ . '/../classes/Admin.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    try {
        $admin = new Admin();
        $result = $admin->login($username, $password);
        if ($result['success']) {
            $_SESSION['admin'] = $result['admin'];
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = 'Login error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/ub.css" rel="stylesheet">
  <style>
    body { background: #faf9f6; }
    .login-card { max-width: 400px; margin: 80px auto; border-radius: 1.25rem; box-shadow: 0 4px 16px rgba(128,0,0,0.08); border: none; }
    .login-header { background: linear-gradient(120deg, #800000 0%, #FFD700 100%); color: #fff; border-radius: 1.25rem 1.25rem 0 0; padding: 2rem 0 1rem 0; text-align: center; }
    .btn-maroon { background: #800000; color: #FFD700; border: none; }
    .btn-maroon:hover { background: #a83232; color: #FFD700; }
  </style>
</head>
<body>
<div class="login-card card">
  <div class="login-header">
    <div class="d-flex flex-column align-items-center mb-2">
      <span class="ub-logo-3d">UB</span>
    </div>
    <h2 class="fw-bold mb-0">Admin Login</h2>
    <p class="mb-0">UB Lost & Found</p>
  </div>
  <div class="card-body p-4">
    <?php if ($error): ?>
      <div class="alert alert-danger text-center"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required autofocus>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-maroon w-100">Login</button>
    </form>
  </div>
</div>
</body>
</html> 