<?php
/**
 * Admin AJAX handler for notification actions
 * Allows admins to manage user notifications
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check admin access
if (!$account->isadmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!$app->formposted()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';
$notification_id = $_POST['notification_id'] ?? '';
$response = ['success' => false];

// Convert notification_id to integer for database query
$notification_id_int = intval($notification_id);
if ($notification_id_int == 0 && $notification_id !== '0') {
    echo json_encode(['error' => 'Invalid notification ID']);
    exit;
}

// Verify the notification exists (admin can access any user's notification)
$sql = "SELECT * FROM bg_user_notifications WHERE notification_id = :notification_id";
$stmt = $database->prepare($sql);
$stmt->execute([':notification_id' => $notification_id_int]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    echo json_encode(['error' => 'Notification not found']);
    exit;
}

switch ($action) {
    case 'mark_notification':
        $status = $_POST['status'] ?? '';

        // Valid status values
        $valid_statuses = ['sent', 'pending', 'failed', 'read', 'unread'];

        if (in_array($status, $valid_statuses)) {
            $sql = "UPDATE bg_user_notifications
                    SET status = :status, modify_dt = NOW()
                    WHERE notification_id = :notification_id";

            $stmt = $database->prepare($sql);
            $success = $stmt->execute([
                ':status' => $status,
                ':notification_id' => $notification_id_int
            ]);

            if ($success) {
                // Log even if no rows changed (status was already set)
                if ($stmt->rowCount() > 0) {
                    session_tracking('admin_notification_status_change', [
                        'notification_id' => $notification_id_int,
                        'user_id' => $notification['user_id'],
                        'old_status' => $notification['status'],
                        'new_status' => $status,
                        'admin_user_id' => $current_user_data['user_id']
                    ]);
                }

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
                'error' => 'Invalid status: ' . $status
            ];
        }
        break;

    case 'delete_notification':
        $sql = "DELETE FROM bg_user_notifications
                WHERE notification_id = :notification_id";
        $stmt = $database->prepare($sql);
        $success = $stmt->execute([':notification_id' => $notification_id_int]);

        if ($success && $stmt->rowCount() > 0) {
            session_tracking('admin_notification_delete', [
                'notification_id' => $notification_id_int,
                'user_id' => $notification['user_id'],
                'admin_user_id' => $current_user_data['user_id']
            ]);

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
