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
$conditions = ["u.type = 'real'"];
$params = [];

if ($search) {
    $searchLike = "%$search%";
    $conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
    $params[':search'] = $searchLike;
}

if ($status) {
    $conditions[] = "u.status = :status";
    $params[':status'] = $status;
}

if ($plan) {
    $conditions[] = "u.account_plan = :plan";
    $params[':plan'] = $plan;
}

if ($type) {
    $conditions[] = "u.account_type = :type";
    $params[':type'] = $type;
}

if ($days !== 'all') {
    $conditions[] = "u.create_dt >= CURDATE() - INTERVAL :days DAY";
    $params[':days'] = (int)$days;
}

$whereClause = implode(' AND ', $conditions);

// Get users
$sql = "
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.username,
        u.email,
        u.birthdate,
        u.city,
        u.state,
        u.status,
        u.account_plan,
        u.account_type,
        u.account_admin,
        u.create_dt,
        u.modify_dt,
        a.description as avatar,
        lt.last_login_dt
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
    $stmt->execute();
    
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
        $row['is_verified'] = $account->isverified('*', $row['user_id']);
        
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