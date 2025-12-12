<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ImageHelper.php';
session_start();
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
$student = $_SESSION['student'];
$db = new Database();
$conn = $db->getConnection();

// Initialize variables
$foundItems = [];
$itemClasses = ['Electronics', 'Books', 'Clothing', 'Bags', 'ID Cards', 'Keys', 'Others'];

// #region agent log
$logPath = __DIR__ . '/../../.cursor/debug.log';
$logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'location' => 'found_items.php:17', 'message' => 'DB connection check', 'data' => ['connIsNull' => ($conn === null)], 'timestamp' => time() * 1000];
file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
// #endregion

if ($conn === null) {
    // Database connection failed - use default item classes
    error_log("Found Items: Database connection unavailable");
} else {
    // Filtering logic for found items
    $foundWhere = [];
    $foundParams = [];
    if (!empty($_GET['found_keyword'])) {
      $foundWhere[] = '(i.Description LIKE :keyword OR i.ItemName LIKE :keyword)';
      $foundParams['keyword'] = '%' . $_GET['found_keyword'] . '%';
    }
    if (!empty($_GET['found_date'])) {
      $foundWhere[] = 'i.DateFound = :date';
      $foundParams['date'] = $_GET['found_date'];
    }
    if (!empty($_GET['found_class'])) {
      $foundWhere[] = 'c.ClassName LIKE :class';
      $foundParams['class'] = '%' . $_GET['found_class'] . '%';
    }
    $foundSql = 'SELECT i.ItemID, i.ItemName, c.ClassName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.CreatedAt, COALESCE(a.AdminName, "Unknown") as AdminName, COALESCE(a.Email, "") as Email FROM `item` i LEFT JOIN `itemclass` c ON i.ItemClassID = c.ItemClassID LEFT JOIN `admin` a ON i.AdminID = a.AdminID WHERE i.StatusConfirmed = 1';
    if ($foundWhere) {
      $foundSql .= ' AND ' . implode(' AND ', $foundWhere);
    }
    $foundSql .= ' ORDER BY i.CreatedAt DESC';
    
    // #region agent log
    $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'found_items.php:42', 'message' => 'Before query execution', 'data' => ['sql' => $foundSql, 'params' => $foundParams, 'filters' => $_GET], 'timestamp' => time() * 1000];
    file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
    // #endregion
    
    try {
        $stmt = $conn->prepare($foundSql);
        $stmt->execute($foundParams);
        $foundItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // #region agent log
        $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'found_items.php:50', 'message' => 'After query execution', 'data' => ['resultCount' => count($foundItems), 'isEmpty' => empty($foundItems), 'firstItem' => $foundItems[0] ?? null], 'timestamp' => time() * 1000];
        file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
        // #endregion
        
        // Diagnostic: Check if items exist but aren't showing
        if (empty($foundItems)) {
            $countStmt = $conn->prepare('SELECT COUNT(*) FROM `item` WHERE StatusConfirmed = 1');
            $countStmt->execute();
            $totalApproved = $countStmt->fetchColumn();
            
            // #region agent log
            $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'found_items.php:58', 'message' => 'Empty results diagnostic', 'data' => ['totalApproved' => $totalApproved, 'queryReturnedZero' => true], 'timestamp' => time() * 1000];
            file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
            // #endregion
            
            if ($totalApproved > 0) {
                error_log("Found Items Warning: Query returned 0 results but $totalApproved approved items exist. Possible data integrity issue.");
            }
        }
    } catch (Exception $e) {
        // #region agent log
        $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'C', 'location' => 'found_items.php:66', 'message' => 'Query exception', 'data' => ['error' => $e->getMessage(), 'sql' => $foundSql], 'timestamp' => time() * 1000];
        file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
        // #endregion
        
        error_log("Found Items SQL Error: " . $e->getMessage());
        error_log("Found Items SQL Query: " . $foundSql);
        error_log("Found Items SQL Params: " . print_r($foundParams, true));
        $foundItems = [];
    }

    // Fetch item classes for dropdown
    try {
        require_once __DIR__ . '/../classes/Item.php';
        $itemObj = new Item();
        $fetchedClasses = $itemObj->getItemClasses();
        if (!empty($fetchedClasses)) {
            $itemClasses = $fetchedClasses;
        }
    } catch (Exception $e) {
        error_log("Found Items: Failed to load Item class - " . $e->getMessage());
        // Use default item classes already set above
    }
}

// #region agent log
$logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'found_items.php:88', 'message' => 'Before rendering check', 'data' => ['itemsCount' => count($foundItems), 'countResult' => count($foundItems) > 0, 'isArray' => is_array($foundItems)], 'timestamp' => time() * 1000];
file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
// #endregion
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Found Items - UB Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
  $ubCssFile = file_exists(__DIR__ . '/../assets/UB.css') ? 'UB.css' : 'ub.css';
  ?>
  <link href="css.php?file=<?php echo urlencode($ubCssFile); ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../templates/header.php'; ?>
<div class="container py-4">
  <div class="row mb-4">
    <div class="col-lg-8 mx-auto">
      <div class="card shadow-sm p-4 mb-3" style="background: #f6fff9;">
        <form class="row g-3" method="GET" action="found_items.php">
          <div class="col-md-4">
            <input type="text" class="form-control" name="found_keyword" placeholder="Keyword" value="<?php echo htmlspecialchars($_GET['found_keyword'] ?? ''); ?>">
          </div>
          <div class="col-md-3">
            <input type="date" class="form-control" name="found_date" value="<?php echo htmlspecialchars($_GET['found_date'] ?? ''); ?>">
          </div>
          <div class="col-md-3">
            <select class="form-select" name="found_class">
              <option value="" selected>Select class</option>
              <?php foreach ($itemClasses as $class): ?>
                <option value="<?php echo htmlspecialchars($class); ?>" <?php if ((isset($_GET['found_class']) && $_GET['found_class'] === $class)) echo 'selected'; ?>><?php echo htmlspecialchars($class); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="row g-4">
    <?php if (count($foundItems) > 0): ?>
      <?php 
      // #region agent log
      $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'D', 'location' => 'found_items.php:133', 'message' => 'Rendering branch taken', 'data' => ['itemsCount' => count($foundItems)], 'timestamp' => time() * 1000];
      file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
      // #endregion
      foreach ($foundItems as $idx => $item): ?>
        <div class="col-md-4 col-lg-3">
          <div class="card h-100 shadow-sm">
            <?php if ($item['PhotoURL']): ?>
              <img src="../<?php echo encodeImageUrl($item['PhotoURL']); ?>" class="card-img-top" alt="Found Item Image" style="object-fit:cover;max-height:180px;" onerror="<?php echo getImageErrorHandler(); ?>">
            <?php else: ?>
              <img src="<?php echo getPlaceholderImage(); ?>" class="card-img-top" alt="No Image">
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-shield-check me-2" style="font-size:1.2rem;color:#28a745;"></i>
                <small class="text-muted"><?php echo htmlspecialchars($item['AdminName'] ?? 'Unknown'); ?> (Admin)</small>
              </div>
              <h5 class="card-title mb-1"><?php echo htmlspecialchars($item['ItemName'] ?? $item['ClassName'] ?? 'N/A'); ?></h5>
              <p class="card-text small mb-2 text-muted"><?php echo htmlspecialchars($item['ClassName'] ?? 'N/A'); ?></p>
              <p class="card-text small mb-2"><?php echo htmlspecialchars(mb_strimwidth($item['Description'] ?? '', 0, 60, '...')); ?></p>
              <button class="btn btn-primary mt-auto" data-bs-toggle="modal" data-bs-target="#foundModal<?php echo $idx; ?>">View Details</button>
            </div>
          </div>
        </div>
        <!-- Modal for found item details -->
        <div class="modal fade" id="foundModal<?php echo $idx; ?>" tabindex="-1" aria-labelledby="foundModalLabel<?php echo $idx; ?>" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="foundModalLabel<?php echo $idx; ?>">Found Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php if ($item['PhotoURL']): ?>
                  <img src="../<?php echo encodeImageUrl($item['PhotoURL']); ?>" class="img-fluid mb-3" alt="Found Item Image" onerror="<?php echo getImageErrorHandler(); ?>">
                <?php endif; ?>
                <ul class="list-group list-group-flush mb-2">
                  <li class="list-group-item"><strong>Item Name:</strong> <?php echo htmlspecialchars($item['ItemName'] ?? 'N/A'); ?></li>
                  <li class="list-group-item"><strong>Class:</strong> <?php echo htmlspecialchars($item['ClassName'] ?? 'N/A'); ?></li>
                  <li class="list-group-item"><strong>Description:</strong> <?php echo htmlspecialchars($item['Description'] ?? 'N/A'); ?></li>
                  <li class="list-group-item"><strong>Date Found:</strong> <?php echo htmlspecialchars($item['DateFound'] ?? 'N/A'); ?></li>
                  <li class="list-group-item"><strong>Location Found:</strong> <?php echo htmlspecialchars($item['LocationFound'] ?? 'N/A'); ?></li>
                  <li class="list-group-item"><strong>Reported By:</strong> <?php echo htmlspecialchars($item['AdminName'] ?? 'Unknown'); ?></li>
                  <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($item['Email'] ?? 'N/A'); ?></li>
                </ul>
                <div class="text-end text-muted small">Reported at: <?php echo htmlspecialchars($item['CreatedAt'] ?? 'N/A'); ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <?php 
      // #region agent log
      $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'D', 'location' => 'found_items.php:178', 'message' => 'Empty branch taken', 'data' => ['itemsCount' => count($foundItems)], 'timestamp' => time() * 1000];
      file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
      // #endregion
      ?>
      <div class="col-12"><p class="text-muted">No found items reported yet.</p></div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/notifications.js"></script>
</body>
</html>
