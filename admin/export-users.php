<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Ensure admin access
if (!$account->isadmin()) {
    die('Unauthorized');
}

// Get filters
$search = $_GET['search'] ?? '';
$userType = $_GET['userType'] ?? '';
$plan = $_GET['plan'] ?? '';
$status = $_GET['status'] ?? '';
$sortBy = $_GET['sortBy'] ?? 'newest';

// Build query conditions
$conditions = [];
$params = [];

$conditions[] = "u.username NOT IN ('system', 'admin', 'root')";

// Check if we have real users
$checkRealUsers = $database->prepare("SELECT COUNT(*) as count FROM bg_users WHERE type = 'real'");
$checkRealUsers->execute();
$result = $checkRealUsers->fetch(PDO::FETCH_ASSOC);
$hasRealUsers = $result && $result['count'] > 0;

if ($userType) {
    $conditions[] = "u.type = :userType";
    $params[':userType'] = $userType;
} else if ($hasRealUsers) {
    $conditions[] = "u.type = 'real'";
}

if ($plan) {
    $conditions[] = "u.account_plan = :plan";
    $params[':plan'] = $plan;
}

if ($status) {
    $conditions[] = "u.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $searchLike = "%$search%";
    $conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
    $params[':search'] = $searchLike;
}

$whereClause = implode(' AND ', $conditions);

// Determine sort order
$orderBy = match($sortBy) {
    'oldest' => 'u.create_dt ASC',
    'name' => 'u.first_name ASC, u.last_name ASC',
    'recent_login' => 'lt.last_login_dt DESC',
    default => 'u.create_dt DESC'
};

// Get all users matching criteria
$sql = "
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.username,
        u.email,
        u.phone_number as phone,
        u.birthdate,
        TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) as age,
        u.city,
        u.state,
        u.country,
        u.zip_code as postal_code,
        u.status,
        u.account_plan,
        u.account_type,
        u.create_dt,
        lt.last_login_dt,
        (SELECT COUNT(*) FROM bg_user_enrollments WHERE user_id = u.user_id) as enrollments,
        (SELECT COUNT(*) FROM bg_referrals WHERE user_id = u.user_id) as referrals
    FROM bg_users u
    LEFT JOIN (
        SELECT user_id, MAX(modify_dt) as last_login_dt 
        FROM bg_logintracking 
        WHERE status = 'A' 
        GROUP BY user_id
    ) lt ON u.user_id = lt.user_id
    WHERE $whereClause
    ORDER BY $orderBy
";

$stmt = $database->prepare($sql);
$stmt->execute($params);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'User ID',
    'First Name',
    'Last Name',
    'Username',
    'Email',
    'Phone',
    'Birthday',
    'Age',
    'City',
    'State',
    'Country',
    'Postal Code',
    'Status',
    'Account Plan',
    'Account Type',
    'Enrollments',
    'Referrals',
    'Join Date',
    'Last Login'
]);

// Write data rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['user_id'],
        $row['first_name'],
        $row['last_name'],
        $row['username'],
        $row['email'],
        $row['phone'] ?: '',
        $row['birthdate'] ?: '',
        $row['age'] ?: '',
        $row['city'] ?: '',
        $row['state'] ?: '',
        $row['country'] ?: '',
        $row['postal_code'] ?: '',
        $row['status'],
        $row['account_plan'] ?: 'free',
        $row['account_type'] ?: '',
        $row['enrollments'],
        $row['referrals'],
        $row['create_dt'],
        $row['last_login_dt'] ?: 'Never'
    ]);
}

fclose($output);
?>