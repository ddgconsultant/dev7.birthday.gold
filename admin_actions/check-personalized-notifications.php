<?php
/**
 * Check if notifications have been personalized
 */

include('../core/site-controller.php');

echo "<pre>";
echo "=== Checking Personalized Notifications ===\n\n";

// Check a sample of notifications
$notifications = $database->getrows(
    "SELECT notification_id, user_id, title, 
            LEFT(message, 200) as message_preview,
            status, modify_dt
     FROM bg_user_notifications 
     WHERE type = 'newsletter'
     AND category = 'campaign_191'
     ORDER BY modify_dt DESC
     LIMIT 5"
);

foreach ($notifications as $n) {
    echo "Notification ID: {$n['notification_id']}\n";
    echo "User ID: {$n['user_id']}\n";
    echo "Status: {$n['status']}\n";
    echo "Modified: {$n['modify_dt']}\n";
    echo "Title: {$n['title']}\n";
    echo "Message Preview: {$n['message_preview']}\n";
    
    // Check if it has placeholders or actual content
    if (strpos($n['title'], '[[') !== false) {
        echo "⚠️ Title still has placeholders!\n";
    } else {
        echo "✓ Title is personalized\n";
    }
    
    if (strpos($n['message_preview'], '[[') !== false || strpos($n['message_preview'], 'campaign_id') !== false) {
        echo "⚠️ Message appears to be raw JSON or has placeholders\n";
    } else {
        echo "✓ Message appears to be personalized HTML\n";
    }
    
    echo "---\n\n";
}

// Check status counts
$stats = $database->getrow(
    "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'notsent' THEN 1 ELSE 0 END) as notsent,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
     FROM bg_user_notifications 
     WHERE type = 'newsletter'
     AND category = 'campaign_191'"
);

echo "=== Status Summary ===\n";
echo "Total: {$stats['total']}\n";
echo "Pending (need personalization): {$stats['pending']}\n";
echo "Not Sent (ready for sender): {$stats['notsent']}\n";
echo "Sent: {$stats['sent']}\n";
echo "Failed: {$stats['failed']}\n";

echo "</pre>";
?>