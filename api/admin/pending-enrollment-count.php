<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

// Validate API token
$auth_token = $_GET['auth_token'] ?? $_POST['auth_token'] ?? '';
if (empty($auth_token) || !$app->validateAPItoken($auth_token)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired auth token']);
    exit;
}

// Query pending enrollments (adapted from enrollment-listv2.php)
$sql = "
SELECT
    u.user_id,
    CONCAT(u.first_name, ' ', u.last_name) AS name,
    u.email,
    DATE_FORMAT(u.birthdate, '%Y-%m-%d') AS birthdate,
    SUM(CASE WHEN uc.status IN ('selected', 'pending') AND c.signup_url != '" . $website['apponlytag'] . "' THEN 1 ELSE 0 END) AS pending
FROM bg_user_companies uc
INNER JOIN bg_users u ON uc.user_id = u.user_id AND u.status = 'active'
INNER JOIN bg_companies c ON c.company_id = uc.company_id
WHERE
    c.status IN ('finalized')
    AND u.create_dt >= '2023-08-01'
    AND uc.create_dt >= '2023-08-01'
    AND NOT (uc.status LIKE '%failed%' AND LOWER(uc.reason) LIKE '%account%exists%')
    AND u.type = 'real'
GROUP BY u.user_id
HAVING SUM(CASE WHEN uc.status IN ('selected', 'pending') THEN 1 ELSE 0 END) > 0
ORDER BY
    CASE
        WHEN MONTH(u.birthdate) > MONTH(CURDATE()) OR
             (MONTH(u.birthdate) = MONTH(CURDATE()) AND DAY(u.birthdate) >= DAY(CURDATE()))
        THEN 0
        ELSE 1
    END,
    MONTH(u.birthdate),
    DAY(u.birthdate)
";

try {
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'pending_count' => count($users),
        'timestamp' => date('c'),
        'users' => $users
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $mode == 'dev' ? $e->getMessage() : 'Database error'
    ]);
}
?>