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
            $action = $_POST['action'] ?? 'create';
            
            if ($action === 'create') {
                $content = trim($_POST['content'] ?? '');
                $media_type = $_POST['media_type'] ?? 'text';
                $visibility = $_POST['visibility'] ?? 'public';
                $location = $_POST['location'] ?? null;
                $hashtags = $_POST['hashtags'] ?? null;
                
                if (empty($content) && $media_type === 'text') {
                    throw new Exception('Post content cannot be empty');
                }
                
                $media_data = null;
                if (!empty($_POST['media_urls'])) {
                    $media_data = json_decode($_POST['media_urls'], true);
                }
                
                $post_id = $social->createPost($user_id, $content, $media_type, $media_data, $visibility);
                
                if ($hashtags) {
                    $hashtag_array = array_map('trim', explode(',', $hashtags));
                    $sql = "UPDATE bg_social_posts SET hashtags = :hashtags WHERE post_id = :post_id";
                    $database->query($sql, [
                        'hashtags' => json_encode($hashtag_array),
                        'post_id' => $post_id
                    ]);
                }
                
                if ($location) {
                    $sql = "UPDATE bg_social_posts SET location = :location WHERE post_id = :post_id";
                    $database->query($sql, ['location' => $location, 'post_id' => $post_id]);
                }
                
                $response['success'] = true;
                $response['message'] = 'Post created successfully';
                $response['data'] = ['post_id' => $post_id];
                
            } elseif ($action === 'share') {
                $post_id = intval($_POST['post_id'] ?? 0);
                $share_text = $_POST['share_text'] ?? '';
                
                if (!$post_id) {
                    throw new Exception('Invalid post ID');
                }
                
                $share_id = $social->sharePost($post_id, $user_id, $share_text);
                
                $response['success'] = true;
                $response['message'] = 'Post shared successfully';
                $response['data'] = ['share_id' => $share_id];
            }
            break;
            
        case 'PUT':
            parse_str(file_get_contents('php://input'), $_PUT);
            $post_id = intval($_PUT['post_id'] ?? 0);
            $content = trim($_PUT['content'] ?? '');
            
            if (!$post_id || empty($content)) {
                throw new Exception('Invalid post ID or content');
            }
            
            $result = $social->updatePost($post_id, $user_id, $content);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Post updated successfully';
            } else {
                throw new Exception('Could not update post. You may not have permission.');
            }
            break;
            
        case 'DELETE':
            parse_str(file_get_contents('php://input'), $_DELETE);
            $post_id = intval($_DELETE['post_id'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Invalid post ID');
            }
            
            $result = $social->deletePost($post_id, $user_id);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Post deleted successfully';
            } else {
                throw new Exception('Could not delete post. You may not have permission.');
            }
            break;
            
        case 'GET':
            $post_id = intval($_GET['post_id'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Invalid post ID');
            }
            
            $post = $social->getPost($post_id, $user_id);
            
            if ($post) {
                $post['media'] = $social->getPostMedia($post_id);
                $post['comments'] = $social->getComments($post_id, 10, 0);
                
                $response['success'] = true;
                $response['data'] = $post;
            } else {
                throw new Exception('Post not found');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
