<?php
require_once __DIR__ . '/base.php';
require_once __DIR__ . '/../../classes/Item.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $item = new Item();
} catch (Exception $e) {
    jsonError('Database connection unavailable', 503);
}

switch ($method) {
    case 'GET':
        // Get query parameters
        $search = $_GET['search'] ?? null;
        $itemClass = $_GET['itemClass'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        try {
            if ($search) {
                // Search found items
                $items = $item->search($search, $itemClass);
            } else {
                // Get all approved items
                $items = $item->getAllApproved($limit, $offset);
            }
            
            jsonResponse([
                'success' => true,
                'data' => $items,
                'count' => count($items)
            ]);
        } catch (Exception $e) {
            error_log("API Items Error: " . $e->getMessage());
            jsonError('Failed to retrieve items', 500);
        }
        break;
    
    case 'POST':
        // Create new found item (for future use)
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            jsonError('Invalid JSON data', 400);
        }
        
        // Validate required fields
        $required = ['adminID', 'itemName', 'itemClass', 'description', 'dateFound', 'locationFound'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonError("Missing required field: {$field}", 400);
            }
        }
        
        try {
            $result = $item->create(
                $data['adminID'],
                $data['itemName'],
                $data['itemClass'],
                $data['description'],
                $data['dateFound'],
                $data['locationFound'],
                $data['photoURL'] ?? null
            );
            
            if ($result['success']) {
                jsonResponse($result, 201);
            } else {
                jsonError($result['message'] ?? 'Failed to create item', 400);
            }
        } catch (Exception $e) {
            error_log("API Create Item Error: " . $e->getMessage());
            jsonError('Failed to create item', 500);
        }
        break;
    
    default:
        jsonError('Method not allowed', 405);
        break;
}

