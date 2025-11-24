<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../classes/Notification.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php');
    exit;
}
$db = new Database();
$conn = $db->getConnection();
$notification = new Notification($conn);
$type = $_POST['type'] ?? '';
$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? '';
$msg = '';

if ($type === 'photo' && $id) {
    // $id should be the PhotoID from profile_photo_history
    // Get photo submission info
    $stmt = $conn->prepare('SELECT * FROM profile_photo_history WHERE PhotoID = :id');
    $stmt->execute(['id' => $id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($photo) {
        $studentNo = $photo['StudentNo'];
        $photoURL = $photo['PhotoURL'];
        $adminID = $_SESSION['admin']['AdminID'];
        require_once __DIR__ . '/../classes/Admin.php';
        $adminObj = new Admin();
        if ($action === 'approve') {
            $adminObj->approveProfilePhoto($id, $adminID);
            // Update student table to set ProfilePhoto and PhotoConfirmed
            $stmt2 = $conn->prepare('UPDATE student SET ProfilePhoto = :photoURL, PhotoConfirmed = 1, UpdatedAt = CURRENT_TIMESTAMP WHERE StudentNo = :studentNo');
            $stmt2->execute(['photoURL' => $photoURL, 'studentNo' => $studentNo]);
            $msg = 'Profile photo approved.';
            // Create notification
            $notification->create(
                $studentNo,
                'photo_approved',
                'Profile Photo Approved!',
                'Your profile photo has been approved and is now visible to other users.',
                $studentNo
            );
        } elseif ($action === 'reject') {
            $adminObj->rejectProfilePhoto($id, $adminID);
            // Update student table to set PhotoConfirmed = -1 (keep photo for resubmission)
            $stmt2 = $conn->prepare('UPDATE student SET PhotoConfirmed = -1, UpdatedAt = CURRENT_TIMESTAMP WHERE StudentNo = :studentNo');
            $stmt2->execute(['studentNo' => $studentNo]);
            $msg = 'Profile photo rejected.';
            // Create notification
            $notification->create(
                $studentNo,
                'photo_rejected',
                'Profile Photo Rejected',
                'Your profile photo was rejected. Please upload a different photo.',
                $studentNo
            );
        }
    }
} elseif ($type === 'lost' && $id) {
    // Get report info first
    $stmt = $conn->prepare('SELECT r.StudentNo, r.ItemName, s.StudentName FROM reportitem r JOIN student s ON r.StudentNo = s.StudentNo WHERE r.ReportID = :id');
    $stmt->execute(['id' => $id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'approve') {
        $stmt = $conn->prepare('UPDATE reportitem SET StatusConfirmed = 1, UpdatedAt = CURRENT_TIMESTAMP WHERE ReportID = :id');
        $stmt->execute(['id' => $id]);
        $msg = 'Lost item report approved.';
        
        // Create notification
        $notification->create(
            $report['StudentNo'],
            'report_approved',
            'Lost Item Report Approved!',
            'Your lost item report for "' . $report['ItemName'] . '" has been approved and is now visible to other users.',
            $id
        );
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare('UPDATE reportitem SET StatusConfirmed = -1, UpdatedAt = CURRENT_TIMESTAMP WHERE ReportID = :id');
        $stmt->execute(['id' => $id]);
        $msg = 'Lost item report rejected.';
        
        // Create notification
        $notification->create(
            $report['StudentNo'],
            'report_rejected',
            'Lost Item Report Rejected',
            'Your lost item report for "' . $report['ItemName'] . '" was rejected. Please check the details and submit again.',
            $id
        );
    }
} elseif ($type === 'found' && $id) {
    if ($action === 'approve') {
        $stmt = $conn->prepare('UPDATE item SET StatusConfirmed = 1, UpdatedAt = CURRENT_TIMESTAMP WHERE ItemID = :id');
        $stmt->execute(['id' => $id]);
        $msg = 'Found item report approved.';
        // Note: Found items are reported by admins, so no user notification needed
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare('UPDATE item SET StatusConfirmed = -1, UpdatedAt = CURRENT_TIMESTAMP WHERE ItemID = :id');
        $stmt->execute(['id' => $id]);
        $msg = 'Found item report rejected.';
        // Note: Found items are reported by admins, so no user notification needed
    }
}
$_SESSION['admin_msg'] = $msg;
header('Location: admin_dashboard.php');
exit; 