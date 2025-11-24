<?php
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ReportItem.php';
require_once __DIR__ . '/../classes/Item.php';

session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Initialize classes
$admin = new Admin();
$student = new Student();
$reportItem = new ReportItem();
$item = new Item();

// Get dashboard statistics (with fallback for no database)
$stats = ['pendingPhotoApprovals' => 0, 'pendingLostApprovals' => 0, 'pendingFoundApprovals' => 0];
try {
    if ($conn) {
        $stats = $admin->getDashboardStats() ?? $stats;
    }
} catch (Exception $e) {
    // Use default stats
}

// Get pending approvals (with fallback)
$pendingApprovals = ['lostItems' => [], 'foundItems' => []];
try {
    if ($conn) {
        $pendingApprovals = $admin->getPendingApprovals() ?? $pendingApprovals;
    }
} catch (Exception $e) {
    // Use empty arrays
}

// Get completed approvals (with fallback)
$completedApprovals = ['lostItems' => [], 'foundItems' => []];
try {
    if ($conn) {
        $completedApprovals = $admin->getCompletedApprovals() ?? $completedApprovals;
    }
} catch (Exception $e) {
    // Use empty arrays
}

// Get all admins (with fallback)
$admins = [];
try {
    if ($conn) {
        $admins = $admin->getAllAdmins() ?? [];
    }
} catch (Exception $e) {
    // Use empty array, add mock admin for display
    if (isset($_SESSION['admin'])) {
        $admins = [$_SESSION['admin']];
    }
}

// Section logic
$section = $_GET['section'] ?? 'pending';
function sidebar_active($sec, $current) { return $sec === $current ? 'active' : ''; }

if ($section === 'itemmgmt') {
  // CATEGORY MANAGEMENT LOGIC
  $catMsg = '';
  // Only process if database is available
  if (!$conn) {
    $catMsg = '<div class="alert alert-warning">Database connection unavailable. Changes cannot be saved.</div>';
  }
  // Handle add
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category']) && $conn) {
    $newCat = trim($_POST['category_name'] ?? '');
    if ($newCat !== '') {
      $stmt = $conn->prepare('SELECT COUNT(*) FROM ItemClass WHERE ClassName = :name');
      $stmt->execute(['name' => $newCat]);
      if ($stmt->fetchColumn() > 0) {
        $catMsg = '<div class="alert alert-warning">Category already exists.</div>';
      } else {
        $stmt = $conn->prepare('INSERT INTO ItemClass (ClassName) VALUES (:name)');
        if ($stmt->execute(['name' => $newCat])) {
          $catMsg = '<div class="alert alert-success">Category added.</div>';
        } else {
          $catMsg = '<div class="alert alert-danger">Failed to add category.</div>';
        }
      }
    }
  }
  // Handle edit
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category']) && $conn) {
    $catId = intval($_POST['edit_id'] ?? 0);
    $catName = trim($_POST['edit_name'] ?? '');
    if ($catId && $catName !== '') {
      $stmt = $conn->prepare('SELECT COUNT(*) FROM ItemClass WHERE ClassName = :name AND ItemClassID != :id');
      $stmt->execute(['name' => $catName, 'id' => $catId]);
      if ($stmt->fetchColumn() > 0) {
        $catMsg = '<div class="alert alert-warning">Category name already exists.</div>';
      } else {
        $stmt = $conn->prepare('UPDATE ItemClass SET ClassName = :name WHERE ItemClassID = :id');
        if ($stmt->execute(['name' => $catName, 'id' => $catId])) {
          $catMsg = '<div class="alert alert-success">Category updated.</div>';
        } else {
          $catMsg = '<div class="alert alert-danger">Failed to update category.</div>';
        }
      }
    }
  }
  // Handle delete
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category']) && $conn) {
    $catId = intval($_POST['delete_id'] ?? 0);
    if ($catId) {
      // Optionally: check if in use
      $stmt = $conn->prepare('SELECT COUNT(*) FROM reportitem WHERE ItemClassID = :id');
      $stmt->execute(['id' => $catId]);
      $inUse = $stmt->fetchColumn();
      if ($inUse > 0) {
        $catMsg = '<div class="alert alert-warning">Cannot delete: category in use.</div>';
      } else {
        $stmt = $conn->prepare('DELETE FROM ItemClass WHERE ItemClassID = :id');
        if ($stmt->execute(['id' => $catId])) {
          $catMsg = '<div class="alert alert-success">Category deleted.</div>';
        } else {
          $catMsg = '<div class="alert alert-danger">Failed to delete category.</div>';
        }
      }
    }
  }
  // Fetch all categories (with fallback)
  $cats = [];
  try {
    if ($conn) {
      $cats = $conn->query('SELECT * FROM ItemClass ORDER BY ClassName')->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (Exception $e) {
    // Use empty array
  }
  $editId = isset($_POST['start_edit']) ? intval($_POST['start_edit']) : 0;

  // STATUS MANAGEMENT LOGIC
  $statusMsg = '';
  // Handle add status
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_status']) && $conn) {
    $newStatus = trim($_POST['status_name'] ?? '');
    if ($newStatus !== '') {
      $stmt = $conn->prepare('SELECT COUNT(*) FROM Status WHERE StatusName = :name');
      $stmt->execute(['name' => $newStatus]);
      if ($stmt->fetchColumn() > 0) {
        $statusMsg = '<div class="alert alert-warning">Status already exists.</div>';
      } else {
        $stmt = $conn->prepare('INSERT INTO Status (StatusName) VALUES (:name)');
        if ($stmt->execute(['name' => $newStatus])) {
          $statusMsg = '<div class="alert alert-success">Status added.</div>';
        } else {
          $statusMsg = '<div class="alert alert-danger">Failed to add status.</div>';
        }
      }
    }
  }
  // Handle edit status
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_status']) && $conn) {
    $statusId = intval($_POST['edit_status_id'] ?? 0);
    $statusName = trim($_POST['edit_status_name'] ?? '');
    if ($statusId && $statusName !== '') {
      $stmt = $conn->prepare('SELECT COUNT(*) FROM Status WHERE StatusName = :name AND StatusID != :id');
      $stmt->execute(['name' => $statusName, 'id' => $statusId]);
      if ($stmt->fetchColumn() > 0) {
        $statusMsg = '<div class="alert alert-warning">Status name already exists.</div>';
      } else {
        $stmt = $conn->prepare('UPDATE Status SET StatusName = :name WHERE StatusID = :id');
        if ($stmt->execute(['name' => $statusName, 'id' => $statusId])) {
          $statusMsg = '<div class="alert alert-success">Status updated.</div>';
        } else {
          $statusMsg = '<div class="alert alert-danger">Failed to update status.</div>';
        }
      }
    }
  }
  // Handle delete status
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_status']) && $conn) {
    $statusId = intval($_POST['delete_status_id'] ?? 0);
    if ($statusId) {
      // Optionally: check if in use
      $stmt = $conn->prepare('SELECT COUNT(*) FROM reportitem WHERE ReportStatusID = :id');
      $stmt->execute(['id' => $statusId]);
      $inUse = $stmt->fetchColumn();
      if ($inUse > 0) {
        $statusMsg = '<div class="alert alert-warning">Cannot delete: status in use.</div>';
      } else {
        $stmt = $conn->prepare('DELETE FROM Status WHERE StatusID = :id');
        if ($stmt->execute(['id' => $statusId])) {
          $statusMsg = '<div class="alert alert-success">Status deleted.</div>';
        } else {
          $statusMsg = '<div class="alert alert-danger">Failed to delete status.</div>';
        }
      }
    }
  }
  // Fetch all statuses (with fallback)
  $statuses = [];
  try {
    if ($conn) {
      $statuses = $conn->query('SELECT * FROM Status ORDER BY StatusName')->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (Exception $e) {
    // Use empty array
  }
  $editStatusId = isset($_POST['start_edit_status']) ? intval($_POST['start_edit_status']) : 0;
}
if ($section === 'site') {
  // SITE SETTINGS LOGIC
  $settingsMsg = '';
  // Simulate settings storage (in real app, use a settings table)
  $siteName = 'UB Lost & Found';
  $contactEmail = 'foundlost004@gmail.com';
  $maintenance = false;
  // Handle settings update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $siteName = trim($_POST['site_name'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $maintenance = isset($_POST['maintenance_mode']) ? true : false;
    $settingsMsg = '<div class="alert alert-success">Settings updated (demo only).</div>';
  }
  // Handle password change
  $pwMsg = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $adminID = $_SESSION['admin']['AdminID'];
    $stmt = $conn->prepare('SELECT PasswordHash FROM Admin WHERE AdminID = :id');
    $stmt->execute(['id' => $adminID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($current, $row['PasswordHash'])) {
      $pwMsg = '<div class="alert alert-danger">Current password is incorrect.</div>';
    } elseif ($new !== $confirm) {
      $pwMsg = '<div class="alert alert-warning">New passwords do not match.</div>';
    } elseif (strlen($new) < 6) {
      $pwMsg = '<div class="alert alert-warning">New password must be at least 6 characters.</div>';
    } else {
      $newHash = password_hash($new, PASSWORD_BCRYPT);
      $stmt = $conn->prepare('UPDATE Admin SET PasswordHash = :pw WHERE AdminID = :id');
      if ($stmt->execute(['pw' => $newHash, 'id' => $adminID])) {
        $pwMsg = '<div class="alert alert-success">Password changed successfully!</div>';
      } else {
        $pwMsg = '<div class="alert alert-danger">Failed to change password.</div>';
      }
    }
  }
}
if ($section === 'students') {
  // STUDENT MANAGEMENT LOGIC
  $studentMsg = '';
  $search = trim($_GET['search'] ?? '');
  $where = $search ? 'WHERE StudentName LIKE :search OR StudentNo LIKE :search OR Email LIKE :search' : '';
  $stmt = $conn->prepare('SELECT * FROM student ' . $where . ' ORDER BY StudentNo');
  if ($search) $stmt->execute(['search' => "%$search%"]); else $stmt->execute();
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
  // Handle deactivate/reactivate/delete
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['deactivate_student'])) {
      $id = $_POST['student_no'] ?? '';
      $stmt = $conn->prepare('UPDATE student SET Active = 0 WHERE StudentNo = :id');
      $stmt->execute(['id' => $id]);
      $studentMsg = '<div class="alert alert-warning">Student deactivated.</div>';
    } elseif (isset($_POST['reactivate_student'])) {
      $id = $_POST['student_no'] ?? '';
      $stmt = $conn->prepare('UPDATE student SET Active = 1 WHERE StudentNo = :id');
      $stmt->execute(['id' => $id]);
      $studentMsg = '<div class="alert alert-success">Student reactivated.</div>';
    } elseif (isset($_POST['delete_student'])) {
      $id = $_POST['student_no'] ?? '';
      $stmt = $conn->prepare('DELETE FROM student WHERE StudentNo = :id');
      $stmt->execute(['id' => $id]);
      $studentMsg = '<div class="alert alert-danger">Student deleted.</div>';
    }
  }
}
if ($section === 'export') {
  // DATA EXPORT LOGIC
  $exportMsg = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_type'])) {
    $type = $_POST['export_type'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_export.csv"');
    $out = fopen('php://output', 'w');
    if ($type === 'lost') {
      fputcsv($out, ['ReportID','ItemName','ClassName','Description','DateOfLoss','LostLocation','StudentNo']);
      $stmt = $conn->query('SELECT r.ReportID, r.ItemName, c.ClassName, r.Description, r.DateOfLoss, r.LostLocation, r.StudentNo FROM reportitem r JOIN itemclass c ON r.ItemClassID = c.ItemClassID');
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
    } elseif ($type === 'found') {
      fputcsv($out, ['ItemID','ItemName','ClassName','Description','DateFound','LocationFound','AdminID']);
      $stmt = $conn->query('SELECT i.ItemID, i.ItemName, c.ClassName, i.Description, i.DateFound, i.LocationFound, i.AdminID FROM Item i JOIN ItemClass c ON i.ItemClassID = c.ItemClassID');
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
    } elseif ($type === 'students') {
      fputcsv($out, ['StudentNo','StudentName','Email','PhoneNo','Active']);
      $stmt = $conn->query('SELECT StudentNo, StudentName, Email, PhoneNo, Active FROM student');
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
    }
    fclose($out);
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
  // Use CSS router for reliable path resolution
  $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
  ?>
  <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
  <link href="css.php?file=admin_dashboard.css" rel="stylesheet">
  <link href="css.php?file=notifications.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="admin-topbar">
  <div class="d-flex align-items-center gap-2">
    <img src="../assets/ub_logo.png" alt="University of Batangas Logo" style="height: 48px; width: 48px; object-fit: contain; background: #fff; border-radius: 50%; border: 2px solid #800000; margin-right: 0.7rem;">
    <span class="fw-bold fs-4">UB Lost & Found Admin</span>
  </div>
  <div>
    <span class="me-3">👤 <?php echo htmlspecialchars($_SESSION['admin']['AdminName'] ?? ''); ?></span>
    <a href="admin_dashboard.php?section=site" class="btn btn-light" title="Site Settings" style="margin-right: 0.5rem;">
      <i class="bi bi-gear" style="font-size: 1.5rem;"></i>
    </a>
    <a href="admin_logout.php" class="btn btn-logout">Logout</a>
  </div>
</div>
<div class="container-fluid">
  <div class="row g-4">
    <div class="col-md-2">
      <div class="admin-sidebar">
        <a href="admin_dashboard.php?section=pending" class="<?php echo sidebar_active('pending', $section); ?>">Pending</a>
        <a href="admin_dashboard.php?section=completed" class="<?php echo sidebar_active('completed', $section); ?>">Completed</a>
        <a href="admin_dashboard.php?section=analytics" class="<?php echo sidebar_active('analytics', $section); ?>">Analytics</a>
        <a href="admin_dashboard.php?section=adminmgmt" class="<?php echo sidebar_active('adminmgmt', $section); ?>">Admin Management</a>
        <a href="admin_dashboard.php?section=itemmgmt" class="<?php echo sidebar_active('itemmgmt', $section); ?>">Item Management</a>
        <a href="admin_dashboard.php?section=students" class="<?php echo sidebar_active('students', $section); ?>">Students</a>
        <a href="admin_dashboard.php?section=export" class="<?php echo sidebar_active('export', $section); ?>">Data Export</a>
      </div>
    </div>
    <div class="col-md-10">
      <div class="admin-header text-center mb-4">
        <h1 class="fw-bold">Admin Dashboard</h1>
        <p class="lead">UB Lost & Found</p>
      </div>
      <?php if (!empty($_SESSION['admin_msg'])): ?>
        <div class="alert alert-info text-center mb-4"><?php echo $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?></div>
      <?php endif; ?>
      <?php if ($section === 'pending'): ?>
        <!-- Pending Section -->
        <div class="container mb-5">
          <div class="row g-4 mb-4">
            <div class="col-md-4">
              <div class="admin-card p-4 admin-stat">
                <h5 class="mb-1">Pending Profile Photos</h5>
                <div class="display-6 fw-bold"><?php echo $stats['pendingPhotoApprovals'] ?? 0; ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="admin-card p-4 admin-stat">
                <h5 class="mb-1">Pending Lost Reports</h5>
                <div class="display-6 fw-bold"><?php echo $stats['pendingLostApprovals'] ?? 0; ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="admin-card p-4 admin-stat">
                <h5 class="mb-1">Pending Found Reports</h5>
                <div class="display-6 fw-bold"><?php echo $stats['pendingFoundApprovals'] ?? 0; ?></div>
              </div>
            </div>
          </div>
          <div class="completed-section-cards">
            <div class="admin-card pending-section p-3 mb-4">
              <h5 class="mb-3">Profile Photo Confirmations</h5>
              <table class="table admin-table pending-table table-sm align-middle">
                <thead><tr><th>Name</th><th>Email</th><th>Photo</th><th>Submitted</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($admin->getPendingPhotoSubmissions() as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['StudentName'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['Email'] ?? ''); ?></td>
                    <td><?php if (!empty($row['PhotoURL'])): ?><img src="../<?php echo htmlspecialchars($row['PhotoURL']); ?>" alt="Photo" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #800000;"><?php else: ?><i class="bi bi-person-circle" style="font-size:2rem;color:#FFD700;"></i><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($row['SubmittedAt'] ?? ''); ?></td>
                    <td class="d-flex gap-2">
                      <form method="POST" action="admin_action.php">
                        <input type="hidden" name="type" value="photo">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['PhotoID']); ?>">
                        <button class="btn btn-success btn-sm btn-admin-approve" type="submit"><i class="bi bi-check-circle"></i> Approve</button>
                      </form>
                      <form method="POST" action="admin_action.php">
                        <input type="hidden" name="type" value="photo">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['PhotoID']); ?>">
                        <button class="btn btn-danger btn-sm btn-admin-reject" type="submit"><i class="bi bi-x-circle"></i> Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (count($admin->getPendingPhotoSubmissions()) === 0): ?>
                  <tr><td colspan="5" class="text-center text-muted">No pending profile photo submissions.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="admin-card pending-section p-3 mb-4">
              <h5 class="mb-3">Lost Item Reports</h5>
              <table class="table admin-table pending-table table-sm">
                <thead><tr><th>Item</th><th>Description</th><th>Photo</th><th>Student No</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($pendingApprovals['lostItems'] as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['ItemName'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['Description'] ?? ''); ?></td>
                    <td><?php if (!empty($row['PhotoURL'])): ?><img src="../<?php echo htmlspecialchars($row['PhotoURL']); ?>" alt="Photo" style="width:40px;height:40px;object-fit:cover;"><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($row['StudentNo'] ?? ''); ?></td>
                    <td>
                      <form method="POST" action="admin_action.php" style="display:inline">
                        <input type="hidden" name="type" value="lost">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['ReportID'] ?? ''); ?>">
                        <button class="btn btn-admin-approve btn-sm" type="submit">Approve</button>
                      </form>
                      <form method="POST" action="admin_action.php" style="display:inline">
                        <input type="hidden" name="type" value="lost">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['ReportID'] ?? ''); ?>">
                        <button class="btn btn-admin-reject btn-sm" type="submit">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="admin-card pending-section p-3">
              <h5 class="mb-3">Found Item Reports</h5>
              <table class="table admin-table pending-table table-sm">
                <thead><tr><th>Item</th><th>Description</th><th>Photo</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($pendingApprovals['foundItems'] as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['ItemName'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['Description'] ?? ''); ?></td>
                    <td><?php if (!empty($row['PhotoURL'])): ?><img src="../<?php echo htmlspecialchars($row['PhotoURL']); ?>" alt="Photo" style="width:40px;height:40px;object-fit:cover;"><?php endif; ?></td>
                    <td>
                      <form method="POST" action="admin_action.php" style="display:inline">
                        <input type="hidden" name="type" value="found">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['ItemID'] ?? ''); ?>">
                        <button class="btn btn-admin-approve btn-sm" type="submit">Approve</button>
                      </form>
                      <form method="POST" action="admin_action.php" style="display:inline">
                        <input type="hidden" name="type" value="found">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['ItemID'] ?? ''); ?>">
                        <button class="btn btn-admin-reject btn-sm" type="submit">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php elseif ($section === 'completed'): ?>
        <!-- Completed Section -->
        <div class="container mb-5">
          <div class="completed-section-cards">
            <div class="admin-card p-3">
              <h5 class="mb-3">Completed Profile Photos</h5>
              <table class="table admin-table table-sm">
                <thead><tr><th>Name</th><th>Email</th><th>Photo</th><th>Status</th><th>Submitted</th><th>Reviewed</th></tr></thead>
                <tbody>
                <?php foreach ($admin->getCompletedPhotoSubmissions() as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['StudentName'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['Email'] ?? ''); ?></td>
                    <td><?php if (!empty($row['PhotoURL'])): ?><img src="../<?php echo htmlspecialchars($row['PhotoURL']); ?>" alt="Photo" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"><?php endif; ?></td>
                    <td>
                      <?php if (($row['Status'] ?? null) == 1): ?>
                        <span class="badge bg-success badge-status">Approved</span>
                      <?php elseif (($row['Status'] ?? null) == -1): ?>
                        <span class="badge bg-danger badge-status">Rejected</span>
                      <?php else: ?>
                        <span class="badge bg-secondary badge-status">Unknown</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['SubmittedAt'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['ReviewedAt'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="admin-card p-3 mb-4">
              <h5 class="mb-3">Completed Lost Item Reports</h5>
              <table class="table admin-table table-sm">
                <thead><tr><th>Item</th><th>Description</th><th>Photo</th><th>Student No</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($completedApprovals['lostItems'] as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['ItemName'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['Description'] ?? ''); ?></td>
                    <td><?php if (!empty($row['PhotoURL'])): ?><img src="../<?php echo htmlspecialchars($row['PhotoURL']); ?>" alt="Photo" style="width:40px;height:40px;object-fit:cover;"><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($row['StudentNo'] ?? ''); ?></td>
                    <td>
                      <?php if (($row['StatusConfirmed'] ?? null) == 1): ?>
                        <span class="badge bg-success badge-status">Approved</span>
                      <?php elseif (($row['StatusConfirmed'] ?? null) == -1): ?>
                        <span class="badge bg-danger badge-status">Rejected</span>
                      <?php else: ?>
                        <span class="badge bg-secondary badge-status">Unknown</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['UpdatedAt'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="admin-card p-3">
            <h5 class="mb-3">Completed Found Item Reports</h5>
            <table class="table admin-table table-sm">
              <thead><tr><th>Item</th><th>Description</th><th>Photo</th><th>Status</th><th>Date</th></tr></thead>
              <tbody>
              <?php foreach ($completedApprovals['foundItems'] as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['ItemName'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($row['Description'] ?? ''); ?></td>
                  <td><?php if (!empty($row['PhotoURL'])): ?><img src="../<?php echo htmlspecialchars($row['PhotoURL']); ?>" alt="Photo" style="width:40px;height:40px;object-fit:cover;"><?php endif; ?></td>
                  <td>
                    <?php if (($row['StatusConfirmed'] ?? null) == 1): ?>
                      <span class="badge bg-success badge-status">Approved</span>
                    <?php elseif (($row['StatusConfirmed'] ?? null) == -1): ?>
                      <span class="badge bg-danger badge-status">Rejected</span>
                    <?php else: ?>
                      <span class="badge bg-secondary badge-status">Unknown</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($row['UpdatedAt'] ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php elseif ($section === 'adminmgmt'): ?>
        <!-- Admin Management Section -->
        <div class="container mb-5">
          <div class="row g-4 mb-4">
            <div class="col-md-8">
              <div class="admin-card p-3">
                <h5 class="mb-3">All Admins</h5>
                <table class="table admin-management-table table-sm">
                  <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Action</th></tr></thead>
                  <tbody>
                  <?php foreach ($admins as $row): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['AdminName'] ?? $row['StudentName'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['Username'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['Email'] ?? ''); ?></td>
                      <td>
                        <?php if (($_SESSION['admin']['AdminID'] ?? null) != ($row['AdminID'] ?? null)): ?>
                          <form method="POST" action="remove_admin.php" style="display:inline" onsubmit="return confirm('Remove this admin?');">
                            <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($row['AdminID'] ?? ''); ?>">
                            <button class="btn btn-admin-remove btn-sm" type="submit">Remove</button>
                          </form>
                        <?php else: ?>
                          <span class="badge bg-secondary">You</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-md-4">
              <div class="admin-card p-3">
                <h5 class="mb-3">Add New Admin</h5>
                <form method="POST" action="add_admin.php">
                  <div class="mb-2">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="admin_name" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="admin_username" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="admin_email" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="admin_password" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Add Admin</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php elseif ($section === 'settings'): ?>
        <!-- Settings Section -->
        <div class="container mb-5">
          <div class="settings-card admin-card p-4 mt-4">
            <h5 class="mb-3">Change Password</h5>
            <?php if (!empty($_SESSION['admin_settings_msg'])): ?>
              <div class="alert alert-info text-center mb-3"><?php echo $_SESSION['admin_settings_msg']; unset($_SESSION['admin_settings_msg']); ?></div>
            <?php endif; ?>
            <form method="POST" action="change_admin_password.php">
              <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" name="current_password" required>
              </div>
              <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="new_password" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_password" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Change Password</button>
            </form>
          </div>
        </div>
      <?php elseif ($section === 'itemmgmt'): ?>
        <!-- Item Management Tab -->
        <div class="container mb-5">
          <div class="admin-card p-4 mb-4">
            <h3 class="mb-3">Manage Item Categories</h3>
            <p class="text-muted">Add, edit, or delete item categories (e.g., Electronics, Books, Clothing).</p>
            <?php if (!empty($catMsg)) echo $catMsg; ?>
            <form method="POST" class="row g-2 mb-3">
              <div class="col-md-6 col-lg-4">
                <input type="text" name="category_name" class="form-control" placeholder="New category name" required>
              </div>
              <div class="col-md-2">
                <button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button>
              </div>
            </form>
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr><th>#</th><th>Name</th><th style="width:160px;">Actions</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($cats as $cat): ?>
                    <tr>
                      <td><?php echo $cat['ItemClassID']; ?></td>
                      <td>
                        <?php if ($editId === intval($cat['ItemClassID'])): ?>
                          <form method="POST" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="edit_id" value="<?php echo $cat['ItemClassID']; ?>">
                            <input type="text" name="edit_name" value="<?php echo htmlspecialchars($cat['ClassName']); ?>" class="form-control" required>
                            <button type="submit" name="edit_category" class="btn btn-success btn-sm">Save</button>
                            <a href="admin_dashboard.php?section=itemmgmt" class="btn btn-secondary btn-sm">Cancel</a>
                          </form>
                        <?php else: ?>
                          <?php echo htmlspecialchars($cat['ClassName']); ?>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($editId === intval($cat['ItemClassID'])): ?>
                          <!-- Editing -->
                        <?php else: ?>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="start_edit" value="<?php echo $cat['ItemClassID']; ?>">
                            <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                          </form>
                          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="delete_category" value="<?php echo $cat['ItemClassID']; ?>">
                            <button type="submit" name="delete_category" class="btn btn-danger btn-sm">Delete</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (empty($cats)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No categories found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="admin-card p-4">
            <h3 class="mb-3">Manage Lost/Found Item Statuses</h3>
            <p class="text-muted">Add, edit, or remove custom statuses (e.g., Claimed, Returned, In Review).</p>
            <?php if (!empty($statusMsg)) echo $statusMsg; ?>
            <form method="POST" class="row g-2 mb-3">
              <div class="col-md-6 col-lg-4">
                <input type="text" name="status_name" class="form-control" placeholder="New status name" required>
              </div>
              <div class="col-md-2">
                <button type="submit" name="add_status" class="btn btn-primary w-100">Add Status</button>
              </div>
            </form>
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr><th>#</th><th>Name</th><th style="width:160px;">Actions</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($statuses as $status): ?>
                    <tr>
                      <td><?php echo $status['StatusID']; ?></td>
                      <td>
                        <?php if ($editStatusId === intval($status['StatusID'])): ?>
                          <form method="POST" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="edit_status_id" value="<?php echo $status['StatusID']; ?>">
                            <input type="text" name="edit_status_name" value="<?php echo htmlspecialchars($status['StatusName']); ?>" class="form-control" required>
                            <button type="submit" name="edit_status" class="btn btn-success btn-sm">Save</button>
                            <a href="admin_dashboard.php?section=itemmgmt" class="btn btn-secondary btn-sm">Cancel</a>
                          </form>
                        <?php else: ?>
                          <?php echo htmlspecialchars($status['StatusName']); ?>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($editStatusId === intval($status['StatusID'])): ?>
                          <!-- Editing -->
                        <?php else: ?>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="start_edit_status" value="<?php echo $status['StatusID']; ?>">
                            <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                          </form>
                          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this status?');">
                            <input type="hidden" name="delete_status" value="<?php echo $status['StatusID']; ?>">
                            <button type="submit" name="delete_status" class="btn btn-danger btn-sm">Delete</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (empty($statuses)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No statuses found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php elseif ($section === 'site'): ?>
        <!-- Site Settings Tab -->
        <div class="container mb-5">
          <div class="admin-card p-4 mb-4">
            <h3 class="mb-3">Site Settings</h3>
            <p class="text-muted">Change the system’s name, contact email, or toggle maintenance mode. (Logo/color palette coming soon.)</p>
            <?php if (!empty($settingsMsg)) echo $settingsMsg; ?>
            <form method="POST" class="row g-3">
              <div class="col-md-6 col-lg-4">
                <label class="form-label">System Name</label>
                <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($siteName); ?>" required>
              </div>
              <div class="col-md-6 col-lg-4">
                <label class="form-label">Contact Email</label>
                <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($contactEmail); ?>" required>
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" <?php if ($maintenance) echo 'checked'; ?>>
                  <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
              </div>
            </form>
          </div>
          <div class="admin-card p-4">
            <h3 class="mb-3">Change Admin Password</h3>
            <?php if (!empty($pwMsg)) echo $pwMsg; ?>
            <form method="POST" class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <div class="col-12">
                <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
              </div>
            </form>
          </div>
        </div>
      <?php elseif ($section === 'students'): ?>
        <!-- Students Tab -->
        <div class="container mb-5">
          <div class="admin-card p-4">
            <h3 class="mb-3">Student Management</h3>
            <p class="text-muted">View, search, deactivate, or delete student accounts.</p>
            <?php if (!empty($studentMsg)) echo $studentMsg; ?>
            <form method="GET" class="row g-2 mb-3">
              <div class="col-md-6 col-lg-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name, student no, or email" value="<?php echo htmlspecialchars($search ?? ''); ?>">
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
              </div>
            </form>
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr><th>Student No</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th style="width:180px;">Actions</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($students as $stu): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($stu['StudentNo']); ?></td>
                      <td><?php echo htmlspecialchars($stu['StudentName']); ?></td>
                      <td><?php echo htmlspecialchars($stu['Email']); ?></td>
                      <td><?php echo htmlspecialchars($stu['PhoneNo']); ?></td>
                      <td>
                        <?php echo (isset($stu['Active']) && $stu['Active'] == 0) ? '<span class="badge bg-danger">Inactive</span>' : '<span class="badge bg-success">Active</span>'; ?>
                      </td>
                      <td>
                        <?php if (isset($stu['Active']) && $stu['Active'] == 0): ?>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="student_no" value="<?php echo $stu['StudentNo']; ?>">
                            <button type="submit" name="reactivate_student" class="btn btn-success btn-sm">Reactivate</button>
                          </form>
                        <?php else: ?>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="student_no" value="<?php echo $stu['StudentNo']; ?>">
                            <button type="submit" name="deactivate_student" class="btn btn-warning btn-sm">Deactivate</button>
                          </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this student?');">
                          <input type="hidden" name="student_no" value="<?php echo $stu['StudentNo']; ?>">
                          <button type="submit" name="delete_student" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No students found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php elseif ($section === 'analytics'): ?>
        <!-- Admin Analytics Section -->
        <section class="page-section admin-analytics">
          <div class="grid-row" style="gap:18px;display:flex;flex-wrap:wrap;">
            <!-- KPI Cards -->
            <div class="card card--kpi" style="flex:1;min-width:220px;max-width:300px;">
              <div class="card-body">
                <h4 class="kpi-title">Total Users</h4>
                <div id="kpiUsers" class="kpi-value">—</div>
              </div>
            </div>

            <div class="card card--kpi" style="flex:1;min-width:220px;max-width:300px;">
              <div class="card-body">
                <h4 class="kpi-title">Total Reports</h4>
                <div id="kpiReports" class="kpi-value">—</div>
              </div>
            </div>

            <div class="card card--kpi" style="flex:1;min-width:220px;max-width:300px;">
              <div class="card-body">
                <h4 class="kpi-title">Active Today</h4>
                <div id="kpiActive" class="kpi-value">—</div>
              </div>
            </div>
          </div>

          <!-- Charts area (responsive) -->
          <div style="margin-top:20px; display:flex; gap:18px; flex-wrap:wrap;">
            <div class="card" style="flex:2; min-width:320px;">
              <div class="card-header"><h3>Usage Trend</h3></div>
              <div class="card-body">
                <canvas id="chartUsage" height="140"></canvas>
              </div>
            </div>

            <div class="card" style="flex:1; min-width:260px;">
              <div class="card-header"><h3>Top Actions</h3></div>
              <div class="card-body">
                <ul id="topActionsList" style="margin:0;padding-left:18px;"> <!-- items injected by JS -->
                </ul>
              </div>
            </div>
          </div>
        </section>

        <!-- minimal chart styles -->
        <style>
        .admin-analytics .kpi-title{ font-size:13px; color:var(--muted,#666); }
        .kpi-value{ font-size:24px; font-weight:700; margin-top:6px; }
        .card{ border-radius:10px; padding:0; box-shadow:0 1px 10px rgba(0,0,0,0.04); overflow:hidden; }
        .card-body{ padding:16px; }
        .card-header{ padding:12px 16px; border-bottom:1px solid rgba(0,0,0,0.06); }
        @media (max-width:800px){ .admin-analytics .kpi-value{ font-size:20px; } }
        </style>

        <!-- Admin analytics frontend logic -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
        <script>
        (async function(){
          // replace with your endpoint
          const analyticsEndpoint = 'php/admin_analytics_data.php';

          function el(id){ return document.getElementById(id); }

          async function loadAnalytics(){
            try{
              const resp = await fetch(analyticsEndpoint);
              if(!resp.ok) throw new Error('Network response not OK');
              const data = await resp.json();

              // KPIs
              el('kpiUsers').textContent = data.total_users ?? '0';
              el('kpiReports').textContent = data.total_reports ?? '0';
              el('kpiActive').textContent = data.active_today ?? '0';

              // Top actions list
              const topList = el('topActionsList');
              topList.innerHTML = '';
              (data.top_actions || []).forEach(item => {
                const li = document.createElement('li');
                li.textContent = `${item.label} — ${item.count}`;
                topList.appendChild(li);
              });

              // Usage trend chart (using Chart.js if available)
              const ctx = document.getElementById('chartUsage');
              if(window.Chart && data.usage && ctx){
                new Chart(ctx, {
                  type: 'line',
                  data: {
                    labels: data.usage.labels,
                    datasets: [{ label: 'Visits', data: data.usage.values, fill: true }]
                  },
                  options: { responsive: true, maintainAspectRatio:false }
                });
              } else {
                // fallback placeholder
                if(ctx){
                  ctx.getContext('2d').clearRect(0,0,ctx.width,ctx.height);
                  ctx.getContext('2d').font = '14px sans-serif';
                  ctx.getContext('2d').fillText('Chart.js not loaded or no usage data', 10, 20);
                }
              }

            }catch(err){
              console.error('Failed to load analytics', err);
            }
          }

          // initial load
          loadAnalytics();

          // optional: refresh every 60s
          setInterval(loadAnalytics, 60000);
        })();
        </script>
      <?php elseif ($section === 'export'): ?>
        <!-- Data Export Tab -->
        <div class="container mb-5">
          <div class="admin-card p-4">
            <h3 class="mb-3">Data Export</h3>
            <p class="text-muted">Export lost/found reports, user lists, or logs to CSV for reporting.</p>
            <form method="POST" class="row g-3">
              <div class="col-md-3">
                <button type="submit" name="export_type" value="lost" class="btn btn-primary w-100">Export Lost Reports (CSV)</button>
              </div>
              <div class="col-md-3">
                <button type="submit" name="export_type" value="found" class="btn btn-success w-100">Export Found Reports (CSV)</button>
              </div>
              <div class="col-md-3">
                <button type="submit" name="export_type" value="students" class="btn btn-warning w-100">Export Students (CSV)</button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/notifications.js"></script>
<script src="../assets/forms.js"></script>
</body>
</html> 