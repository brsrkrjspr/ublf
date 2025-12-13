<?php
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ReportItem.php';
require_once __DIR__ . '/../classes/Item.php';
require_once __DIR__ . '/../classes/FileUpload.php';
require_once __DIR__ . '/../includes/Config.php';
require_once __DIR__ . '/../includes/Logger.php';

session_start();
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}

// Initialize classes
$student = new Student();
$reportItem = new ReportItem();
$item = new Item();
// Increase max file size to 20MB (20 * 1024 * 1024 = 20971520 bytes)
$fileUpload = new FileUpload(null, 20971520);

// Fetch fresh student data (with fallback for no database)
$studentData = null;
try {
    $studentData = $student->getByStudentNo($_SESSION['student']['StudentNo']);
    if ($studentData) {
        $_SESSION['student'] = $studentData;
    }
} catch (Exception $e) {
    // Use session data if database unavailable
    $studentData = $_SESSION['student'] ?? ['StudentName' => 'Test User', 'StudentNo' => 'TEST001'];
}

// Fetch item classes for dropdown (with fallback)
$itemClasses = ['Electronics', 'Books', 'Clothing', 'Bags', 'ID Cards', 'Keys', 'Others'];
try {
    require_once __DIR__ . '/../classes/ReportItem.php';
    $reportItemObj = new ReportItem();
    $dbItemClasses = $reportItemObj->getItemClasses();
    if (!empty($dbItemClasses)) {
        $itemClasses = $dbItemClasses;
    }
} catch (Exception $e) {
    // Use default item classes
}

// Count approved lost items for match detection button
$approvedCount = 0;
try {
    require_once __DIR__ . '/../includes/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM reportitem WHERE StudentNo = :studentNo AND StatusConfirmed = 1');
        $stmt->execute(['studentNo' => $studentData['StudentNo']]);
        $approvedCount = (int)$stmt->fetchColumn();
    }
} catch (Exception $e) {
    // Use default count of 0
}

// Handle form submissions
$dashboardMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['report_lost'])) {
        $result = handleLostItemReport($reportItem, $fileUpload);
        $dashboardMsg = $result['message'];
    } elseif (isset($_POST['report_found'])) {
        $result = handleFoundItemReport($item, $fileUpload);
        $dashboardMsg = $result['message'];
    }
}

function handleLostItemReport($reportItem, $fileUpload) {
    Logger::log("=== DASHBOARD: handleLostItemReport START ===");
    Logger::log("Session StudentNo: " . ($_SESSION['student']['StudentNo'] ?? 'NOT SET'));
    
    $studentNo = $_SESSION['student']['StudentNo'];
    $itemName = $_POST['lostItemName'] ?? '';
    $itemClass = $_POST['lostItemClass'] ?? '';
    $description = $_POST['lostDescription'] ?? '';
    $dateOfLoss = $_POST['lostDate'] ?? '';
    $lostLocation = $_POST['lostLocation'] ?? '';
    
    Logger::log("Form data extracted:");
    Logger::log("  - itemName: $itemName");
    Logger::log("  - itemClass: $itemClass");
    Logger::log("  - dateOfLoss: $dateOfLoss");
    Logger::log("  - lostLocation: $lostLocation");
    Logger::log("  - Photo file present: " . (isset($_FILES['lostPhoto']) ? 'YES' : 'NO'));
    
    if (isset($_FILES['lostPhoto'])) {
        Logger::log("Photo file details:");
        Logger::log("  - Error code: " . $_FILES['lostPhoto']['error']);
        Logger::log("  - Name: " . ($_FILES['lostPhoto']['name'] ?? 'N/A'));
        Logger::log("  - Size: " . ($_FILES['lostPhoto']['size'] ?? 0) . " bytes");
        Logger::log("  - Type: " . ($_FILES['lostPhoto']['type'] ?? 'N/A'));
    }
    
    // Upload photo to server FIRST (before sending to n8n)
    $photoURL = null;
    
    // Log PHP upload configuration for debugging
    Logger::log("PHP upload_max_filesize: " . ini_get('upload_max_filesize'));
    Logger::log("PHP post_max_size: " . ini_get('post_max_size'));
    Logger::log("PHP max_file_uploads: " . ini_get('max_file_uploads'));
    
    if (isset($_FILES['lostPhoto'])) {
        $fileSize = $_FILES['lostPhoto']['size'] ?? 0;
        $errorCode = $_FILES['lostPhoto']['error'] ?? UPLOAD_ERR_NO_FILE;
        
        Logger::log("--- Photo upload attempt ---");
        Logger::log("File name: " . ($_FILES['lostPhoto']['name'] ?? 'N/A'));
        Logger::log("File size: " . $fileSize . " bytes (" . round($fileSize / 1024 / 1024, 2) . " MB)");
        Logger::log("Error code: $errorCode");
        
        if ($errorCode === UPLOAD_ERR_OK) {
            Logger::log("--- Uploading photo to server ---");
            $uploadResult = $fileUpload->uploadPhoto($_FILES['lostPhoto'], 'lost');
            if ($uploadResult['success']) {
                $photoURL = $uploadResult['path'];
                Logger::log("Photo uploaded successfully: $photoURL");
            } else {
                Logger::log("Photo upload failed: " . ($uploadResult['message'] ?? 'Unknown error'));
                // Continue without photo
            }
        } else {
            // Handle specific upload errors
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload_max_filesize limit (' . ini_get('upload_max_filesize') . ')',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $errorMsg = $errorMessages[$errorCode] ?? "Unknown upload error (code: $errorCode)";
            Logger::log("Upload error: $errorMsg");
            Logger::log("File size was: " . $fileSize . " bytes");
            
            // Continue without photo
        }
    } else {
        Logger::log("No photo file uploaded (file not present in request)");
    }
    
    // Get n8n webhook URL
    $n8nWebhookUrl = Config::get('N8N_CREATE_LOST_REPORT_WEBHOOK_URL');
    
    if (empty($n8nWebhookUrl) || strpos($n8nWebhookUrl, 'your-n8n-instance.com') !== false) {
        // Fallback to direct database if n8n not configured
        Logger::log("n8n webhook not configured, using direct database");
        Logger::log("=== DASHBOARD: handleLostItemReport END (NO N8N CONFIG) ===");
        return $reportItem->create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL);
    }
    
    // Prepare JSON payload (not multipart)
    $payload = [
        'studentNo' => $studentNo,
        'itemName' => $itemName,
        'itemClass' => $itemClass,
        'description' => $description,
        'dateOfLoss' => $dateOfLoss,
        'lostLocation' => $lostLocation,
        'photoURL' => $photoURL  // String URL, not file
    ];
    
    Logger::log("=== SENDING TO N8N (JSON) ===");
    Logger::log("Payload keys: " . implode(', ', array_keys($payload)));
    Logger::log("Photo URL: " . ($photoURL ?? 'NULL'));
    
    // Send to n8n webhook using JSON
    if (!function_exists('curl_init')) {
        Logger::log('cURL is not available. Falling back to direct database.');
        Logger::log("=== DASHBOARD: handleLostItemReport END (NO CURL) ===");
        return $reportItem->create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL);
    }
    
    $ch = curl_init($n8nWebhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),  // JSON, not multipart
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],  // Set JSON header
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    Logger::log("Sending cURL request to n8n webhook...");
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    Logger::log("n8n webhook response - HTTP Code: $httpCode");
    
    if ($curlError) {
        Logger::log("n8n webhook cURL error: " . $curlError);
        // Fallback to direct database on error (photo already uploaded)
        Logger::log("=== DASHBOARD: handleLostItemReport END (CURL ERROR FALLBACK) ===");
        return $reportItem->create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL);
    }
    
    if ($httpCode !== 200) {
        Logger::log("n8n webhook returned HTTP $httpCode. Response: " . substr($response, 0, 500));
        // Fallback to direct database on error (photo already uploaded)
        Logger::log("=== DASHBOARD: handleLostItemReport END (HTTP ERROR FALLBACK) ===");
        return $reportItem->create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL);
    }
    
    // Parse n8n response
    $responseData = json_decode($response, true);
    Logger::log("n8n response parsed: " . ($responseData ? 'YES' : 'NO'));
    if ($responseData) {
        Logger::log("n8n response success: " . ($responseData['success'] ?? 'not set'));
    }
    
    if ($responseData && isset($responseData['success'])) {
        Logger::log("=== N8N RESPONSE RECEIVED ===");
        Logger::log("=== DASHBOARD: handleLostItemReport END (SUCCESS via n8n) ===");
        return [
            'success' => $responseData['success'],
            'message' => $responseData['message'] ?? ($responseData['success'] ? 'Lost item report submitted successfully. It will be visible to others after admin approval.' : 'Failed to submit lost item report.')
        ];
    }
    
    // If response parsing fails, fallback to direct database (photo already uploaded)
    Logger::log("WARNING: n8n webhook returned invalid response: " . substr($response, 0, 500));
    Logger::log("Falling back to direct database creation...");
    Logger::log("=== DASHBOARD: handleLostItemReport END (FALLBACK to direct DB) ===");
    return $reportItem->create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL);
}

function handleFoundItemReport($item, $fileUpload) {
        // #region agent log
        $logPath = __DIR__ . '/../.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_handle_entry','timestamp'=>time()*1000,'location'=>'dashboard.php:236','message'=>'handleFoundItemReport called','data'=>['itemName'=>$_POST['foundItemName']??'','itemClass'=>$_POST['foundItemClass']??''],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
        // #endregion
        
        $adminID = 1; // For demo, use AdminID=1. In real app, use session for admin login.
        $itemName = $_POST['foundItemName'] ?? '';
        $itemClass = $_POST['foundItemClass'] ?? '';
        $description = $_POST['foundDescription'] ?? '';
        $dateFound = $_POST['foundDate'] ?? '';
        $locationFound = $_POST['foundLocation'] ?? '';
    
        $photoURL = null;
        if (isset($_FILES['foundPhoto']) && $_FILES['foundPhoto']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = $fileUpload->uploadPhoto($_FILES['foundPhoto'], 'found');
        if ($uploadResult['success']) {
            $photoURL = $uploadResult['path'];
        } else {
            // #region agent log
            $logPath = __DIR__ . '/../.cursor/debug.log';
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_upload_failed','timestamp'=>time()*1000,'location'=>'dashboard.php:250','message'=>'Photo upload failed','data'=>['error'=>$uploadResult['message']??''],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
            // #endregion
            return ['success' => false, 'message' => $uploadResult['message']];
        }
    }
    
    $result = $item->create($adminID, $itemName, $itemClass, $description, $dateFound, $locationFound, $photoURL);
    
    // #region agent log
    $logPath = __DIR__ . '/../.cursor/debug.log';
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_handle_result','timestamp'=>time()*1000,'location'=>'dashboard.php:254','message'=>'handleFoundItemReport result','data'=>$result,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
    // #endregion
    
    return $result;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
  // Use CSS router for reliable path resolution
  $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
  ?>
  <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
  <link href="css.php?file=dash.css" rel="stylesheet">
  <link href="css.php?file=notifications.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../templates/header.php'; ?>
<div class="container py-4">
  <div class="hero-section position-relative mb-5">
    <div class="hero-bg"></div>
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1><i class="bi bi-gem me-2"></i>Welcome, <?php echo htmlspecialchars($studentData['StudentName']); ?>!</h1>
        <p class="mb-4">This is your University of Batangas Lost & Found dashboard. Browse lost and found items, report your own, and contact the admin—all in a modern, secure, and beautiful interface.</p>
        <a href="#" class="btn btn-danger btn-lg mb-3 me-2" data-bs-toggle="modal" data-bs-target="#reportLostModal"><i class="bi bi-plus-circle"></i> Report Lost Item</a>
        <a href="#" class="btn btn-success btn-lg mb-3 me-2" data-bs-toggle="modal" data-bs-target="#reportFoundModal"><i class="bi bi-plus-circle"></i> Report Found Item</a>
        <?php if ($approvedCount > 0): ?>
          <button id="check-matches-btn" class="btn btn-primary btn-lg mb-3 me-2">
            <i class="bi bi-search"></i> Check for Matches (<?php echo $approvedCount; ?>)
          </button>
        <?php endif; ?>
        <a href="all_lost.php" class="btn btn-warning me-2"><i class="bi bi-search"></i> Browse Lost Items</a>
        <a href="found_items.php" class="btn btn-light"><i class="bi bi-box-seam"></i> Browse Found Items</a>
      </div>
      <div class="col-md-4 text-center d-none d-md-block">
        <?php if (!empty($studentData['ProfilePhoto']) && isset($studentData['PhotoConfirmed']) && $studentData['PhotoConfirmed'] == 1): ?>
          <img src="../<?php echo htmlspecialchars($studentData['ProfilePhoto']); ?>" alt="Profile Photo" class="rounded-circle shadow-lg" style="width:120px;height:120px;object-fit:cover;border:4px solid #FFD700;">
        <?php else: ?>
          <i class="bi bi-person-circle" style="font-size:7rem;color:#FFD700;"></i>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($dashboardMsg): ?>
    <div class="alert alert-info mt-3 shadow-sm"> <?php echo htmlspecialchars($dashboardMsg); ?> </div>
  <?php endif; ?>
  
  <!-- Approval System Notice -->
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Admin Approval Required:</strong> All lost and found item reports require admin approval before being visible to other users. You can track the status of your reports in "My Reports".
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  
  <!-- Profile Photo Rejection Notice -->
  <?php if (isset($studentData['PhotoConfirmed']) && $studentData['PhotoConfirmed'] == -1): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>
      <strong>Profile Photo Rejected:</strong> Your profile photo was rejected by admin. Please upload a new photo in your profile page.
      <a href="profile.php" class="btn btn-outline-danger btn-sm ms-2">Upload New Photo</a>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  

  <div class="dashboard-cards">
    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 text-center">
          <div class="card-body py-4">
            <i class="bi bi-clipboard-data display-4 mb-3" style="color:var(--ub-maroon);"></i>
            <h5 class="card-title fw-bold mb-2">My Reports</h5>
            <p class="card-text mb-3">View and manage your lost item reports in one place.</p>
            <a href="my_reports.php" class="btn btn-primary w-75"><i class="bi bi-clipboard-data"></i> My Reports</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 text-center">
          <div class="card-body py-4">
            <i class="bi bi-search display-4 mb-3" style="color:var(--ub-maroon);"></i>
            <h5 class="card-title fw-bold mb-2">All Lost Items</h5>
            <p class="card-text mb-3">Browse approved lost items reported by students. Use filters to find your item.</p>
            <a href="all_lost.php" class="btn btn-warning w-75"><i class="bi bi-search"></i> Browse Lost</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 text-center">
          <div class="card-body py-4">
            <i class="bi bi-box-seam display-4 mb-3" style="color:var(--ub-maroon);"></i>
            <h5 class="card-title fw-bold mb-2">Found Items</h5>
            <p class="card-text mb-3">See approved items found and reported by admins. Maybe yours is here!</p>
            <a href="found_items.php" class="btn btn-light w-75"><i class="bi bi-box-seam"></i> Browse Found</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 text-center">
          <div class="card-body py-4">
            <i class="bi bi-envelope-paper-heart display-4 mb-3" style="color:var(--ub-maroon);"></i>
            <h5 class="card-title fw-bold mb-2">Contact Admin</h5>
            <p class="card-text mb-3">Need help? Send a message to the Lost & Found admin directly.</p>
            <a href="contact_admin.php" class="btn btn-primary w-75"><i class="bi bi-envelope"></i> Contact Admin</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 text-center">
          <div class="card-body py-4">
            <i class="bi bi-person display-4 mb-3" style="color:var(--ub-maroon);"></i>
            <h5 class="card-title fw-bold mb-2">My Profile</h5>
            <p class="card-text mb-3">View and update your personal information and profile photo.</p>
            <a href="profile.php" class="btn btn-warning w-75"><i class="bi bi-person"></i> My Profile</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Floating Chatbot Icon Button -->
<button id="chatbotIcon" class="chatbot-icon-btn" type="button" data-bs-toggle="modal" data-bs-target="#chatbotModal" title="Chat with Assistant">
  <i class="bi bi-robot"></i>
  <span class="chatbot-pulse"></span>
</button>

<!-- Chatbot Modal -->
<div class="modal fade" id="chatbotModal" tabindex="-1" aria-labelledby="chatbotModalLabel" aria-hidden="true">
  <div class="modal-dialog chatbot-modal-dialog">
    <div class="modal-content chatbot-modal-content">
      <div class="modal-header chatbot-modal-header">
        <div class="d-flex align-items-center">
          <i class="bi bi-robot me-2" style="font-size: 1.5rem; color: var(--ub-maroon);"></i>
          <div>
            <h5 class="modal-title mb-0" id="chatbotModalLabel">Help & Chat Assistant</h5>
            <small class="text-muted">Ask about reporting, tracking, or site help</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body chatbot-modal-body p-0">
        <div id="chatWindow" class="chat-window" aria-live="polite"></div>
      </div>
      <div class="modal-footer chatbot-modal-footer">
        <form id="chatForm" class="chat-form w-100" onsubmit="return false;">
          <div class="input-group">
            <input id="chatInput" class="form-control" type="text" placeholder="Type a message and press Enter" autocomplete="off">
            <button id="chatSend" class="btn btn-primary" type="button">
              <i class="bi bi-send"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Chatbot Styles -->
<style>
/* Floating Chatbot Icon Button */
.chatbot-icon-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--ub-maroon, #800000) 0%, #a83232 100%);
  color: #fff;
  border: none;
  box-shadow: 0 4px 12px rgba(128, 0, 0, 0.3);
  cursor: pointer;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  transition: all 0.3s ease;
}

.chatbot-icon-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(128, 0, 0, 0.4);
  background: linear-gradient(135deg, #a83232 0%, var(--ub-maroon, #800000) 100%);
}

.chatbot-icon-btn:active {
  transform: scale(0.95);
}

/* Pulse animation for chatbot icon */
.chatbot-pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

/* Chatbot Modal Styles */
.chatbot-modal-dialog {
  max-width: 450px;
  margin: 0;
  position: fixed;
  right: 20px;
  bottom: 100px;
  top: auto;
  transform: none;
  max-height: calc(100vh - 120px);
}

/* Override Bootstrap modal backdrop positioning */
.modal.show .chatbot-modal-dialog {
  transform: none;
}

.chatbot-modal-content {
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  max-height: calc(100vh - 140px);
  display: flex;
  flex-direction: column;
}

.chatbot-modal-header {
  background: linear-gradient(135deg, var(--ub-maroon, #800000) 0%, #a83232 100%);
  color: #fff;
  border-bottom: none;
  padding: 1rem 1.25rem;
}

.chatbot-modal-header .btn-close {
  filter: invert(1);
  opacity: 0.8;
}

.chatbot-modal-header .btn-close:hover {
  opacity: 1;
}

.chatbot-modal-body {
  padding: 0;
  flex: 1;
  min-height: 300px;
  max-height: 500px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-window {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: #f8f9fa;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.chat-message {
  max-width: 75%;
  padding: 10px 14px;
  border-radius: 12px;
  display: inline-block;
  word-wrap: break-word;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.chat-message--user {
  margin-left: auto;
  background: linear-gradient(135deg, var(--ub-maroon, #800000) 0%, #a83232 100%);
  color: #fff;
  border-bottom-right-radius: 4px;
}

.chat-message--ai {
  margin-right: auto;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-bottom-left-radius: 4px;
  color: #333;
}

.chatbot-modal-footer {
  border-top: 1px solid #e0e0e0;
  padding: 1rem;
  background: #fff;
}

.chat-form {
  margin: 0;
}

.chat-form .input-group {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-radius: 25px;
  overflow: hidden;
}

.chat-form .form-control {
  border: none;
  padding: 12px 20px;
  border-radius: 25px 0 0 25px;
}

.chat-form .form-control:focus {
  box-shadow: none;
  border: none;
}

.chat-form .btn {
  border-radius: 0 25px 25px 0;
  padding: 12px 20px;
  border: none;
}

/* Mobile Responsive */
@media (max-width: 640px) {
  .chatbot-icon-btn {
    width: 55px;
    height: 55px;
    bottom: 20px;
    right: 20px;
    font-size: 1.5rem;
  }
  
  .chatbot-modal-dialog {
    max-width: calc(100% - 40px);
    right: 20px;
    left: auto;
    bottom: 100px;
    top: auto;
  }
  
  .chatbot-modal-body {
    min-height: 300px;
    max-height: 400px;
  }
  
  .chat-message {
    max-width: 85%;
  }
}

/* Scrollbar styling for chat window */
.chat-window::-webkit-scrollbar {
  width: 6px;
}

.chat-window::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.chat-window::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.chat-window::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>

<!-- Chatbot frontend logic -->
<script>
(function(){
  const chatWindow = document.getElementById('chatWindow');
  const chatInput = document.getElementById('chatInput');
  const chatSend = document.getElementById('chatSend');
  const chatForm = document.getElementById('chatForm');

  // Helper to append messages
  function appendMessage(text, role){
    const el = document.createElement('div');
    el.className = 'chat-message ' + (role==='user' ? 'chat-message--user' : 'chat-message--ai');
    el.textContent = text;
    chatWindow.appendChild(el);
    chatWindow.scrollTop = chatWindow.scrollHeight - chatWindow.clientHeight;
  }

  // Send message to backend (fetch to a php endpoint you will create: php/chat_handler.php)
  async function sendMessage(msg){
    // append user message immediately
    appendMessage(msg, 'user');

    // show typing placeholder
    const placeholder = document.createElement('div');
    placeholder.className = 'chat-message chat-message--ai';
    placeholder.textContent = 'Typing…';
    chatWindow.appendChild(placeholder);
    chatWindow.scrollTop = chatWindow.scrollHeight;

    try{
      // change this URL to your server-side handler
      const res = await fetch('php/chat_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg })
      });

      if(!res.ok) throw new Error('Network response not ok');
      const data = await res.json();

      // remove placeholder and append real reply
      placeholder.remove();
      appendMessage(data.reply || 'No response from server', 'ai');
    }catch(err){
      placeholder.remove();
      appendMessage('Error contacting server. Try again later.', 'ai');
      console.error(err);
    }
  }

  // UI events
  chatSend.addEventListener('click', () => {
    const val = chatInput.value.trim();
    if(!val) return;
    sendMessage(val);
    chatInput.value = '';
    chatInput.focus();
  });

  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    chatSend.click();
  });

  // Add welcome message when modal opens
  const chatbotModal = document.getElementById('chatbotModal');
  if (chatbotModal) {
    chatbotModal.addEventListener('shown.bs.modal', function () {
      // Only show welcome message if chat window is empty
      if (chatWindow.children.length === 0) {
        appendMessage('Hello! I\'m here to help you with reporting lost items, tracking reports, or answering questions about the site. How can I assist you today?', 'ai');
      }
      // Auto-focus input when modal opens
      chatInput.focus();
    });
  }
})();
</script>

<!-- Report Lost Item Modal -->
<div class="modal fade" id="reportLostModal" tabindex="-1" aria-labelledby="reportLostModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="reportLostModalLabel">Report Lost Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="report_lost" value="1">
          <div class="mb-3">
            <label for="lostItemName" class="form-label">Item Name</label>
            <input type="text" class="form-control" id="lostItemName" name="lostItemName" required>
          </div>
          <div class="mb-3">
            <label for="lostItemClass" class="form-label">Item Class</label>
            <select class="form-select" id="lostItemClass" name="lostItemClass" required>
              <option value="" disabled selected>Select a category</option>
              <?php foreach ($itemClasses as $class): ?>
                <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="lostDescription" class="form-label">Description</label>
            <textarea class="form-control" id="lostDescription" name="lostDescription" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label for="lostDate" class="form-label">Date of Loss</label>
            <input type="date" class="form-control" id="lostDate" name="lostDate" required>
          </div>
          <div class="mb-3">
            <label for="lostLocation" class="form-label">Location Lost</label>
            <input type="text" class="form-control" id="lostLocation" name="lostLocation" required>
          </div>
          <div class="mb-3">
            <label for="lostPhoto" class="form-label">Photo (optional)</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="20971520">
            <input type="file" class="form-control" id="lostPhoto" name="lostPhoto" accept="image/*">
            <small class="form-text text-muted">Maximum file size: 20MB</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Submit Lost Report</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Report Found Item Modal -->
<div class="modal fade" id="reportFoundModal" tabindex="-1" aria-labelledby="reportFoundModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="reportFoundModalLabel">Report Found Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="report_found" value="1">
          <div class="mb-3">
            <label for="foundItemName" class="form-label">Item Name</label>
            <input type="text" class="form-control" id="foundItemName" name="foundItemName" required>
          </div>
          <div class="mb-3">
            <label for="foundItemClass" class="form-label">Item Class</label>
            <select class="form-select" id="foundItemClass" name="foundItemClass" required>
              <option value="" disabled selected>Select a category</option>
              <?php foreach ($itemClasses as $class): ?>
                <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="foundDescription" class="form-label">Description</label>
            <textarea class="form-control" id="foundDescription" name="foundDescription" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label for="foundDate" class="form-label">Date Found</label>
            <input type="date" class="form-control" id="foundDate" name="foundDate" required>
          </div>
          <div class="mb-3">
            <label for="foundLocation" class="form-label">Location Found</label>
            <input type="text" class="form-control" id="foundLocation" name="foundLocation" required>
          </div>
          <div class="mb-3">
            <label for="foundPhoto" class="form-label">Photo (optional)</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="20971520">
            <input type="file" class="form-control" id="foundPhoto" name="foundPhoto" accept="image/*">
            <small class="form-text text-muted">Maximum file size: 20MB</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Submit Found Report</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/notifications.js"></script>
  <script src="../assets/forms.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const checkMatchesBtn = document.getElementById('check-matches-btn');
  
  if (checkMatchesBtn) {
    checkMatchesBtn.addEventListener('click', function() {
      const originalText = this.innerHTML;
      const originalDisabled = this.disabled;
      
      // Disable button and show loading
      this.disabled = true;
      this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Checking...';
      
      // Make AJAX request to check all approved items
      fetch('check_matches.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '' // No report_id means check all
      })
      .then(response => response.json())
      .then(data => {
        // Re-enable button
        this.disabled = false;
        this.innerHTML = originalText;
        
        if (data.success) {
          const matchesCount = data.matchesFound || 0;
          const totalChecked = data.totalChecked || 0;
          
          let message = `✅ Match detection completed!\n\n`;
          message += `Checked ${totalChecked} approved lost item(s).\n\n`;
          
          if (matchesCount > 0) {
            message += `Found ${matchesCount} potential match(es)!\n\n`;
            message += `You will receive notifications and emails if matches are confirmed.`;
          } else {
            message += `No matches found at this time.\n\n`;
            message += `We'll continue checking automatically when new found items are added.`;
          }
          
          if (data.errors && data.errors.length > 0) {
            message += `\n\nNote: ${data.errors.length} item(s) encountered errors.`;
          }
          
          alert(message);
        } else {
          alert('❌ Error: ' + (data.message || 'Failed to check for matches'));
        }
      })
      .catch(error => {
        // Re-enable button
        this.disabled = false;
        this.innerHTML = originalText;
        alert('❌ Error: Failed to connect to match detection service');
        console.error('Error:', error);
      });
    });
  }
});
</script>
<?php include '../templates/footer.php'; ?>
</body>
</html> 