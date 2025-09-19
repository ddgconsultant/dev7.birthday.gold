<?php
/**
 * Create newsletter tracking table if it doesn't exist
 */

$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h1>Newsletter Tracking Table Setup</h1>";

try {
    // Check if table exists
    $tables = $database->getrows("SHOW TABLES LIKE 'bg_newsletter_tracking'");
    
    if (!empty($tables)) {
        echo "<p>✓ Table bg_newsletter_tracking already exists</p>";
        
        // Show structure
        $columns = $database->getrows("SHOW COLUMNS FROM bg_newsletter_tracking");
        echo "<h3>Current Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Table doesn't exist. Creating...</p>";
        
        $sql = "CREATE TABLE bg_newsletter_tracking (
            tracking_id INT AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT NOT NULL,
            user_id INT,
            company_id INT,
            action_type VARCHAR(50) NOT NULL,
            action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            additional_data JSON,
            INDEX idx_campaign (campaign_id),
            INDEX idx_user (user_id),
            INDEX idx_action (action_type),
            INDEX idx_timestamp (action_timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $database->query($sql);
        echo "<p>✓ Table bg_newsletter_tracking created successfully!</p>";
    }
    
    // Check if campaigns table exists
    $tables = $database->getrows("SHOW TABLES LIKE 'bg_newsletter_campaigns'");
    if (empty($tables)) {
        echo "<p>Creating bg_newsletter_campaigns table...</p>";
        
        $sql = "CREATE TABLE bg_newsletter_campaigns (
            campaign_id INT AUTO_INCREMENT PRIMARY KEY,
            newsletter_id INT NOT NULL,
            subject VARCHAR(255),
            sent_date DATETIME,
            sent_count INT DEFAULT 0,
            opens INT DEFAULT 0,
            clicks INT DEFAULT 0,
            last_open DATETIME,
            last_click DATETIME,
            status VARCHAR(50) DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_newsletter (newsletter_id),
            INDEX idx_status (status),
            INDEX idx_sent_date (sent_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $database->query($sql);
        echo "<p>✓ Table bg_newsletter_campaigns created successfully!</p>";
    } else {
        echo "<p>✓ Table bg_newsletter_campaigns already exists</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<a href='/admin/'>Back to Admin</a>";
?>