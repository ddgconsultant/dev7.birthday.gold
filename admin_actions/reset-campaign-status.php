<?php
/**
 * Reset campaign status for testing
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<pre>";
echo "=== Reset Campaign Status ===\n\n";

// Find the campaign
$campaign = $database->getrow(
    "SELECT campaign_id, campaign_name, newsletter_status, status 
     FROM mk_campaigns 
     WHERE campaign_id = 191"
);

if ($campaign) {
    echo "Found campaign: {$campaign['campaign_name']}\n";
    echo "Current newsletter_status: {$campaign['newsletter_status']}\n";
    echo "Current status: {$campaign['status']}\n\n";
    
    // First clear any existing notifications for this campaign
    echo "Clearing existing notifications for this campaign...\n";
    $delete_sql = "DELETE FROM bg_user_notifications 
                   WHERE type = 'newsletter' 
                   AND category = :campaign_category";
    
    $delete_result = $database->query($delete_sql, ['campaign_category' => 'campaign_191']);
    echo "✓ Deleted existing notifications\n\n";
    
    // Reset to scheduled
    echo "Resetting newsletter_status to 'scheduled'...\n";
    
    $update_sql = "UPDATE mk_campaigns 
                  SET newsletter_status = 'scheduled',
                      status = 'scheduled' 
                  WHERE campaign_id = :campaign_id";
    
    $database->query($update_sql, ['campaign_id' => 191]);
    
    echo "✓ Campaign reset to 'scheduled' status\n\n";
    
    // Verify
    $updated = $database->getrow(
        "SELECT newsletter_status 
         FROM mk_campaigns 
         WHERE campaign_id = 191"
    );
    
    echo "New newsletter_status: {$updated['newsletter_status']}\n";
    
    echo "\nNow you can run the queue scheduler:\n";
    echo "https://dev7.birthday.gold/admin_actions/scheduler--mk-newsletter-queue-v2.php\n";
} else {
    echo "Campaign not found!\n";
}

echo "</pre>";
?>