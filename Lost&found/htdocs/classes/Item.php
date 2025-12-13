<?php
require_once __DIR__ . '/../includes/Database.php';

class Item {
    private $conn;
    private $table = '`item`';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection unavailable');
        }
    }

    public function create($adminID, $itemName, $itemClass, $description, $dateFound, $locationFound, $photoURL = null) {
        // #region agent log
        $logPath = __DIR__ . '/../../.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_item_create_entry','timestamp'=>time()*1000,'location'=>'Item.php:16','message'=>'Item::create called','data'=>['adminID'=>$adminID,'itemName'=>$itemName,'itemClass'=>$itemClass,'StatusConfirmed'=>0],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
        // #endregion
        
        // Get or create ItemClassID
        $itemClassID = $this->getOrCreateItemClass($itemClass);
        
        // #region agent log
        $logPath = __DIR__ . '/../../.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_item_classid','timestamp'=>time()*1000,'location'=>'Item.php:20','message'=>'ItemClassID resolved','data'=>['itemClassID'=>$itemClassID],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B'])."\n", FILE_APPEND);
        // #endregion
        
        $query = "INSERT INTO {$this->table} (AdminID, ItemName, ItemClassID, Description, DateFound, LocationFound, PhotoURL, StatusID, StatusConfirmed) 
                  VALUES (:adminID, :itemName, :itemClassID, :description, :dateFound, :locationFound, :photoURL, 1, 0)";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            'adminID' => $adminID,
            'itemName' => $itemName,
            'itemClassID' => $itemClassID,
            'description' => $description,
            'dateFound' => $dateFound,
            'locationFound' => $locationFound,
            'photoURL' => $photoURL
        ]);

        $itemID = $result ? $this->conn->lastInsertId() : null;
        
        // #region agent log
        $logPath = __DIR__ . '/../../.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        if ($result) {
            $verifyStmt = $this->conn->prepare("SELECT ItemID, StatusConfirmed, ItemClassID, AdminID FROM {$this->table} WHERE ItemID = :id");
            $verifyStmt->execute(['id' => $itemID]);
            $inserted = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_item_inserted','timestamp'=>time()*1000,'location'=>'Item.php:35','message'=>'Item inserted to database','data'=>['itemID'=>$itemID,'insertedStatusConfirmed'=>$inserted['StatusConfirmed']??null,'insertedItemClassID'=>$inserted['ItemClassID']??null,'insertedAdminID'=>$inserted['AdminID']??null],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
        } else {
            @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_item_failed','timestamp'=>time()*1000,'location'=>'Item.php:35','message'=>'Item insert failed','data'=>[],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
        }
        // #endregion

        return $result ? 
            ['success' => true, 'message' => 'Found item report submitted successfully. It will be visible to others after admin approval.', 'id' => $itemID] : 
            ['success' => false, 'message' => 'Failed to report found item.'];
    }

    private function getOrCreateItemClass($className) {
        // Check if class exists
        $stmt = $this->conn->prepare('SELECT ItemClassID FROM `itemclass` WHERE ClassName = :className LIMIT 1');
        $stmt->execute(['className' => $className]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return $row['ItemClassID'];
        } else {
            // Create new class
            $stmt = $this->conn->prepare('INSERT INTO `itemclass` (ClassName) VALUES (:className)');
            $stmt->execute(['className' => $className]);
            return $this->conn->lastInsertId();
        }
    }

    public function getAllApproved($limit = null, $offset = 0) {
        $query = "SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.StatusConfirmed,
                         ic.ClassName
                  FROM {$this->table} i
                  JOIN `itemclass` ic ON i.ItemClassID = ic.ItemClassID
                  WHERE i.StatusConfirmed = 1
                  ORDER BY i.ItemID DESC";
        
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

    public function getById($itemID) {
        $query = "SELECT i.*, ic.ClassName
                  FROM {$this->table} i
                  JOIN `itemclass` ic ON i.ItemClassID = ic.ItemClassID
                  WHERE i.ItemID = :itemID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['itemID' => $itemID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approve($itemID, $adminID) {
        $query = "UPDATE {$this->table} SET StatusConfirmed = 1 WHERE ItemID = :itemID";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['itemID' => $itemID]);
    }

    public function reject($itemID, $adminID) {
        $query = "UPDATE {$this->table} SET StatusConfirmed = 2 WHERE ItemID = :itemID";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['itemID' => $itemID]);
    }

    public function delete($itemID, $adminID) {
        // Only allow deletion if admin owns the item
        $query = "DELETE FROM {$this->table} WHERE ItemID = :itemID AND AdminID = :adminID";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            'itemID' => $itemID,
            'adminID' => $adminID
        ]);
    }

    public function search($searchTerm, $itemClass = null) {
        try {
            $query = "SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.StatusConfirmed,
                             ic.ClassName
                      FROM {$this->table} i
                      JOIN `itemclass` ic ON i.ItemClassID = ic.ItemClassID
                      WHERE i.StatusConfirmed = 1 AND 
                            (i.ItemName LIKE :searchTerm OR i.Description LIKE :searchTerm OR i.LocationFound LIKE :searchTerm)";
            
            $params = ['searchTerm' => "%{$searchTerm}%"];
            
            if ($itemClass) {
                $query .= " AND ic.ClassName = :itemClass";
                $params['itemClass'] = $itemClass;
            }
            
            $query .= " ORDER BY i.ItemID DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Item::search() PDO Error: " . $e->getMessage());
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }

    public function getItemClasses() {
        if (!$this->conn) {
            // Return default item classes
            return ['Electronics', 'Books', 'Clothing', 'Bags', 'ID Cards', 'Keys', 'Others'];
        }
        // Get ALL classes from itemclass table, not just ones with approved items
        $query = "SELECT DISTINCT ic.ClassName 
                  FROM `itemclass` ic 
                  ORDER BY ic.ClassName";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If no classes in database, return defaults
        if (empty($classes)) {
            return ['Electronics', 'Books', 'Clothing', 'Bags', 'ID Cards', 'Keys', 'Others'];
        }
        
        return $classes;
    }

    public function findMatches($lostItemName, $lostItemClass = null) {
        $query = "SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL,
                         ic.ClassName
                  FROM {$this->table} i
                  JOIN `itemclass` ic ON i.ItemClassID = ic.ItemClassID
                  WHERE i.StatusConfirmed = 1 AND 
                        (i.ItemName LIKE :searchTerm OR i.Description LIKE :searchTerm)";
        
        $params = ['searchTerm' => "%{$lostItemName}%"];
        
        if ($lostItemClass) {
            $query .= " AND ic.ClassName = :itemClass";
            $params['itemClass'] = $lostItemClass;
        }
        
        $query .= " ORDER BY i.ItemID DESC LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}