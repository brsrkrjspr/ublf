<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../classes/ReportItem.php';
require_once __DIR__ . '/../includes/ImageHelper.php';
session_start();
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
$student = $_SESSION['student'];
$db = new Database();
$conn = $db->getConnection();
// Filtering logic
$lostWhere = [];
$lostParams = [];
if (!empty($_GET['lost_keyword'])) {
  $lostWhere[] = '(r.Description LIKE :keyword)';
  $lostParams['keyword'] = '%' . $_GET['lost_keyword'] . '%';
}
if (!empty($_GET['lost_date'])) {
  $lostWhere[] = 'r.DateOfLoss = :date';
  $lostParams['date'] = $_GET['lost_date'];
}
if (!empty($_GET['lost_class'])) {
  $lostWhere[] = 'c.ClassName LIKE :class';
  $lostParams['class'] = '%' . $_GET['lost_class'] . '%';
}
$lostSql = 'SELECT r.ReportID, c.ClassName, r.Description, r.DateOfLoss, r.CreatedAt, s.StudentName, s.Email, s.PhoneNo, r.PhotoURL, s.ProfilePhoto, s.PhotoConfirmed, s.StudentNo FROM reportitem r JOIN itemclass c ON r.ItemClassID = c.ItemClassID JOIN student s ON r.StudentNo = s.StudentNo WHERE r.StatusConfirmed = 1';
if ($lostWhere) {
  $lostSql .= ' AND ' . implode(' AND ', $lostWhere);
}
$lostSql .= ' ORDER BY r.CreatedAt DESC';
$stmt = $conn->prepare($lostSql);
$stmt->execute($lostParams);
$allLost = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch item classes for dropdown
$reportItemObj = new ReportItem();
$itemClasses = $reportItemObj->getItemClasses();
if (empty($itemClasses)) {
  $itemClasses = ['Electronics', 'Books', 'Clothing', 'Bags', 'ID Cards', 'Keys', 'Others'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Lost Items - UB Lost & Found</title>
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
      <div class="card shadow-sm p-4 mb-3" style="background: #fff9f6;">
        <form class="row g-3" method="GET" action="all_lost.php">
          <div class="col-md-4">
            <input type="text" class="form-control" name="lost_keyword" placeholder="Keyword" value="<?php echo htmlspecialchars($_GET['lost_keyword'] ?? ''); ?>">
          </div>
          <div class="col-md-3">
            <input type="date" class="form-control" name="lost_date" value="<?php echo htmlspecialchars($_GET['lost_date'] ?? ''); ?>">
          </div>
          <div class="col-md-3">
            <select class="form-select" name="lost_class">
              <option value="" selected>Select class</option>
              <?php foreach ($itemClasses as $class): ?>
                <option value="<?php echo htmlspecialchars($class); ?>" <?php if ((isset($_GET['lost_class']) && $_GET['lost_class'] === $class)) echo 'selected'; ?>><?php echo htmlspecialchars($class); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="row g-4">
    <?php if (count($allLost) > 0): ?>
      <?php foreach ($allLost as $idx => $lost): ?>
        <div class="col-md-4 col-lg-3">
          <div class="card h-100 shadow-sm">
            <?php if ($lost['PhotoURL']): ?>
              <img src="<?php echo getImagePath($lost['PhotoURL']); ?>" class="card-img-top" alt="Lost Item Image" style="object-fit:cover;max-height:180px;" onerror="<?php echo getImageErrorHandler(); ?>">
            <?php else: ?>
              <img src="<?php echo getPlaceholderImage(); ?>" class="card-img-top" alt="No Image">
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-center mb-2">
                <?php if (!empty($lost['ProfilePhoto']) && isset($lost['PhotoConfirmed']) && $lost['PhotoConfirmed'] == 1): ?>
                  <a href="view_profile.php?student_no=<?php echo htmlspecialchars($lost['StudentNo']); ?>" class="text-decoration-none">
                    <img src="<?php echo getImagePath($lost['ProfilePhoto']); ?>" alt="Profile Photo" class="rounded-circle me-2" style="width:24px;height:24px;object-fit:cover;" onerror="<?php echo getImageErrorHandler(); ?>">
                  </a>
                <?php else: ?>
                  <i class="bi bi-person-circle me-2" style="font-size:1.2rem;color:#6c757d;"></i>
                <?php endif; ?>
                <a href="view_profile.php?student_no=<?php echo htmlspecialchars($lost['StudentNo']); ?>" class="text-decoration-none">
                  <small class="text-muted"><?php echo htmlspecialchars($lost['StudentName']); ?></small>
                </a>
              </div>
              <h5 class="card-title mb-1"><?php echo htmlspecialchars($lost['ClassName']); ?></h5>
              <p class="card-text small mb-2"><?php echo htmlspecialchars(mb_strimwidth($lost['Description'], 0, 60, '...')); ?></p>
              <button class="btn btn-primary mt-auto" data-bs-toggle="modal" data-bs-target="#lostModal<?php echo $idx; ?>">View Details</button>
            </div>
          </div>
        </div>
        <!-- Modal for item details -->
        <div class="modal fade" id="lostModal<?php echo $idx; ?>" tabindex="-1" aria-labelledby="lostModalLabel<?php echo $idx; ?>" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="lostModalLabel<?php echo $idx; ?>">Lost Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php if ($lost['PhotoURL']): ?>
                  <img src="<?php echo getImagePath($lost['PhotoURL']); ?>" class="img-fluid mb-3" alt="Lost Item Image" onerror="<?php echo getImageErrorHandler(); ?>">
                <?php endif; ?>
                <ul class="list-group list-group-flush mb-2">
                  <li class="list-group-item"><strong>Class:</strong> <?php echo htmlspecialchars($lost['ClassName']); ?></li>
                  <li class="list-group-item"><strong>Description:</strong> <?php echo htmlspecialchars($lost['Description']); ?></li>
                  <li class="list-group-item"><strong>Date of Loss:</strong> <?php echo htmlspecialchars($lost['DateOfLoss']); ?></li>
                  <li class="list-group-item">
                    <strong>Reported By:</strong> 
                    <div class="d-flex align-items-center mt-1">
                      <?php if (!empty($lost['ProfilePhoto']) && isset($lost['PhotoConfirmed']) && $lost['PhotoConfirmed'] == 1): ?>
                        <a href="view_profile.php?student_no=<?php echo htmlspecialchars($lost['StudentNo']); ?>" class="text-decoration-none">
                          <img src="<?php echo getImagePath($lost['ProfilePhoto']); ?>" alt="Profile Photo" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;" onerror="<?php echo getImageErrorHandler(); ?>">
                        </a>
                      <?php else: ?>
                        <i class="bi bi-person-circle me-2" style="font-size:1.5rem;color:#6c757d;"></i>
                      <?php endif; ?>
                      <a href="view_profile.php?student_no=<?php echo htmlspecialchars($lost['StudentNo']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($lost['StudentName']); ?>
                      </a>
                    </div>
                  </li>
                  <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($lost['Email']); ?></li>
                  <li class="list-group-item"><strong>Phone:</strong> <?php echo htmlspecialchars($lost['PhoneNo']); ?></li>
                </ul>
                <div class="text-end text-muted small">Reported at: <?php echo htmlspecialchars($lost['CreatedAt']); ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12"><p class="text-muted">No lost items found.</p></div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/notifications.js"></script>
</body>
</html> 