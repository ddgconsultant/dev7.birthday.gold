<?php
/**
 * Add gen_specific_messaging to bg_newsletter_campaigns table
 */

include('../core/site-controller.php');

echo "<pre>";
echo "=== Adding gen_specific_messaging to bg_newsletter_campaigns ===\n\n";

if (!isset($database)) {
    die("ERROR: Database not available\n");
}

// Add the column to bg_newsletter_campaigns
try {
    echo "Checking if column already exists...\n";
    $check = $database->query("SHOW COLUMNS FROM bg_newsletter_campaigns LIKE 'gen_specific_messaging'");
    
    if ($check && $check->rowCount() > 0) {
        echo "✓ Column already exists in bg_newsletter_campaigns\n";
    } else {
        echo "Adding column to bg_newsletter_campaigns...\n";
        
        // Try to add after cta_mode if it exists, otherwise just add at end
        $cta_check = $database->query("SHOW COLUMNS FROM bg_newsletter_campaigns LIKE 'cta_mode'");
        
        if ($cta_check && $cta_check->rowCount() > 0) {
            $sql = "ALTER TABLE bg_newsletter_campaigns 
                    ADD COLUMN gen_specific_messaging TINYINT(1) DEFAULT 0 
                    COMMENT 'AI generates age-specific content' 
                    AFTER cta_mode";
        } else {
            $sql = "ALTER TABLE bg_newsletter_campaigns 
                    ADD COLUMN gen_specific_messaging TINYINT(1) DEFAULT 0 
                    COMMENT 'AI generates age-specific content'";
        }
        
        $database->query($sql);
        echo "✓ Successfully added gen_specific_messaging column!\n";
    }
    
    // Verify it worked
    echo "\nVerifying...\n";
    $test = $database->query("SELECT campaign_id, title, gen_specific_messaging 
                             FROM bg_newsletter_campaigns 
                             ORDER BY campaign_id DESC 
                             LIMIT 1");
    
    if ($test) {
        $row = $test->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "✓ Latest newsletter can be queried with gen_specific_messaging\n";
            echo "  Campaign: " . $row['title'] . "\n";
            echo "  Gen-Specific Messaging: " . ($row['gen_specific_messaging'] ? 'Enabled' : 'Disabled') . "\n";
        }
    }
    
    echo "\n=== SUCCESS ===\n";
    echo "The Gen-Specific Messaging feature is now fully functional!\n";
    echo "\nYou can now:\n";
    echo "1. Toggle 'Gen-Specific Messaging' in the newsletter editor\n";
    echo "2. The setting will be saved with each campaign\n";
    echo "3. When enabled, AI will generate age-appropriate content for each recipient\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nSQL State: " . $e->getCode() . "\n";
}

echo "</pre>";
echo "<hr>";
echo "<p><a href='/myaccount/marketing/newsletter-edit.php' class='btn btn-primary'>Go to Newsletter Editor</a></p>";
?>