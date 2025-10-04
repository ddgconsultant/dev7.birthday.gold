<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only access
if (!$account->isadmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$lockout_id = $_GET['id'] ?? 0;

// Get parent record
$parent_sql = "SELECT * FROM bg_lockout WHERE id = :id";
$parent = $database->query($parent_sql, ['id' => $lockout_id])->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Lockout not found']);
    exit;
}

// Get history records
$history_sql = "SELECT
    id,
    parent_id,
    ip,
    type,
    session_id,
    start_dt,
    expire_dt,
    create_dt,
    status,
    lockout_minutes,
    CASE
        WHEN lockout_minutes >= 99999 THEN 18
        WHEN lockout_minutes >= 65536 THEN 17
        WHEN lockout_minutes >= 32768 THEN 16
        WHEN lockout_minutes >= 16384 THEN 15
        WHEN lockout_minutes >= 8192 THEN 14
        WHEN lockout_minutes >= 4096 THEN 13
        WHEN lockout_minutes >= 2048 THEN 12
        WHEN lockout_minutes >= 1024 THEN 11
        WHEN lockout_minutes >= 512 THEN 10
        WHEN lockout_minutes >= 256 THEN 9
        WHEN lockout_minutes >= 128 THEN 8
        WHEN lockout_minutes >= 64 THEN 7
        WHEN lockout_minutes >= 32 THEN 6
        WHEN lockout_minutes >= 16 THEN 5
        WHEN lockout_minutes >= 8 THEN 4
        WHEN lockout_minutes >= 4 THEN 3
        WHEN lockout_minutes >= 2 THEN 2
        ELSE 1
    END as level
FROM bg_lockout_history
WHERE parent_id = :id
ORDER BY create_dt DESC
LIMIT 100";

$history = $database->query($history_sql, ['id' => $lockout_id])->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'parent' => $parent,
    'history' => $history
]);
