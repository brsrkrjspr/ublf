<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Initialize variables
$student = null;
$conn = null;

// Fetch fresh student data if logged in to get latest PhotoConfirmed status
if (isset($_SESSION['student'])) {
    $student = $_SESSION['student'];
    // Try to fetch fresh data if database is available
    try {
        require_once __DIR__ . '/../includes/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        if ($conn) {
            $stmt = $conn->prepare('SELECT * FROM student WHERE StudentNo = :studentNo LIMIT 1');
            $stmt->execute(['studentNo' => $_SESSION['student']['StudentNo']]);
            $freshStudent = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($freshStudent) {
                $student = $freshStudent;
                $_SESSION['student'] = $student;
            }
        }
    } catch (Exception $e) {
        // Use session data if database unavailable
        $student = $_SESSION['student'];
        $conn = null;
    }
}
function nav_active($page) {
  $current = basename($_SERVER['PHP_SELF']);
  return $current === $page ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background: var(--ub-maroon);">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
      <img src="../assets/ub_logo.png" alt="University of Batangas Logo" style="height: 40px; width: 40px; object-fit: contain; background: #fff; border-radius: 50%; border: 2px solid #800000; margin-right: 0.7rem;">
      <span>Lost & Found</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link <?php echo nav_active('dashboard.php'); ?>" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?php echo nav_active('all_lost.php'); ?>" href="all_lost.php"><i class="bi bi-search"></i> All Lost Items</a></li>
        <li class="nav-item"><a class="nav-link <?php echo nav_active('found_items.php'); ?>" href="found_items.php"><i class="bi bi-box-seam"></i> Found Items</a></li>
        <li class="nav-item"><a class="nav-link <?php echo nav_active('my_reports.php'); ?>" href="my_reports.php"><i class="bi bi-clipboard-data"></i> My Reports</a></li>
        <li class="nav-item"><a class="nav-link <?php echo nav_active('notifications.php'); ?>" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
        <li class="nav-item"><a class="nav-link <?php echo nav_active('contact_admin.php'); ?>" href="contact_admin.php"><i class="bi bi-envelope"></i> Contact Admin</a></li>
      </ul>
      <?php if ($student): ?>
        <div class="d-flex align-items-center">
          <!-- Notification Bell -->
          <?php
          $unreadCount = 0;
          try {
              if ($conn) {
                  require_once __DIR__ . '/../classes/Notification.php';
                  $notification = new Notification($conn);
                  $unreadCount = $notification->getUnreadCount($student['StudentNo']);
              }
          } catch (Exception $e) {
              // No notifications if database unavailable
              $unreadCount = 0;
          }
          ?>
          <div class="dropdown me-3">
            <button class="btn btn-outline-light position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-bell"></i>
              <?php if ($unreadCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                  <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                </span>
              <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
              <li><h6 class="dropdown-header">Notifications</h6></li>
              <?php
              $notifications = [];
              try {
                  if ($conn && isset($notification)) {
                      $notifications = $notification->getUnread($student['StudentNo'], 5);
                  }
              } catch (Exception $e) {
                  $notifications = [];
              }
              if (count($notifications) > 0):
                foreach ($notifications as $notif):
              ?>
                <li>
                  <a class="dropdown-item notification-item" href="#" data-notification-id="<?php echo $notif['NotificationID']; ?>">
                    <div class="d-flex align-items-start">
                      <i class="<?php echo Notification::getIcon($notif['Type']); ?> me-2 mt-1"></i>
                      <div class="flex-grow-1">
                        <div class="fw-bold small"><?php echo htmlspecialchars($notif['Title']); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($notif['Message']); ?></div>
                        <div class="small text-muted"><?php echo Notification::formatTime($notif['CreatedAt']); ?></div>
                      </div>
                    </div>
                  </a>
                </li>
              <?php 
                endforeach;
              else:
              ?>
                <li><span class="dropdown-item-text text-muted">No new notifications</span></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
            </ul>
          </div>
          
          <?php if (!empty($student['ProfilePhoto']) && isset($student['PhotoConfirmed']) && $student['PhotoConfirmed'] == 1): ?>
            <img src="../<?php echo htmlspecialchars($student['ProfilePhoto']); ?>" alt="Profile Photo" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;">
          <?php else: ?>
            <span class="me-2"><i class="bi bi-person-circle" style="font-size:2rem;color:var(--ub-gold);"></i></span>
          <?php endif; ?>
          <div class="dropdown">
            <button class="btn btn-outline-light dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo htmlspecialchars($student['StudentName']); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
              <li>
                <a class="dropdown-item d-flex align-items-center" href="profile.php">
                  <i class="bi bi-person me-2"></i> My Profile
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <button class="dropdown-item d-flex align-items-center" id="darkModeToggle" type="button">
                  <i class="bi bi-moon me-2"></i> <span id="darkModeText">Dark Mode</span>
                  <span class="ms-auto form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="darkModeSwitch">
                  </span>
                </button>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="POST" action="logout.php">
                  <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Logout</button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>
<script>
// Dark mode toggle logic
function setDarkMode(enabled) {
  if (enabled) {
    document.body.classList.add('dark-mode');
    localStorage.setItem('ubDarkMode', '1');
    document.getElementById('darkModeSwitch').checked = true;
    document.getElementById('darkModeText').textContent = 'Light Mode';
  } else {
    document.body.classList.remove('dark-mode');
    localStorage.setItem('ubDarkMode', '0');
    document.getElementById('darkModeSwitch').checked = false;
    document.getElementById('darkModeText').textContent = 'Dark Mode';
  }
}
document.addEventListener('DOMContentLoaded', function() {
  var darkMode = localStorage.getItem('ubDarkMode') === '1';
  setDarkMode(darkMode);
  document.getElementById('darkModeSwitch').addEventListener('change', function(e) {
    setDarkMode(e.target.checked);
  });
  document.getElementById('darkModeToggle').addEventListener('click', function(e) {
    if (e.target.tagName !== 'INPUT') {
      var current = document.getElementById('darkModeSwitch').checked;
      setDarkMode(!current);
    }
  });
});
</script> 