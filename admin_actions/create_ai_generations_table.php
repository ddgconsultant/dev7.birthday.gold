<?php
// Script to create the bg_ai_generations table
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h3>Creating bg_ai_generations Table</h3>";

try {
    // Check if table exists
    $check_sql = "SHOW TABLES LIKE 'bg_ai_generations'";
    $result = $database->getone($check_sql);
    
    if ($result) {
        echo "<p style='color: orange;'>⚠ Table bg_ai_generations already exists</p>";
    } else {
        // Create table
        $create_sql = "CREATE TABLE IF NOT EXISTS `bg_ai_generations` (
          `generation_id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `generation_type` varchar(50) NOT NULL,
          `prompt` text,
          `response` text,
          `model` varchar(50) DEFAULT 'gpt-4o-mini',
          `tokens_used` int(11) DEFAULT NULL,
          `created_dt` datetime NOT NULL,
          PRIMARY KEY (`generation_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_generation_type` (`generation_type`),
          KEY `idx_created_dt` (`created_dt`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $database->query($create_sql);
        echo "<p style='color: green;'>✓ Table bg_ai_generations created successfully!</p>";
    }
    
    // Verify table structure
    $describe_sql = "DESCRIBE bg_ai_generations";
    $columns = $database->getrows($describe_sql);
    
    echo "<h4>Table Structure:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='/myaccount/marketing/newsletter-edit.php'>Return to Newsletter Editor</a></p>";
?>