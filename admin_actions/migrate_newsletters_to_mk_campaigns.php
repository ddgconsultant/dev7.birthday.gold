<?php
/**
 * Migrate Newsletter System to mk_campaigns table
 * This script:
 * 1. Adds newsletter-specific columns to mk_campaigns
 * 2. Migrates data from bg_newsletter_campaigns to mk_campaigns
 * 3. Updates the newsletter system to use mk_campaigns directly
 */

// Set the document root for CLI execution
$_SERVER['DOCUMENT_ROOT'] = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold';
$publicMode = true; // Skip auth check
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "=== Newsletter to mk_campaigns Migration ===\n\n";

// Step 1: Add newsletter-specific columns to mk_campaigns if they don't exist
echo "Step 1: Adding newsletter-specific columns to mk_campaigns...\n";

$columns_to_add = [
    'email_subject' => "ALTER TABLE mk_campaigns ADD COLUMN email_subject VARCHAR(255) NULL COMMENT 'Email subject line for newsletters' AFTER campaign_content",
    'cta_category' => "ALTER TABLE mk_campaigns ADD COLUMN cta_category VARCHAR(100) NULL COMMENT 'CTA block category for personalized recommendations' AFTER email_subject",
    'cta_mode' => "ALTER TABLE mk_campaigns ADD COLUMN cta_mode ENUM('inclusive', 'exclusive') DEFAULT 'inclusive' COMMENT 'CTA selection mode' AFTER cta_category",
    'gen_specific_messaging' => "ALTER TABLE mk_campaigns ADD COLUMN gen_specific_messaging TINYINT(1) DEFAULT 0 COMMENT 'If 1, AI generates age-specific content for each recipient' AFTER cta_mode",
    'recipient_criteria' => "ALTER TABLE mk_campaigns ADD COLUMN recipient_criteria JSON NULL COMMENT 'JSON criteria for recipient filtering' AFTER gen_specific_messaging",
    'newsletter_status' => "ALTER TABLE mk_campaigns ADD COLUMN newsletter_status ENUM('draft', 'scheduled', 'sending', 'sent', 'cancelled') DEFAULT 'draft' COMMENT 'Newsletter send status' AFTER recipient_criteria"
];

foreach ($columns_to_add as $column => $sql) {
    // Check if column exists
    $check_sql = "SHOW COLUMNS FROM mk_campaigns LIKE '$column'";
    $exists = $database->getrow($check_sql);
    
    if (!$exists) {
        try {
            $database->query($sql);
            echo "  ✓ Added column: $column\n";
        } catch (Exception $e) {
            echo "  ✗ Error adding column $column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  - Column $column already exists\n";
    }
}

echo "\nStep 2: Migrating existing newsletter campaigns...\n";

// Get all newsletter campaigns from bg_newsletter_campaigns
$newsletters_sql = "SELECT nc.*, 
                           mk.campaign_id as existing_mk_id,
                           mk.campaign_name as existing_mk_name
                    FROM bg_newsletter_campaigns nc
                    LEFT JOIN mk_campaigns mk ON nc.mk_campaign_id = mk.campaign_id
                    ORDER BY nc.campaign_id";

$newsletters = $database->getrows($newsletters_sql);

if (empty($newsletters)) {
    echo "  No newsletters to migrate.\n";
} else {
    $migrated = 0;
    $updated = 0;
    
    foreach ($newsletters as $newsletter) {
        if ($newsletter['existing_mk_id']) {
            // Update existing mk_campaign with newsletter data
            $update_sql = "UPDATE mk_campaigns SET 
                          campaign_type = 'newsletter',
                          email_subject = :email_subject,
                          campaign_content = :campaign_content,
                          cta_category = :cta_category,
                          cta_mode = :cta_mode,
                          gen_specific_messaging = :gen_specific_messaging,
                          recipient_criteria = :recipient_criteria,
                          newsletter_status = :newsletter_status,
                          start_date = :start_date
                          WHERE campaign_id = :campaign_id";
            
            $params = [
                'email_subject' => $newsletter['subject'],
                'campaign_content' => $newsletter['body_html'],
                'cta_category' => $newsletter['cta_category'],
                'cta_mode' => $newsletter['cta_mode'] ?? 'inclusive',
                'gen_specific_messaging' => $newsletter['gen_specific_messaging'] ?? 0,
                'recipient_criteria' => $newsletter['recipient_criteria'],
                'newsletter_status' => $newsletter['status'],
                'start_date' => $newsletter['send_dt'],
                'campaign_id' => $newsletter['existing_mk_id']
            ];
            
            try {
                $database->query($update_sql, $params);
                echo "  ✓ Updated mk_campaign #{$newsletter['existing_mk_id']} with newsletter data\n";
                $updated++;
            } catch (Exception $e) {
                echo "  ✗ Error updating mk_campaign #{$newsletter['existing_mk_id']}: " . $e->getMessage() . "\n";
            }
        } else {
            // Create new mk_campaign for orphaned newsletter
            $insert_sql = "INSERT INTO mk_campaigns 
                          (company_id, campaign_name, campaign_type, email_subject, campaign_content,
                           cta_category, cta_mode, gen_specific_messaging, recipient_criteria,
                           newsletter_status, start_date, status, create_by, create_dt)
                          VALUES 
                          (:company_id, :campaign_name, 'newsletter', :email_subject, :campaign_content,
                           :cta_category, :cta_mode, :gen_specific_messaging, :recipient_criteria,
                           :newsletter_status, :start_date, 'active', :create_by, :create_dt)";
            
            // Get company_id from creator
            $creator_sql = "SELECT company_id FROM bg_users WHERE user_id = :user_id";
            $creator = $database->getrow($creator_sql, ['user_id' => $newsletter['created_by']]);
            $company_id = $creator['company_id'] ?? 1; // Default to Birthday Gold
            
            $params = [
                'company_id' => $company_id,
                'campaign_name' => $newsletter['title'],
                'email_subject' => $newsletter['subject'],
                'campaign_content' => $newsletter['body_html'],
                'cta_category' => $newsletter['cta_category'],
                'cta_mode' => $newsletter['cta_mode'] ?? 'inclusive',
                'gen_specific_messaging' => $newsletter['gen_specific_messaging'] ?? 0,
                'recipient_criteria' => $newsletter['recipient_criteria'],
                'newsletter_status' => $newsletter['status'],
                'start_date' => $newsletter['send_dt'],
                'create_by' => $newsletter['created_by'],
                'create_dt' => $newsletter['created_dt'] ?? date('Y-m-d H:i:s')
            ];
            
            try {
                $database->query($insert_sql, $params);
                $new_mk_id = $database->lastInsertId();
                
                // Update the bg_newsletter_campaigns record with the new mk_campaign_id
                $link_sql = "UPDATE bg_newsletter_campaigns SET mk_campaign_id = :mk_id WHERE campaign_id = :nc_id";
                $database->query($link_sql, ['mk_id' => $new_mk_id, 'nc_id' => $newsletter['campaign_id']]);
                
                echo "  ✓ Created new mk_campaign #{$new_mk_id} for newsletter '{$newsletter['title']}'\n";
                $migrated++;
            } catch (Exception $e) {
                echo "  ✗ Error creating mk_campaign for newsletter '{$newsletter['title']}': " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nMigration Summary:\n";
    echo "  - Updated existing mk_campaigns: $updated\n";
    echo "  - Created new mk_campaigns: $migrated\n";
    echo "  - Total newsletters processed: " . count($newsletters) . "\n";
}

// Step 3: Create a mapping table for backward compatibility during transition
echo "\nStep 3: Creating newsletter mapping view for backward compatibility...\n";

$view_sql = "CREATE OR REPLACE VIEW v_newsletter_campaigns AS
             SELECT 
                mk.campaign_id as campaign_id,
                mk.campaign_id as mk_campaign_id,
                mk.campaign_name as title,
                mk.email_subject as subject,
                mk.campaign_content as body_html,
                mk.cta_category,
                mk.cta_mode,
                mk.gen_specific_messaging,
                mk.recipient_criteria,
                mk.start_date as send_dt,
                mk.newsletter_status as status,
                mk.create_by as created_by,
                mk.create_dt as created_dt,
                mk.modify_by,
                mk.modify_dt
             FROM mk_campaigns mk
             WHERE mk.campaign_type = 'newsletter'";

try {
    $database->query($view_sql);
    echo "  ✓ Created compatibility view v_newsletter_campaigns\n";
} catch (Exception $e) {
    echo "  ✗ Error creating view: " . $e->getMessage() . "\n";
}

// Step 4: Update queue table to use mk_campaign_id
echo "\nStep 4: Updating newsletter queue table...\n";

// Check if bg_newsletter_queue exists
$queue_check = $database->getrow("SHOW TABLES LIKE 'bg_newsletter_queue'");
if ($queue_check) {
    // Add mk_campaign_id column if it doesn't exist
    $col_check = $database->getrow("SHOW COLUMNS FROM bg_newsletter_queue LIKE 'mk_campaign_id'");
    if (!$col_check) {
        try {
            $database->query("ALTER TABLE bg_newsletter_queue ADD COLUMN mk_campaign_id INT NULL AFTER campaign_id");
            echo "  ✓ Added mk_campaign_id column to bg_newsletter_queue\n";
            
            // Update existing queue entries
            $update_queue_sql = "UPDATE bg_newsletter_queue q 
                                JOIN bg_newsletter_campaigns nc ON q.campaign_id = nc.campaign_id 
                                SET q.mk_campaign_id = nc.mk_campaign_id 
                                WHERE nc.mk_campaign_id IS NOT NULL";
            $database->query($update_queue_sql);
            echo "  ✓ Updated queue entries with mk_campaign_id\n";
        } catch (Exception $e) {
            echo "  ✗ Error updating queue table: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  - mk_campaign_id column already exists in queue table\n";
    }
}

echo "\n=== Migration Complete ===\n";
echo "\nNext Steps:\n";
echo "1. Update newsletter-edit.php to use mk_campaigns table directly\n";
echo "2. Update newsletter-create.php to create campaigns in mk_campaigns\n";
echo "3. Test the newsletter system thoroughly\n";
echo "4. Once verified, the bg_newsletter_campaigns table can be archived\n";
?>