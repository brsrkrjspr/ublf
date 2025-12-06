<?php
require_once __DIR__ . '/../includes/Database.php';

class ReportItem {
    private $conn;
    private $table = 'reportitem';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        // If no database connection, $this->conn will be null - methods will handle this
    }

    public function create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL = null) {
        // Get or create ItemClassID
        $itemClassID = $this->getOrCreateItemClass($itemClass);
        
        $query = "INSERT INTO {$this->table} (StudentNo, ItemName, ItemClassID, Description, DateOfLoss, LostLocation, PhotoURL, ReportStatusID, StatusConfirmed) 
                  VALUES (:studentNo, :itemName, :itemClassID, :description, :dateOfLoss, :lostLocation, :photoURL, 1, 0)";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            'studentNo' => $studentNo,
            'itemName' => $itemName,
            'itemClassID' => $itemClassID,
            'description' => $description,
            'dateOfLoss' => $dateOfLoss,
            'lostLocation' => $lostLocation,
            'photoURL' => $photoURL
        ]);

        return $result ? 
            ['success' => true, 'message' => 'Lost item report submitted successfully. It will be visible to others after admin approval.', 'id' => $this->conn->lastInsertId()] : 
            ['success' => false, 'message' => 'Failed to submit lost item report.'];
    }

    private function getOrCreateItemClass($className) {
        // Check if class exists
        $stmt = $this->conn->prepare('SELECT ItemClassID FROM itemclass WHERE ClassName = :className LIMIT 1');
        $stmt->execute(['className' => $className]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return $row['ItemClassID'];
        } else {
            // Create new class
            $stmt = $this->conn->prepare('INSERT INTO itemclass (ClassName) VALUES (:className)');
            $stmt->execute(['className' => $className]);
            return $this->conn->lastInsertId();
        }
    }

    public function getAllApproved($limit = null, $offset = 0) {
        $query = "SELECT ri.ReportID, ri.ItemName, ri.Description, ri.DateOfLoss, ri.LostLocation, ri.PhotoURL, ri.StatusConfirmed,
                         s.StudentName, ic.ClassName
                  FROM {$this->table} ri
                  JOIN student s ON ri.StudentNo = s.StudentNo
                  JOIN itemclass ic ON ri.ItemClassID = ic.ItemClassID
                  WHERE ri.StatusConfirmed = 1
                  ORDER BY ri.ReportID DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($query);
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByStudent($studentNo) {
        $query = "SELECT ri.ReportID, ri.StudentNo, ri.ItemName, ri.Description, ri.DateOfLoss, ri.LostLocation, ri.PhotoURL, ri.StatusConfirmed,
                         ic.ClassName
                  FROM {$this->table} ri
                  JOIN itemclass ic ON ri.ItemClassID = ic.ItemClassID
                  WHERE ri.StudentNo = :studentNo
                  ORDER BY ri.ReportID DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['studentNo' => $studentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($reportItemID) {
        $query = "SELECT ri.*, s.StudentName, ic.ClassName
                  FROM {$this->table} ri
                  JOIN student s ON ri.StudentNo = s.StudentNo
                  JOIN itemclass ic ON ri.ItemClassID = ic.ItemClassID
                  WHERE ri.ReportID = :reportItemID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['reportItemID' => $reportItemID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approve($reportItemID, $adminID) {
        $query = "UPDATE {$this->table} SET StatusConfirmed = 1 WHERE ReportID = :reportItemID";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute(['reportItemID' => $reportItemID]);
        
        if ($result) {
            // Get student info for notification
            $item = $this->getById($reportItemID);
            if ($item) {
                $this->createNotification($item['StudentNo'], 'report_approved', 'Lost Item Approved', 
                    "Your lost item report '{$item['ItemName']}' has been approved and is now visible to others.");
            }
        }
        
        return $result;
    }

    public function reject($reportItemID, $adminID) {
        $query = "UPDATE {$this->table} SET StatusConfirmed = 2 WHERE ReportID = :reportItemID";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute(['reportItemID' => $reportItemID]);
        
        if ($result) {
            // Get student info for notification
            $item = $this->getById($reportItemID);
            if ($item) {
                $this->createNotification($item['StudentNo'], 'report_rejected', 'Lost Item Rejected', 
                    "Your lost item report '{$item['ItemName']}' has been rejected. Please contact admin for details.");
            }
        }
        
        return $result;
    }

    public function delete($reportItemID, $studentNo) {
        // Only allow deletion if student owns the report
        $query = "DELETE FROM {$this->table} WHERE ReportID = :reportItemID AND StudentNo = :studentNo";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            'reportItemID' => $reportItemID,
            'studentNo' => $studentNo
        ]);
    }

    public function search($searchTerm, $itemClass = null) {
        $query = "SELECT ri.ReportID, ri.ItemName, ri.Description, ri.DateOfLoss, ri.LostLocation, ri.PhotoURL, ri.StatusConfirmed,
                         s.StudentName, ic.ClassName
                  FROM {$this->table} ri
                  JOIN student s ON ri.StudentNo = s.StudentNo
                  JOIN itemclass ic ON ri.ItemClassID = ic.ItemClassID
                  WHERE ri.StatusConfirmed = 1 AND 
                        (ri.ItemName LIKE :searchTerm OR ri.Description LIKE :searchTerm OR ri.LostLocation LIKE :searchTerm)";
        
        $params = ['searchTerm' => "%{$searchTerm}%"];
        
        if ($itemClass) {
            $query .= " AND ic.ClassName = :itemClass";
            $params['itemClass'] = $itemClass;
        }
        
        $query .= " ORDER BY ri.ReportID DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemClasses() {
        if (!$this->conn) {
            // Return default item classes
            return ['Electronics', 'Books', 'Clothing', 'Bags', 'ID Cards', 'Keys', 'Others'];
        }
        $query = "SELECT DISTINCT ic.ClassName 
                  FROM itemclass ic 
                  JOIN {$this->table} ri ON ic.ItemClassID = ri.ItemClassID 
                  WHERE ri.StatusConfirmed = 1 
                  ORDER BY ic.ClassName";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function createNotification($studentNo, $type, $title, $message) {
        try {
            require_once __DIR__ . '/Notification.php';
            $notification = new Notification($this->conn);
            $notification->create($studentNo, $type, $title, $message);
        } catch (Exception $e) {
            // Silently fail if notification system is not available
        }
    }
}
?> 