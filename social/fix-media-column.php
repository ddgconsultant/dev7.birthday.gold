<?php
// Add missing media_type column if it doesn't exist
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h2>Fixing Social Tables</h2>";
echo "<pre>";

// Check if media_type column exists
$check_sql = "SHOW COLUMNS FROM bg_social_posts LIKE 'media_type'";
$result = $database->query($check_sql);

if ($result && $result->rowCount() == 0) {
    // Column doesn't exist, add it
    echo "Adding media_type column to bg_social_posts...\n";
    
    $alter_sql = "ALTER TABLE bg_social_posts 
                  ADD COLUMN `media_type` VARCHAR(20) DEFAULT NULL 
                  AFTER `content`";
    
    try {
        $database->query($alter_sql);
        echo "✅ Successfully added media_type column\n";
    } catch (Exception $e) {
        echo "❌ Error adding column: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ media_type column already exists\n";
}

// Also check and add like_count, comment_count, share_count if missing
$count_columns = ['like_count', 'comment_count', 'share_count', 'view_count'];

foreach ($count_columns as $column) {
    $check_sql = "SHOW COLUMNS FROM bg_social_posts LIKE '$column'";
    $result = $database->query($check_sql);
    
    if ($result && $result->rowCount() == 0) {
        echo "Adding $column column...\n";
        $alter_sql = "ALTER TABLE bg_social_posts ADD COLUMN `$column` INT(11) NOT NULL DEFAULT 0";
        
        try {
            $database->query($alter_sql);
            echo "✅ Added $column\n";
        } catch (Exception $e) {
            echo "❌ Error adding $column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ $column already exists\n";
    }
}

echo "\n";
echo "Table structure fixed!\n";
echo "</pre>";

echo '<div style="margin: 20px;">';
echo '<a href="/social/seed-birthday-posts.php" class="btn btn-primary">Now Create Birthday Posts</a>';
echo '</div>';
?>