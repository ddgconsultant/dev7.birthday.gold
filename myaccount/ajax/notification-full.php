<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Log the request
session_tracking('notification_full_start', 'Request received');
session_tracking('notification_full_get', json_encode($_GET));

// Check if user is logged in
if (!$account->isloggedin()) {
    session_tracking('notification_full_auth_fail', 'User not logged in');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$notification_id = intval($_GET['id'] ?? 0);
$user_id = $current_user_data['user_id'];

session_tracking('notification_full_params', "NotifID: $notification_id, UserID: $user_id");

// Get the notification
$sql = "SELECT * FROM bg_user_notifications WHERE notification_id = :notification_id AND user_id = :user_id";
$stmt = $database->prepare($sql);
$stmt->execute([
    ':notification_id' => $notification_id,
    ':user_id' => $user_id
]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    session_tracking('notification_full_not_found', "Notification $notification_id not found for user $user_id");
    echo json_encode(['error' => 'Notification not found', 'success' => false]);
    exit;
}

session_tracking('notification_full_found', 'Notification found: ' . $notification['title']);

// Extract full content
$fullContent = $notification['message'];
if ($notification['type'] == 'email_notification' || $notification['type'] == 'security_alert') {
    preg_match('/<!-- Message Content start -->(.*?)<!-- Message Content end -->/s', $fullContent, $matches);
    if (isset($matches[1])) {
        $fullContent = trim($matches[1]);
    }
}

// Mark as read automatically when viewing full message
if ($notification['status'] == 'unread') {
    $updateSql = "UPDATE bg_user_notifications SET status = 'read', modify_dt = NOW() WHERE notification_id = :notification_id";
    $database->query($updateSql, [':notification_id' => $notification_id]);
}

// Return the response
$response = [
    'success' => true,
    'title' => htmlspecialchars($notification['title']),
    'content' => $fullContent,
    'type' => $notification['type'],
    'created' => date('F j, Y at g:i A', strtotime($notification['create_dt']))
];

session_tracking('notification_full_response', 'Returning response with title: ' . $response['title']);

header('Content-Type: application/json');
echo json_encode($response);

session_tracking('notification_full_complete', 'Request completed');
?>