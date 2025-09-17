<?php
/**
 * Marketing Newsletter Queue Scheduler
 * Runs every minute to check for scheduled campaigns and populate the notification queue
 * This job focuses on generating the queue entries with personalized content
 */

$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo date('Y-m-d H:i:s') . " - Starting newsletter queue scheduler\n";

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
    
    // Update campaign status to processing
    $update_sql = "UPDATE mk_campaigns 
                  SET newsletter_status = 'processing' 
                  WHERE campaign_id = :campaign_id";
    
    $database->query($update_sql, ['campaign_id' => $campaign['campaign_id']]);
    
    // Parse recipient criteria
    $recipient_criteria = !empty($campaign['recipient_criteria']) 
        ? json_decode($campaign['recipient_criteria'], true) 
        : ['type' => 'all'];
    
    // Build user query based on criteria
    $where_conditions = ["u.status = 'active'"];
    $params = [];
    
    // Apply recipient criteria filters
    if (!empty($recipient_criteria['city'])) {
        $where_conditions[] = "u.city = :city";
        $params['city'] = $recipient_criteria['city'];
    }
    
    if (!empty($recipient_criteria['state'])) {
        $where_conditions[] = "u.state = :state";
        $params['state'] = $recipient_criteria['state'];
    }
    
    if (!empty($recipient_criteria['birth_month'])) {
        $where_conditions[] = "MONTH(u.birthdate) = :birth_month";
        $params['birth_month'] = $recipient_criteria['birth_month'];
    }
    
    if (!empty($recipient_criteria['age_min'])) {
        $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) >= :age_min";
        $params['age_min'] = $recipient_criteria['age_min'];
    }
    
    if (!empty($recipient_criteria['age_max'])) {
        $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) <= :age_max";
        $params['age_max'] = $recipient_criteria['age_max'];
    }
    
    // Exclude unsubscribed users
    $where_conditions[] = "u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')";
    
    // Exclude users who already have this campaign in queue
    $where_conditions[] = "u.user_id NOT IN (
        SELECT user_id FROM bg_user_notifications 
        WHERE type = 'newsletter' 
        AND category = :campaign_id_check
        AND status IN ('pending', 'sent')
    )";
    $params['campaign_id_check'] = 'campaign_' . $campaign['campaign_id'];
    
    // Get eligible users
    $users_sql = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.city, u.state,
                         u.birthdate, 
                         MONTH(u.birthdate) as birth_month, 
                         CASE 
                            WHEN TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) >= 58 THEN 'boomer'
                            WHEN TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) >= 43 THEN 'genx'
                            WHEN TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) >= 27 THEN 'millennial'
                            ELSE 'genz'
                         END as generation
                  FROM bg_users u
                  WHERE " . implode(' AND ', $where_conditions) . "
                  LIMIT 1000"; // Process in batches of 1000
    
    $users = $database->getrows($users_sql, $params);
    
    echo "Found " . count($users) . " eligible recipients\n";
    
    if (empty($users)) {
        // No more users to process, mark campaign as sent
        $update_sql = "UPDATE mk_campaigns 
                      SET newsletter_status = 'sent' 
                      WHERE campaign_id = :campaign_id";
        $database->query($update_sql, ['campaign_id' => $campaign['campaign_id']]);
        continue;
    }
    
    // Initialize marketing class for content generation
    $marketing = new Marketing($database, $qik, $mail);
    
    // Process each user
    $queue_count = 0;
    foreach ($users as $user) {
        try {
            // Prepare personalized content based on generation
            $personalized_subject = $campaign['email_subject'];
            $personalized_content = $campaign['campaign_content'];
            
            // Apply generation-specific messaging if enabled
            if (!empty($campaign['gen_specific_messaging'])) {
                $gen_messaging = json_decode($campaign['gen_specific_messaging'], true);
                if (!empty($gen_messaging[$user['generation']])) {
                    // Replace generation-specific placeholders
                    $gen_content = $gen_messaging[$user['generation']];
                    if (!empty($gen_content['greeting'])) {
                        $personalized_content = str_replace('{{GEN_GREETING}}', $gen_content['greeting'], $personalized_content);
                    }
                    if (!empty($gen_content['tone'])) {
                        // Apply tone adjustments (this could be more sophisticated)
                        $personalized_content = str_replace('{{GEN_TONE}}', $gen_content['tone'], $personalized_content);
                    }
                }
            }
            
            // Replace standard placeholders
            $personalized_subject = $marketing->replacePlaceholders($personalized_subject, $user);
            $personalized_content = $marketing->replacePlaceholders($personalized_content, $user);
            
            // Generate CTA companies if needed
            $cta_data = null;
            if (!empty($campaign['cta_category']) && strpos($personalized_content, '{{CTA_BLOCK}}') !== false) {
                // This will be processed by the sender job
                $cta_data = [
                    'category' => $campaign['cta_category'],
                    'mode' => $campaign['cta_mode'] ?? 'inclusive'
                ];
            }
            
            // Create notification entry
            $notification_data = [
                'user_id' => $user['user_id'],
                'type' => 'newsletter',
                'title' => $personalized_subject,
                'message' => json_encode([
                    'campaign_id' => $campaign['campaign_id'],
                    'subject' => $personalized_subject,
                    'content' => $personalized_content,
                    'cta_data' => $cta_data,
                    'user_data' => $user,
                    'generation' => $user['generation']
                ]),
                'status' => 'pending',
                'create_dt' => date('Y-m-d H:i:s'),
                'alert_class' => 'marketing',
                'priority' => $campaign['priority'] ?? '10',
                'category' => 'campaign_' . $campaign['campaign_id'],
                'sent_to' => 'email',
                'start_dt' => date('Y-m-d H:i:s')
            ];
            
            // Insert into notifications table
            $fields = array_keys($notification_data);
            $placeholders = array_map(function($f) { return ":$f"; }, $fields);
            
            $insert_sql = "INSERT INTO bg_user_notifications (" . implode(', ', $fields) . ") 
                          VALUES (" . implode(', ', $placeholders) . ")";
            
            $database->query($insert_sql, $notification_data);
            $queue_count++;
            
        } catch (Exception $e) {
            echo "Error processing user " . $user['user_id'] . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "Added " . $queue_count . " notifications to queue\n";
    
    // Log activity
    try {
        $activity_sql = "INSERT INTO mk_activities 
                        (campaign_id, activity_type, activity_data, create_dt) 
                        VALUES 
                        (:campaign_id, 'queue_generated', :data, NOW())";
        
        $database->query($activity_sql, [
            'campaign_id' => $campaign['campaign_id'],
            'data' => json_encode(['recipients' => $queue_count])
        ]);
    } catch (Exception $e) {
        echo "Warning: Could not log activity: " . $e->getMessage() . "\n";
    }
    
    // If we processed fewer than 1000 users, we're done with this campaign
    if (count($users) < 1000) {
        $update_sql = "UPDATE mk_campaigns 
                      SET newsletter_status = 'queued' 
                      WHERE campaign_id = :campaign_id";
        $database->query($update_sql, ['campaign_id' => $campaign['campaign_id']]);
    }
}

echo date('Y-m-d H:i:s') . " - Queue scheduler completed\n";
?>