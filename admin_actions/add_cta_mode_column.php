<?php
// Add cta_mode column to bg_newsletter_campaigns table
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Access denied. Admin privileges required.");
}

try {
    // Check if column already exists
    $check_sql = "SHOW COLUMNS FROM bg_newsletter_campaigns LIKE 'cta_mode'";
    $exists = $database->getrow($check_sql);
    
    if ($exists) {
        echo "Column 'cta_mode' already exists in bg_newsletter_campaigns table.\n";
    } else {
        // Add the column
        $alter_sql = "ALTER TABLE bg_newsletter_campaigns 
                      ADD COLUMN cta_mode VARCHAR(20) DEFAULT 'inclusive' 
                      AFTER cta_category";
        
        $database->query($alter_sql);
        echo "Successfully added 'cta_mode' column to bg_newsletter_campaigns table.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>