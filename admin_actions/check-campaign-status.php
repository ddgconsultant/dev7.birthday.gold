<?php
/**
 * Check campaign status fields
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<pre>";
echo "=== Campaign Status Check ===\n\n";

$campaign = $database->getrow(
    "SELECT campaign_id, campaign_name, campaign_type, 
            status, newsletter_status, start_date, end_date,
            (SELECT COUNT(*) FROM bg_user_notifications 
             WHERE category = CONCAT('campaign_', c.campaign_id)) as total_notifications,
            (SELECT COUNT(*) FROM bg_user_notifications 
             WHERE category = CONCAT('campaign_', c.campaign_id) 
             AND status = 'pending') as pending,
            (SELECT COUNT(*) FROM bg_user_notifications 
             WHERE category = CONCAT('campaign_', c.campaign_id) 
             AND status = 'sent') as sent
     FROM mk_campaigns c
     WHERE campaign_id = 191"
);

if ($campaign) {
    echo "Campaign: {$campaign['campaign_name']}\n";
    echo "ID: {$campaign['campaign_id']}\n";
    echo "Type: {$campaign['campaign_type']}\n";
    echo "Status (general): {$campaign['status']}\n";
    echo "Newsletter Status: {$campaign['newsletter_status']}\n";
    echo "Start Date: {$campaign['start_date']}\n";
    echo "End Date: {$campaign['end_date']}\n";
    echo "\nNotifications:\n";
    echo "Total: {$campaign['total_notifications']}\n";
    echo "Pending: {$campaign['pending']}\n";
    echo "Sent: {$campaign['sent']}\n";
    
    echo "\n=== What the UI is showing ===\n";
    echo "The campaigns.php page is showing the 'status' field: {$campaign['status']}\n";
    echo "But the scheduler uses 'newsletter_status' field: {$campaign['newsletter_status']}\n";
    
    echo "\n=== Fix Options ===\n";
    echo "1. Update the 'status' field to match newsletter_status\n";
    echo "2. Update campaigns.php to show newsletter_status for newsletters\n";
    echo "3. Keep both fields in sync\n";
    
    // Let's update the status field to match
    if ($campaign['sent'] > 0 && $campaign['pending'] < 50) {
        echo "\n=== Updating Status ===\n";
        echo "Campaign has sent emails. Updating status to 'sent'...\n";
        
        $update_sql = "UPDATE mk_campaigns 
                      SET status = 'sent',
                          newsletter_status = 'sent' 
                      WHERE campaign_id = 191";
        
        $database->query($update_sql);
        echo "✓ Status updated to 'sent'\n";
    } elseif ($campaign['pending'] > 0) {
        echo "\n=== Updating Status ===\n";
        echo "Campaign has pending emails. Updating status to 'sending'...\n";
        
        $update_sql = "UPDATE mk_campaigns 
                      SET status = 'sending'
                      WHERE campaign_id = 191";
        
        $database->query($update_sql);
        echo "✓ Status updated to 'sending'\n";
    }
}

echo "</pre>";
?>