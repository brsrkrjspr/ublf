  </main>
</div>
<!-- End of sidebar-wrapper -->

<!-- Notification Modal -->
<?php 
if (isset($student) && $student): 
  // Get notifications for modal
  $modalNotifications = [];
  $modalUnreadCount = 0;
  try {
      if (isset($conn) && $conn) {
          require_once __DIR__ . '/../classes/Notification.php';
          $notification = new Notification($conn);
          $modalNotifications = $notification->getUnread($student['StudentNo'], 10);
          $modalUnreadCount = $notification->getUnreadCount($student['StudentNo']);
      }
  } catch (Exception $e) {
      $modalNotifications = [];
      $modalUnreadCount = 0;
  }
?>
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #800000 0%, #a83232 100%); color: #FFD700;">
        <h5 class="modal-title fw-bold" id="notificationModalLabel">
          <i class="bi bi-bell me-2"></i>Notifications
          <?php if ($modalUnreadCount > 0): ?>
            <span class="badge bg-danger ms-2"><?php echo $modalUnreadCount; ?></span>
          <?php endif; ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
        <?php if (count($modalNotifications) > 0): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($modalNotifications as $notif): ?>
              <a href="#" class="list-group-item list-group-item-action notification-item p-3" data-notification-id="<?php echo $notif['NotificationID']; ?>" style="border-left: 4px solid #800000; margin-bottom: 0.5rem; border-radius: 0.5rem; transition: background-color 0.2s;">
                <div class="d-flex align-items-start">
                  <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #800000 0%, #a83232 100%); color: #FFD700;">
                      <i class="<?php echo Notification::getIcon($notif['Type']); ?>" style="font-size: 1.2rem;"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1" style="min-width: 0;">
                    <h6 class="mb-1 fw-bold" style="word-wrap: break-word; color: #495057;"><?php echo htmlspecialchars($notif['Title']); ?></h6>
                    <p class="mb-1 text-muted" style="word-wrap: break-word; white-space: normal; line-height: 1.5;"><?php echo htmlspecialchars($notif['Message']); ?></p>
                    <small class="text-muted">
                      <i class="bi bi-clock me-1"></i><?php echo Notification::formatTime($notif['CreatedAt']); ?>
                    </small>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-bell-slash" style="font-size: 3rem; color: #6c757d; opacity: 0.5;"></i>
            <p class="text-muted mt-3 mb-0">No new notifications</p>
            <p class="text-muted small">You're all caught up!</p>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <a href="notifications.php" class="btn btn-outline-primary">
          <i class="bi bi-list-ul me-1"></i>View All Notifications
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<style>
#notificationModal .notification-item:hover {
  background-color: rgba(128, 0, 0, 0.05) !important;
  cursor: pointer;
}
</style>
<?php endif; ?>

