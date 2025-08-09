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
            $post_id = intval($_POST['post_id'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Invalid post ID');
            }
            
            $bookmarked = $social->bookmarkPost($post_id, $user_id);
            
            $response['success'] = true;
            $response['message'] = $bookmarked ? 'Post bookmarked' : 'Bookmark removed';
            $response['data'] = ['bookmarked' => $bookmarked];
            break;
            
        case 'GET':
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            $sql = "SELECT p.*, u.username, u.first_name, u.last_name,
                    (SELECT description FROM bg_user_attributes 
                     WHERE user_id = u.user_id AND type = 'profile_image' 
                     AND name = 'avatar' AND status = 'active' AND category = 'primary' LIMIT 1) as avatar_url,
                    b.created_at as bookmarked_at
                    FROM bg_social_interactions b
                    JOIN bg_social_posts p ON b.post_id = p.post_id
                    JOIN bg_users u ON p.user_id = u.user_id
                    WHERE b.user_id = :user_id 
                    AND b.interaction_type = 'bookmark' 
                    AND b.status = 'active'
                    AND p.status = 'active'
                    ORDER BY b.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $bookmarks = $database->getrows($sql, [
                'user_id' => $user_id,
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            foreach ($bookmarks as &$bookmark) {
                $bookmark['time_ago'] = $social->formatTimeAgo($bookmark['created_at']);
            }
            
            $response['success'] = true;
            $response['data'] = [
                'bookmarks' => $bookmarks,
                'count' => count($bookmarks),
                'has_more' => count($bookmarks) >= $limit
            ];
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

