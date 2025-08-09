<?php
// Minimal verification script
?>
<!DOCTYPE html>
<html>
<head>
    <title>Social Module Verification</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
<h2>Social Module Setup Verification</h2>
<pre>
<?php
// Include site controller
$addClasses[] = 'Social';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "=== Class Loading ===\n";
if (class_exists('Social')) {
    echo "<span class='success'>✅ Social class loaded</span>\n";
} else {
    echo "<span class='error'>❌ Social class NOT loaded</span>\n";
}

if (isset($social)) {
    echo "<span class='success'>✅ \$social instance created</span>\n";
} else {
    echo "<span class='error'>❌ \$social instance NOT created</span>\n";
}

echo "\n=== Database Tables ===\n";
$tables = [
    'bg_social_posts' => 'Posts table',
    'bg_social_interactions' => 'Interactions (likes/bookmarks)',
    'bg_social_follows' => 'User follows',
    'bg_social_notifications' => 'Notifications',
    'bg_social_activity' => 'Activity log'
];

foreach ($tables as $table => $desc) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = $database->query($sql);
    if ($result && $result->rowCount() > 0) {
        echo "<span class='success'>✅ $table - $desc</span>\n";
    } else {
        echo "<span class='error'>❌ $table - $desc</span>\n";
    }
}

echo "\n=== Authentication ===\n";
if (!empty($current_user_data['user_id'])) {
    echo "<span class='success'>✅ Logged in as user #" . $current_user_data['user_id'] . "</span>\n";
    echo "<span class='info'>   Username: " . ($current_user_data['username'] ?? 'N/A') . "</span>\n";
} else {
    echo "<span class='error'>❌ Not logged in</span>\n";
    echo "<span class='info'>   Login required for full functionality</span>\n";
}

echo "\n=== Quick Tests ===\n";
if (!empty($current_user_data['user_id']) && isset($social)) {
    try {
        $stats = $social->getUserStats($current_user_data['user_id']);
        echo "<span class='success'>✅ getUserStats() works</span>\n";
        echo "<span class='info'>   Posts: " . $stats['post_count'] . "</span>\n";
        echo "<span class='info'>   Followers: " . $stats['follower_count'] . "</span>\n";
        echo "<span class='info'>   Following: " . $stats['following_count'] . "</span>\n";
    } catch (Exception $e) {
        echo "<span class='error'>❌ getUserStats() failed: " . $e->getMessage() . "</span>\n";
    }
} else {
    echo "<span class='info'>⚠️ Login required to test methods</span>\n";
}

?>
</pre>

<h3>Actions</h3>
<ul>
    <?php if (empty($current_user_data['user_id'])): ?>
        <li><a href="/login.php?redirect=/social/verify-setup.php">Login to run full tests</a></li>
    <?php else: ?>
        <li><a href="/social/">Go to Social Feed</a></li>
        <li><a href="/social/test-create-posts.php">Create Sample Posts</a></li>
        <li><a href="/social/test-db.php">Run Database Tests</a></li>
    <?php endif; ?>
</ul>
</body>
</html>