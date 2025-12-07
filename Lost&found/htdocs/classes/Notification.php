<?php
class Notification {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Create a new notification
     */
    public function create($studentNo, $type, $title, $message, $relatedID = null) {
        try {
            require_once __DIR__ . '/../includes/Logger.php';
            Logger::log("Notification::create called with:");
            Logger::log("  - studentNo: $studentNo");
            Logger::log("  - type: $type");
            Logger::log("  - title: $title");
            Logger::log("  - message: " . substr($message, 0, 50) . '...');
            Logger::log("  - relatedID: " . ($relatedID ?? 'NULL'));
            
            if (!$this->conn) {
                Logger::log("ERROR: Database connection is null");
                return false;
            }
            
            $stmt = $this->conn->prepare('INSERT INTO notifications (StudentNo, Type, Title, Message, RelatedID) VALUES (:studentNo, :type, :title, :message, :relatedID)');
            $params = [
                'studentNo' => $studentNo,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'relatedID' => $relatedID
            ];
            
            Logger::log("Executing INSERT with params: " . json_encode($params));
            $result = $stmt->execute($params);
            
            if ($result) {
                Logger::log("SUCCESS: Notification created with ID: " . $this->conn->lastInsertId());
            } else {
                $errorInfo = $stmt->errorInfo();
                Logger::log("ERROR: Notification creation failed. PDO Error: " . json_encode($errorInfo));
            }
            
            return $result;
        } catch (PDOException $e) {
            require_once __DIR__ . '/../includes/Logger.php';
            Logger::log("EXCEPTION in Notification::create: " . $e->getMessage());
            Logger::log("SQL State: " . $e->getCode());
            Logger::log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            require_once __DIR__ . '/../includes/Logger.php';
            Logger::log("EXCEPTION in Notification::create: " . $e->getMessage());
            Logger::log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Get unread notifications for a student
     */
    public function getUnread($studentNo, $limit = 10) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM notifications WHERE StudentNo = :studentNo AND IsRead = 0 ORDER BY CreatedAt DESC LIMIT :limit');
            $stmt->bindValue(':studentNo', $studentNo);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If table doesn't exist, return empty array
            return [];
        }
    }
    
    /**
     * Get all notifications for a student
     */
    public function getAll($studentNo, $limit = 20) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM notifications WHERE StudentNo = :studentNo ORDER BY CreatedAt DESC LIMIT :limit');
            $stmt->bindValue(':studentNo', $studentNo);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If table doesn't exist, return empty array
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationID, $studentNo) {
        try {
            $stmt = $this->conn->prepare('UPDATE notifications SET IsRead = 1 WHERE NotificationID = :notificationID AND StudentNo = :studentNo');
            return $stmt->execute([
                'notificationID' => $notificationID,
                'studentNo' => $studentNo
            ]);
        } catch (PDOException $e) {
            // If table doesn't exist, return false
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a student
     */
    public function markAllAsRead($studentNo) {
        try {
            $stmt = $this->conn->prepare('UPDATE notifications SET IsRead = 1 WHERE StudentNo = :studentNo AND IsRead = 0');
            return $stmt->execute(['studentNo' => $studentNo]);
        } catch (PDOException $e) {
            // If table doesn't exist, return false
            return false;
        }
    }
    
    /**
     * Get unread count for a student
     */
    public function getUnreadCount($studentNo) {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM notifications WHERE StudentNo = :studentNo AND IsRead = 0');
            $stmt->execute(['studentNo' => $studentNo]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            // If table doesn't exist, return 0
            return 0;
        }
    }
    
    /**
     * Delete old notifications (older than 30 days)
     */
    public function cleanupOldNotifications() {
        try {
            $stmt = $this->conn->prepare('DELETE FROM notifications WHERE CreatedAt < DATE_SUB(NOW(), INTERVAL 30 DAY)');
            return $stmt->execute();
        } catch (PDOException $e) {
            // If table doesn't exist, return false
            return false;
        }
    }
    
    /**
     * Get notification icon based on type
     */
    public static function getIcon($type) {
        $icons = [
            'photo_approved' => 'bi-check-circle-fill text-success',
            'photo_rejected' => 'bi-x-circle-fill text-danger',
            'report_approved' => 'bi-check-circle-fill text-success',
            'report_rejected' => 'bi-x-circle-fill text-danger',
            'item_matched' => 'bi-link-45deg text-primary',
            'admin_message' => 'bi-envelope-fill text-info',
            'system_alert' => 'bi-exclamation-triangle-fill text-warning'
        ];
        return $icons[$type] ?? 'bi-bell-fill text-secondary';
    }
    
    /**
     * Get notification color based on type
     */
    public static function getColor($type) {
        $colors = [
            'photo_approved' => 'success',
            'photo_rejected' => 'danger',
            'report_approved' => 'success',
            'report_rejected' => 'danger',
            'item_matched' => 'primary',
            'admin_message' => 'info',
            'system_alert' => 'warning'
        ];
        return $colors[$type] ?? 'secondary';
    }
    
    /**
     * Format notification time
     */
    public static function formatTime($timestamp) {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}
?> 