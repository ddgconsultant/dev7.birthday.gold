<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

// Parse criteria from POST
$criteria = isset($_POST['criteria']) ? json_decode($_POST['criteria'], true) : null;

if (!$criteria || !isset($criteria['type'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid criteria']);
    exit;
}

$count = 0;
$details = '';
$sql = '';
$params = [];

// Build SQL based on criteria type
switch ($criteria['type']) {
    case 'all':
        $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                FROM bg_users u 
                WHERE u.status = 'active' 
                AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                AND u.email IS NOT NULL 
                AND u.email != ''";
        $details = "All active users with valid email addresses";
        break;
        
    case 'birthday_month':
        $month = isset($criteria['month']) ? intval($criteria['month']) : date('n');
        $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                FROM bg_users u 
                WHERE u.status = 'active' 
                AND MONTH(u.birthdate) = :month
                AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                AND u.email IS NOT NULL 
                AND u.email != ''";
        $params['month'] = $month;
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $details = "Users with birthdays in $monthName";
        break;
        
    case 'plan':
        $plans = isset($criteria['plans']) && is_array($criteria['plans']) ? $criteria['plans'] : [];
        if (empty($plans)) {
            echo json_encode(['success' => true, 'count' => 0, 'details' => 'No plans selected']);
            exit;
        }
        
        // Build plan conditions
        $planConditions = [];
        foreach ($plans as $i => $plan) {
            $planConditions[] = "u.account_plan = :plan_$i";
            $params["plan_$i"] = $plan;
        }
        
        $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                FROM bg_users u 
                WHERE u.status = 'active' 
                AND (" . implode(' OR ', $planConditions) . ")
                AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                AND u.email IS NOT NULL 
                AND u.email != ''";
        $planNames = array_map('ucfirst', $plans);
        $details = "Users on " . implode(', ', $planNames) . " plans";
        break;
        
    case 'enrollment':
        $range = isset($criteria['range']) ? $criteria['range'] : '0';
        
        if ($range === '0') {
            $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                    FROM bg_users u 
                    LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = 'active'
                    WHERE u.status = 'active' 
                    AND e.enrollment_id IS NULL
                    AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                    AND u.email IS NOT NULL 
                    AND u.email != ''";
            $details = "New users with no enrollments";
        } elseif ($range === '1-5') {
            $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                    FROM bg_users u 
                    INNER JOIN (
                        SELECT user_id, COUNT(*) as enrollment_count
                        FROM bg_user_enrollments
                        WHERE status = 'active'
                        GROUP BY user_id
                        HAVING enrollment_count BETWEEN 1 AND 5
                    ) e ON u.user_id = e.user_id
                    WHERE u.status = 'active'
                    AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                    AND u.email IS NOT NULL 
                    AND u.email != ''";
            $details = "Users with 1-5 enrollments";
        } elseif ($range === '6-10') {
            $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                    FROM bg_users u 
                    INNER JOIN (
                        SELECT user_id, COUNT(*) as enrollment_count
                        FROM bg_user_enrollments
                        WHERE status = 'active'
                        GROUP BY user_id
                        HAVING enrollment_count BETWEEN 6 AND 10
                    ) e ON u.user_id = e.user_id
                    WHERE u.status = 'active'
                    AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                    AND u.email IS NOT NULL 
                    AND u.email != ''";
            $details = "Users with 6-10 enrollments";
        } else { // 11+
            $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                    FROM bg_users u 
                    INNER JOIN (
                        SELECT user_id, COUNT(*) as enrollment_count
                        FROM bg_user_enrollments
                        WHERE status = 'active'
                        GROUP BY user_id
                        HAVING enrollment_count >= 11
                    ) e ON u.user_id = e.user_id
                    WHERE u.status = 'active'
                    AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                    AND u.email IS NOT NULL 
                    AND u.email != ''";
            $details = "Power users with 11+ enrollments";
        }
        break;
        
    case 'test':
        $sql = "SELECT COUNT(DISTINCT u.user_id) as count 
                FROM bg_users u 
                INNER JOIN bg_user_attributes ua ON u.user_id = ua.user_id
                WHERE u.status = 'active' 
                AND ua.type = 'staff' 
                AND ua.status = 'active'
                AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')
                AND u.email IS NOT NULL 
                AND u.email != ''";
        $details = "Staff members only (for testing)";
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid recipient type']);
        exit;
}

// Execute query
try {
    // Debug SQL for testing
    if (!$sql) {
        echo json_encode([
            'success' => false,
            'message' => 'No SQL query generated',
            'criteria' => $criteria
        ]);
        exit;
    }
    
    $result = $database->getrow($sql, $params);
    
    // Check if result is valid
    if ($result === false || $result === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Query returned no results',
            'sql' => $sql,
            'params' => $params
        ]);
        exit;
    }
    
    $count = isset($result['count']) ? intval($result['count']) : 0;
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'details' => $details
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'sql' => $sql,
        'params' => $params
    ]);
}
?>