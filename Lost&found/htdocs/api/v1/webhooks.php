<?php
/**
 * Webhook Receiver for n8n
 * 
 * This endpoint receives webhook calls from n8n workflows
 * and triggers the appropriate PHP backend processes.
 */

require_once __DIR__ . '/base.php';
require_once __DIR__ . '/../../classes/Student.php';
require_once __DIR__ . '/../../classes/ReportItem.php';
require_once __DIR__ . '/../../classes/Item.php';
require_once __DIR__ . '/../../classes/FileUpload.php';
require_once __DIR__ . '/../../classes/Notification.php';
require_once __DIR__ . '/../../classes/Admin.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonError('Only POST method allowed', 405);
}

// Get webhook data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    jsonError('Invalid JSON data', 400);
}

// Get action type
$action = $data['action'] ?? '';
$type = $data['type'] ?? '';

if (empty($action)) {
    jsonError('Action is required', 400);
}

try {
    switch ($action) {
        // Authentication Actions
        case 'login':
            handleLogin($data);
            break;
        
        case 'signup':
            handleSignup($data);
            break;
        
        // Report Actions
        case 'create_lost_report':
            handleCreateLostReport($data);
            break;
        
        case 'create_found_report':
            handleCreateFoundReport($data);
            break;
        
        case 'delete_report':
            handleDeleteReport($data);
            break;
        
        case 'approve_report':
            handleApproveReport($data);
            break;
        
        case 'reject_report':
            handleRejectReport($data);
            break;
        
        // Profile Actions
        case 'update_profile':
            handleUpdateProfile($data);
            break;
        
        case 'upload_profile_photo':
            handleUploadProfilePhoto($data);
            break;
        
        case 'change_password':
            handleChangePassword($data);
            break;
        
        // Notification Actions
        case 'get_notifications':
            handleGetNotifications($data);
            break;
        
        case 'mark_notification_read':
            handleMarkNotificationRead($data);
            break;
        
        case 'mark_all_notifications_read':
            handleMarkAllNotificationsRead($data);
            break;
        
        // Admin Actions
        case 'approve_profile_photo':
            handleApproveProfilePhoto($data);
            break;
        
        case 'reject_profile_photo':
            handleRejectProfilePhoto($data);
            break;
        
        case 'approve_found_item':
            handleApproveFoundItem($data);
            break;
        
        case 'reject_found_item':
            handleRejectFoundItem($data);
            break;
        
        case 'get_dashboard_stats':
            handleGetDashboardStats($data);
            break;
        
        case 'create_notification':
            handleCreateNotification($data);
            break;
        
        case 'cleanup_notifications':
            handleCleanupNotifications($data);
            break;
        
        default:
            jsonError("Unknown action: {$action}", 400);
    }
} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
    jsonError('Internal server error: ' . $e->getMessage(), 500);
}

// Handler Functions

function handleLogin($data) {
    $studentNo = $data['studentNo'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($studentNo) || empty($password)) {
        jsonError('Student number and password are required', 400);
    }
    
    $student = new Student();
    $result = $student->login($studentNo, $password);
    
    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'user' => $result['user'],
            'message' => 'Login successful'
        ]);
    } else {
        jsonError($result['message'], 401);
    }
}

function handleSignup($data) {
    $studentNo = $data['studentNo'] ?? '';
    $studentName = $data['studentName'] ?? '';
    $phoneNo = $data['phoneNo'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    $required = ['studentNo', 'studentName', 'phoneNo', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("Missing required field: {$field}", 400);
        }
    }
    
    // Validate UB email format
    $expectedEmail = $studentNo . '@ub.edu.ph';
    if (strtolower($email) !== strtolower($expectedEmail)) {
        jsonError('Email must be your student number followed by @ub.edu.ph', 400);
    }
    
    $student = new Student();
    $result = $student->register($studentNo, $studentName, $phoneNo, $email, $password);
    
    jsonResponse($result, $result['success'] ? 201 : 400);
}

function handleCreateLostReport($data) {
    $studentNo = $data['studentNo'] ?? '';
    $itemName = $data['itemName'] ?? '';
    $itemClass = $data['itemClass'] ?? '';
    $description = $data['description'] ?? '';
    $dateOfLoss = $data['dateOfLoss'] ?? '';
    $lostLocation = $data['lostLocation'] ?? '';
    $photoData = $data['photo'] ?? null; // Base64 encoded image
    
    $required = ['studentNo', 'itemName', 'itemClass', 'description', 'dateOfLoss', 'lostLocation'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("Missing required field: {$field}", 400);
        }
        if (empty($$field)) {
            jsonError("Missing required field: {$field}", 400);
        }
    }
    
    $photoURL = null;
    if ($photoData) {
        // Handle base64 image upload
        $fileUpload = new FileUpload();
        $uploadResult = $fileUpload->uploadBase64Image($photoData, 'lost');
        if ($uploadResult['success']) {
            $photoURL = $uploadResult['path'];
        } else {
            jsonError($uploadResult['message'], 400);
        }
    }
    
    $reportItem = new ReportItem();
    $result = $reportItem->create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL);
    
    jsonResponse($result, $result['success'] ? 201 : 400);
}

function handleCreateFoundReport($data) {
    $adminID = $data['adminID'] ?? 1;
    $itemName = $data['itemName'] ?? '';
    $itemClass = $data['itemClass'] ?? '';
    $description = $data['description'] ?? '';
    $dateFound = $data['dateFound'] ?? '';
    $locationFound = $data['locationFound'] ?? '';
    $photoData = $data['photo'] ?? null;
    
    $required = ['itemName', 'itemClass', 'description', 'dateFound', 'locationFound'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("Missing required field: {$field}", 400);
        }
    }
    
    $photoURL = null;
    if ($photoData) {
        $fileUpload = new FileUpload();
        $uploadResult = $fileUpload->uploadBase64Image($photoData, 'found');
        if ($uploadResult['success']) {
            $photoURL = $uploadResult['path'];
        } else {
            jsonError($uploadResult['message'], 400);
        }
    }
    
    $item = new Item();
    $result = $item->create($adminID, $itemName, $itemClass, $description, $dateFound, $locationFound, $photoURL);
    
    jsonResponse($result, $result['success'] ? 201 : 400);
}

function handleDeleteReport($data) {
    $reportID = $data['reportID'] ?? '';
    $studentNo = $data['studentNo'] ?? '';
    
    if (empty($reportID) || empty($studentNo)) {
        jsonError('Report ID and Student Number are required', 400);
    }
    
    $reportItem = new ReportItem();
    $result = $reportItem->delete($reportID, $studentNo);
    
    jsonResponse($result, $result['success'] ? 200 : 400);
}

function handleApproveReport($data) {
    $reportID = $data['reportID'] ?? '';
    $adminID = $data['adminID'] ?? '';
    
    if (empty($reportID) || empty($adminID)) {
        jsonError('Report ID and Admin ID are required', 400);
    }
    
    $reportItem = new ReportItem();
    $result = $reportItem->approve($reportID, $adminID);
    
    jsonResponse($result, $result['success'] ? 200 : 400);
}

function handleRejectReport($data) {
    $reportID = $data['reportID'] ?? '';
    $adminID = $data['adminID'] ?? '';
    
    if (empty($reportID) || empty($adminID)) {
        jsonError('Report ID and Admin ID are required', 400);
    }
    
    $reportItem = new ReportItem();
    $result = $reportItem->reject($reportID, $adminID);
    
    jsonResponse($result, $result['success'] ? 200 : 400);
}

function handleUpdateProfile($data) {
    $studentNo = $data['studentNo'] ?? '';
    $studentName = $data['studentName'] ?? '';
    $phoneNo = $data['phoneNo'] ?? '';
    $email = $data['email'] ?? '';
    $bio = $data['bio'] ?? '';
    
    if (empty($studentNo)) {
        jsonError('Student number is required', 400);
    }
    
    $student = new Student();
    $result = $student->updateProfile($studentNo, $studentName, $phoneNo, $email);
    
    // Update bio separately if provided
    if (!empty($bio)) {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('UPDATE student SET Bio = :bio WHERE StudentNo = :studentNo');
        $stmt->execute(['bio' => $bio, 'studentNo' => $studentNo]);
    }
    
    jsonResponse(['success' => true, 'message' => 'Profile updated successfully'], 200);
}

function handleUploadProfilePhoto($data) {
    $studentNo = $data['studentNo'] ?? '';
    $photoData = $data['photo'] ?? ''; // Base64 encoded
    
    if (empty($studentNo) || empty($photoData)) {
        jsonError('Student number and photo are required', 400);
    }
    
    $fileUpload = new FileUpload();
    $uploadResult = $fileUpload->uploadBase64Image($photoData, 'profile');
    
    if (!$uploadResult['success']) {
        jsonError($uploadResult['message'], 400);
    }
    
    // Add to approval queue
    $admin = new Admin();
    $admin->addProfilePhotoSubmission($studentNo, $uploadResult['path']);
    
    jsonResponse([
        'success' => true,
        'message' => 'Profile photo uploaded and pending approval',
        'photoURL' => $uploadResult['path']
    ], 201);
}

function handleChangePassword($data) {
    $studentNo = $data['studentNo'] ?? '';
    $currentPassword = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    
    if (empty($studentNo) || empty($currentPassword) || empty($newPassword)) {
        jsonError('All password fields are required', 400);
    }
    
    // Verify current password
    $student = new Student();
    $loginResult = $student->login($studentNo, $currentPassword);
    
    if (!$loginResult['success']) {
        jsonError('Current password is incorrect', 401);
    }
    
    // Update password
    $db = new Database();
    $conn = $db->getConnection();
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE student SET Password = :password WHERE StudentNo = :studentNo');
    $stmt->execute(['password' => $hashedPassword, 'studentNo' => $studentNo]);
    
    jsonResponse(['success' => true, 'message' => 'Password updated successfully'], 200);
}

function handleGetNotifications($data) {
    $studentNo = $data['studentNo'] ?? '';
    $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
    
    if (empty($studentNo)) {
        jsonError('Student number is required', 400);
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    $notification = new Notification($conn);
    
    $notifications = $notification->getAll($studentNo, $limit);
    $unreadCount = $notification->getUnreadCount($studentNo);
    
    jsonResponse([
        'success' => true,
        'data' => $notifications,
        'unreadCount' => $unreadCount
    ]);
}

function handleMarkNotificationRead($data) {
    $notificationID = $data['notificationID'] ?? '';
    $studentNo = $data['studentNo'] ?? '';
    
    if (empty($notificationID) || empty($studentNo)) {
        jsonError('Notification ID and Student Number are required', 400);
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    $notification = new Notification($conn);
    
    $result = $notification->markAsRead($notificationID, $studentNo);
    
    jsonResponse(['success' => $result, 'message' => $result ? 'Notification marked as read' : 'Failed to mark as read'], $result ? 200 : 400);
}

function handleMarkAllNotificationsRead($data) {
    $studentNo = $data['studentNo'] ?? '';
    
    if (empty($studentNo)) {
        jsonError('Student number is required', 400);
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    $notification = new Notification($conn);
    
    $result = $notification->markAllAsRead($studentNo);
    
    jsonResponse(['success' => $result, 'message' => 'All notifications marked as read'], 200);
}

function handleApproveProfilePhoto($data) {
    $photoID = $data['photoID'] ?? '';
    $adminID = $data['adminID'] ?? '';
    
    if (empty($photoID) || empty($adminID)) {
        jsonError('Photo ID and Admin ID are required', 400);
    }
    
    $admin = new Admin();
    $result = $admin->approveProfilePhoto($photoID, $adminID);
    
    // Also update student table
    if ($result) {
        $db = new Database();
        $conn = $db->getConnection();
        $photoStmt = $conn->prepare('SELECT StudentNo, PhotoURL FROM profile_photo_history WHERE PhotoID = :photoID');
        $photoStmt->execute(['photoID' => $photoID]);
        $photo = $photoStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($photo) {
            $updateStmt = $conn->prepare('UPDATE student SET ProfilePhoto = :photoURL, PhotoConfirmed = 1, UpdatedAt = CURRENT_TIMESTAMP WHERE StudentNo = :studentNo');
            $updateStmt->execute(['photoURL' => $photo['PhotoURL'], 'studentNo' => $photo['StudentNo']]);
            
            // Create notification
            $notification = new Notification($conn);
            $notification->create(
                $photo['StudentNo'],
                'photo_approved',
                'Profile Photo Approved!',
                'Your profile photo has been approved and is now visible to other users.',
                $photo['StudentNo']
            );
        }
    }
    
    jsonResponse(['success' => $result, 'message' => $result ? 'Profile photo approved' : 'Failed to approve'], $result ? 200 : 400);
}

function handleRejectProfilePhoto($data) {
    $photoID = $data['photoID'] ?? '';
    $adminID = $data['adminID'] ?? '';
    
    if (empty($photoID) || empty($adminID)) {
        jsonError('Photo ID and Admin ID are required', 400);
    }
    
    $admin = new Admin();
    $result = $admin->rejectProfilePhoto($photoID, $adminID);
    
    // Also update student table
    if ($result) {
        $db = new Database();
        $conn = $db->getConnection();
        $photoStmt = $conn->prepare('SELECT StudentNo, PhotoURL FROM profile_photo_history WHERE PhotoID = :photoID');
        $photoStmt->execute(['photoID' => $photoID]);
        $photo = $photoStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($photo) {
            $updateStmt = $conn->prepare('UPDATE student SET PhotoConfirmed = -1, UpdatedAt = CURRENT_TIMESTAMP WHERE StudentNo = :studentNo');
            $updateStmt->execute(['studentNo' => $photo['StudentNo']]);
            
            // Create notification
            $notification = new Notification($conn);
            $notification->create(
                $photo['StudentNo'],
                'photo_rejected',
                'Profile Photo Rejected',
                'Your profile photo was rejected by admin. Please upload a new photo.',
                $photo['StudentNo']
            );
        }
    }
    
    jsonResponse(['success' => $result, 'message' => $result ? 'Profile photo rejected' : 'Failed to reject'], $result ? 200 : 400);
}

function handleApproveFoundItem($data) {
    $itemID = $data['itemID'] ?? '';
    $adminID = $data['adminID'] ?? '';
    
    if (empty($itemID) || empty($adminID)) {
        jsonError('Item ID and Admin ID are required', 400);
    }
    
    $item = new Item();
    $result = $item->approve($itemID, $adminID);
    
    jsonResponse(['success' => $result, 'message' => $result ? 'Item approved' : 'Failed to approve'], $result ? 200 : 400);
}

function handleRejectFoundItem($data) {
    $itemID = $data['itemID'] ?? '';
    $adminID = $data['adminID'] ?? '';
    
    if (empty($itemID) || empty($adminID)) {
        jsonError('Item ID and Admin ID are required', 400);
    }
    
    $item = new Item();
    $result = $item->reject($itemID, $adminID);
    
    jsonResponse(['success' => $result, 'message' => $result ? 'Item rejected' : 'Failed to reject'], $result ? 200 : 400);
}

function handleGetDashboardStats($data) {
    $admin = new Admin();
    $stats = $admin->getDashboardStats();
    
    jsonResponse([
        'success' => true,
        'data' => $stats
    ]);
}

function handleCreateNotification($data) {
    $studentNo = $data['studentNo'] ?? '';
    $type = $data['type'] ?? '';
    $title = $data['title'] ?? '';
    $message = $data['message'] ?? '';
    $relatedID = $data['relatedID'] ?? null;
    
    if (empty($studentNo) || empty($type) || empty($title) || empty($message)) {
        jsonError('Student number, type, title, and message are required', 400);
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    $notification = new Notification($conn);
    
    $result = $notification->create($studentNo, $type, $title, $message, $relatedID);
    
    jsonResponse([
        'success' => $result,
        'message' => $result ? 'Notification created successfully' : 'Failed to create notification'
    ], $result ? 201 : 400);
}

function handleCleanupNotifications($data) {
    $daysOld = isset($data['daysOld']) ? (int)$data['daysOld'] : 30;
    
    $db = new Database();
    $conn = $db->getConnection();
    $notification = new Notification($conn);
    
    // Get count before deletion
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM Notifications WHERE CreatedAt < DATE_SUB(NOW(), INTERVAL :days DAY)');
    $stmt->execute(['days' => $daysOld]);
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $countBefore = $countResult['count'] ?? 0;
    
    // Perform cleanup
    $result = $notification->cleanupOldNotifications();
    
    jsonResponse([
        'success' => $result,
        'deletedCount' => $countBefore,
        'message' => $result ? "Deleted {$countBefore} old notifications" : 'Failed to cleanup notifications'
    ], $result ? 200 : 400);
}

