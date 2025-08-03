<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Log the request
session_tracking('notification_action_start', 'Request received v3');
session_tracking('notification_action_post', json_encode($_POST));

// User authentication is already handled by site-controller.php

// Debug CSRF token
session_tracking('notification_action_csrf_debug', 'POST token: ' . ($_POST['_token'] ?? 'NOT SET') . ', Session token: ' . ($session->get('csrf_token', 'NOT SET')));

// Verify CSRF token
if (!$app->formposted()) {
    session_tracking('notification_action_csrf_fail', 'CSRF validation failed');
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
session_tracking('notification_action_csrf_pass', 'CSRF validation passed');

$action = $_POST['action'] ?? '';
$notification_id = $_POST['notification_id'] ?? '';
$user_id = $current_user_data['user_id'];
$response = ['success' => false];

session_tracking('notification_action_params', "Action: $action, NotifID: $notification_id, UserID: $user_id");

// Convert notification_id to integer for database query
$notification_id_int = intval($notification_id);
if ($notification_id_int == 0 && $notification_id !== '0') {
    session_tracking('notification_action_invalid_id', "Invalid notification ID: $notification_id");
    echo json_encode(['error' => 'Invalid notification ID']);
    exit;
}

// Verify the notification belongs to the current user
$sql = "SELECT * FROM bg_user_notifications WHERE notification_id = :notification_id AND user_id = :user_id";
$stmt = $database->prepare($sql);
$stmt->execute([
    ':notification_id' => $notification_id_int,
    ':user_id' => $user_id
]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

session_tracking('notification_action_lookup', 'Found notification: ' . ($notification ? 'YES' : 'NO'));

if (!$notification) {
    session_tracking('notification_action_not_found', "Notification $notification_id_int not found for user $user_id");
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
            
            $stmt = $database->prepare($sql);
            $success = $stmt->execute([
                ':status' => $status,
                ':notification_id' => $notification_id_int,
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
                    'error' => 'Failed to update notification'
                ];
                session_tracking('notification_action_failed', 'No rows affected');
            }
        } else {
            $response = [
                'success' => false,
                'error' => 'Invalid status'
            ];
            session_tracking('notification_action_invalid_status', "Invalid status provided: $status");
        }
        break;
        
    case 'delete_notification':
        session_tracking('notification_action_delete', "Deleting notification: $notification_id_int");
        
        $sql = "DELETE FROM bg_user_notifications 
                WHERE notification_id = :notification_id AND user_id = :user_id";
        $stmt = $database->prepare($sql);
        $success = $stmt->execute([
            ':notification_id' => $notification_id_int,
            ':user_id' => $user_id
        ]);
        
        if ($success && $stmt->rowCount() > 0) {
            $response = [
                'success' => true,
                'message' => 'Notification deleted'
            ];
            session_tracking('notification_action_delete_success', 'Notification deleted successfully');
        } else {
            $response = [
                'success' => false,
                'error' => 'Failed to delete notification'
            ];
            session_tracking('notification_action_delete_failed', 'No rows affected');
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'error' => 'Invalid action'
        ];
        session_tracking('notification_action_invalid_action', "Invalid action: $action");
}

session_tracking('notification_action_response', json_encode($response));

header('Content-Type: application/json');
echo json_encode($response);
?>