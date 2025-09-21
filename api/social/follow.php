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
        case 'POST':
            $target_user_id = intval($_POST['user_id'] ?? 0);
            
            if (!$target_user_id) {
                throw new Exception('Invalid user ID');
            }
            
            if ($target_user_id === $user_id) {
                throw new Exception('You cannot follow yourself');
            }
            
            $following = $social->followUser($user_id, $target_user_id);
            
            $response['success'] = true;
            $response['message'] = $following ? 'User followed' : 'User unfollowed';
            $response['data'] = ['following' => $following];
            break;
            
        case 'GET':
            $type = $_GET['type'] ?? 'followers';
            $target_user_id = intval($_GET['user_id'] ?? $user_id);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            if ($type === 'followers') {
                $users = $social->getFollowers($target_user_id, $limit, $offset);
            } elseif ($type === 'following') {
                $users = $social->getFollowing($target_user_id, $limit, $offset);
            } else {
                throw new Exception('Invalid type. Must be "followers" or "following"');
            }
            
            foreach ($users as &$user) {
                $user['is_following'] = $social->isFollowing($user_id, $user['user_id']);
            }
            
            $response['success'] = true;
            $response['data'] = [
                'users' => $users,
                'count' => count($users),
                'has_more' => count($users) >= $limit
            ];
            break;
            
        case 'DELETE':
            parse_str(file_get_contents('php://input'), $_DELETE);
            $target_user_id = intval($_DELETE['user_id'] ?? 0);
            
            if (!$target_user_id) {
                throw new Exception('Invalid user ID');
            }
            
            $sql = "UPDATE bg_social_relationships 
                    SET status = 'inactive', updated_at = NOW()
                    WHERE user_id = :user_id 
                    AND target_user_id = :target_user_id 
                    AND relationship_type = 'follow'";
            
            $database->query($sql, [
                'user_id' => $user_id,
                'target_user_id' => $target_user_id
            ]);
            
            $response['success'] = true;
            $response['message'] = 'User unfollowed';
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

