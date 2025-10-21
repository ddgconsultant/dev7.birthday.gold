<?php
/**
 * AJAX handler for user allocations
 * Returns allocation data for a specific user
 */

$addClasses[] = 'allocationmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get parameters
$user_id = intval($_GET['user_id'] ?? 0);

// Validate
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

try {
    // Get user's current allocation balance
    $balance = $allocationmanager->getUserBalance($user_id);

    // Get allocations from bg_user_allocations
    $alloc_sql = "SELECT * FROM bg_user_allocations
                  WHERE user_id = :user_id
                  ORDER BY created_at DESC";
    $allocations = $database->getrows($alloc_sql, ['user_id' => $user_id]);

    // Get enrollment statistics for "total used" calculation
    $stats_sql = "SELECT COUNT(*) as total_used
                  FROM bg_user_companies
                  WHERE user_id = :user_id
                  AND status NOT IN ('failed', 'removed')";
    $enrollment_stats = $database->getrow($stats_sql, ['user_id' => $user_id]);

    // Update balance with actual usage
    $balance['total_used'] = $enrollment_stats['total_used'] ?? 0;

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'allocations' => $allocations,
        'balance' => $balance,
        'count' => count($allocations)
    ]);

} catch (PDOException $e) {
    error_log("Database error in user-allocations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
