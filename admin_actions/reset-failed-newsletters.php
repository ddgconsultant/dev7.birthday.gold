<?php
/**
 * Reset Failed Newsletters
 * Resets failed newsletter notifications back to 'notsent' status so they can be retried
 */

$pagename = 'reset-failed-newsletters';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Only allow staff access
if (!$account->isstaff()) {
    die("Access denied. Staff only.");
}

echo "<pre>";
echo date('Y-m-d H:i:s') . " - Starting newsletter reset\n";

// Get failed newsletters
$failed_sql = "SELECT notification_id, user_id, title 
               FROM bg_user_notifications 
               WHERE type = 'newsletter' 
               AND status = 'failed'";

$failed = $database->getrows($failed_sql);

if (empty($failed)) {
    echo "No failed newsletters to reset\n";
    exit;
}

echo "Found " . count($failed) . " failed newsletters to reset\n\n";

// Reset them to notsent
$reset_count = 0;
foreach ($failed as $notification) {
    $update_sql = "UPDATE bg_user_notifications 
                   SET status = 'notsent',
                       modify_dt = NOW()
                   WHERE notification_id = :notification_id";
    
    try {
        $database->query($update_sql, ['notification_id' => $notification['notification_id']]);
        $reset_count++;
        echo "✓ Reset notification {$notification['notification_id']} for user {$notification['user_id']}\n";
    } catch (Exception $e) {
        echo "✗ Failed to reset notification {$notification['notification_id']}: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Reset complete: $reset_count notifications reset to 'notsent' status\n";
echo "You can now run the newsletter sender to send these emails.\n";
echo str_repeat('=', 60) . "\n";

echo "</pre>";
?>