<?php
// Simple test to verify social module database setup
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is logged in
if (empty($current_user_data['user_id'])) {
    die("You must be logged in to test\n");
}

$user_id = $current_user_data['user_id'];

echo "<h2>Social Module Database Test</h2>";
echo "<pre>";

// Test 1: Check if tables exist
echo "=== Checking Tables ===\n";
$tables = ['bg_social_posts', 'bg_social_interactions', 'bg_social_follows', 'bg_social_notifications', 'bg_social_activity'];
foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE :table";
    $result = $database->getrow($sql, ['table' => $table]);
    echo $table . ": " . ($result ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

// Test 2: Try to create a simple post
echo "\n=== Creating Test Post ===\n";
try {
    $post_id = $social->createPost(
        $user_id,
        "Test post from database test script at " . date('Y-m-d H:i:s'),
        null,
        null,
        'public',
        null,
        ['TestPost', 'DatabaseTest']
    );
    echo "✅ Created post with ID: $post_id\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Get user stats
echo "\n=== User Stats ===\n";
try {
    $stats = $social->getUserStats($user_id);
    foreach ($stats as $key => $value) {
        echo "$key: $value\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Get feed
echo "\n=== Feed Test ===\n";
try {
    $posts = $social->getFeed($user_id, 5, 0, 'all');
    echo "Found " . count($posts) . " posts in feed\n";
    foreach ($posts as $post) {
        echo "- Post #" . $post['post_id'] . ": " . substr($post['content'], 0, 50) . "...\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo '<p><a href="/social/" class="btn btn-primary">Go to Social Feed</a></p>';
?>