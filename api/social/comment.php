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
            $comment_text = trim($_POST['comment_text'] ?? '');
            $parent_comment_id = !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
            
            if (!$post_id || empty($comment_text)) {
                throw new Exception('Invalid post ID or comment text');
            }
            
            $comment_id = $social->addComment($post_id, $user_id, $comment_text, $parent_comment_id);
            
            $response['success'] = true;
            $response['message'] = 'Comment added successfully';
            $response['data'] = ['comment_id' => $comment_id];
            break;
            
        case 'DELETE':
            parse_str(file_get_contents('php://input'), $_DELETE);
            $comment_id = intval($_DELETE['comment_id'] ?? 0);
            
            if (!$comment_id) {
                throw new Exception('Invalid comment ID');
            }
            
            $result = $social->deleteComment($comment_id, $user_id);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Comment deleted successfully';
            } else {
                throw new Exception('Could not delete comment. You may not have permission.');
            }
            break;
            
        case 'GET':
            $post_id = intval($_GET['post_id'] ?? 0);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Invalid post ID');
            }
            
            $comments = $social->getComments($post_id, $limit, $offset);
            
            $response['success'] = true;
            $response['data'] = [
                'comments' => $comments,
                'count' => count($comments),
                'has_more' => count($comments) >= $limit
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

