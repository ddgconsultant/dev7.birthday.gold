#!/usr/bin/env php
<?php
/**
 * CLI tool for creating blog posts
 * Usage: php blog-post-cli.php "Title" "Content" "Description" [options]
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Include site controller
include(__DIR__ . '/../core/site-controller.php');

// Function to create a blog post
function createBlogPost($data) {
    global $database;
    
    // Extract data
    $display_name = trim($data['title']);
    $content = trim($data['content']);
    $description = trim($data['description']);
    $name = $data['slug'] ?? '';
    $tags = $data['tags'] ?? '';
    $status = $data['status'] ?? 'draft';
    $featured = $data['featured'] ?? false;
    $read_time = intval($data['read_time'] ?? 5);
    $grouping = $data['grouping'] ?? 'general';
    
    // Auto-generate slug if empty
    if (empty($name)) {
        $name = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $display_name)));
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');
    }
    
    // Ensure read time is in tags
    if (!preg_match('/\d+\s*min\s*read/i', $tags)) {
        $tags = trim($tags . ', ' . $read_time . ' min read');
    }
    
    // Set rank based on featured status
    $rank = $featured ? 10 : 50;
    
    // Insert into database
    try {
        $sql = "INSERT INTO bg_content (name, category, type, `grouping`, display_name, description, content, tags, `rank`, status, publish_dt, create_dt, modify_dt) 
                VALUES (?, 'blog', 'post', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())";
        
        $stmt = $database->prepare($sql);
        $result = $stmt->execute([
            $name,
            $grouping,
            $display_name,
            $description,
            $content,
            $tags,
            $rank,
            $status
        ]);
        
        if ($result) {
            $post_id = $database->lastInsertId();
            $blog_url = 'https://dev7.birthday.gold/blog/' . $name;
            
            return [
                'success' => true,
                'id' => $post_id,
                'slug' => $name,
                'url' => $blog_url,
                'status' => $status
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Unknown error'
    ];
}

// Parse command line arguments
$options = getopt('', ['title:', 'content:', 'description:', 'tags:', 'status:', 'featured', 'read_time:', 'grouping:', 'file:']);

// Check if using file input
if (isset($options['file'])) {
    $json_file = $options['file'];
    if (!file_exists($json_file)) {
        die("Error: File '$json_file' not found.\n");
    }
    
    $json_content = file_get_contents($json_file);
    $data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error: Invalid JSON in file - " . json_last_error_msg() . "\n");
    }
} else {
    // Build data from command line options
    if (!isset($options['title']) || !isset($options['content']) || !isset($options['description'])) {
        echo "Usage: php blog-post-cli.php --title=\"Title\" --content=\"Content\" --description=\"Description\" [options]\n";
        echo "   Or: php blog-post-cli.php --file=blog-post.json\n\n";
        echo "Options:\n";
        echo "  --tags=\"tag1, tag2\"     Comma-separated tags\n";
        echo "  --status=active|draft   Status (default: draft)\n";
        echo "  --featured              Mark as featured\n";
        echo "  --read_time=5           Reading time in minutes\n";
        echo "  --grouping=general      Category grouping\n";
        die();
    }
    
    $data = [
        'title' => $options['title'],
        'content' => $options['content'],
        'description' => $options['description'],
        'tags' => $options['tags'] ?? '',
        'status' => $options['status'] ?? 'draft',
        'featured' => isset($options['featured']),
        'read_time' => $options['read_time'] ?? 5,
        'grouping' => $options['grouping'] ?? 'general'
    ];
}

// Create the blog post
echo "Creating blog post...\n";
$result = createBlogPost($data);

if ($result['success']) {
    echo "✅ Blog post created successfully!\n";
    echo "ID: " . $result['id'] . "\n";
    echo "Slug: " . $result['slug'] . "\n";
    echo "URL: " . $result['url'] . "\n";
    echo "Status: " . $result['status'] . "\n";
} else {
    echo "❌ Error creating blog post: " . $result['error'] . "\n";
    exit(1);
}
?>