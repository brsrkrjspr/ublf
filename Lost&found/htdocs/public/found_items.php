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
$stats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'null_status' => 0, 'rejected' => 0];
$allItems = [];
$typeCheck = [];
$dbDiagnostics = ['database' => 'unknown', 'table_exists' => false, 'direct_count' => 0, 'error' => null];

if ($conn === null) {
    // Database connection failed - use default item classes
    error_log("Found Items: Database connection unavailable");
} else {
    // Comprehensive database diagnostics BEFORE queries
    try {
        // Check database name
        $dbNameStmt = $conn->query('SELECT DATABASE() as dbname');
        $dbNameResult = $dbNameStmt->fetch(PDO::FETCH_ASSOC);
        $dbDiagnostics['database'] = $dbNameResult['dbname'] ?? 'unknown';
        
        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'item'");
        $dbDiagnostics['table_exists'] = $tableCheck->rowCount() > 0;
        
        // Direct count query (no JOINs, no WHERE clauses)
        if ($dbDiagnostics['table_exists']) {
            $countStmt = $conn->query("SELECT COUNT(*) as cnt FROM `item`");
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            $dbDiagnostics['direct_count'] = (int)($countResult['cnt'] ?? 0);
        }
    } catch (Exception $e) {
        $dbDiagnostics['error'] = $e->getMessage();
        error_log("Found Items: Database diagnostics error - " . $e->getMessage());
    }
    
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
    // Query for approved found items - matches pattern from all_lost.php
    $foundSql = 'SELECT i.ItemID, i.ItemName, c.ClassName, i.Description, i.DateFound, i.LocationFound, i.PhotoURL, i.CreatedAt, COALESCE(a.AdminName, "Unknown") as AdminName, COALESCE(a.Email, "N/A") as Email FROM `item` i LEFT JOIN `itemclass` c ON i.ItemClassID = c.ItemClassID LEFT JOIN `admin` a ON i.AdminID = a.AdminID WHERE i.StatusConfirmed = 1';
    if ($foundWhere) {
      $foundSql .= ' AND ' . implode(' AND ', $foundWhere);
    }
    $foundSql .= ' ORDER BY i.CreatedAt DESC';
    
    // #region agent log
    $logPath = __DIR__ . '/../.cursor/debug.log';
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_found_query','timestamp'=>time()*1000,'location'=>'found_items.php:36','message'=>'Found items query prepared','data'=>['sql'=>$foundSql,'params'=>$foundParams],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
    // #endregion
    
    try {
        $stmt = $conn->prepare($foundSql);
        $stmt->execute($foundParams);
        $foundItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // #region agent log
        $logPath = __DIR__ . '/../.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_found_results','timestamp'=>time()*1000,'location'=>'found_items.php:45','message'=>'Found items query executed','data'=>['resultCount'=>count($foundItems),'firstItem'=>$foundItems[0]??null],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
        
        // Check database state
        $checkStmt = $conn->prepare('SELECT ItemID, StatusConfirmed, ItemClassID, AdminID, ItemName FROM `item` ORDER BY ItemID DESC LIMIT 5');
        $checkStmt->execute();
        $allItems = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_db_state','timestamp'=>time()*1000,'location'=>'found_items.php:50','message'=>'Database state check','data'=>['allItems'=>$allItems],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
        
        $statsStmt = $conn->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN StatusConfirmed = 1 OR StatusConfirmed = "1" THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN StatusConfirmed = 0 OR StatusConfirmed = "0" THEN 1 ELSE 0 END) as pending FROM `item`');
        $statsStmt->execute();
        $statsResult = $statsStmt->fetch(PDO::FETCH_ASSOC);
        $stats = $statsResult ?: ['total' => 0, 'approved' => 0, 'pending' => 0, 'null_status' => 0, 'rejected' => 0];
        
        // Also check the actual StatusConfirmed values
        $typeCheckStmt = $conn->prepare('SELECT ItemID, ItemName, StatusConfirmed FROM `item` ORDER BY ItemID DESC LIMIT 10');
        $typeCheckStmt->execute();
        $typeCheck = $typeCheckStmt->fetchAll(PDO::FETCH_ASSOC);
        
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_stats','timestamp'=>time()*1000,'location'=>'found_items.php:55','message'=>'Item statistics','data'=>['stats'=>$stats,'typeCheck'=>$typeCheck],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
        // #endregion
        
        // Debug: Log if no items found
        if (empty($foundItems)) {
            error_log("Found Items: No approved items found. Query: " . $foundSql);
            // Check if there are any items at all (approved or not)
            $checkStmt = $conn->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN StatusConfirmed = 1 THEN 1 ELSE 0 END) as approved FROM `item`');
            $checkStmt->execute();
            $stats = $checkStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Found Items Stats: Total items: " . ($stats['total'] ?? 0) . ", Approved: " . ($stats['approved'] ?? 0));
        }
    } catch (PDOException $e) {
        // #region agent log
        $logPath = __DIR__ . '/../.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_sql_error','timestamp'=>time()*1000,'location'=>'found_items.php:57','message'=>'SQL error','data'=>['error'=>$e->getMessage(),'sql'=>$foundSql],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E'])."\n", FILE_APPEND);
        // #endregion
        error_log("Found Items SQL Error: " . $e->getMessage() . " | SQL: " . $foundSql);
        $foundItems = [];
        $stats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'null_status' => 0, 'rejected' => 0, 'error' => $e->getMessage()];
    } catch (Exception $e) {
        // #region agent log
        $logPath = __DIR__ . '/../.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logPath, json_encode(['id'=>'log_'.time().'_exception','timestamp'=>time()*1000,'location'=>'found_items.php:60','message'=>'Exception','data'=>['error'=>$e->getMessage()],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E'])."\n", FILE_APPEND);
        // #endregion
        error_log("Found Items Error: " . $e->getMessage());
        $foundItems = [];
        $stats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'null_status' => 0, 'rejected' => 0, 'error' => $e->getMessage()];
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
  <!-- Debug Information (remove after fixing) -->
  <?php if ($conn): ?>
    <div class="row mb-3">
      <div class="col-12">
        <div class="alert alert-info">
          <strong>Debug Info:</strong><br>
          <strong style="color:blue;">Database Connection Diagnostics:</strong><br>
          - Connected to database: <strong><?php echo htmlspecialchars($dbDiagnostics['database']); ?></strong><br>
          - Table 'item' exists: <strong><?php echo $dbDiagnostics['table_exists'] ? 'YES' : 'NO'; ?></strong><br>
          - Direct COUNT(*) query: <strong><?php echo $dbDiagnostics['direct_count']; ?></strong> items<br>
          <?php if ($dbDiagnostics['error']): ?>
            - <strong style="color:red;">Diagnostics Error: <?php echo htmlspecialchars($dbDiagnostics['error']); ?></strong><br>
          <?php endif; ?>
          <hr>
          <strong style="color:blue;">Statistics Query Results:</strong><br>
          Total items in database: <?php echo $stats['total'] ?? 0; ?><br>
          Approved (StatusConfirmed=1): <?php echo $stats['approved'] ?? 0; ?><br>
          Pending (StatusConfirmed=0): <?php echo $stats['pending'] ?? 0; ?><br>
          Rejected (StatusConfirmed=-1): <?php echo $stats['rejected'] ?? 0; ?><br>
          NULL StatusConfirmed: <?php echo $stats['null_status'] ?? 0; ?><br>
          <hr>
          <strong style="color:blue;">Main Query Results:</strong><br>
          Query returned: <?php echo count($foundItems); ?> items<br>
          <?php if (isset($stats['error'])): ?>
            <strong style="color:red;">Query Error: <?php echo htmlspecialchars($stats['error']); ?></strong><br>
          <?php endif; ?>
          <?php if (isset($allItems) && !empty($allItems)): ?>
            <strong>Recent 5 items in DB:</strong><br>
            <?php foreach ($allItems as $item): ?>
              - ID: <?php echo $item['ItemID']; ?>, Name: <?php echo htmlspecialchars($item['ItemName']); ?>, StatusConfirmed: <?php echo var_export($item['StatusConfirmed'], true); ?> (type: <?php echo gettype($item['StatusConfirmed']); ?>)<br>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if (isset($typeCheck) && !empty($typeCheck)): ?>
            <strong>StatusConfirmed values:</strong><br>
            <?php foreach ($typeCheck as $item): ?>
              - ID: <?php echo $item['ItemID']; ?>, Status: <?php echo var_export($item['StatusConfirmed'], true); ?><br>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
  
  <div class="row g-4">
    <?php if (count($foundItems) > 0): ?>
      <?php foreach ($foundItems as $idx => $item): ?>
        <div class="col-md-4 col-lg-3">
          <div class="card h-100 shadow-sm">
            <?php if ($item['PhotoURL']): ?>
              <img src="<?php echo getImagePath($item['PhotoURL']); ?>" class="card-img-top" alt="Found Item Image" style="object-fit:cover;max-height:180px;" onerror="<?php echo getImageErrorHandler(); ?>">
            <?php else: ?>
              <img src="<?php echo getPlaceholderImage(); ?>" class="card-img-top" alt="No Image">
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-shield-check me-2" style="font-size:1.2rem;color:#28a745;"></i>
                <small class="text-muted"><?php echo htmlspecialchars($item['AdminName']); ?> (Admin)</small>
              </div>
              <h5 class="card-title mb-1"><?php echo htmlspecialchars($item['ClassName']); ?></h5>
              <p class="card-text small mb-2"><?php echo htmlspecialchars(mb_strimwidth($item['Description'], 0, 60, '...')); ?></p>
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
                  <img src="<?php echo getImagePath($item['PhotoURL']); ?>" class="img-fluid mb-3" alt="Found Item Image" onerror="<?php echo getImageErrorHandler(); ?>">
                <?php endif; ?>
                <ul class="list-group list-group-flush mb-2">
                  <li class="list-group-item"><strong>Class:</strong> <?php echo htmlspecialchars($item['ClassName']); ?></li>
                  <li class="list-group-item"><strong>Description:</strong> <?php echo htmlspecialchars($item['Description']); ?></li>
                  <li class="list-group-item"><strong>Date Found:</strong> <?php echo htmlspecialchars($item['DateFound']); ?></li>
                  <li class="list-group-item"><strong>Location Found:</strong> <?php echo htmlspecialchars($item['LocationFound']); ?></li>
                  <li class="list-group-item"><strong>Reported By:</strong> <?php echo htmlspecialchars($item['AdminName']); ?></li>
                  <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($item['Email']); ?></li>
                </ul>
                <div class="text-end text-muted small">Reported at: <?php echo htmlspecialchars($item['CreatedAt']); ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12"><p class="text-muted">No found items reported yet.</p></div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/notifications.js"></script>
<?php include '../templates/footer.php'; ?>
</body>
</html> 