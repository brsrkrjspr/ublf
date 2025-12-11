<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ImageHelper.php';
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
$db = new Database();
$conn = $db->getConnection();

// Fetch fresh student data to get the latest PhotoConfirmed status
$stmt = $conn->prepare('SELECT * FROM student WHERE StudentNo = :studentNo LIMIT 1');
$stmt->execute(['studentNo' => $_SESSION['student']['StudentNo']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Update session with fresh data
$_SESSION['student'] = $student;
// Fetch user's lost item reports
$stmt = $conn->prepare('SELECT r.ReportID, c.ClassName, r.Description, r.DateOfLoss, r.CreatedAt, r.PhotoURL, rs.StatusName, r.StatusConfirmed FROM reportitem r JOIN itemclass c ON r.ItemClassID = c.ItemClassID LEFT JOIN reportstatus rs ON r.ReportStatusID = rs.ReportStatusID WHERE r.StudentNo = :studentNo ORDER BY r.CreatedAt DESC');
$stmt->execute(['studentNo' => $student['StudentNo']]);
$myReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
$msg = $_SESSION['profile_msg'] ?? '';
unset($_SESSION['profile_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
  $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
  ?>
  <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
  <link href="css.php?file=profile.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../templates/header.php'; ?>
<div class="fb-profile-container position-relative">
  <div class="fb-cover-banner position-relative">
    <div class="banner-bg"></div>
  </div>
  <!-- Profile photo OUTSIDE the grid, absolutely positioned -->
  <div class="fb-profile-photo-wrapper fb-profile-photo-left">
      <?php if (!empty($student['ProfilePhoto'])): ?>
      <img src="../<?php echo htmlspecialchars($student['ProfilePhoto']); ?>" alt="Profile Photo" class="fb-profile-photo" id="profilePhotoDisplay">
      <?php if (isset($student['PhotoConfirmed']) && $student['PhotoConfirmed'] == 0): ?>
        <div class="badge bg-warning text-dark position-absolute" style="top:10px;right:-10px;z-index:1001;">Pending admin approval</div>
      <?php elseif (isset($student['PhotoConfirmed']) && $student['PhotoConfirmed'] == -1): ?>
        <div class="badge bg-danger text-white position-absolute" style="top:10px;right:-10px;z-index:1001;">Rejected by admin</div>
      <?php endif; ?>
    <?php else: ?>
      <i class="bi bi-person-circle fb-profile-photo" id="profilePhotoDisplay" style="font-size:8rem;color:#FFD700;background:#fff;"></i>
      <?php endif; ?>
      <form method="POST" action="update_profile.php" enctype="multipart/form-data" id="photoForm">
        <input type="file" name="profilePhoto" accept="image/*" class="hidden-file-input" id="profilePhotoInput" onchange="document.getElementById('photoForm').submit();">
      <label class="fb-profile-photo-overlay" for="profilePhotoInput" title="Change Photo">
          <i class="bi bi-camera" style="font-size:2rem;color:#800000"></i>
      </label>
      </form>
  </div>
  <div class="fb-profile-main row gx-4 gy-4 align-items-start">
    <!-- Profile Photo and Intro (Left) -->
    <div class="col-lg-4 d-flex flex-column align-items-center align-items-lg-start position-relative fb-intro-col">
      <div class="fb-intro-card card p-4 mb-4 mt-4 w-100">
        <h5 class="card-title mb-3"><i class="bi bi-person-lines-fill me-2"></i>Intro</h5>
        <?php if (!empty($student['Bio'])): ?>
          <div class="mb-2"><?php echo nl2br(htmlspecialchars($student['Bio'])); ?></div>
        <?php else: ?>
          <div class="text-muted">No bio set. Click Edit Profile to add a short bio about yourself.</div>
        <?php endif; ?>
        <button class="btn btn-outline-secondary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="bi bi-pencil"></i> Edit Bio</button>
        <button class="btn btn-outline-primary btn-sm mt-2" onclick="location.reload();"><i class="bi bi-arrow-clockwise"></i> Refresh Status</button>
        <?php if (isset($student['PhotoConfirmed']) && $student['PhotoConfirmed'] == 0): ?>
          <div class="alert alert-warning alert-sm mt-2 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            <small>Your profile photo is pending admin approval and will be visible to others once approved.</small>
          </div>
        <?php elseif (isset($student['PhotoConfirmed']) && $student['PhotoConfirmed'] == -1): ?>
          <div class="alert alert-danger alert-sm mt-2 mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <small>Your profile photo was rejected by admin. Please upload a different photo.</small>
            <button class="btn btn-outline-danger btn-sm ms-2" onclick="document.getElementById('profilePhotoInput').click();">
              <i class="bi bi-camera"></i> Upload New Photo
            </button>
          </div>
        <?php elseif (isset($student['PhotoConfirmed']) && $student['PhotoConfirmed'] == 1): ?>
          <div class="alert alert-success alert-sm mt-2 mb-0">
            <i class="bi bi-check-circle me-1"></i>
            <small>Your profile photo is approved and visible to other users!</small>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <!-- Main Content (Right) -->
    <div class="col-lg-8">
      <div class="d-flex flex-column flex-md-row align-items-md-end align-items-center mb-3 gap-3">
        <div class="flex-grow-1">
          <h2 class="fb-profile-name mb-0"><?php echo htmlspecialchars($student['StudentName']); ?></h2>
          <div class="fb-profile-studentno">Student No: <?php echo htmlspecialchars($student['StudentNo']); ?></div>
        </div>
        <div class="fb-profile-actions d-flex gap-2">
          <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="bi bi-pencil"></i> Edit Profile</button>
          <button class="btn btn-outline-secondary px-4" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</button>
        </div>
  </div>
      <ul class="nav nav-tabs mb-4 fb-profile-tabs" id="profileTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab" aria-controls="about" aria-selected="true">About</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="false">Reports</button>
        </li>
      </ul>
      <div class="tab-content" id="profileTabContent">
        <!-- About Tab -->
        <div class="tab-pane fade show active" id="about" role="tabpanel" aria-labelledby="about-tab">
          <div class="fb-about-card card p-4 mb-4">
      <div class="row mb-2">
        <div class="col-6"><strong>Name:</strong></div>
        <div class="col-6 text-end"><?php echo htmlspecialchars($student['StudentName']); ?></div>
      </div>
      <div class="row mb-2">
        <div class="col-6"><strong>Email:</strong></div>
        <div class="col-6 text-end"><?php echo htmlspecialchars($student['Email']); ?></div>
      </div>
      <div class="row mb-2">
        <div class="col-6"><strong>Phone:</strong></div>
        <div class="col-6 text-end"><?php echo htmlspecialchars($student['PhoneNo']); ?></div>
      </div>
      <div class="row mb-2">
        <div class="col-6"><strong>Student No:</strong></div>
        <div class="col-6 text-end"><?php echo htmlspecialchars($student['StudentNo']); ?></div>
      </div>
    </div>
  </div>
        <!-- Reports Tab -->
        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
          <div class="fb-reports-card card p-4 mb-4">
            <div class="profile-activity-title mb-3"><i class="bi bi-clipboard-data me-2"></i>My Lost Item Reports</div>
  <div class="profile-activity-cards row g-4">
    <?php if (count($myReports) > 0): ?>
      <?php foreach ($myReports as $idx => $report): ?>
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
                        <?php if (isset($report['StatusConfirmed']) && $report['StatusConfirmed'] == 1): ?>
                          <span class="badge bg-success mb-2">Approved</span>
                        <?php elseif (isset($report['StatusConfirmed']) && $report['StatusConfirmed'] == 0): ?>
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
                  <img src="../<?php echo encodeImageUrl($report['PhotoURL']); ?>" class="img-fluid mb-3" alt="Lost Item Image" onerror="<?php echo getImageErrorHandler(); ?>">
                <?php endif; ?>
                <ul class="list-group list-group-flush mb-2">
                  <li class="list-group-item"><strong>Class:</strong> <?php echo htmlspecialchars($report['ClassName']); ?></li>
                  <li class="list-group-item"><strong>Description:</strong> <?php echo htmlspecialchars($report['Description']); ?></li>
                  <li class="list-group-item"><strong>Date of Loss:</strong> <?php echo htmlspecialchars($report['DateOfLoss']); ?></li>
                            <li class="list-group-item"><strong>Approval Status:</strong> 
                              <?php if (isset($report['StatusConfirmed']) && $report['StatusConfirmed'] == 1): ?>
                                <span class="badge bg-success">Approved</span>
                              <?php elseif (isset($report['StatusConfirmed']) && $report['StatusConfirmed'] == 0): ?>
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
            <hr>
            <div class="profile-activity-title mb-3"><i class="bi bi-clipboard-check me-2"></i>My Found Item Reports</div>
            <div class="profile-activity-cards row g-4">
              <?php if (isset($myFoundReports) && count($myFoundReports) > 0): ?>
                <?php foreach ($myFoundReports as $idx => $report): ?>
                  <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                      <?php if ($report['PhotoURL']): ?>
                        <img src="../<?php echo encodeImageUrl($report['PhotoURL']); ?>" class="card-img-top" alt="Found Item Image" style="object-fit:cover;max-height:180px;" onerror="<?php echo getImageErrorHandler(); ?>">
                      <?php else: ?>
                        <img src="<?php echo getPlaceholderImage(); ?>" class="card-img-top" alt="No Image">
                      <?php endif; ?>
                      <div class="card-body d-flex flex-column">
                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($report['ClassName']); ?></h6>
                        <p class="card-text small mb-2"><?php echo htmlspecialchars(mb_strimwidth($report['Description'], 0, 60, '...')); ?></p>
                        <span class="badge bg-success mb-2">Found</span>
                        <button class="btn btn-primary btn-sm mt-auto" data-bs-toggle="modal" data-bs-target="#foundReportModal<?php echo $idx; ?>">View Details</button>
                        <form method="POST" action="delete_report.php" class="mt-2" onsubmit="return confirm('Delete this report?');">
                          <input type="hidden" name="report_id" value="<?php echo $report['ReportID']; ?>">
                          <button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
                        </form>
                      </div>
                    </div>
                  </div>
                  <!-- Modal for found report details -->
                  <div class="modal fade" id="foundReportModal<?php echo $idx; ?>" tabindex="-1" aria-labelledby="foundReportModalLabel<?php echo $idx; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="foundReportModalLabel<?php echo $idx; ?>">Found Item Details</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <?php if ($report['PhotoURL']): ?>
                            <img src="../<?php echo encodeImageUrl($report['PhotoURL']); ?>" class="img-fluid mb-3" alt="Found Item Image" onerror="<?php echo getImageErrorHandler(); ?>">
                          <?php endif; ?>
                          <ul class="list-group list-group-flush mb-2">
                            <li class="list-group-item"><strong>Class:</strong> <?php echo htmlspecialchars($report['ClassName']); ?></li>
                            <li class="list-group-item"><strong>Description:</strong> <?php echo htmlspecialchars($report['Description']); ?></li>
                            <li class="list-group-item"><strong>Date Found:</strong> <?php echo htmlspecialchars($report['DateOfLoss']); ?></li>
                            <li class="list-group-item"><strong>Status:</strong> Found</li>
                            <li class="list-group-item"><strong>Reported at:</strong> <?php echo htmlspecialchars($report['CreatedAt']); ?></li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="col-12"><p class="text-muted">You have not reported any found items yet.</p></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="update_profile.php">
        <div class="modal-header">
          <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="profileName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="profileName" name="profileName" value="<?php echo htmlspecialchars($student['StudentName']); ?>" required>
          </div>
          <div class="mb-3">
            <label for="profileEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="profileEmail" name="profileEmail" value="<?php echo htmlspecialchars($student['Email']); ?>" required>
          </div>
          <div class="mb-3">
            <label for="profilePhone" class="form-label">Phone No</label>
            <input type="text" class="form-control" id="profilePhone" name="profilePhone" value="<?php echo htmlspecialchars($student['PhoneNo']); ?>">
          </div>
          <div class="mb-3">
            <label for="profileBio" class="form-label">Bio</label>
            <textarea class="form-control" id="profileBio" name="profileBio" rows="3"><?php echo htmlspecialchars($student['Bio'] ?? ''); ?></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="update_profile.php">
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="profilePassword" class="form-label">New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="profilePassword" name="profilePassword" required>
              <button type="button" class="btn btn-outline-secondary password-toggle" tabindex="-1" onclick="togglePassword('profilePassword', this)" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Change Password</button>
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
<script src="../assets/notifications.js"></script>
</body>
</html> 