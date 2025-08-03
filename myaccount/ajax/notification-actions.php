<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is logged in
if (!$account->isloggedin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!$app->formposted() || !$display->verifycsrf_token($_POST['_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

$action = $_POST['action'] ?? '';
$notification_id = intval($_POST['notification_id'] ?? 0);
$user_id = $current_user_data['user_id'];
$response = ['success' => false];

// Verify the notification belongs to the current user
$sql = "SELECT * FROM bg_user_notifications WHERE notification_id = :notification_id AND user_id = :user_id";
$stmt = $database->prepare($sql);
$stmt->execute([
    ':notification_id' => $notification_id,
    ':user_id' => $user_id
]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    echo json_encode(['error' => 'Notification not found']);
    exit;
}

switch ($action) {
    case 'mark_notification':
        $status = $_POST['status'] ?? '';
        if ($status === 'read' || $status === 'unread') {
            $sql = "UPDATE bg_user_notifications 
                    SET status = :status, modify_dt = NOW() 
                    WHERE notification_id = :notification_id AND user_id = :user_id";
            $stmt = $database->prepare($sql);
            $success = $stmt->execute([
                ':status' => $status,
                ':notification_id' => $notification_id,
                ':user_id' => $user_id
            ]);
            
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Notification marked as ' . $status
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Failed to update notification'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'error' => 'Invalid status'
            ];
        }
        break;
        
    case 'delete_notification':
        $sql = "DELETE FROM bg_user_notifications 
                WHERE notification_id = :notification_id AND user_id = :user_id";
        $stmt = $database->prepare($sql);
        $success = $stmt->execute([
            ':notification_id' => $notification_id,
            ':user_id' => $user_id
        ]);
        
        if ($success) {
            $response = [
                'success' => true,
                'message' => 'Notification deleted'
            ];
        } else {
            $response = [
                'success' => false,
                'error' => 'Failed to delete notification'
            ];
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'error' => 'Invalid action'
        ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>