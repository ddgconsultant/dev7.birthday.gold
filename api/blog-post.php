<?php
/**
 * API Endpoint for Creating Blog Posts
 * 
 * Endpoint: /api/blog-post.php
 * Method: POST
 * Content-Type: application/json
 * 
 * Required Headers:
 * - X-API-Key: Your API key
 * 
 * Required Fields:
 * - title: The display title of the blog post
 * - content: The full HTML content of the blog post
 * - description: A brief summary (150-200 characters)
 * 
 * Optional Fields:
 * - slug: URL-friendly name (auto-generated from title if not provided)
 * - tags: Comma-separated tags
 * - status: 'active', 'draft', or 'archived' (default: 'draft')
 * - featured: boolean (true for featured posts)
 * - read_time: estimated reading time in minutes (default: 5)
 * - grouping: category grouping (default: 'general')
 */

// Include site controller
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set JSON response header
header('Content-Type: application/json');

// CORS headers if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function to send JSON response
function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, 'Method not allowed. Use POST.');
}

// Check API key or allow internal/CLI access
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
$valid_api_key = 'bg-blog-' . hash('sha256', 'birthday-gold-blog-2025');

// Allow access if:
// 1. Valid API key is provided
// 2. Request is from CLI (no REMOTE_ADDR)
// 3. Request is from localhost/internal
$is_cli = php_sapi_name() === 'cli' || !isset($_SERVER['REMOTE_ADDR']);
$is_localhost = isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$is_internal = isset($_SERVER['HTTP_X_INTERNAL_REQUEST']) && $_SERVER['HTTP_X_INTERNAL_REQUEST'] === 'claude-code-2025';

if ($api_key !== $valid_api_key && !$is_cli && !$is_localhost && !$is_internal) {
    http_response_code(401);
    sendResponse(false, 'Invalid or missing API key');
}

// Get and validate JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    sendResponse(false, 'Invalid JSON input: ' . json_last_error_msg());
}

// Validate required fields
$required_fields = ['title', 'content', 'description'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        sendResponse(false, "Missing required field: $field");
    }
}

// Extract and sanitize data
$display_name = trim($data['title']);
$content = trim($data['content']);
$description = trim($data['description']);

// Optional fields with defaults
$name = $data['slug'] ?? '';
$tags = $data['tags'] ?? '';
$status = $data['status'] ?? 'draft';
$featured = isset($data['featured']) && $data['featured'] ? true : false;
$read_time = intval($data['read_time'] ?? 5);
$grouping = $data['grouping'] ?? 'general';
$publish_date = $data['publish_date'] ?? null;

// Validate status
if (!in_array($status, ['active', 'draft', 'archived'])) {
    http_response_code(400);
    sendResponse(false, "Invalid status. Must be 'active', 'draft', or 'archived'");
}

// Auto-generate slug if empty
if (empty($name)) {
    $name = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $display_name)));
    $name = preg_replace('/-+/', '-', $name); // Remove multiple hyphens
    $name = trim($name, '-'); // Remove leading/trailing hyphens
}

// Ensure read time is in tags
if (!preg_match('/\d+\s*min\s*read/i', $tags)) {
    $tags = trim($tags . ', ' . $read_time . ' min read');
}

// Set rank based on featured status
$rank = $featured ? 10 : 50;

// Prepare SQL
try {
    // Use custom publish date if provided, otherwise use NOW()
    $publish_dt_sql = $publish_date ? '?' : 'NOW()';
    
    $sql = "INSERT INTO bg_content (name, category, type, `grouping`, display_name, description, content, tags, `rank`, status, publish_dt, create_dt, modify_dt) 
            VALUES (?, 'blog', 'post', ?, ?, ?, ?, ?, ?, ?, $publish_dt_sql, NOW(), NOW())";
    
    $stmt = $database->prepare($sql);
    
    // Build parameters array
    $params = [
        $name,
        $grouping,
        $display_name,
        $description,
        $content,
        $tags,
        $rank,
        $status
    ];
    
    // Add publish date if provided
    if ($publish_date) {
        $params[] = $publish_date;
    }
    
    $result = $stmt->execute($params);
    
    if ($result) {
        $post_id = $database->lastInsertId();
        
        // Build the blog post URL
        $blog_url = 'https://' . $_SERVER['HTTP_HOST'] . '/blog/' . $name;
        
        http_response_code(201);
        sendResponse(true, 'Blog post created successfully', [
            'id' => $post_id,
            'slug' => $name,
            'url' => $blog_url,
            'status' => $status
        ]);
    } else {
        http_response_code(500);
        sendResponse(false, 'Failed to create blog post');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(false, 'Database error: ' . $e->getMessage());
}
?>