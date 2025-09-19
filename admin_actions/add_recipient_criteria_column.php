<?php
// Add recipient_criteria column to bg_newsletter_campaigns table
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

try {
    // Check if column exists
    $check_sql = "SELECT COLUMN_NAME 
                  FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'bg_newsletter_campaigns' 
                  AND COLUMN_NAME = 'recipient_criteria'";
    
    $exists = $database->getrow($check_sql);
    
    if (!$exists) {
        // Add the column
        $alter_sql = "ALTER TABLE bg_newsletter_campaigns 
                      ADD COLUMN recipient_criteria JSON NULL 
                      AFTER cta_category";
        
        $database->query($alter_sql);
        echo "✅ Successfully added recipient_criteria column to bg_newsletter_campaigns table\n";
        
        // Add index for better performance
        $index_sql = "ALTER TABLE bg_newsletter_campaigns 
                      ADD INDEX idx_recipient_criteria ((CAST(recipient_criteria AS CHAR(100))))";
        
        try {
            $database->query($index_sql);
            echo "✅ Successfully added index on recipient_criteria column\n";
        } catch (Exception $e) {
            echo "⚠️ Could not add index (may already exist): " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️ Column recipient_criteria already exists in bg_newsletter_campaigns table\n";
    }
    
    // Show current table structure
    echo "\nCurrent table structure:\n";
    $columns = $database->getrows("DESCRIBE bg_newsletter_campaigns");
    
    foreach ($columns as $col) {
        echo sprintf("  %-25s %s\n", $col['Field'], $col['Type']);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>