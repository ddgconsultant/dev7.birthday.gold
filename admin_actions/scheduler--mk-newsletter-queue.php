<?php
/**
 * MK Newsletter Queue - Production Version
 * Runs every 5 minutes to check for scheduled campaigns and populate the notification queue
 * This job creates notification entries that will be processed by the personalizer
 */

// Output HTML header for better formatting in browser
echo '<pre style="font-family: monospace; font-size: 12px; line-height: 1.4; background: #f5f5f5; padding: 20px;">';

$addClasses[] = 'marketing';
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo date('Y-m-d H:i:s') . " - Starting MK Newsletter Queue\n";

// Find campaigns that are scheduled and ready to send
$campaigns_sql = "SELECT * FROM mk_campaigns 
                 WHERE campaign_type = 'newsletter' 
                 AND newsletter_status = 'scheduled' 
                 AND start_date <= NOW()
                 AND (end_date IS NULL OR end_date > NOW())";

$campaigns = $database->getrows($campaigns_sql);

if (empty($campaigns)) {
    echo "No scheduled campaigns found\n";
    exit;
}

foreach ($campaigns as $campaign) {
    echo "Processing campaign: " . $campaign['campaign_name'] . " (ID: " . $campaign['campaign_id'] . ")\n";
    
    // Update campaign status to processing (both status fields)
    $update_sql = "UPDATE mk_campaigns 
                  SET newsletter_status = 'processing',
                      status = 'processing' 
                  WHERE campaign_id = :campaign_id";
    
    $database->query($update_sql, ['campaign_id' => $campaign['campaign_id']]);
    
    // Parse recipient criteria - this is a tokens array
    $tokens = !empty($campaign['recipient_criteria']) 
        ? json_decode($campaign['recipient_criteria'], true) 
        : [['type' => 'all']];
    
    echo "  Recipient criteria: " . $campaign['recipient_criteria'] . "\n";
    echo "  Parsed tokens: " . json_encode($tokens) . "\n";
    
    // Check if tokens is empty or invalid
    if (empty($tokens) || !is_array($tokens)) {
        $tokens = [['type' => 'all']];
    }
    
    // Use the Marketing class to process tokens and get SQL conditions
    $marketing = new Marketing($database, $qik, $mail);
    
    // Build user query based on tokens
    $where_conditions = ["u.status = 'active'"];
    $params = [];
    
    // Check for "all" token
    $isAllRecipients = false;
    foreach ($tokens as $token) {
        if (isset($token['type']) && $token['type'] === 'all') {
            $isAllRecipients = true;
            break;
        }
    }
    
    echo "  Processing mode: " . ($isAllRecipients ? "ALL recipients" : "Filtered recipients") . "\n";
    
    // If not "all", we need to process the complex token structure
    if (!$isAllRecipients) {
        // For now, let's use the Marketing class method directly
        // We'll get recipients in a different way
        $batch_users = $marketing->getRecipients($tokens, 500);
        $user_ids = array_column($batch_users, 'user_id');
        
        if (!empty($user_ids)) {
            // Filter out users who already have this campaign
            $already_sent_sql = "SELECT user_id FROM bg_user_notifications 
                                WHERE type = 'newsletter' 
                                AND category = :campaign_category
                                AND status IN ('pending', 'sent', 'notsent')";
            
            $already_sent = $database->getrows($already_sent_sql, [
                'campaign_category' => 'campaign_' . $campaign['campaign_id']
            ]);
            
            $already_sent_ids = array_column($already_sent, 'user_id');
            $user_ids = array_diff($user_ids, $already_sent_ids);
            
            // Now get full user data for the filtered IDs
            if (!empty($user_ids)) {
                $placeholders = array_map(function($i) { return ":uid_$i"; }, array_keys($user_ids));
                $uid_params = array_combine(
                    array_map(function($i) { return "uid_$i"; }, array_keys($user_ids)),
                    $user_ids
                );
                
                $users_sql = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.city, u.state,
                                     u.birthdate, 
                                     MONTH(u.birthdate) as birth_month,
                                     TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) as age
                              FROM bg_users u
                              WHERE u.user_id IN (" . implode(',', $placeholders) . ")
                              AND u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')";
                
                $users = $database->getrows($users_sql, $uid_params);
            } else {
                $users = [];
            }
        } else {
            $users = [];
        }
    } else {
        // Original logic for "all" recipients
        // Exclude unsubscribed users
        $where_conditions[] = "u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')";
        
        // Exclude users who already have this campaign in their notifications
        $where_conditions[] = "u.user_id NOT IN (
            SELECT user_id FROM bg_user_notifications 
            WHERE type = 'newsletter' 
            AND category = :campaign_category
            AND status IN ('pending', 'sent', 'notsent')
        )";
        $params['campaign_category'] = 'campaign_' . $campaign['campaign_id'];
        
        // Get eligible users in batches
        $users_sql = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.city, u.state,
                             u.birthdate, 
                             MONTH(u.birthdate) as birth_month,
                             TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) as age
                      FROM bg_users u
                      WHERE " . implode(' AND ', $where_conditions) . "
                      LIMIT 500"; // Process in smaller batches
        
        $users = $database->getrows($users_sql, $params);
    }
    
    echo "Found " . count($users) . " eligible recipients for this batch\n";
    
    if (empty($users)) {
        // No more users to process, mark campaign as queued
        $update_sql = "UPDATE mk_campaigns 
                      SET newsletter_status = 'queued',
                          status = 'queued' 
                      WHERE campaign_id = :campaign_id";
        $database->query($update_sql, ['campaign_id' => $campaign['campaign_id']]);
        echo "Campaign " . $campaign['campaign_id'] . " fully queued\n";
        continue;
    }
    
    // Process each user - just create the notification entry
    $queue_count = 0;
    foreach ($users as $user) {
        try {
            // Determine user generation based on age
            $generation = 'millennial'; // default
            if ($user['age'] >= 58) {
                $generation = 'boomer';
            } elseif ($user['age'] >= 43) {
                $generation = 'genx';
            } elseif ($user['age'] >= 27) {
                $generation = 'millennial';
            } else {
                $generation = 'genz';
            }
            
            // Store campaign data and user data in the options column
            $options_data = [
                'campaign_id' => $campaign['campaign_id'],
                'campaign_name' => $campaign['campaign_name'],
                'email_subject' => $campaign['email_subject'],
                'campaign_content' => $campaign['campaign_content'],
                'cta_category' => $campaign['cta_category'],
                'cta_mode' => $campaign['cta_mode'] ?? 'exclusive',
                'gen_specific_messaging' => $campaign['gen_specific_messaging'],
                'user_generation' => $generation,
                'user_data' => [
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'city' => $user['city'],
                    'state' => $user['state'],
                    'birth_month' => $user['birth_month'],
                    'age' => $user['age']
                ]
            ];
            
            // Create notification entry with options column
            // Leave title and message NULL - they'll be populated by the personalizer
            $notification_sql = "INSERT INTO bg_user_notifications 
                (user_id, type, title, message, options, status, create_dt, alert_class, 
                 priority, category, sent_to, start_dt) 
                VALUES 
                (:user_id, :type, NULL, NULL, :options, :status, NOW(), :alert_class, 
                 :priority, :category, :sent_to, NOW())";
            
            $notification_params = [
                'user_id' => $user['user_id'],
                'type' => 'newsletter',
                'options' => json_encode($options_data),
                'status' => 'pending',
                'alert_class' => 'marketing',
                'priority' => $campaign['priority'] ?? '10',
                'category' => 'campaign_' . $campaign['campaign_id'],
                'sent_to' => $user['email']  // Store actual email address
            ];
            
            $database->query($notification_sql, $notification_params);
            $queue_count++;
            
        } catch (Exception $e) {
            echo "Error processing user " . $user['user_id'] . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "Added " . $queue_count . " notifications to queue for campaign " . $campaign['campaign_id'] . "\n";
    
    // Log activity in mk_activities if table exists
    try {
        $activity_sql = "INSERT INTO mk_activities 
                        (campaign_id, activity_type, activity_data, create_dt) 
                        VALUES 
                        (:campaign_id, 'queue_batch_generated', :data, NOW())";
        
        $database->query($activity_sql, [
            'campaign_id' => $campaign['campaign_id'],
            'data' => json_encode([
                'batch_size' => $queue_count,
                'total_processed' => $queue_count
            ])
        ]);
    } catch (Exception $e) {
        // Table might not exist, that's okay
    }
    
    // If we processed fewer than the batch size, we're done with this campaign
    if (count($users) < 500) {
        $update_sql = "UPDATE mk_campaigns 
                      SET newsletter_status = 'queued',
                          status = 'sending' 
                      WHERE campaign_id = :campaign_id";
        $database->query($update_sql, ['campaign_id' => $campaign['campaign_id']]);
        echo "Campaign " . $campaign['campaign_id'] . " fully queued\n";
    }
}

// Check for campaigns that are fully sent
$check_sql = "SELECT c.campaign_id 
              FROM mk_campaigns c
              WHERE c.newsletter_status = 'queued'
              AND NOT EXISTS (
                  SELECT 1 FROM bg_user_notifications n
                  WHERE n.category = CONCAT('campaign_', c.campaign_id)
                  AND n.status = 'pending'
              )";

$completed_campaigns = $database->getrows($check_sql);

foreach ($completed_campaigns as $completed) {
    $update_sql = "UPDATE mk_campaigns 
                  SET newsletter_status = 'sent',
                      status = 'sent' 
                  WHERE campaign_id = :campaign_id";
    $database->query($update_sql, ['campaign_id' => $completed['campaign_id']]);
    echo "Campaign " . $completed['campaign_id'] . " marked as sent\n";
}

echo date('Y-m-d H:i:s') . " - Queue scheduler completed\n";

// Close the pre tag for HTML formatting
echo '</pre>';
?>