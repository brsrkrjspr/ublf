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

// Get notification count
$unreadCount = 0;
if ($student && $conn) {
    try {
        require_once __DIR__ . '/../classes/Notification.php';
        $notification = new Notification($conn);
        $unreadCount = $notification->getUnreadCount($student['StudentNo']);
    } catch (Exception $e) {
        $unreadCount = 0;
    }
}
?>
<link href="css.php?file=sidebar.css" rel="stylesheet">
<div class="sidebar-wrapper">
  <!-- Sidebar Overlay for Mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  
  <!-- Sidebar Toggle Button (Mobile) -->
  <button class="sidebar-toggle" id="sidebarToggle" type="button">
    <i class="bi bi-list"></i>
  </button>
  
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <img src="../assets/ub_logo.png" alt="University of Batangas Logo">
      <span class="brand-text">Lost & Found</span>
    </div>
    
    <nav class="sidebar-nav">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link <?php echo nav_active('dashboard.php'); ?>" href="dashboard.php">
            <i class="bi bi-house-door"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo nav_active('all_lost.php'); ?>" href="all_lost.php">
            <i class="bi bi-search"></i>
            <span>All Lost Items</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo nav_active('found_items.php'); ?>" href="found_items.php">
            <i class="bi bi-box-seam"></i>
            <span>Found Items</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo nav_active('my_reports.php'); ?>" href="my_reports.php">
            <i class="bi bi-clipboard-data"></i>
            <span>My Reports</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo nav_active('notifications.php'); ?>" href="notifications.php">
            <i class="bi bi-bell"></i>
            <span>Notifications</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo nav_active('contact_admin.php'); ?>" href="contact_admin.php">
            <i class="bi bi-envelope"></i>
            <span>Contact Admin</span>
          </a>
        </li>
      </ul>
    </nav>
    
    <?php if ($student): ?>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <?php if (!empty($student['ProfilePhoto']) && isset($student['PhotoConfirmed']) && $student['PhotoConfirmed'] == 1): ?>
            <img src="../<?php echo htmlspecialchars($student['ProfilePhoto']); ?>" alt="Profile Photo">
          <?php else: ?>
            <div class="user-icon">
              <i class="bi bi-person-circle"></i>
            </div>
          <?php endif; ?>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name d-flex align-items-center justify-content-between">
              <span><?php echo htmlspecialchars($student['StudentName']); ?></span>
              <div class="dropdown">
                <?php if ($unreadCount > 0): ?>
                  <button class="notification-bell-link position-relative btn p-0 border-0" type="button" id="sidebarNotificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="View Notifications">
                    <i class="bi bi-bell-fill" style="color: #FFD700; font-size: 1.1rem;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 0.2rem 0.4rem;">
                      <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                    </span>
                  </button>
                <?php else: ?>
                  <button class="notification-bell-link btn p-0 border-0" type="button" id="sidebarNotificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="View Notifications">
                    <i class="bi bi-bell" style="color: rgba(255, 255, 255, 0.7); font-size: 1.1rem;"></i>
                  </button>
                <?php endif; ?>
                <ul class="dropdown-menu dropdown-menu-end sidebar-notification-dropdown" aria-labelledby="sidebarNotificationDropdown" style="min-width: 380px; max-width: 450px; max-height: 500px; overflow-y: auto;">
                  <li><h6 class="dropdown-header">Notifications</h6></li>
                  <?php
                  $notifications = [];
                  try {
                      if ($conn) {
                          require_once __DIR__ . '/../classes/Notification.php';
                          $notification = new Notification($conn);
                          $notifications = $notification->getUnread($student['StudentNo'], 5);
                      }
                  } catch (Exception $e) {
                      $notifications = [];
                  }
                  if (count($notifications) > 0):
                    foreach ($notifications as $notif):
                  ?>
                    <li>
                      <a class="dropdown-item notification-item" href="#" data-notification-id="<?php echo $notif['NotificationID']; ?>" style="white-space: normal; word-wrap: break-word;">
                        <div class="d-flex align-items-start">
                          <i class="<?php echo Notification::getIcon($notif['Type']); ?> me-2 mt-1 flex-shrink-0" style="font-size: 1.1rem;"></i>
                          <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-bold small mb-1" style="word-wrap: break-word;"><?php echo htmlspecialchars($notif['Title']); ?></div>
                            <div class="small text-muted mb-1" style="word-wrap: break-word; white-space: normal; line-height: 1.4;"><?php echo htmlspecialchars($notif['Message']); ?></div>
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
            </div>
          </div>
        </div>
        <div class="sidebar-user-actions">
          <a href="profile.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-person"></i> Profile
          </a>
          <form method="POST" action="logout.php" style="display: inline; flex: 1;">
            <button type="submit" class="btn btn-outline-light btn-sm w-100">
              <i class="bi bi-box-arrow-right"></i> Logout
            </button>
          </form>
        </div>
        <div class="mt-2">
          <button class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-between" id="darkModeToggle" type="button">
            <span>
              <i class="bi bi-moon me-2"></i>
              <span id="darkModeText">Dark Mode</span>
            </span>
            <span class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="darkModeSwitch">
            </span>
          </button>
        </div>
      </div>
    <?php endif; ?>
  </aside>
  
  <!-- Main Content Area -->
  <main class="main-content">
<script>
// Sidebar toggle for mobile
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      sidebarOverlay.classList.toggle('show');
    });
  }
  
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      sidebarOverlay.classList.remove('show');
    });
  }
  
  // Close sidebar when clicking on a nav link (mobile)
  const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
  navLinks.forEach(function(link) {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
      }
    });
  });
  
  // Handle notification clicks in sidebar dropdown
  const sidebarNotificationItems = document.querySelectorAll('.sidebar-notification-dropdown .notification-item');
  sidebarNotificationItems.forEach(function(item) {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      const notificationId = this.getAttribute('data-notification-id');
      if (notificationId) {
        // Mark as read
        fetch('mark_notification_read.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'notification_id=' + encodeURIComponent(notificationId)
        }).then(function(response) {
          return response.json();
        }).then(function(data) {
          if (data.success) {
            // Reload page to update notification count
            window.location.reload();
          }
        }).catch(function(error) {
          console.error('Error marking notification as read:', error);
        });
      }
    });
  });
});

// Dark mode toggle logic
function setDarkMode(enabled) {
  if (enabled) {
    document.body.classList.add('dark-mode');
    localStorage.setItem('ubDarkMode', '1');
    const switchEl = document.getElementById('darkModeSwitch');
    const textEl = document.getElementById('darkModeText');
    if (switchEl) switchEl.checked = true;
    if (textEl) textEl.textContent = 'Light Mode';
  } else {
    document.body.classList.remove('dark-mode');
    localStorage.setItem('ubDarkMode', '0');
    const switchEl = document.getElementById('darkModeSwitch');
    const textEl = document.getElementById('darkModeText');
    if (switchEl) switchEl.checked = false;
    if (textEl) textEl.textContent = 'Dark Mode';
  }
}
document.addEventListener('DOMContentLoaded', function() {
  var darkMode = localStorage.getItem('ubDarkMode') === '1';
  setDarkMode(darkMode);
  const switchEl = document.getElementById('darkModeSwitch');
  const toggleEl = document.getElementById('darkModeToggle');
  if (switchEl) {
    switchEl.addEventListener('change', function(e) {
      setDarkMode(e.target.checked);
    });
  }
  if (toggleEl) {
    toggleEl.addEventListener('click', function(e) {
      if (e.target.tagName !== 'INPUT') {
        var current = document.getElementById('darkModeSwitch').checked;
        setDarkMode(!current);
      }
    });
  }
});
</script> 