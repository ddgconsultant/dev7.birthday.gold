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
    $search_term = trim($_GET['q'] ?? '');
    $search_type = $_GET['type'] ?? 'all';
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);
    
    if (empty($search_term)) {
        throw new Exception('Search term cannot be empty');
    }
    
    if (strlen($search_term) < 2) {
        throw new Exception('Search term must be at least 2 characters');
    }
    
    $results = [
        'posts' => [],
        'users' => [],
        'hashtags' => []
    ];
    
    if ($search_type === 'all' || $search_type === 'posts') {
        $posts = $social->searchPosts($search_term, $limit, $offset);
        
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
            
            $post['user_liked'] = false;
            $sql = "SELECT COUNT(*) as liked FROM bg_social_interactions 
                    WHERE post_id = :post_id AND user_id = :user_id 
                    AND interaction_type = 'like' AND status = 'active'";
            $result = $database->getrow($sql, [
                'post_id' => $post['post_id'],
                'user_id' => $user_id
            ]);
            $post['user_liked'] = ($result['liked'] > 0);
            
            $post['is_own_post'] = ($post['user_id'] == $user_id);
        }
        
        $results['posts'] = $posts;
    }
    
    if ($search_type === 'all' || $search_type === 'users') {
        $users = $social->searchUsers($search_term, $limit, $offset);
        
        foreach ($users as &$user) {
            $user['is_following'] = $social->isFollowing($user_id, $user['user_id']);
            $user['is_self'] = ($user['user_id'] == $user_id);
        }
        
        $results['users'] = $users;
    }
    
    if ($search_type === 'all' || $search_type === 'hashtags') {
        if (strpos($search_term, '#') !== 0) {
            $hashtag_search = '#' . $search_term;
        } else {
            $hashtag_search = $search_term;
        }
        
        $sql = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(hashtags, '$[*]')) as hashtag,
                COUNT(*) as post_count
                FROM bg_social_posts
                WHERE status = 'active'
                AND JSON_CONTAINS(hashtags, JSON_QUOTE(:hashtag))
                GROUP BY hashtag
                ORDER BY post_count DESC
                LIMIT :limit";
        
        $hashtags = $database->getrows($sql, [
            'hashtag' => $hashtag_search,
            'limit' => 10
        ]);
        
        $results['hashtags'] = $hashtags;
    }
    
    $response['success'] = true;
    $response['data'] = [
        'results' => $results,
        'search_term' => $search_term,
        'search_type' => $search_type,
        'counts' => [
            'posts' => count($results['posts']),
            'users' => count($results['users']),
            'hashtags' => count($results['hashtags'])
        ]
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

