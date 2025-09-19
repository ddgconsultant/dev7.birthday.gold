<?php
// Simple test to check if improved version works
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "Social class loaded: " . (class_exists('Social') ? 'Yes' : 'No') . "<br>";
echo "Social object exists: " . (isset($social) ? 'Yes' : 'No') . "<br>";

// Try to get posts
try {
    $post_count = $database->getrow("SELECT COUNT(*) as count FROM bg_social_posts WHERE status = 'active'");
    echo "Active posts: " . $post_count['count'] . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo '<a href="/social/index-improved">View Improved Social Feed</a><br>';
echo '<a href="/social/">View Original Social Feed</a>';
?>