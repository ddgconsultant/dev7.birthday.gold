<?php
/**
 * Fix Newsletter Email Addresses
 * Updates sent_to field from "email" to actual email addresses
 */

$pagename = 'fix-newsletter-emails';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Only allow staff access
if (!$account->isstaff()) {
    die("Access denied. Staff only.");
}

echo "<pre>";
echo date('Y-m-d H:i:s') . " - Starting newsletter email fix\n";
echo str_repeat('=', 60) . "\n";

// First, count how many need to be reset
$count_sql = "SELECT COUNT(*) as count FROM bg_user_notifications 
              WHERE type = 'newsletter' AND status = 'failed'";
$count_result = $database->getrow($count_sql);
$reset_count = $count_result['count'] ?? 0;

if ($reset_count > 0) {
    // Reset failed ones to notsent
    $reset_sql = "UPDATE bg_user_notifications 
                  SET status = 'notsent' 
                  WHERE type = 'newsletter' 
                  AND status = 'failed'";
    
    $database->query($reset_sql);
    echo "✓ Reset $reset_count failed notifications to 'notsent'\n\n";
}

// Get notifications that need email addresses fixed
$notifications_sql = "SELECT n.notification_id, n.user_id, n.sent_to, n.options, u.email
                     FROM bg_user_notifications n
                     JOIN bg_users u ON n.user_id = u.user_id
                     WHERE n.type = 'newsletter' 
                     AND (n.sent_to = 'email' OR n.sent_to IS NULL OR n.sent_to = '')";

$notifications = $database->getrows($notifications_sql);

if (empty($notifications)) {
    echo "No newsletters need email addresses fixed\n";
    
    // Show current status
    $status_sql = "SELECT status, COUNT(*) as count, 
                   SUM(CASE WHEN sent_to LIKE '%@%' THEN 1 ELSE 0 END) as with_email
                   FROM bg_user_notifications 
                   WHERE type = 'newsletter' 
                   GROUP BY status";
    $statuses = $database->getrows($status_sql);
    
    echo "\nCurrent newsletter status:\n";
    foreach ($statuses as $status) {
        echo "  - {$status['status']}: {$status['count']} total, {$status['with_email']} with email addresses\n";
    }
    exit;
}

echo "Found " . count($notifications) . " newsletters to fix\n\n";

// Fix each notification
$fixed_count = 0;
$error_count = 0;

foreach ($notifications as $notification) {
    // Try to get email from user record first
    $email = $notification['email'];
    
    // If not found, try to extract from options JSON
    if (empty($email) && !empty($notification['options'])) {
        $options = json_decode($notification['options'], true);
        if (!empty($options['user_data']['email'])) {
            $email = $options['user_data']['email'];
        }
    }
    
    if (!empty($email)) {
        $update_sql = "UPDATE bg_user_notifications 
                      SET sent_to = :email,
                          modify_dt = NOW()
                      WHERE notification_id = :notification_id";
        
        try {
            $database->query($update_sql, [
                'email' => $email,
                'notification_id' => $notification['notification_id']
            ]);
            $fixed_count++;
            echo "✓ Fixed notification {$notification['notification_id']} - User {$notification['user_id']}: $email\n";
        } catch (Exception $e) {
            $error_count++;
            echo "✗ Failed to fix notification {$notification['notification_id']}: " . $e->getMessage() . "\n";
        }
    } else {
        $error_count++;
        echo "✗ No email found for user {$notification['user_id']} (notification {$notification['notification_id']})\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Fix complete:\n";
echo "  ✓ Fixed: $fixed_count notifications\n";
if ($reset_count > 0) {
    echo "  ✓ Reset: $reset_count failed notifications to 'notsent'\n";
}
if ($error_count > 0) {
    echo "  ✗ Errors: $error_count notifications could not be fixed\n";
}
echo "\nYou can now run the newsletter sender to send these emails.\n";
echo str_repeat('=', 60) . "\n";

echo "</pre>";
?>