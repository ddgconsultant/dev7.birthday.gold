<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set JSON header
header('Content-Type: application/json');

// Check if user is admin or has Claude Code auth
$isAuthorized = false;

// Check Claude Code authentication
if (isset($_SERVER['HTTP_X_CLAUDE_CODE_KEY']) && $mode == 'dev') {
    $claude_key = $_SERVER['HTTP_X_CLAUDE_CODE_KEY'];
    if (isset($sitesettings['app']['CLAUDE_CODE_AUTH_KEY']) && $claude_key == $sitesettings['app']['CLAUDE_CODE_AUTH_KEY']) {
        $isAuthorized = true;
    }
}

// Check regular admin authentication
if (!$isAuthorized && $account->isadmin()) {
    $isAuthorized = true;
}

if (!$isAuthorized) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get parameters
$offset = (int)($_GET['offset'] ?? 0);
$limit = (int)($_GET['limit'] ?? 50);
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$plan = $_GET['plan'] ?? '';
$type = $_GET['type'] ?? '';
$days = $_GET['days'] ?? '180';

// Build query conditions
$conditions = [];
$params = [];

// Enhanced search functionality - search across more fields
if ($search) {
    $searchLike = "%$search%";
    $searchConditions = [];

    // Search across multiple fields
    $searchFields = ['first_name', 'last_name', 'username', 'email', 'city', 'state', 'zip_code', 'status', 'account_plan', 'account_type'];
    for ($i = 0; $i < count($searchFields); $i++) {
        $searchConditions[] = "u.{$searchFields[$i]} LIKE :search$i";
        $params[":search$i"] = $searchLike;
    }

    // Add date-based searches
    $searchConditions[] = "DATE_FORMAT(u.birthdate, '%M %d') LIKE :search_bd";
    $searchConditions[] = "DATE_FORMAT(u.create_dt, '%M %d %Y') LIKE :search_cd";
    $searchConditions[] = "DATE_FORMAT(u.create_dt, '%Y-%m-%d') LIKE :search_cd2";
    $params[':search_bd'] = $searchLike;
    $params[':search_cd'] = $searchLike;
    $params[':search_cd2'] = $searchLike;

    $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
}

if ($status) {
    $conditions[] = "u.status = :status";
    $params[':status'] = $status;
}

if ($plan) {
    $conditions[] = "u.account_plan = :plan";
    $params[':plan'] = $plan;
}

// Handle user type filter - distinguish between real/test users and account types
if ($type) {
    if ($type === 'real' || $type === 'test') {
        // Filter by user type (real vs test)
        $conditions[] = "u.type = :usertype";
        $params[':usertype'] = $type;
    } else {
        // Filter by account type (individual/business/parental)
        $conditions[] = "u.account_type = :type";
        $params[':type'] = $type;
    }
} else {
    // Default to showing only real users when no filter is specified
    $conditions[] = "u.type = 'real'";
}

// Fix the day filter to properly handle 'all' option
if ($days !== 'all' && !empty($days) && is_numeric($days)) {
    $conditions[] = "u.create_dt >= CURDATE() - INTERVAL :days DAY";
    $params[':days'] = (int)$days;
}
// When $days === 'all', we don't add any date restriction

$whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';

// Get users with enrollment counts
$sql = "
    SELECT
        u.user_id,
        u.first_name,
        u.last_name,
        u.username,
        u.email,
        u.birthdate,
        TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) AS age,
        u.city,
        u.state,
        u.status,
        u.account_plan,
        u.account_type,
        u.account_admin,
        u.type,
        u.create_dt,
        u.modify_dt,
        a.description as avatar,
        lt.last_login_dt,
        COALESCE(ec.pending_count, 0) as pending_enrollments,
        COALESCE(ec.success_count, 0) as success_enrollments,
        COALESCE(ec.total_count, 0) as total_enrollments
    FROM bg_users u
    LEFT JOIN bg_user_attributes a ON u.user_id = a.user_id
        AND a.name = 'avatar'
        AND a.category = 'primary'
        AND a.status = 'active'
    LEFT JOIN (
        SELECT user_id, MAX(modify_dt) as last_login_dt
        FROM bg_logintracking
        WHERE status = 'A'
        GROUP BY user_id
    ) lt ON u.user_id = lt.user_id
    LEFT JOIN (
        SELECT
            user_id,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
            COUNT(*) as total_count
        FROM bg_user_companies
        GROUP BY user_id
    ) ec ON u.user_id = ec.user_id
    WHERE $whereClause
    ORDER BY u.create_dt DESC
    LIMIT :limit OFFSET :offset
";

try {
    $stmt = $database->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        if ($key === ':days') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed');
    }
    
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Clean up avatar URL
        if ($row['avatar']) {
            $row['avatar'] = str_replace('cdn.birthday.gold', $website['cdnurl'], $row['avatar']);
        } else {
            $row['avatar'] = '/public/avatars/problemavatar.png';
        }
        
        // Add badge flags
        $row['is_admin'] = $account->isadmin(['user_id' => $row['user_id'], 'account_admin' => $row['account_admin']]);
        $row['is_staff'] = $account->isstaff('*', $row['user_id']);
        $row['is_verified'] = !empty($account->getUserAttribute($row['user_id'], 'verified'));
        
        // Format dates
        $row['create_dt'] = date('c', strtotime($row['create_dt']));
        if ($row['last_login_dt']) {
            $row['last_login_dt'] = date('c', strtotime($row['last_login_dt']));
        }
        
        $users[] = $row;
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM bg_users u WHERE $whereClause";
    $countStmt = $database->prepare($countSql);
    
    foreach ($params as $key => $value) {
        if ($key === ':days') {
            $countStmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $countStmt->bindValue($key, $value);
        }
    }
    
    $countStmt->execute();
    $totalCount = $countStmt->fetchColumn();
    
    // Return response
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => $totalCount,
        'offset' => $offset,
        'limit' => $limit,
        'hasMore' => ($offset + $limit) < $totalCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $mode == 'dev' ? $e->getMessage() : 'An error occurred'
    ]);
}
?>