<?php
session_start();
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
$student = $_SESSION['student'];
$msg = $_SESSION['contact_msg'] ?? '';
unset($_SESSION['contact_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Admin - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
  $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
  ?>
  <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #fff9f6 0%, #f6fff9 100%);
    }
  </style>
</head>
<body>
<?php include '../templates/header.php'; ?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-lg border-0 p-4" style="background: #fff; border-radius: 18px;">
        <div class="card-body">
          <h3 class="card-title text-center mb-4" style="color:var(--ub-maroon);"><i class="bi bi-envelope-paper-heart me-2"></i>Contact Admin</h3>
          <?php if ($msg): ?>
            <div class="alert alert-info text-center shadow-sm"><?php echo htmlspecialchars($msg); ?></div>
          <?php endif; ?>
          <form method="POST" action="contact_admin_send.php">
            <div class="mb-3">
              <label for="contactSubject" class="form-label">Subject</label>
              <input type="text" class="form-control" id="contactSubject" name="contactSubject" required>
            </div>
            <div class="mb-3">
              <label for="contactMessage" class="form-label">Message</label>
              <textarea class="form-control" id="contactMessage" name="contactMessage" rows="6" required></textarea>
            </div>
            <div class="mb-3 text-end">
              <button type="submit" class="btn btn-primary btn-lg px-4"><i class="bi bi-send"></i> Send Email</button>
            </div>
          </form>
          <div class="text-center text-muted mt-3" style="font-size:0.95rem;">
            <i class="bi bi-info-circle"></i> Your message will be sent to the UB Lost & Found admin (foundlost004@gmail.com).
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/notifications.js"></script>
</body>
</html> 