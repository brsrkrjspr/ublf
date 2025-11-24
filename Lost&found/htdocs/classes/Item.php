<?php
require_once __DIR__ . '/../includes/Database.php';

class Item {
    private $conn;
    private $table = 'Item';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($adminID, $itemName, $itemClass, $description, $dateFound, $locationFound, $photoURL = null) {
        // Get or create ItemClassID
        $itemClassID = $this->getOrCreateItemClass($itemClass);
        
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

        return $result ? 
            ['success' => true, 'message' => 'Found item report submitted successfully. It will be visible to others after admin approval.', 'id' => $this->conn->lastInsertId()] : 
            ['success' => false, 'message' => 'Failed to report found item.'];
    }

    private function getOrCreateItemClass($className) {
        // Check if class exists
        $stmt = $this->conn->prepare('SELECT ItemClassID FROM ItemClass WHERE ClassName = :className LIMIT 1');
        $stmt->execute(['className' => $className]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return $row['ItemClassID'];
        } else {
            // Create new class
            $stmt = $this->conn->prepare('INSERT INTO ItemClass (ClassName) VALUES (:className)');
            $stmt->execute(['className' => $className]);
            return $this->conn->lastInsertId();
        }
    }

    public function getAllApproved($limit = null, $offset = 0) {
        $query = "SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.StatusConfirmed,
                         ic.ClassName
                  FROM {$this->table} i
                  JOIN ItemClass ic ON i.ItemClassID = ic.ItemClassID
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
                  JOIN ItemClass ic ON i.ItemClassID = ic.ItemClassID
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
        $query = "SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.StatusConfirmed,
                         ic.ClassName
                  FROM {$this->table} i
                  JOIN ItemClass ic ON i.ItemClassID = ic.ItemClassID
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
    }

    public function getItemClasses() {
        $query = "SELECT DISTINCT ic.ClassName 
                  FROM ItemClass ic 
                  JOIN {$this->table} i ON ic.ItemClassID = i.ItemClassID 
                  WHERE i.StatusConfirmed = 1 
                  ORDER BY ic.ClassName";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findMatches($lostItemName, $lostItemClass = null) {
        $query = "SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL,
                         ic.ClassName
                  FROM {$this->table} i
                  JOIN ItemClass ic ON i.ItemClassID = ic.ItemClassID
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
?> 