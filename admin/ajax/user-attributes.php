<?php
/**
 * AJAX handler for user attributes
 * Returns attribute summary and recent attributes for a specific user
 */

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
    // Get attributes summary
    $summary_sql = "SELECT type, COUNT(*) as count
                   FROM bg_user_attributes
                   WHERE user_id = :user_id AND status = 'active'
                   GROUP BY type";
    $summary = $database->getrows($summary_sql, ['user_id' => $user_id]);

    // Get recent attributes
    $recent_sql = "SELECT * FROM bg_user_attributes
                  WHERE user_id = :user_id
                  ORDER BY create_dt DESC
                  LIMIT 20";
    $recent = $database->getrows($recent_sql, ['user_id' => $user_id]);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'recent' => $recent
    ]);

} catch (PDOException $e) {
    error_log("Database error in user-attributes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
