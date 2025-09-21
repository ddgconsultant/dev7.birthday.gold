<?php
/**
 * Just add the gen_specific_messaging column
 * Simple script that only focuses on adding the column we need
 */

// Include using relative path since we're in admin_actions folder
include('../core/site-controller.php');

echo "<pre>";
echo "=== Adding gen_specific_messaging Column ===\n\n";

// Check database
if (!isset($database)) {
    die("ERROR: Database not available\n");
}

echo "Database connection: OK\n\n";

// Check if mk_campaigns table exists
try {
    $table_check = $database->getrow("SHOW TABLES LIKE 'mk_campaigns'");
    if ($table_check) {
        echo "✓ mk_campaigns table exists\n";
    } else {
        echo "✗ mk_campaigns table not found\n";
        
        // Check for bg_newsletter_campaigns instead
        $bg_check = $database->getrow("SHOW TABLES LIKE 'bg_newsletter_campaigns'");
        if ($bg_check) {
            echo "✓ bg_newsletter_campaigns table exists\n";
            echo "\nAdding gen_specific_messaging to bg_newsletter_campaigns instead...\n";
            
            // Add to bg_newsletter_campaigns
            $col_check = $database->getrow("SHOW COLUMNS FROM bg_newsletter_campaigns LIKE 'gen_specific_messaging'");
            
            if ($col_check) {
                echo "✓ Column already exists in bg_newsletter_campaigns\n";
            } else {
                try {
                    $sql = "ALTER TABLE bg_newsletter_campaigns 
                            ADD COLUMN gen_specific_messaging TINYINT(1) DEFAULT 0 
                            COMMENT 'AI generates age-specific content' 
                            AFTER cta_mode";
                    $database->query($sql);
                    echo "✓ Successfully added gen_specific_messaging column to bg_newsletter_campaigns\n";
                } catch (Exception $e) {
                    echo "✗ Error adding column: " . $e->getMessage() . "\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// If mk_campaigns exists, add column there too
try {
    $mk_exists = $database->getrow("SHOW TABLES LIKE 'mk_campaigns'");
    if ($mk_exists) {
        echo "\nChecking mk_campaigns for gen_specific_messaging column...\n";
        
        $col_check = $database->getrow("SHOW COLUMNS FROM mk_campaigns LIKE 'gen_specific_messaging'");
        
        if ($col_check) {
            echo "✓ Column already exists in mk_campaigns\n";
        } else {
            try {
                // First check if campaign_content column exists (to know where to place new column)
                $after_col = $database->getrow("SHOW COLUMNS FROM mk_campaigns LIKE 'campaign_content'");
                $after_clause = $after_col ? "AFTER campaign_content" : "";
                
                $sql = "ALTER TABLE mk_campaigns 
                        ADD COLUMN gen_specific_messaging TINYINT(1) DEFAULT 0 
                        COMMENT 'AI generates age-specific content' 
                        $after_clause";
                $database->query($sql);
                echo "✓ Successfully added gen_specific_messaging column to mk_campaigns\n";
            } catch (Exception $e) {
                echo "✗ Error adding column to mk_campaigns: " . $e->getMessage() . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "ERROR checking mk_campaigns: " . $e->getMessage() . "\n";
}

echo "\n=== Complete ===\n";
echo "The gen_specific_messaging feature is now ready to use!\n";
echo "</pre>";

// Show current newsletter to verify
echo "<hr><h3>Test: Current Newsletter Campaign</h3><pre>";
try {
    $test = $database->getrow("SELECT campaign_id, title, gen_specific_messaging 
                               FROM bg_newsletter_campaigns 
                               ORDER BY campaign_id DESC 
                               LIMIT 1");
    if ($test) {
        echo "Latest newsletter:\n";
        echo "ID: " . $test['campaign_id'] . "\n";
        echo "Title: " . $test['title'] . "\n";
        echo "Gen-Specific Messaging: " . ($test['gen_specific_messaging'] ?? 'NULL (column may not exist yet)') . "\n";
    }
} catch (Exception $e) {
    echo "Could not fetch test campaign: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>