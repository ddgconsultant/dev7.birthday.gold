<?php
/**
 * AJAX handler for user activity logs
 * Returns paginated activity logs for a specific user
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check admin access
if (!$account->isadmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get parameters
$user_id = intval($_GET['user_id'] ?? 0);
$offset = intval($_GET['offset'] ?? 0);
$limit = intval($_GET['limit'] ?? 20);

// Validate
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// Ensure limit is reasonable
$limit = min($limit, 100);

try {
    // Build query
    $sql = "SELECT id, ip, name, site, page, create_dt 
            FROM bg_sessiontracking 
            WHERE user_id = :user_id " . 
            ($mode != 'dev' ? "AND site = 'www'" : "AND type = 'user'") . 
            " ORDER BY create_dt DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $database->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates for consistency
    foreach ($logs as &$log) {
        // Keep original format or convert as needed
        $log['create_dt'] = $log['create_dt'];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'count' => count($logs),
        'offset' => $offset,
        'limit' => $limit
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in user-activity-logs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>