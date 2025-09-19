<?php
// Add recipient_criteria column to bg_newsletter_campaigns table
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is admin
if (!$account->isstaff()) {
    die("Access denied");
}

echo "<h1>Add recipient_criteria Column</h1>";
echo "<pre>";

try {
    // Check if column exists
    $check_sql = "SHOW COLUMNS FROM bg_newsletter_campaigns LIKE 'recipient_criteria'";
    $exists = $database->getrow($check_sql);
    
    if (!$exists) {
        // Add the column as TEXT to store JSON-encoded data
        $alter_sql = "ALTER TABLE bg_newsletter_campaigns 
                      ADD COLUMN recipient_criteria TEXT NULL 
                      AFTER cta_category";
        
        $database->query($alter_sql);
        echo "✅ Successfully added recipient_criteria column (TEXT) to bg_newsletter_campaigns table\n";
    } else {
        echo "ℹ️ Column recipient_criteria already exists in bg_newsletter_campaigns table\n";
    }
    
    // Show current table structure
    echo "\nCurrent table structure:\n";
    echo "----------------------------\n";
    $columns = $database->getrows("DESCRIBE bg_newsletter_campaigns");
    
    foreach ($columns as $col) {
        $hasRecipient = ($col['Field'] == 'recipient_criteria') ? ' ← THIS ONE' : '';
        echo sprintf("  %-25s %-20s %s\n", $col['Field'], $col['Type'], $hasRecipient);
    }
    
    echo "\n✅ Done! You can now use the newsletter editor with saved recipient criteria.\n";
    echo "\n<a href='/myaccount/marketing/newsletter-edit.php'>Go to Newsletter Editor</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nSQL Error Details:\n";
    print_r($e);
}

echo "</pre>";
?>