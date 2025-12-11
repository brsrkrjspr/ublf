<?php
session_start();
require_once __DIR__ . '/../classes/Student.php';

$loginMsg = '';
$signupMsg = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // #region agent log
    $logFile = __DIR__ . '/../debug.log';
    $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'index.php:9', 'message' => 'Login attempt', 'data' => ['student_no' => $_POST['login_studentNo'] ?? '', 'session_id' => session_id()], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'E'];
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    // #endregion
    $studentNo = $_POST['login_studentNo'] ?? '';
    $password = $_POST['login_password'] ?? '';
    try {
        $student = new Student();
        $result = $student->login($studentNo, $password);
        if ($result['success']) {
            $_SESSION['student'] = $result['user'];
            // #region agent log
            $logFile = __DIR__ . '/../debug.log';
            $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'index.php:16', 'message' => 'Login success session set', 'data' => ['student_no' => $studentNo, 'session_keys' => array_keys($_SESSION)], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'E'];
            @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            header('Location: dashboard.php');
            exit;
        } else {
            // #region agent log
            $logFile = __DIR__ . '/../debug.log';
            $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'index.php:20', 'message' => 'Login failed', 'data' => ['message' => $result['message'] ?? ''], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'E'];
            @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            $loginMsg = $result['message'];
        }
    } catch (Exception $e) {
        // #region agent log
        $logFile = __DIR__ . '/../debug.log';
        $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'index.php:22', 'message' => 'Login exception', 'data' => ['error' => $e->getMessage()], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'E'];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        $loginMsg = 'Database connection unavailable. Please check your database settings.';
    }
}

// Handle Signup
$signupSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $studentNo = $_POST['signup_studentNo'] ?? '';
    $studentName = $_POST['signup_studentName'] ?? '';
    $phoneNo = $_POST['signup_phoneNo'] ?? '';
    $email = $_POST['signup_email'] ?? '';
    $password = $_POST['signup_password'] ?? '';
    // Enforce UB email format
    $expectedEmail = $studentNo . '@ub.edu.ph';
    if (strtolower($email) !== strtolower($expectedEmail)) {
        $signupMsg = 'Email must be your student number followed by @ub.edu.ph (e.g., ' . $expectedEmail . ')';
    } else {
        try {
            $student = new Student();
            $result = $student->register($studentNo, $studentName, $phoneNo, $email, $password);
            $signupMsg = $result['message'];
            if ($result['success']) {
                $signupSuccess = true;
                $loginMsg = 'Registration successful! Please log in.';
            }
        } catch (Exception $e) {
            $signupMsg = 'Database connection unavailable. Please check your database settings.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Lost and Found System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php
    // Use CSS router for reliable path resolution
    $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
    ?>
    <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
</head>
<body>
<div class="index-bg">
  <nav class="navbar navbar-expand-lg navbar-dark index-navbar mb-4">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
        <span class="ub-logo-3d me-2">UB</span>
        <span>Lost & Found</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
          </li>
          <li class="nav-item">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#signupModal">Sign Up</button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="index-content">
    <div class="index-card">
      <h1>Welcome to the University of Batangas Lost and Found System</h1>
      <p class="lead">Please log in or sign up to continue.<br>Help us reunite lost items with their owners!</p>
    </div>
  </div>
</div>
<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Login</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if ($loginMsg): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($loginMsg); ?></div>
          <?php endif; ?>
          <div class="mb-3">
            <label for="login_studentNo" class="form-label">Student No</label>
            <input type="text" class="form-control" id="login_studentNo" name="login_studentNo" required>
          </div>
          <div class="mb-3">
            <label for="login_password" class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="login_password" name="login_password" required>
              <button type="button" class="btn btn-outline-secondary password-toggle" tabindex="-1" onclick="togglePassword('login_password', this)" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="login" class="btn btn-primary">Login</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Signup Modal -->
<div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="signupModalLabel">Sign Up</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if ($signupMsg): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($signupMsg); ?></div>
          <?php endif; ?>
          <div class="mb-3">
            <label for="signup_studentNo" class="form-label">Student No</label>
            <input type="text" class="form-control" id="signup_studentNo" name="signup_studentNo" required>
          </div>
          <div class="mb-3">
            <label for="signup_studentName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="signup_studentName" name="signup_studentName" required>
          </div>
          <div class="mb-3">
            <label for="signup_phoneNo" class="form-label">Phone No</label>
            <input type="text" class="form-control" id="signup_phoneNo" name="signup_phoneNo">
          </div>
          <div class="mb-3">
            <label for="signup_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="signup_email" name="signup_email" required>
          </div>
          <div class="mb-3">
            <label for="signup_password" class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="signup_password" name="signup_password" required>
              <button type="button" class="btn btn-outline-secondary password-toggle" tabindex="-1" onclick="togglePassword('signup_password', this)" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="signup" class="btn btn-success">Sign Up</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
<script>
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('bi-eye');
    icon.classList.add('bi-eye-slash');
    btn.setAttribute('aria-label', 'Hide password');
  } else {
    input.type = 'password';
    icon.classList.remove('bi-eye-slash');
    icon.classList.add('bi-eye');
    btn.setAttribute('aria-label', 'Show password');
  }
}
</script>
<?php if ($loginMsg || $signupSuccess): ?>
<script>
  var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
  loginModal.show();
</script>
<?php elseif ($signupMsg): ?>
<script>
  var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
  signupModal.show();
</script>
<?php endif; ?>
</body>
</html>
