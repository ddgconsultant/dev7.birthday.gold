<?php
// Include site controller
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set response header
header('Content-Type: application/json');

// Check if user is logged in
if (!$account->isactive()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get the tokens from POST
$tokens = isset($_POST['tokens']) ? json_decode($_POST['tokens'], true) : [];

// If no tokens, return 0
if (empty($tokens)) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

// Build SQL query based on tokens
$where_conditions = [];
$params = [];

// Start with base query for active users
$base_where = "user_status = 'active' AND user_email_verified = 1";

// Check if "all" token exists
$has_all = false;
foreach ($tokens as $token) {
    if ($token['type'] === 'all') {
        $has_all = true;
        break;
    }
}

// If "all" is selected, just count all active users
if ($has_all) {
    $sql = "SELECT COUNT(DISTINCT user_id) as count FROM bg_user WHERE $base_where";
    $result = $database->query($sql);
    $count = $result['count'] ?? 0;
    
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// Otherwise, build complex query based on tokens
$current_conditions = [];
$current_operator = 'OR'; // Default operator

foreach ($tokens as $token) {
    if ($token['type'] === 'operator') {
        // Handle operators (AND, OR, NOT)
        $current_operator = $token['value'];
        continue;
    }
    
    if ($token['type'] === 'parenthesis') {
        // Skip parentheses for now - would need more complex parsing
        continue;
    }
    
    // Build condition based on token type
    $condition = '';
    switch ($token['type']) {
        case 'account_type':
            if ($token['value'] === 'real') {
                $condition = "user_type = 'user'";
            } else {
                $condition = "user_type = :type_" . count($params);
                $params['type_' . count($params)] = $token['value'];
            }
            break;
            
        case 'gender':
            $condition = "user_gender = :gender_" . count($params);
            $params['gender_' . count($params)] = $token['value'];
            break;
            
        case 'birthday_month':
            $condition = "MONTH(user_dob) = :month_" . count($params);
            $params['month_' . count($params)] = intval($token['value']);
            break;
            
        case 'state':
            $condition = "user_state = :state_" . count($params);
            $params['state_' . count($params)] = $token['value'];
            break;
            
        case 'age_range':
            // Parse age range (e.g., "25-34")
            $parts = explode('-', $token['value']);
            if (count($parts) === 2) {
                $min_age = intval($parts[0]);
                $max_age = ($parts[1] === '+') ? 150 : intval($parts[1]);
                
                $condition = "TIMESTAMPDIFF(YEAR, user_dob, CURDATE()) BETWEEN :min_age_" . count($params) . " AND :max_age_" . count($params);
                $params['min_age_' . count($params)] = $min_age;
                $params['max_age_' . count($params)] = $max_age;
            }
            break;
            
        case 'plan':
            // Map plan to subscription status
            if ($token['value'] === 'free') {
                $condition = "(user_subscription_status IS NULL OR user_subscription_status = 'inactive')";
            } else {
                $condition = "user_subscription_plan = :plan_" . count($params);
                $params['plan_' . count($params)] = $token['value'];
            }
            break;
            
        case 'profile_completeness':
            // This would need a calculation of profile fields
            // For now, use a simplified version
            $parts = explode('-', $token['value']);
            if ($token['value'] === '100') {
                $condition = "user_profile_complete = 1";
            } else {
                // Simplified - check if key fields are filled
                $condition = "1=1"; // Placeholder
            }
            break;
            
        case 'enrollment_count':
            // Would need to join with enrollments table
            // For now, return a placeholder
            $condition = "1=1";
            break;
            
        case 'business_category':
            // Would need to join with business interests
            // For now, return a placeholder  
            $condition = "1=1";
            break;
    }
    
    if ($condition) {
        $current_conditions[] = $condition;
    }
}

// Combine conditions
if (!empty($current_conditions)) {
    $combined = '(' . implode(' OR ', $current_conditions) . ')';
    $where_conditions[] = $combined;
}

// Build final query
$final_where = $base_where;
if (!empty($where_conditions)) {
    $final_where .= ' AND (' . implode(' AND ', $where_conditions) . ')';
}

$sql = "SELECT COUNT(DISTINCT user_id) as count FROM bg_user WHERE $final_where";

try {
    if (empty($params)) {
        $result = $database->query($sql);
    } else {
        $result = $database->query($sql, $params);
    }
    
    $count = $result['count'] ?? 0;
    
    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $e) {
    // Log error but return a reasonable response
    error_log('Newsletter recipient count error: ' . $e->getMessage());
    
    // Return a default count instead of error
    echo json_encode(['success' => true, 'count' => 0]);
}
?>