<?PHP
// Newsletter Campaign Scheduler
// Runs every minute to check for scheduled campaigns and populate the queue

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Find campaigns that are scheduled and ready to send
$campaigns_sql = "SELECT * FROM bg_newsletter_campaigns 
                 WHERE status = 'scheduled' 
                 AND send_dt <= NOW()";

$campaigns = $database->getrows($campaigns_sql);

foreach ($campaigns as $campaign) {
    echo "Processing campaign: " . $campaign['title'] . " (ID: " . $campaign['campaign_id'] . ")\n";
    
    // Update campaign status to sending
    $update_sql = "UPDATE bg_newsletter_campaigns 
                  SET status = 'sending' 
                  WHERE campaign_id = :campaign_id";
    
    $database->query($update_sql, ['campaign_id' => $campaign['campaign_id']]);
    
    // Get all active users who are not unsubscribed
    $users_sql = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.city, u.birth_month
                 FROM bg_users u
                 WHERE u.status = 'active' 
                 AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes)
                 AND u.user_id NOT IN (
                     SELECT user_id FROM bg_newsletter_queue 
                     WHERE campaign_id = :campaign_id
                 )";
    
    $users = $database->getrows($users_sql, ['campaign_id' => $campaign['campaign_id']]);
    
    echo "Found " . count($users) . " eligible recipients\n";
    
    // Populate queue for each user
    $queue_count = 0;
    foreach ($users as $user) {
        $queue_sql = "INSERT INTO bg_newsletter_queue 
                     (campaign_id, user_id, scheduled_dt, status) 
                     VALUES 
                     (:campaign_id, :user_id, NOW(), 'pending')";
        
        $database->query($queue_sql, [
            'campaign_id' => $campaign['campaign_id'],
            'user_id' => $user['user_id']
        ]);
        
        $queue_count++;
    }
    
    echo "Added " . $queue_count . " items to queue\n";
    
    // Log the campaign start
    $log_sql = "INSERT INTO bg_newsletter_events 
               (campaign_id, user_id, event_type, event_dt, extra) 
               VALUES 
               (:campaign_id, 0, 'campaign_started', NOW(), :extra)";
    
    $database->query($log_sql, [
        'campaign_id' => $campaign['campaign_id'],
        'extra' => json_encode(['recipients' => $queue_count])
    ]);
}

// Clean up old completed campaigns (optional)
$cleanup_sql = "UPDATE bg_newsletter_campaigns 
               SET status = 'sent' 
               WHERE status = 'sending' 
               AND campaign_id NOT IN (
                   SELECT DISTINCT campaign_id 
                   FROM bg_newsletter_queue 
                   WHERE status IN ('pending', 'processing')
               )
               AND TIMESTAMPDIFF(HOUR, send_dt, NOW()) > 24";

$database->query($cleanup_sql);

echo "Scheduler completed at " . date('Y-m-d H:i:s') . "\n";
?>