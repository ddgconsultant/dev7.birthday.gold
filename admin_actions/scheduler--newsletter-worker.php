<?PHP
// Newsletter Worker Script
// Processes the queue and sends actual emails

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Rate limiting
$batch_size = 100; // Process 100 emails per run
$rate_limit_delay = 100000; // 100ms between emails (10 emails/second)

// Get pending queue items
$queue_sql = "SELECT 
    q.*,
    c.subject,
    c.body_html,
    c.cta_category,
    u.email,
    u.first_name,
    u.last_name,
    u.city,
    u.birth_month
FROM bg_newsletter_queue q
JOIN bg_newsletter_campaigns c ON q.campaign_id = c.campaign_id
JOIN bg_users u ON q.user_id = u.user_id
WHERE q.status = 'pending'
AND q.scheduled_dt <= NOW()
ORDER BY q.scheduled_dt ASC
LIMIT :batch_size";

$queue_items = $database->getrows($queue_sql, ['batch_size' => $batch_size]);

echo "Processing " . count($queue_items) . " emails from queue\n";

foreach ($queue_items as $item) {
    // Mark as processing
    $update_sql = "UPDATE bg_newsletter_queue 
                  SET status = 'processing' 
                  WHERE queue_id = :queue_id";
    
    $database->query($update_sql, ['queue_id' => $item['queue_id']]);
    
    // Generate personalized content
    $personalized_body = generatePersonalizedContent($item, $database, $qik);
    
    // Prepare email
    $email_details = [
        'to' => [$item['email'], $item['first_name'] . ' ' . $item['last_name']],
        'subject' => replacePlaceholders($item['subject'], $item),
        'body' => $personalized_body,
        'from' => ['hello@birthday.gold', 'Birthday Gold'],
        'donottrack' => false,
        // Newsletters are marketing — marketing_only suppressions block them.
        'category' => 'marketing'
    ];

    // Send email. sendmail() returns an assoc array with mail_sent/suppressed;
    // the previous version of this loop only caught exceptions and silently
    // marked soft-failures as 'sent'. Inspect the return value now.
    try {
        $result = $mail->sendmail($email_details);

        if (!empty($result['suppressed'])) {
            // Address is on the suppression list — terminal state, no retry.
            $update_sql = "UPDATE bg_newsletter_queue
                          SET status = 'suppressed', processed_dt = NOW()
                          WHERE queue_id = :queue_id";
            $database->query($update_sql, ['queue_id' => $item['queue_id']]);

            $log_sql = "INSERT INTO bg_newsletter_events
                       (campaign_id, user_id, event_type, event_dt, extra)
                       VALUES
                       (:campaign_id, :user_id, 'suppressed', NOW(), :extra)";
            $database->query($log_sql, [
                'campaign_id' => $item['campaign_id'],
                'user_id' => $item['user_id'],
                'extra' => json_encode([
                    'source' => $result['suppression']['source'] ?? 'unknown',
                    'scope'  => $result['suppression']['scope']  ?? 'unknown',
                ])
            ]);

            echo "Suppressed: " . $item['email'] . " (source: " . ($result['suppression']['source'] ?? 'unknown') . ")\n";

        } elseif (!empty($result['mail_sent'])) {
            // Mark as sent
            $update_sql = "UPDATE bg_newsletter_queue
                          SET status = 'sent', processed_dt = NOW()
                          WHERE queue_id = :queue_id";
            $database->query($update_sql, ['queue_id' => $item['queue_id']]);

            // Log sent event
            $log_sql = "INSERT INTO bg_newsletter_events
                       (campaign_id, user_id, event_type, event_dt)
                       VALUES
                       (:campaign_id, :user_id, 'sent', NOW())";
            $database->query($log_sql, [
                'campaign_id' => $item['campaign_id'],
                'user_id' => $item['user_id']
            ]);

            echo "Sent to " . $item['email'] . "\n";

        } else {
            // Soft failure — sendmail returned without throwing but mail_sent is false.
            $update_sql = "UPDATE bg_newsletter_queue
                          SET status = 'error', processed_dt = NOW()
                          WHERE queue_id = :queue_id";
            $database->query($update_sql, ['queue_id' => $item['queue_id']]);

            $log_sql = "INSERT INTO bg_newsletter_events
                       (campaign_id, user_id, event_type, event_dt, extra)
                       VALUES
                       (:campaign_id, :user_id, 'error', NOW(), :extra)";
            $database->query($log_sql, [
                'campaign_id' => $item['campaign_id'],
                'user_id' => $item['user_id'],
                'extra' => json_encode(['error' => $result['processingerror'] ?? 'sendmail returned mail_sent=false'])
            ]);

            echo "Soft-failed sending to " . $item['email'] . "\n";
        }

    } catch (Exception $e) {
        // Mark as error
        $update_sql = "UPDATE bg_newsletter_queue
                      SET status = 'error', processed_dt = NOW()
                      WHERE queue_id = :queue_id";

        $database->query($update_sql, ['queue_id' => $item['queue_id']]);

        // Log error
        $log_sql = "INSERT INTO bg_newsletter_events
                   (campaign_id, user_id, event_type, event_dt, extra)
                   VALUES
                   (:campaign_id, :user_id, 'error', NOW(), :extra)";

        $database->query($log_sql, [
            'campaign_id' => $item['campaign_id'],
            'user_id' => $item['user_id'],
            'extra' => json_encode(['error' => $e->getMessage()])
        ]);

        echo "Error sending to " . $item['email'] . ": " . $e->getMessage() . "\n";
    }
    
    // Rate limiting
    usleep($rate_limit_delay);
}

// Check if campaign is complete
$campaigns_sql = "SELECT DISTINCT campaign_id 
                 FROM bg_newsletter_queue 
                 WHERE status IN ('pending', 'processing')";

$active_campaigns = $database->getrows($campaigns_sql);
$active_campaign_ids = array_column($active_campaigns, 'campaign_id');

// Mark completed campaigns as sent
if (!empty($active_campaign_ids)) {
    $complete_sql = "UPDATE bg_newsletter_campaigns 
                    SET status = 'sent' 
                    WHERE status = 'sending' 
                    AND campaign_id NOT IN (" . implode(',', $active_campaign_ids) . ")";
    
    $database->query($complete_sql);
}

echo "Worker completed at " . date('Y-m-d H:i:s') . "\n";

// Helper functions
function replacePlaceholders($text, $user_data) {
    $text = str_replace('[[first_name]]', $user_data['first_name'], $text);
    $text = str_replace('[[city]]', $user_data['city'], $text);
    
    $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
               'July', 'August', 'September', 'October', 'November', 'December'];
    $birth_month_name = isset($months[$user_data['birth_month']]) ? $months[$user_data['birth_month']] : '';
    $text = str_replace('[[birthday_month]]', $birth_month_name, $text);
    
    return $text;
}

function generatePersonalizedContent($item, $database, $qik) {
    $body = replacePlaceholders($item['body_html'], $item);
    
    // Generate CTA block if placeholder exists
    if (strpos($body, '[[CTA_BLOCK]]') !== false) {
        $cta_html = generateCTABlock($item, $database, $qik);
        $body = str_replace('[[CTA_BLOCK]]', $cta_html, $body);
    }
    
    // Add tracking pixel
    $tracking_url = 'https://birthday.gold/staff/newsletter-pixel.php?c=' . 
                   $qik->encodeId($item['campaign_id']) . '&u=' . 
                   $qik->encodeId($item['user_id']);
    
    $tracking_pixel = '<img src="' . $tracking_url . '" width="1" height="1" style="display:none;" alt="">';
    
    // Add unsubscribe footer
    $unsubscribe_url = 'https://birthday.gold/staff/newsletter-unsubscribe.php?u=' . 
                      $qik->encodeId($item['user_id']);
    
    $footer = '<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc; font-size: 12px; color: #666; text-align: center;">
        <p>Birthday Gold | 123 Main St, Seattle, WA 98101</p>
        <p><a href="' . $unsubscribe_url . '" style="color: #666;">Unsubscribe</a> | 
           <a href="https://birthday.gold/support" style="color: #666;">Support</a></p>
    </div>';
    
    return $body . $tracking_pixel . $footer;
}

function generateCTABlock($item, $database, $qik) {
    // Get brands not enrolled by user
    $brands_sql = "SELECT c.* 
                  FROM bg_companies c
                  WHERE c.status = 'active'
                  AND c.company_category = :category
                  AND c.company_id NOT IN (
                      SELECT company_id FROM bg_enrollments 
                      WHERE user_id = :user_id AND status = 'active'
                  )
                  AND c.company_id NOT IN (
                      SELECT brand_id FROM bg_newsletter_cta_log 
                      WHERE user_id = :user_id 
                      AND shown_dt > DATE_SUB(NOW(), INTERVAL 14 DAY)
                  )
                  ORDER BY 
                      CASE WHEN c.city = :city THEN 0 ELSE 1 END,
                      RAND()
                  LIMIT 4";
    
    $brands = $database->getrows($brands_sql, [
        'category' => $item['cta_category'],
        'user_id' => $item['user_id'],
        'city' => $item['city']
    ]);
    
    if (empty($brands)) {
        return '<div style="padding: 20px; text-align: center; background: #f8f9fa; margin: 20px 0;">
                <h3>More birthday rewards coming soon!</h3>
                <p>We are adding new businesses every day.</p>
                </div>';
    }
    
    // Build CTA HTML
    $cta_html = '<div style="background: #f8f9fa; padding: 30px; margin: 30px 0; border-radius: 10px;">
        <h2 style="text-align: center; margin-bottom: 20px;">Birthday Rewards You Might Like</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';
    
    foreach ($brands as $brand) {
        // Log CTA impression
        $log_sql = "INSERT INTO bg_newsletter_cta_log 
                   (campaign_id, user_id, brand_id, shown_dt) 
                   VALUES 
                   (:campaign_id, :user_id, :brand_id, NOW())";
        
        $database->query($log_sql, [
            'campaign_id' => $item['campaign_id'],
            'user_id' => $item['user_id'],
            'brand_id' => $brand['company_id']
        ]);
        
        // Track URL for clicks
        $click_url = 'https://birthday.gold/staff/newsletter-track.php?c=' . 
                    $qik->encodeId($item['campaign_id']) . '&u=' . 
                    $qik->encodeId($item['user_id']) . '&b=' . 
                    $qik->encodeId($brand['company_id']) . '&url=' . 
                    urlencode('https://birthday.gold/company/' . $brand['company_id']);
        
        $cta_html .= '
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                <img src="https://birthday.gold/images/companies/' . $brand['company_id'] . '/' . 
                $brand['company_logo'] . '" 
                     style="width: 100px; height: 100px; object-fit: contain; margin-bottom: 10px;" alt="' . 
                     htmlspecialchars($brand['company_name']) . '">
                <h4 style="margin: 10px 0;">' . htmlspecialchars($brand['company_name']) . '</h4>
                <p style="font-size: 14px; color: #666; margin-bottom: 15px;">' . 
                htmlspecialchars(substr($brand['birthday_offer'], 0, 50)) . '...</p>
                <a href="' . $click_url . '" 
                   style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
                          text-decoration: none; border-radius: 5px;">
                    Enroll Now
                </a>
            </div>';
    }
    
    $cta_html .= '</div></div>';
    
    return $cta_html;
}
?>