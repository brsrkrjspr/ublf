<?php
require_once __DIR__ . '/../includes/Database.php';

class Student {
    private $conn;
    private $table = 'student';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection unavailable');
        }
    }

    public function register($studentNo, $studentName, $phoneNo, $email, $password) {
        if (!$this->conn) {
            return ['success' => false, 'message' => 'Database unavailable. Registration disabled.'];
        }
        // Check if student number or email already exists
        $query = "SELECT * FROM {$this->table} WHERE StudentNo = :studentNo OR Email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['studentNo' => $studentNo, 'email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Student No or Email already exists.'];
        }
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO {$this->table} (StudentNo, StudentName, PhoneNo, Email, PasswordHash) VALUES (:studentNo, :studentName, :phoneNo, :email, :passwordHash)";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            'studentNo' => $studentNo,
            'studentName' => $studentName,
            'phoneNo' => $phoneNo,
            'email' => $email,
            'passwordHash' => $passwordHash
        ]);
        if ($result) {
            return ['success' => true, 'message' => 'Registration successful.'];
        } else {
            return ['success' => false, 'message' => 'Registration failed.'];
        }
    }

    public function login($studentNo, $password) {
        if (!$this->conn) {
            // Mock login for testing - accept TEST001/test123
            if ($studentNo === 'TEST001' && $password === 'test123') {
                return ['success' => true, 'user' => [
                    'StudentNo' => 'TEST001',
                    'StudentName' => 'Test Student',
                    'Email' => 'TEST001@ub.edu.ph',
                    'PhoneNo' => '09123456789',
                    'ProfilePhoto' => null,
                    'PhotoConfirmed' => 0
                ]];
            }
            return ['success' => false, 'message' => 'Database unavailable. Use TEST001/test123 for testing.'];
        }
        $query = "SELECT * FROM {$this->table} WHERE StudentNo = :studentNo LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['studentNo' => $studentNo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['PasswordHash'])) {
            return ['success' => true, 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'Invalid Student No or Password.'];
        }
    }

    public function updateProfile($studentNo, $studentName, $phoneNo, $email) {
        $query = "UPDATE {$this->table} SET StudentName = :studentName, PhoneNo = :phoneNo, Email = :email WHERE StudentNo = :studentNo";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            'studentName' => $studentName,
            'phoneNo' => $phoneNo,
            'email' => $email,
            'studentNo' => $studentNo
        ]);
        
        return $result ? 
            ['success' => true, 'message' => 'Profile updated successfully.'] : 
            ['success' => false, 'message' => 'Failed to update profile.'];
    }

    public function updateProfilePhoto($studentNo, $photoURL) {
        $query = "UPDATE {$this->table} SET ProfilePhoto = :photoURL, PhotoConfirmed = 0 WHERE StudentNo = :studentNo";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            'photoURL' => $photoURL,
            'studentNo' => $studentNo
        ]);
        
        return $result ? 
            ['success' => true, 'message' => 'Profile photo updated successfully. It will be visible after admin approval.'] : 
            ['success' => false, 'message' => 'Failed to update profile photo.'];
    }

    public function getByStudentNo($studentNo) {
        if (!$this->conn) {
            // Return mock data
            return [
                'StudentNo' => $studentNo,
                'StudentName' => 'Test Student',
                'Email' => $studentNo . '@ub.edu.ph',
                'PhoneNo' => '09123456789',
                'ProfilePhoto' => null,
                'PhotoConfirmed' => 0
            ];
        }
        $query = "SELECT * FROM {$this->table} WHERE StudentNo = :studentNo LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['studentNo' => $studentNo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approvePhoto($studentNo) {
        $query = "UPDATE {$this->table} SET PhotoConfirmed = 1 WHERE StudentNo = :studentNo";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute(['studentNo' => $studentNo]);
        
        if ($result) {
            $this->createNotification($studentNo, 'photo_approved', 'Profile Photo Approved', 
                'Your profile photo has been approved and is now visible to others.');
        }
        
        return $result;
    }

    public function rejectPhoto($studentNo) {
        $query = "UPDATE {$this->table} SET PhotoConfirmed = 2 WHERE StudentNo = :studentNo";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute(['studentNo' => $studentNo]);
        
        if ($result) {
            $this->createNotification($studentNo, 'photo_rejected', 'Profile Photo Rejected', 
                'Your profile photo has been rejected. Please upload a different photo.');
        }
        
        return $result;
    }

    public function getPendingPhotoApprovals() {
        $query = "SELECT StudentNo, StudentName, ProfilePhoto, Email FROM {$this->table} WHERE PhotoConfirmed = 0 AND ProfilePhoto IS NOT NULL ORDER BY StudentNo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompletedPhotoApprovals() {
        $query = "SELECT StudentNo, StudentName, ProfilePhoto, Email, PhotoConfirmed FROM {$this->table} WHERE ProfilePhoto IS NOT NULL AND PhotoConfirmed IN (1, 2) ORDER BY StudentNo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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