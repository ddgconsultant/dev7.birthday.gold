<?php
/**
 * Add gen_specific_messaging column to bg_newsletter_campaigns table
 * This column stores whether AI should generate age-specific content for each recipient
 */

$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
include($_SERVER['DOCUMENT_ROOT'] . '/core/connection.inc');

// Check if the column already exists
$check_sql = "SHOW COLUMNS FROM bg_newsletter_campaigns LIKE 'gen_specific_messaging'";
$column_exists = $database->getrow($check_sql);

if (!$column_exists) {
    // Add the gen_specific_messaging column
    $alter_sql = "ALTER TABLE bg_newsletter_campaigns 
                  ADD COLUMN gen_specific_messaging TINYINT(1) DEFAULT 0 
                  COMMENT 'If 1, AI generates age-specific content for each recipient'
                  AFTER cta_mode";
    
    try {
        $database->query($alter_sql);
        echo "✓ Successfully added gen_specific_messaging column to bg_newsletter_campaigns table.\n";
    } catch (Exception $e) {
        echo "✗ Error adding gen_specific_messaging column: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ gen_specific_messaging column already exists in bg_newsletter_campaigns table.\n";
}

// Display current table structure
echo "\nCurrent relevant columns in bg_newsletter_campaigns:\n";
$columns_sql = "SHOW COLUMNS FROM bg_newsletter_campaigns WHERE Field IN ('cta_category', 'cta_mode', 'gen_specific_messaging', 'recipient_criteria')";
$columns = $database->getrows($columns_sql);

foreach ($columns as $column) {
    echo " - " . $column['Field'] . " (" . $column['Type'] . ")" . 
         ($column['Default'] !== null ? " DEFAULT " . $column['Default'] : "") . "\n";
}
?>