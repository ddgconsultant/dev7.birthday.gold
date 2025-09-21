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
    
    if (empty($_FILES['media'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['media'];
    $post_id = intval($_POST['post_id'] ?? 0);
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed with error code: ' . $file['error']);
    }
    
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm',
        'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'
    ];
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('File type not allowed: ' . $file['type']);
    }
    
    $max_size = 50 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds 50MB limit');
    }
    
    $media_type = explode('/', $file['type'])[0];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'social_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/social/' . date('Y/m/');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $upload_path = $upload_dir . $filename;
    $web_path = '/uploads/social/' . date('Y/m/') . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    $metadata = [
        'original_name' => $file['name'],
        'size' => $file['size'],
        'type' => $file['type'],
        'uploaded_at' => date('Y-m-d H:i:s')
    ];
    
    if ($media_type === 'image') {
        $image_info = getimagesize($upload_path);
        if ($image_info) {
            $metadata['width'] = $image_info[0];
            $metadata['height'] = $image_info[1];
            
            if ($image_info[0] > 1920 || $image_info[1] > 1920) {
                $thumbnail_path = $upload_dir . 'thumb_' . $filename;
                $thumb_web_path = '/uploads/social/' . date('Y/m/') . 'thumb_' . $filename;
                
                $metadata['thumbnail'] = $thumb_web_path;
            }
        }
    }
    
    if ($post_id) {
        $sql = "SELECT user_id FROM bg_social_posts WHERE post_id = :post_id";
        $result = $database->getrow($sql, ['post_id' => $post_id]);
        
        if (!$result || $result['user_id'] != $user_id) {
            unlink($upload_path);
            throw new Exception('Invalid post or permission denied');
        }
        
        $media_id = $social->addPostMedia($post_id, $media_type, $web_path, $metadata);
        
        $response['success'] = true;
        $response['message'] = 'Media added to post';
        $response['data'] = [
            'media_id' => $media_id,
            'url' => $web_path,
            'type' => $media_type
        ];
    } else {
        $response['success'] = true;
        $response['message'] = 'Media uploaded successfully';
        $response['data'] = [
            'url' => $web_path,
            'type' => $media_type,
            'metadata' => $metadata
        ];
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

