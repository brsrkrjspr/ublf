<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ImageHelper.php';
session_start();
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
$student = $_SESSION['student'];
$db = new Database();
$conn = $db->getConnection();
// Fetch user's lost item reports
$stmt = $conn->prepare('SELECT r.ReportID, c.ClassName, r.Description, r.DateOfLoss, r.CreatedAt, r.PhotoURL, rs.StatusName, r.StatusConfirmed FROM `reportitem` r LEFT JOIN `itemclass` c ON r.ItemClassID = c.ItemClassID LEFT JOIN `reportstatus` rs ON r.ReportStatusID = rs.ReportStatusID WHERE r.StudentNo = :studentNo ORDER BY r.CreatedAt DESC');
$stmt->execute(['studentNo' => $student['StudentNo']]);
$myReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
$msg = $_SESSION['dashboard_msg'] ?? '';
unset($_SESSION['dashboard_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Reports - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
  $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
  ?>
  <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../templates/header.php'; ?>
<div class="container py-4">
  <div class="row mb-4">
    <div class="col-lg-8 mx-auto">
      <?php if ($msg): ?>
        <div class="alert alert-info shadow-sm"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>
    </div>
  </div>
  <h4 class="mb-3">My Lost Item Reports</h4>
  <div class="row g-4">
    <?php if (count($myReports) > 0): ?>
      <?php foreach ($myReports as $idx => $report): ?>
        <div class="col-md-4 col-lg-3">
          <div class="card h-100 shadow-sm">
            <?php if ($report['PhotoURL']): ?>
              <img src="<?php echo getImagePath($report['PhotoURL']); ?>" class="card-img-top" alt="Lost Item Image" style="object-fit:cover;max-height:180px;" onerror="<?php echo getImageErrorHandler(); ?>">
            <?php else: ?>
              <img src="<?php echo getPlaceholderImage(); ?>" class="card-img-top" alt="No Image">
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <h6 class="card-title mb-1"><?php echo htmlspecialchars($report['ClassName']); ?></h6>
              <p class="card-text small mb-2"><?php echo htmlspecialchars(mb_strimwidth($report['Description'], 0, 60, '...')); ?></p>
              <?php if ($report['StatusConfirmed'] == 1): ?>
                <span class="badge bg-success mb-2">Approved</span>
              <?php elseif ($report['StatusConfirmed'] == 0): ?>
                <span class="badge bg-warning mb-2">Pending Approval</span>
              <?php else: ?>
                <span class="badge bg-danger mb-2">Rejected</span>
              <?php endif; ?>
              <button class="btn btn-primary btn-sm mt-auto" data-bs-toggle="modal" data-bs-target="#reportModal<?php echo $idx; ?>">View Details</button>
              <form method="POST" action="delete_report.php" class="mt-2" onsubmit="return confirm('Delete this report?');">
                <input type="hidden" name="report_id" value="<?php echo $report['ReportID']; ?>">
                <button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
              </form>
            </div>
          </div>
        </div>
        <!-- Modal for report details -->
        <div class="modal fade" id="reportModal<?php echo $idx; ?>" tabindex="-1" aria-labelledby="reportModalLabel<?php echo $idx; ?>" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel<?php echo $idx; ?>">Lost Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php if ($report['PhotoURL']): ?>
                  <img src="<?php echo getImagePath($report['PhotoURL']); ?>" class="img-fluid mb-3" alt="Lost Item Image" onerror="<?php echo getImageErrorHandler(); ?>">
                <?php endif; ?>
                <ul class="list-group list-group-flush mb-2">
                  <li class="list-group-item"><strong>Class:</strong> <?php echo htmlspecialchars($report['ClassName']); ?></li>
                  <li class="list-group-item"><strong>Description:</strong> <?php echo htmlspecialchars($report['Description']); ?></li>
                  <li class="list-group-item"><strong>Date of Loss:</strong> <?php echo htmlspecialchars($report['DateOfLoss']); ?></li>
                  <li class="list-group-item"><strong>Approval Status:</strong> 
                    <?php if ($report['StatusConfirmed'] == 1): ?>
                      <span class="badge bg-success">Approved</span>
                    <?php elseif ($report['StatusConfirmed'] == 0): ?>
                      <span class="badge bg-warning">Pending Admin Approval</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Rejected by Admin</span>
                    <?php endif; ?>
                  </li>
                  <li class="list-group-item"><strong>Reported at:</strong> <?php echo htmlspecialchars($report['CreatedAt']); ?></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12"><p class="text-muted">You have not reported any lost items yet.</p></div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/notifications.js"></script>
<?php include '../templates/footer.php'; ?>
</body>
</html> 