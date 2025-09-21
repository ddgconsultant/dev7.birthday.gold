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
    if ($method !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    $type = $_POST['type'] ?? 'post';
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Invalid ID');
    }
    
    if ($type === 'post') {
        $liked = $social->likePost($id, $user_id);
        
        $sql = "SELECT like_count FROM bg_social_posts WHERE post_id = :post_id";
        $result = $database->getrow($sql, ['post_id' => $id]);
        $like_count = $result['like_count'] ?? 0;
        
    } elseif ($type === 'comment') {
        $liked = $social->likeComment($id, $user_id);
        
        $sql = "SELECT COUNT(*) as like_count FROM bg_social_interactions 
                WHERE post_id = :comment_id AND interaction_type = 'like' AND status = 'active'";
        $result = $database->getrow($sql, ['comment_id' => $id]);
        $like_count = $result['like_count'] ?? 0;
        
    } else {
        throw new Exception('Invalid type. Must be "post" or "comment"');
    }
    
    $response['success'] = true;
    $response['message'] = $liked ? 'Liked' : 'Unliked';
    $response['data'] = [
        'liked' => $liked,
        'like_count' => $like_count
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

