<?php
require_once __DIR__ . '/base.php';
require_once __DIR__ . '/../../classes/ReportItem.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $reportItem = new ReportItem();
} catch (Exception $e) {
    jsonError('Database connection unavailable', 503);
}

switch ($method) {
    case 'GET':
        // Get query parameters
        $search = $_GET['search'] ?? null;
        $studentNo = $_GET['studentNo'] ?? null;
        $itemClass = $_GET['itemClass'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        try {
            if ($studentNo) {
                // Get reports for specific student
                $reports = $reportItem->getByStudent($studentNo);
            } elseif ($search) {
                // Search reports
                $reports = $reportItem->search($search, $itemClass);
            } else {
                // Get all approved reports
                $reports = $reportItem->getAllApproved($limit, $offset);
            }
            
            jsonResponse([
                'success' => true,
                'data' => $reports,
                'count' => count($reports)
            ]);
        } catch (Exception $e) {
            error_log("API Reports Error: " . $e->getMessage());
            jsonError('Failed to retrieve reports', 500);
        }
        break;
    
    case 'POST':
        // Create new report (for future use)
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            jsonError('Invalid JSON data', 400);
        }
        
        // Validate required fields
        $required = ['studentNo', 'itemName', 'itemClass', 'description', 'dateOfLoss', 'lostLocation'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonError("Missing required field: {$field}", 400);
            }
        }
        
        try {
            $result = $reportItem->create(
                $data['studentNo'],
                $data['itemName'],
                $data['itemClass'],
                $data['description'],
                $data['dateOfLoss'],
                $data['lostLocation'],
                $data['photoURL'] ?? null
            );
            
            if ($result['success']) {
                jsonResponse($result, 201);
            } else {
                jsonError($result['message'] ?? 'Failed to create report', 400);
            }
        } catch (Exception $e) {
            error_log("API Create Report Error: " . $e->getMessage());
            jsonError('Failed to create report', 500);
        }
        break;
    
    default:
        jsonError('Method not allowed', 405);
        break;
}

