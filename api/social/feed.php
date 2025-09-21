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

try {
    $feed_type = $_GET['type'] ?? 'all';
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);
    $user_profile_id = intval($_GET['user_id'] ?? 0);
    
    if ($feed_type === 'trending') {
        $hours = intval($_GET['hours'] ?? 24);
        $posts = $social->getTrendingPosts($hours, $limit);
        
    } elseif ($feed_type === 'user' && $user_profile_id) {
        $posts = $social->getFeed($user_profile_id, $limit, $offset, 'user');
        
    } else {
        $posts = $social->getFeed($user_id, $limit, $offset, $feed_type);
    }
    
    foreach ($posts as &$post) {
        $post['time_ago'] = $social->formatTimeAgo($post['created_at']);
        
        $post['media'] = [];
        if (!empty($post['media_urls'])) {
            $post['media'] = json_decode($post['media_urls'], true) ?? [];
        }
        
        $post['hashtags_array'] = [];
        if (!empty($post['hashtags'])) {
            $post['hashtags_array'] = json_decode($post['hashtags'], true) ?? [];
        }
        
        $sql = "SELECT COUNT(*) as comment_count FROM bg_social_posts 
                WHERE parent_post_id = :post_id AND post_type = 'comment' AND status = 'active'";
        $result = $database->getrow($sql, ['post_id' => $post['post_id']]);
        $post['comment_count'] = $result['comment_count'] ?? 0;
        
        if ($feed_type !== 'user') {
            $post['is_following'] = $social->isFollowing($user_id, $post['user_id']);
        }
        
        $post['is_own_post'] = ($post['user_id'] == $user_id);
    }
    
    $response['success'] = true;
    $response['data'] = [
        'posts' => $posts,
        'count' => count($posts),
        'has_more' => count($posts) >= $limit,
        'feed_type' => $feed_type
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

