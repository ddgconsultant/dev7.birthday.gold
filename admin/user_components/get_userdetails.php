<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set JSON header
header('Content-Type: application/json');

// Ensure admin access
if (!$account->isadmin()) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$userId = (int)($_GET['id'] ?? 0);
if (!$userId) {
    die(json_encode(['success' => false, 'error' => 'Invalid user ID']));
}

try {
    // Get user details
    $sql = "
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.phone_number as phone,
            u.birthdate,
            u.city,
            u.state,
            u.country,
            u.zip_code,
            u.status,
            u.account_plan,
            u.account_type,
            u.create_dt,
            u.modify_dt,
            TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) as age,
            a.description as avatar,
            lt.last_login_dt,
            admin_attr.description as account_admin,
            staff_attr.description as account_staff
        FROM bg_users u
        LEFT JOIN bg_user_attributes a ON u.user_id = a.user_id 
            AND a.name = 'avatar' 
            AND a.category = 'primary' 
            AND a.status = 'active'
        LEFT JOIN bg_user_attributes admin_attr ON u.user_id = admin_attr.user_id 
            AND admin_attr.name = 'account_admin' 
            AND admin_attr.status = 'active'
        LEFT JOIN bg_user_attributes staff_attr ON u.user_id = staff_attr.user_id 
            AND staff_attr.name = 'account_staff' 
            AND staff_attr.status = 'active'
        LEFT JOIN (
            SELECT user_id, MAX(modify_dt) as last_login_dt 
            FROM bg_logintracking 
            WHERE status = 'A' 
            GROUP BY user_id
        ) lt ON u.user_id = lt.user_id
        WHERE u.user_id = :userId
    ";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([':userId' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die(json_encode(['success' => false, 'error' => 'User not found']));
    }
    
    // Clean up avatar URL
    if ($user['avatar']) {
        $user['avatar'] = str_replace('cdn.birthday.gold', $website['cdnurl'], $user['avatar']);
    }
    
    // Remove sensitive data
    unset($user['password']);
    unset($user['salt']);
    unset($user['token']);
    
    echo json_encode(['success' => true, 'user' => $user]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load user details']);
}
?>