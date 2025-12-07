<?php
/**
 * Webhook Receiver for n8n
 * 
 * This endpoint receives webhook calls from n8n workflows
 * and triggers the appropriate PHP backend processes.
 */

require_once __DIR__ . '/base.php';
require_once __DIR__ . '/../../includes/Logger.php';
require_once __DIR__ . '/../../classes/Student.php';
require_once __DIR__ . '/../../classes/ReportItem.php';
require_once __DIR__ . '/../../classes/Item.php';
require_once __DIR__ . '/../../classes/FileUpload.php';
require_once __DIR__ . '/../../classes/Notification.php';
require_once __DIR__ . '/../../classes/Admin.php';

// Suppress PHP errors from corrupting JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them instead
ini_set('log_errors', 1);

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
    Logger::log("=== WEBHOOK: handleCreateLostReport START ===");
    Logger::log("Raw input data keys: " . implode(', ', array_keys($data)));
    Logger::log("Full input data: " . json_encode($data));
    
    try {
        $studentNo = $data['studentNo'] ?? '';
        $itemName = $data['itemName'] ?? '';
        $itemClass = $data['itemClass'] ?? '';
        $description = $data['description'] ?? '';
        $dateOfLoss = $data['dateOfLoss'] ?? '';
        $lostLocation = $data['lostLocation'] ?? '';
        $photoURL = $data['photoURL'] ?? null; // Photo URL (string), already uploaded to server
        
        Logger::log("Extracted values:");
        Logger::log("  - studentNo: $studentNo");
        Logger::log("  - itemName: $itemName");
        Logger::log("  - itemClass: $itemClass");
        Logger::log("  - description: " . substr($description, 0, 50) . '...');
        Logger::log("  - dateOfLoss: $dateOfLoss");
        Logger::log("  - lostLocation: $lostLocation");
        Logger::log("  - photoURL: " . ($photoURL ?? 'NULL'));
        
        if ($photoURL) {
            Logger::log("Photo URL received: $photoURL");
            // Verify file actually exists (optional check)
            $fullPath = __DIR__ . '/../../' . $photoURL;
            if (file_exists($fullPath)) {
                Logger::log("VERIFICATION: Photo file exists at: $fullPath");
                Logger::log("VERIFICATION: Photo file size: " . filesize($fullPath) . " bytes");
            } else {
                Logger::log("WARNING: Photo file NOT FOUND at: $fullPath (but continuing anyway)");
            }
        } else {
            Logger::log("No photo URL provided (report will be created without photo)");
        }
        
        $required = ['studentNo', 'itemName', 'itemClass', 'description', 'dateOfLoss', 'lostLocation'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                Logger::log("ERROR: Missing required field: $field");
                jsonError("Missing required field: {$field}", 400);
            }
            if (empty($$field)) {
                Logger::log("ERROR: Empty value for field: $field");
                jsonError("Missing required field: {$field}", 400);
            }
        }
        
        Logger::log("Final photoURL value before calling ReportItem::create: " . ($photoURL ?? 'NULL'));
        
        // Wrap ReportItem instantiation and create() in try-catch
        try {
            $reportItem = new ReportItem();
            $result = $reportItem->create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL);
        } catch (Exception $e) {
            Logger::log("EXCEPTION in ReportItem::create: " . $e->getMessage());
            Logger::log("Stack trace: " . $e->getTraceAsString());
            jsonError('Failed to create lost report: ' . $e->getMessage(), 500);
        }
        
        Logger::log("ReportItem::create returned: " . json_encode($result));
        Logger::log("Report creation result: " . ($result['success'] ? 'SUCCESS' : 'FAILED'));
        
        // Validate result structure
        if (!is_array($result) || !isset($result['success'])) {
            Logger::log("ERROR: Invalid result structure from ReportItem::create");
            jsonError('Invalid response from report creation', 500);
        }
        
        if (isset($result['id'])) {
            Logger::log("Report ID: " . $result['id']);
            
            // Final verification: Check what's actually in the database
            try {
                require_once __DIR__ . '/../../includes/Database.php';
                $db = new Database();
                $conn = $db->getConnection();
                if ($conn) {
                    $verifyStmt = $conn->prepare("SELECT PhotoURL FROM reportitem WHERE ReportID = :id");
                    $verifyStmt->execute(['id' => $result['id']]);
                    $dbRow = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                    Logger::log("FINAL VERIFICATION: PhotoURL in database for ReportID {$result['id']}: " . ($dbRow['PhotoURL'] ?? 'NULL'));
                }
            } catch (Exception $e) {
                Logger::log("Could not verify database entry: " . $e->getMessage());
            }
        }
        Logger::log("=== WEBHOOK: handleCreateLostReport END ===");
        
        jsonResponse($result, $result['success'] ? 201 : 400);
        
    } catch (Exception $e) {
        Logger::log("EXCEPTION in handleCreateLostReport: " . $e->getMessage());
        Logger::log("Stack trace: " . $e->getTraceAsString());
        jsonError('Failed to create lost report: ' . $e->getMessage(), 500);
    } catch (Error $e) {
        Logger::log("FATAL ERROR in handleCreateLostReport: " . $e->getMessage());
        Logger::log("Stack trace: " . $e->getTraceAsString());
        jsonError('Fatal error while creating lost report: ' . $e->getMessage(), 500);
    }
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
        // Increase max file size to 20MB (20 * 1024 * 1024 = 20971520 bytes)
        $fileUpload = new FileUpload(null, 20971520);
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
    try {
        Logger::log("=== WEBHOOK: handleCreateNotification START ===");
        Logger::log("Raw input data keys: " . implode(', ', array_keys($data)));
        
        $studentNo = $data['studentNo'] ?? '';
        $type = $data['type'] ?? '';
        $title = $data['title'] ?? '';
        $message = $data['message'] ?? '';
        $relatedID = $data['relatedID'] ?? null;
        
        Logger::log("Extracted values:");
        Logger::log("  - studentNo: $studentNo");
        Logger::log("  - type: $type");
        Logger::log("  - title: $title");
        Logger::log("  - message: " . substr($message, 0, 50) . '...');
        Logger::log("  - relatedID: " . ($relatedID ?? 'NULL'));
        
        if (empty($studentNo) || empty($type) || empty($title) || empty($message)) {
            Logger::log("ERROR: Missing required fields");
            jsonError('Student number, type, title, and message are required', 400);
        }
        
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            if ($conn === null) {
                Logger::log("ERROR: Database connection unavailable");
                jsonError('Database connection unavailable', 500);
            }
            
            $notification = new Notification($conn);
            $result = $notification->create($studentNo, $type, $title, $message, $relatedID);
            
            Logger::log("Notification::create returned: " . ($result ? 'true' : 'false'));
            
            $errorMessage = 'Failed to create notification';
            if (!$result) {
                // Try to get more details about the failure
                Logger::log("ERROR: Notification creation returned false");
                // Check if it's a PDO error
                if ($conn) {
                    $errorInfo = $conn->errorInfo();
                    if ($errorInfo && $errorInfo[0] !== '00000') {
                        Logger::log("PDO Error Info: " . json_encode($errorInfo));
                        $errorMessage = 'Failed to create notification: ' . ($errorInfo[2] ?? 'Database error');
                    }
                }
            }
            
            Logger::log("=== WEBHOOK: handleCreateNotification END ===");
            
            jsonResponse([
                'success' => $result,
                'message' => $result ? 'Notification created successfully' : $errorMessage
            ], $result ? 201 : 400);
            
        } catch (Exception $e) {
            Logger::log("EXCEPTION in Notification::create: " . $e->getMessage());
            Logger::log("Stack trace: " . $e->getTraceAsString());
            jsonError('Failed to create notification: ' . $e->getMessage(), 500);
        }
        
    } catch (Exception $e) {
        Logger::log("EXCEPTION in handleCreateNotification: " . $e->getMessage());
        Logger::log("Stack trace: " . $e->getTraceAsString());
        jsonError('Failed to create notification: ' . $e->getMessage(), 500);
    } catch (Error $e) {
        Logger::log("FATAL ERROR in handleCreateNotification: " . $e->getMessage());
        Logger::log("Stack trace: " . $e->getTraceAsString());
        jsonError('Fatal error while creating notification: ' . $e->getMessage(), 500);
    }
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

