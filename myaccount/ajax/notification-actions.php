<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Start session tracking
session_tracking('notification_action_start', 'Request received');
session_tracking('notification_action_post', json_encode($_POST));

// Check if user is logged in
if (!$account->isloggedin()) {
    session_tracking('notification_action_auth_fail', 'User not logged in');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_tracking('notification_action_auth_ok', 'User authenticated: ' . $current_user_data['user_id']);

// Verify CSRF token using formposted which checks the token automatically
if (!$app->formposted()) {
    session_tracking('notification_action_csrf_fail', 'CSRF validation failed');
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
session_tracking('notification_action_csrf_ok', 'CSRF validation passed');

$action = $_POST['action'] ?? '';
$notification_id = intval($_POST['notification_id'] ?? 0);
$user_id = $current_user_data['user_id'];
$response = ['success' => false];

session_tracking('notification_action_params', "Action: $action, NotifID: $notification_id, UserID: $user_id");

// Verify the notification belongs to the current user
$sql = "SELECT * FROM bg_user_notifications WHERE notification_id = :notification_id AND user_id = :user_id";
try {
    $stmt = $database->prepare($sql);
    $stmt->execute([
        ':notification_id' => $notification_id,
        ':user_id' => $user_id
    ]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    session_tracking('notification_action_lookup', 'Found notification: ' . ($notification ? 'YES' : 'NO'));
} catch (Exception $e) {
    session_tracking('notification_action_lookup_error', 'Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
    exit;
}

if (!$notification) {
    session_tracking('notification_action_not_found', "Notification $notification_id not found for user $user_id");
    echo json_encode(['error' => 'Notification not found']);
    exit;
}

switch ($action) {
    case 'mark_notification':
        $status = $_POST['status'] ?? '';
        session_tracking('notification_action_mark', "Marking as: $status");
        
        if ($status === 'read' || $status === 'unread') {
            $sql = "UPDATE bg_user_notifications 
                    SET status = :status, modify_dt = NOW() 
                    WHERE notification_id = :notification_id AND user_id = :user_id";
            
            try {
                $stmt = $database->prepare($sql);
                $success = $stmt->execute([
                    ':status' => $status,
                    ':notification_id' => $notification_id,
                    ':user_id' => $user_id
                ]);
                
                session_tracking('notification_action_update', "Update result: " . ($success ? 'SUCCESS' : 'FAILED') . ", Rows affected: " . $stmt->rowCount());
                
                if ($success && $stmt->rowCount() > 0) {
                    $response = [
                        'success' => true,
                        'message' => 'Notification marked as ' . $status
                    ];
                    session_tracking('notification_action_success', 'Notification updated successfully');
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Failed to update notification (no rows affected)'
                    ];
                    session_tracking('notification_action_no_rows', 'Update executed but no rows affected');
                }
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ];
                session_tracking('notification_action_update_error', 'Exception: ' . $e->getMessage());
            }
        } else {
            $response = [
                'success' => false,
                'error' => 'Invalid status: ' . $status
            ];
            session_tracking('notification_action_invalid_status', "Invalid status provided: $status");
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

session_tracking('notification_action_response', json_encode($response));
session_tracking('notification_action_complete', 'Request completed');

header('Content-Type: application/json');
echo json_encode($response);
?>