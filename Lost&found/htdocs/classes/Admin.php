<?php
require_once __DIR__ . '/../includes/Database.php';

class Admin {
    private $conn;
    private $table = 'admin';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection unavailable');
        }
    }

    public function login($username, $password) {
        if (!$this->conn) {
            // Mock login for testing - accept admin/admin123
            if ($username === 'admin' && $password === 'admin123') {
                return ['success' => true, 'admin' => [
                    'AdminID' => 1,
                    'Username' => 'admin',
                    'AdminName' => 'Test Admin',
                    'Email' => 'admin@ub.edu.ph'
                ]];
            }
            return ['success' => false, 'message' => 'Database unavailable. Use admin/admin123 for testing.'];
        }
        $query = "SELECT * FROM {$this->table} WHERE Username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['PasswordHash'])) {
            return ['success' => true, 'admin' => $admin];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
    }

    public function changePassword($adminID, $currentPassword, $newPassword) {
        // Verify current password
        $query = "SELECT PasswordHash FROM {$this->table} WHERE AdminID = :adminID LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['adminID' => $adminID]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin || !password_verify($currentPassword, $admin['PasswordHash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $query = "UPDATE {$this->table} SET PasswordHash = :passwordHash WHERE AdminID = :adminID";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            'passwordHash' => $newPasswordHash,
            'adminID' => $adminID
        ]);

        return $result ? 
            ['success' => true, 'message' => 'Password updated successfully.'] : 
            ['success' => false, 'message' => 'Failed to update password.'];
    }

    public function getAllAdmins() {
        if (!$this->conn) {
            return [['AdminID' => 1, 'Username' => 'admin', 'Email' => 'admin@ub.edu.ph', 'AdminName' => 'Test Admin']];
        }
        $query = "SELECT AdminID, Username, Email, CreatedAt FROM {$this->table} ORDER BY CreatedAt DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAdmin($username, $email, $password) {
        // Check if username or email already exists
        $query = "SELECT * FROM {$this->table} WHERE Username = :username OR Email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or Email already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO {$this->table} (Username, Email, PasswordHash) VALUES (:username, :email, :passwordHash)";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            'username' => $username,
            'email' => $email,
            'passwordHash' => $passwordHash
        ]);

        return $result ? 
            ['success' => true, 'message' => 'Admin added successfully.'] : 
            ['success' => false, 'message' => 'Failed to add admin.'];
    }

    public function removeAdmin($adminID, $currentAdminID) {
        if ($adminID == $currentAdminID) {
            return ['success' => false, 'message' => 'You cannot remove yourself.'];
        }

        $query = "DELETE FROM {$this->table} WHERE AdminID = :adminID";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute(['adminID' => $adminID]);

        return $result ? 
            ['success' => true, 'message' => 'Admin removed successfully.'] : 
            ['success' => false, 'message' => 'Failed to remove admin.'];
    }

    public function getDashboardStats() {
        if (!$this->conn) {
            // Return mock stats
            return [
                'totalStudents' => 0,
                'pendingPhotoApprovals' => 0,
                'pendingLostApprovals' => 0,
                'pendingFoundApprovals' => 0,
                'totalLostItems' => 0,
                'totalFoundItems' => 0
            ];
        }
        $stats = [];

        // Total students
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM student");
        $stmt->execute();
        $stats['totalStudents'] = $stmt->fetchColumn();

        // Pending photo approvals (using ProfilePhoto instead of PhotoURL)
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM student WHERE ProfilePhoto IS NOT NULL AND PhotoConfirmed = 0");
        $stmt->execute();
        $stats['pendingPhotoApprovals'] = $stmt->fetchColumn();

        // Pending lost item approvals (using StatusConfirmed instead of ReportStatusID)
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM reportitem WHERE StatusConfirmed = 0");
        $stmt->execute();
        $stats['pendingLostApprovals'] = $stmt->fetchColumn();

        // Pending found item approvals (using StatusConfirmed instead of StatusID)
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM item WHERE StatusConfirmed = 0");
        $stmt->execute();
        $stats['pendingFoundApprovals'] = $stmt->fetchColumn();

        // Total lost items
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM reportitem WHERE StatusConfirmed = 1");
        $stmt->execute();
        $stats['totalLostItems'] = $stmt->fetchColumn();

        // Total found items
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM item WHERE StatusConfirmed = 1");
        $stmt->execute();
        $stats['totalFoundItems'] = $stmt->fetchColumn();

        return $stats;
    }

    public function getPendingApprovals() {
        if (!$this->conn) {
            return ['lostItems' => [], 'foundItems' => [], 'photos' => []];
        }
        $approvals = [];

        // Pending photo approvals
        $stmt = $this->conn->prepare("
            SELECT s.StudentNo, s.StudentName, s.ProfilePhoto, s.Email 
            FROM student s 
            WHERE s.ProfilePhoto IS NOT NULL AND s.PhotoConfirmed = 0
            ORDER BY s.StudentNo
        ");
        $stmt->execute();
        $approvals['photos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pending lost item approvals
        $stmt = $this->conn->prepare("
            SELECT ri.ReportID, ri.ItemName, ri.Description, ri.DateOfLoss, ri.LostLocation, ri.PhotoURL, ri.ReportStatusID,
                   s.StudentName, ic.ClassName
            FROM reportitem ri
            JOIN student s ON ri.StudentNo = s.StudentNo
            JOIN itemclass ic ON ri.ItemClassID = ic.ItemClassID
            WHERE ri.StatusConfirmed = 0
            ORDER BY ri.ReportID DESC
        ");
        $stmt->execute();
        $approvals['lostItems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pending found item approvals
        $stmt = $this->conn->prepare("
            SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.StatusID,
                   ic.ClassName
            FROM item i
            JOIN itemclass ic ON i.ItemClassID = ic.ItemClassID
            WHERE i.StatusConfirmed = 0
            ORDER BY i.ItemID DESC
        ");
        $stmt->execute();
        $approvals['foundItems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $approvals;
    }

    public function getCompletedApprovals() {
        if (!$this->conn) {
            return ['lostItems' => [], 'foundItems' => [], 'photos' => []];
        }
        $completed = [];

        // Completed photo approvals (approved or rejected)
        $stmt = $this->conn->prepare("
            SELECT s.StudentNo, s.StudentName, s.ProfilePhoto, s.Email, s.PhotoConfirmed, s.UpdatedAt
            FROM student s 
            WHERE s.ProfilePhoto IS NOT NULL AND s.PhotoConfirmed IN (1, -1)
            ORDER BY s.StudentNo
        ");
        $stmt->execute();
        $completed['photos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Completed lost item approvals (approved or rejected)
        $stmt = $this->conn->prepare("
            SELECT ri.ReportID, ri.ItemName, ri.Description, ri.DateOfLoss, ri.LostLocation, ri.PhotoURL, ri.ReportStatusID,
                   ri.StatusConfirmed, ri.UpdatedAt, s.StudentName, s.StudentNo, ic.ClassName
            FROM reportitem ri
            JOIN student s ON ri.StudentNo = s.StudentNo
            JOIN itemclass ic ON ri.ItemClassID = ic.ItemClassID
            WHERE ri.StatusConfirmed IN (1, -1)
            ORDER BY ri.ReportID DESC
        ");
        $stmt->execute();
        $completed['lostItems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Completed found item approvals (approved or rejected)
        $stmt = $this->conn->prepare("
            SELECT i.ItemID, i.ItemName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.StatusID,
                   i.StatusConfirmed, i.UpdatedAt, ic.ClassName
            FROM item i
            JOIN itemclass ic ON i.ItemClassID = ic.ItemClassID
            WHERE i.StatusConfirmed IN (1, -1)
            ORDER BY i.ItemID DESC
        ");
        $stmt->execute();
        $completed['foundItems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $completed;
    }

    // Profile Photo History Methods
    public function addProfilePhotoSubmission($studentNo, $photoURL) {
        $stmt = $this->conn->prepare("INSERT INTO profile_photo_history (StudentNo, PhotoURL, Status) VALUES (:studentNo, :photoURL, 0)");
        return $stmt->execute([
            'studentNo' => $studentNo,
            'photoURL' => $photoURL
        ]);
    }

    public function approveProfilePhoto($photoID, $adminID) {
        $stmt = $this->conn->prepare("UPDATE profile_photo_history SET Status = 1, ReviewedAt = CURRENT_TIMESTAMP, ReviewedBy = :adminID WHERE PhotoID = :photoID");
        return $stmt->execute([
            'adminID' => $adminID,
            'photoID' => $photoID
        ]);
    }

    public function rejectProfilePhoto($photoID, $adminID) {
        $stmt = $this->conn->prepare("UPDATE profile_photo_history SET Status = -1, ReviewedAt = CURRENT_TIMESTAMP, ReviewedBy = :adminID WHERE PhotoID = :photoID");
        return $stmt->execute([
            'adminID' => $adminID,
            'photoID' => $photoID
        ]);
    }

    public function getCompletedPhotoSubmissions() {
        if (!$this->conn) {
            return [];
        }
        $stmt = $this->conn->prepare("SELECT p.*, s.StudentName, s.Email FROM profile_photo_history p JOIN student s ON p.StudentNo = s.StudentNo WHERE p.Status IN (1, -1) ORDER BY p.SubmittedAt DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingPhotoSubmissions() {
        if (!$this->conn) {
            return [];
        }
        $stmt = $this->conn->prepare("SELECT p.*, s.StudentName, s.Email FROM profile_photo_history p JOIN student s ON p.StudentNo = s.StudentNo WHERE p.Status = 0 ORDER BY p.SubmittedAt DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}