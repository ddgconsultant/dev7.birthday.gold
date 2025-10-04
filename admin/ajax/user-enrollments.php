<?php
/**
 * AJAX handler for user enrollments
 * Returns enrollment data for a specific user
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
    // Get enrollments
    $enrollment_sql = "SELECT uc.*, c.company_name
                      FROM bg_user_companies uc
                      JOIN bg_companies c ON uc.company_id = c.company_id
                      WHERE uc.user_id = :user_id
                      ORDER BY uc.create_dt DESC
                      LIMIT 100";

    $enrollments = $database->getrows($enrollment_sql, ['user_id' => $user_id]);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'enrollments' => $enrollments,
        'count' => count($enrollments)
    ]);

} catch (PDOException $e) {
    error_log("Database error in user-enrollments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
