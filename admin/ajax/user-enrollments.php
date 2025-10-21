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
    // Get enrollment statistics
    $stats_sql = "SELECT
                    status,
                    COUNT(*) as count
                  FROM bg_user_companies
                  WHERE user_id = :user_id
                  GROUP BY status
                  ORDER BY count DESC";

    $stats_raw = $database->getrows($stats_sql, ['user_id' => $user_id]);

    // Format statistics
    $stats = [];
    $total_enrollments = 0;
    foreach ($stats_raw as $stat) {
        $stats[$stat['status']] = intval($stat['count']);
        $total_enrollments += intval($stat['count']);
    }

    // Get enrollments with company details
    // Use subquery to get only one logo per company to avoid duplicates
    $enrollment_sql = "SELECT
                        uc.*,
                        c.company_name,
                        c.company_id,
                        c.display_category as company_category,
                        c.description as company_description,
                        (SELECT ca.description
                         FROM bg_company_attributes ca
                         WHERE ca.company_id = c.company_id
                           AND ca.category = 'company_logos'
                           AND ca.grouping = 'primary_logo'
                         LIMIT 1) as company_logo,
                        uc.create_dt,
                        uc.status
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
        'count' => count($enrollments),
        'stats' => $stats,
        'total_enrollments' => $total_enrollments
    ]);

} catch (PDOException $e) {
    error_log("Database error in user-enrollments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
