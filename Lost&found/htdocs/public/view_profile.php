<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ImageHelper.php';
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}

$studentNo = $_GET['student_no'] ?? '';
if (empty($studentNo)) {
    header('Location: dashboard.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Fetch the profile data
$stmt = $conn->prepare('SELECT StudentNo, StudentName, Email, PhoneNo, ProfilePhoto, PhotoConfirmed, Bio, CreatedAt FROM student WHERE StudentNo = :studentNo LIMIT 1');
$stmt->execute(['studentNo' => $studentNo]);
$profileStudent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileStudent) {
    header('Location: dashboard.php');
    exit;
}

// Fetch their approved lost item reports
$stmt = $conn->prepare('SELECT r.ReportID, c.ClassName, r.Description, r.DateOfLoss, r.CreatedAt, r.PhotoURL FROM reportitem r JOIN itemclass c ON r.ItemClassID = c.ItemClassID WHERE r.StudentNo = :studentNo AND r.StatusConfirmed = 1 ORDER BY r.CreatedAt DESC LIMIT 6');
$stmt->execute(['studentNo' => $studentNo]);
$theirReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentUser = $_SESSION['student'];
$isOwnProfile = ($currentUser['StudentNo'] === $studentNo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($profileStudent['StudentName']); ?>'s Profile - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
  $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
  ?>
  <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
  <link href="css.php?file=profile.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .profile-header {
      background: linear-gradient(120deg, #800000 0%, #a83232 60%, #FFD700 100%);
      color: #fff;
      border-radius: 1.5rem;
      padding: 2rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    .profile-photo-large {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #FFD700;
      background: #fff;
    }
    .profile-stats {
      background: #fff;
      border-radius: 1rem;
      padding: 1.5rem;
      box-shadow: 0 4px 16px rgba(128,0,0,0.08);
      margin-bottom: 2rem;
    }
    .stat-item {
      text-align: center;
      padding: 1rem;
    }
    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #800000;
    }
    .stat-label {
      color: #6c757d;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<?php include '../templates/header.php'; ?>
<div class="container py-4">
  <!-- Profile Header -->
  <div class="profile-header text-center">
    <div class="row align-items-center">
      <div class="col-md-4">
        <?php if (!empty($profileStudent['ProfilePhoto']) && isset($profileStudent['PhotoConfirmed']) && $profileStudent['PhotoConfirmed'] == 1): ?>
          <img src="../<?php echo htmlspecialchars($profileStudent['ProfilePhoto']); ?>" alt="Profile Photo" class="profile-photo-large">
        <?php else: ?>
          <i class="bi bi-person-circle profile-photo-large d-flex align-items-center justify-content-center" style="font-size:6rem;color:#FFD700;background:#fff;"></i>
        <?php endif; ?>
      </div>
      <div class="col-md-8 text-md-start">
        <h1 class="mb-2">
          <?php echo htmlspecialchars($profileStudent['StudentName']); ?>
          <?php if (!empty($profileStudent['ProfilePhoto']) && isset($profileStudent['PhotoConfirmed']) && $profileStudent['PhotoConfirmed'] == 1): ?>
            <i class="bi bi-patch-check-fill text-primary ms-2" title="Profile Verified"></i>
          <?php endif; ?>
        </h1>
        <p class="mb-2"><i class="bi bi-person-badge me-2"></i>Student No: <?php echo htmlspecialchars($profileStudent['StudentNo']); ?></p>
        <p class="mb-2"><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($profileStudent['Email']); ?></p>
        <?php if (!empty($profileStudent['PhoneNo'])): ?>
          <p class="mb-0"><i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($profileStudent['PhoneNo']); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Profile Stats -->
  <div class="profile-stats">
    <div class="row">
      <div class="col-md-4">
        <div class="stat-item">
          <div class="stat-number"><?php echo count($theirReports); ?></div>
          <div class="stat-label">Lost Items Reported</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-item">
          <div class="stat-number"><?php echo date('M Y', strtotime($profileStudent['CreatedAt'])); ?></div>
          <div class="stat-label">Member Since</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-item">
          <div class="stat-number">
            <?php if (!empty($profileStudent['ProfilePhoto']) && isset($profileStudent['PhotoConfirmed']) && $profileStudent['PhotoConfirmed'] == 1): ?>
              <i class="bi bi-check-circle-fill text-success"></i>
            <?php else: ?>
              <i class="bi bi-x-circle-fill text-muted"></i>
            <?php endif; ?>
          </div>
          <div class="stat-label">Profile Verified</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bio Section -->
  <?php if (!empty($profileStudent['Bio'])): ?>
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-person-lines-fill me-2"></i>About</h5>
        <p class="card-text"><?php echo nl2br(htmlspecialchars($profileStudent['Bio'])); ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Their Lost Item Reports -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Lost Items Reported</h5>
    </div>
    <div class="card-body">
      <?php if (count($theirReports) > 0): ?>
        <div class="row g-4">
          <?php foreach ($theirReports as $report): ?>
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm">
                <?php if ($report['PhotoURL']): ?>
                  <img src="../<?php echo encodeImageUrl($report['PhotoURL']); ?>" class="card-img-top" alt="Lost Item Image" style="object-fit:cover;max-height:180px;" onerror="<?php echo getImageErrorHandler(); ?>">
                <?php else: ?>
                  <img src="<?php echo getPlaceholderImage(); ?>" class="card-img-top" alt="No Image">
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title mb-1"><?php echo htmlspecialchars($report['ClassName']); ?></h6>
                  <p class="card-text small mb-2"><?php echo htmlspecialchars(mb_strimwidth($report['Description'], 0, 60, '...')); ?></p>
                  <small class="text-muted mb-2">Lost on: <?php echo htmlspecialchars($report['DateOfLoss']); ?></small>
                  <button class="btn btn-primary btn-sm mt-auto" data-bs-toggle="modal" data-bs-target="#reportModal<?php echo $report['ReportID']; ?>">View Details</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted text-center py-4">No lost items reported yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="text-center mt-4">
    <a href="dashboard.php" class="btn btn-outline-secondary me-2">
      <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
    <?php if (!$isOwnProfile): ?>
      <a href="contact_admin.php" class="btn btn-primary">
        <i class="bi bi-envelope"></i> Contact Admin About This User
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Modals for report details -->
<?php foreach ($theirReports as $report): ?>
  <div class="modal fade" id="reportModal<?php echo $report['ReportID']; ?>" tabindex="-1" aria-labelledby="reportModalLabel<?php echo $report['ReportID']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reportModalLabel<?php echo $report['ReportID']; ?>">Lost Item Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if ($report['PhotoURL']): ?>
            <img src="../<?php echo encodeImageUrl($report['PhotoURL']); ?>" class="img-fluid mb-3" alt="Lost Item Image" onerror="<?php echo getImageErrorHandler(); ?>">
          <?php endif; ?>
          <ul class="list-group list-group-flush mb-2">
            <li class="list-group-item"><strong>Class:</strong> <?php echo htmlspecialchars($report['ClassName']); ?></li>
            <li class="list-group-item"><strong>Description:</strong> <?php echo htmlspecialchars($report['Description']); ?></li>
            <li class="list-group-item"><strong>Date of Loss:</strong> <?php echo htmlspecialchars($report['DateOfLoss']); ?></li>
            <li class="list-group-item"><strong>Reported at:</strong> <?php echo htmlspecialchars($report['CreatedAt']); ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/notifications.js"></script>
</body>
</html> 