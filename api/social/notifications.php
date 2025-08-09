<?php
$addClasses[] = 'Social';
require_once '../../core/site-controller.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

// Check if user is logged in
if (empty($current_user_data['user_id'])) {
    $response['message'] = 'Authentication required';
    http_response_code(401);
    echo json_encode($response);
    exit;
}


$user_id = $current_user_data['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $limit = intval($_GET['limit'] ?? 50);
            
            $notifications = $social->getNotifications($user_id, $unread_only, $limit);
            
            foreach ($notifications as &$notification) {
                $notification['time_ago'] = $social->formatTimeAgo($notification['created_at']);
                
                switch ($notification['notification_type']) {
                    case 'like':
                        $notification['message'] = $notification['first_name'] . ' liked your post';
                        $notification['icon'] = 'bi-heart-fill';
                        break;
                    case 'comment':
                        $notification['message'] = $notification['first_name'] . ' commented on your post';
                        $notification['icon'] = 'bi-chat-fill';
                        break;
                    case 'reply':
                        $notification['message'] = $notification['first_name'] . ' replied to your comment';
                        $notification['icon'] = 'bi-reply-fill';
                        break;
                    case 'follow':
                        $notification['message'] = $notification['first_name'] . ' started following you';
                        $notification['icon'] = 'bi-person-plus-fill';
                        break;
                    case 'share':
                        $notification['message'] = $notification['first_name'] . ' shared your post';
                        $notification['icon'] = 'bi-share-fill';
                        break;
                    case 'mention':
                        $notification['message'] = $notification['first_name'] . ' mentioned you';
                        $notification['icon'] = 'bi-at';
                        break;
                    case 'comment_like':
                        $notification['message'] = $notification['first_name'] . ' liked your comment';
                        $notification['icon'] = 'bi-heart-fill';
                        break;
                    default:
                        $notification['message'] = 'New notification';
                        $notification['icon'] = 'bi-bell-fill';
                }
            }
            
            $sql = "SELECT COUNT(*) as unread_count FROM bg_social_notifications 
                    WHERE user_id = :user_id AND is_read = 0";
            $result = $database->getrow($sql, ['user_id' => $user_id]);
            $unread_count = $result['unread_count'] ?? 0;
            
            $response['success'] = true;
            $response['data'] = [
                'notifications' => $notifications,
                'unread_count' => $unread_count,
                'count' => count($notifications)
            ];
            break;
            
        case 'PUT':
            parse_str(file_get_contents('php://input'), $_PUT);
            $notification_id = intval($_PUT['notification_id'] ?? 0);
            $mark_all = isset($_PUT['mark_all']) && $_PUT['mark_all'] === 'true';
            
            if ($mark_all) {
                $sql = "UPDATE bg_social_notifications 
                        SET is_read = 1, read_at = NOW() 
                        WHERE user_id = :user_id AND is_read = 0";
                $database->query($sql, ['user_id' => $user_id]);
                
                $response['success'] = true;
                $response['message'] = 'All notifications marked as read';
                
            } elseif ($notification_id) {
                $result = $social->markNotificationRead($notification_id, $user_id);
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Notification marked as read';
                } else {
                    throw new Exception('Could not mark notification as read');
                }
            } else {
                throw new Exception('Invalid notification ID');
            }
            break;
            
        case 'DELETE':
            parse_str(file_get_contents('php://input'), $_DELETE);
            $notification_id = intval($_DELETE['notification_id'] ?? 0);
            
            if (!$notification_id) {
                throw new Exception('Invalid notification ID');
            }
            
            $sql = "DELETE FROM bg_social_notifications 
                    WHERE notification_id = :notification_id AND user_id = :user_id";
            $database->query($sql, [
                'notification_id' => $notification_id,
                'user_id' => $user_id
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Notification deleted';
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

