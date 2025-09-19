<?php
/**
 * Simple Newsletter Migration Script with Debug Output
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes

echo "Starting migration script...\n";

// Set the document root - detect Windows vs Linux
if (PHP_OS_FAMILY === 'Windows' || stripos(PHP_OS, 'WIN') === 0) {
    $_SERVER['DOCUMENT_ROOT'] = 'W:/BIRTHDAY_SERVER/dev7.birthday.gold';
} else {
    $_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
}
$publicMode = true;

echo "Including site controller from: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
try {
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
    echo "✓ Site controller loaded\n";
} catch (Exception $e) {
    die("ERROR loading site controller: " . $e->getMessage() . "\n");
}

// Check database connection
if (!isset($database)) {
    die("ERROR: Database connection not available\n");
}
echo "✓ Database connection confirmed\n\n";

echo "=== Step 1: Adding Columns ===\n";

// Simplified column addition - one at a time with individual checks
$columns = [
    'email_subject' => "VARCHAR(255) NULL COMMENT 'Email subject line'",
    'cta_category' => "VARCHAR(100) NULL COMMENT 'CTA block category'",
    'cta_mode' => "VARCHAR(20) DEFAULT 'inclusive' COMMENT 'CTA selection mode'",
    'gen_specific_messaging' => "TINYINT(1) DEFAULT 0 COMMENT 'AI generation flag'",
    'recipient_criteria' => "TEXT NULL COMMENT 'JSON recipient criteria'",
    'newsletter_status' => "VARCHAR(20) DEFAULT 'draft' COMMENT 'Newsletter status'"
];

foreach ($columns as $col_name => $col_def) {
    echo "Checking column: $col_name... ";
    
    try {
        // Check if column exists
        $check = $database->getrow("SHOW COLUMNS FROM mk_campaigns LIKE '$col_name'");
        
        if ($check) {
            echo "already exists\n";
        } else {
            // Add column
            $sql = "ALTER TABLE mk_campaigns ADD COLUMN $col_name $col_def";
            $database->query($sql);
            echo "✓ ADDED\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Step 2: Checking for newsletters to migrate ===\n";

try {
    // Count newsletters
    $count_sql = "SELECT COUNT(*) as total FROM bg_newsletter_campaigns";
    $count = $database->getrow($count_sql);
    echo "Found " . ($count['total'] ?? 0) . " newsletters\n";
    
    if ($count['total'] > 0) {
        // Get newsletters
        $newsletters = $database->getrows("SELECT * FROM bg_newsletter_campaigns LIMIT 10");
        echo "Processing first " . count($newsletters) . " newsletters...\n";
        
        foreach ($newsletters as $idx => $nl) {
            echo ($idx + 1) . ". Newsletter: " . substr($nl['title'], 0, 50) . "... ";
            
            // Check if already linked to mk_campaign
            if (!empty($nl['mk_campaign_id'])) {
                echo "already linked to mk_campaign #" . $nl['mk_campaign_id'] . "\n";
                
                // Update the mk_campaign with newsletter data
                try {
                    $update_sql = "UPDATE mk_campaigns SET 
                                  campaign_type = 'newsletter',
                                  email_subject = :subject,
                                  campaign_content = :content
                                  WHERE campaign_id = :id";
                    
                    $database->query($update_sql, [
                        'subject' => $nl['subject'],
                        'content' => $nl['body_html'],
                        'id' => $nl['mk_campaign_id']
                    ]);
                    echo "  ✓ Updated mk_campaign\n";
                } catch (Exception $e) {
                    echo "  ERROR updating: " . $e->getMessage() . "\n";
                }
            } else {
                echo "needs new mk_campaign\n";
                // Would create new mk_campaign here
            }
        }
    }
} catch (Exception $e) {
    echo "ERROR checking newsletters: " . $e->getMessage() . "\n";
}

echo "\n=== Step 3: Testing Gen-Specific Messaging Column ===\n";

try {
    // Test the gen_specific_messaging column
    $test = $database->getrow("SELECT gen_specific_messaging FROM mk_campaigns LIMIT 1");
    echo "✓ gen_specific_messaging column is accessible\n";
} catch (Exception $e) {
    echo "ERROR: Cannot access gen_specific_messaging column - " . $e->getMessage() . "\n";
}

echo "\n=== Migration Check Complete ===\n";
echo "The gen_specific_messaging feature should now work!\n";
?>