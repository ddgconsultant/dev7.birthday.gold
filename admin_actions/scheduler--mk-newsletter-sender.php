<?php
/**
 * Marketing Newsletter Sender
 * Runs every minute to process pending newsletter notifications and send emails
 * This job handles the actual email sending with generation-specific content
 */

// Output HTML header for better formatting in browser
echo '<pre style="font-family: monospace; font-size: 12px; line-height: 1.4; background: #f5f5f5; padding: 20px;">';

$pagename = 'mk-newsletter-sender'; // Set pagename for CLI script
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo date('Y-m-d H:i:s') . " - Starting newsletter sender\n";

// Configuration
$batch_size = 50; // Process 50 emails per run to avoid timeouts
$rate_limit_delay = 100000; // 100ms between emails (10 emails/second)

// Get newsletters ready to send (status = 'notsent' after personalization)
$notifications_sql = "SELECT * FROM bg_user_notifications 
                     WHERE type = 'newsletter' 
                     AND status = 'notsent'
                     AND sent_to IS NOT NULL
                     AND sent_to != ''
                     ORDER BY create_dt ASC
                     LIMIT :batch_size";

$notifications = $database->getrows($notifications_sql, ['batch_size' => $batch_size]);

if (empty($notifications)) {
    echo "No newsletters ready to send (status='notsent')\n";
    
    // Check other statuses for debugging
    $status_check = "SELECT status, COUNT(*) as count FROM bg_user_notifications 
                    WHERE type = 'newsletter' GROUP BY status";
    $statuses = $database->getrows($status_check);
    
    if (!empty($statuses)) {
        echo "\nCurrent newsletter status breakdown:\n";
        foreach ($statuses as $status) {
            echo "  - {$status['status']}: {$status['count']} notifications\n";
        }
    }
    exit;
}

echo "Processing " . count($notifications) . " newsletter notifications\n";

// Initialize marketing class for content processing
$marketing = new Marketing($database, $qik, $mail);

$sent_count = 0;
$error_count = 0;

foreach ($notifications as $notification) {
    try {
        // The personalizer already created the final HTML in the message field
        // and the subject in the title field
        $email_subject = $notification['title'];
        $final_html = $notification['message'];
        
        // Validate we have content
        if (empty($email_subject) || empty($final_html)) {
            throw new Exception("Missing email subject or content");
        }
        
        // The HTML is already complete from the personalizer
        // No need to process placeholders, CTA blocks, or add footer - it's all done
        
        // Use email from sent_to field (already populated by queue creator)
        $user_email = $notification['sent_to'];
        
        if (empty($user_email)) {
            throw new Exception("User email not found in sent_to field");
        }
        
        // Get user name for the To field (optional, can use email only if needed)
        $user_sql = "SELECT first_name, last_name FROM bg_users WHERE user_id = :user_id";
        $user = $database->getrow($user_sql, ['user_id' => $notification['user_id']]);
        $user_name = !empty($user) ? $user['first_name'] . ' ' . $user['last_name'] : '';
        
        // Prepare email details with the pre-personalized content
        $email_details = [
            'to' => [$user_email, $user_name],
            'subject' => $email_subject,
            'body' => $final_html,
            'from' => ['hello@birthday.gold', 'Birthday Gold'],
            'notificationid' => $notification['notification_id']
        ];
        
        // Send the email
        $result = $mail->sendmail($email_details);
        
        if ($result['mail_sent'] === true) {
            // Update notification status to sent
            $update_sql = "UPDATE bg_user_notifications 
                          SET status = 'sent', 
                              sent_dt = NOW(), 
                              modify_dt = NOW() 
                          WHERE notification_id = :notification_id";
            
            $database->query($update_sql, ['notification_id' => $notification['notification_id']]);
            
            $sent_count++;
            echo "Sent newsletter to " . $user_email . " (User: " . $notification['user_id'] . ")\n";
            
            // Log activity
            try {
                // Extract campaign_id from category field if present
                $campaign_id = null;
                if (preg_match('/campaign_(\d+)/', $notification['category'], $matches)) {
                    $campaign_id = $matches[1];
                }
                
                session_tracking('newsletter_sent', [
                    'campaign_id' => $campaign_id,
                    'user_id' => $notification['user_id']
                ], 'mk-newsletter-sender');
            } catch (Exception $e) {
                // Logging error is not critical
            }
            
        } else {
            // Update notification status to failed
            $error_message = isset($result['error']) ? $result['error'] : 'Unknown error';
            
            $update_sql = "UPDATE bg_user_notifications 
                          SET status = 'failed', 
                              modify_dt = NOW() 
                          WHERE notification_id = :notification_id";
            
            $database->query($update_sql, ['notification_id' => $notification['notification_id']]);
            
            $error_count++;
            echo "Failed to send to " . $user_data['email'] . ": " . $error_message . "\n";
        }
        
        // Rate limiting
        usleep($rate_limit_delay);
        
    } catch (Exception $e) {
        echo "Error processing notification " . $notification['notification_id'] . ": " . $e->getMessage() . "\n";
        
        // Mark as failed
        $update_sql = "UPDATE bg_user_notifications 
                      SET status = 'failed', 
                          modify_dt = NOW() 
                      WHERE notification_id = :notification_id";
        
        $database->query($update_sql, ['notification_id' => $notification['notification_id']]);
        
        $error_count++;
    }
}

echo "Newsletter sender completed: Sent = $sent_count, Errors = $error_count\n";

// Check if any campaigns are fully sent and update their status
$check_sql = "SELECT DISTINCT 
                SUBSTRING(n.category, 10) as campaign_id,
                COUNT(*) as total,
                SUM(CASE WHEN n.status = 'sent' THEN 1 ELSE 0 END) as sent
              FROM bg_user_notifications n
              WHERE n.type = 'newsletter'
              AND n.category LIKE 'campaign_%'
              GROUP BY n.category
              HAVING COUNT(*) = SUM(CASE WHEN n.status = 'sent' THEN 1 ELSE 0 END)";

$completed = $database->getrows($check_sql);

foreach ($completed as $c) {
    $update_sql = "UPDATE mk_campaigns 
                  SET newsletter_status = 'sent',
                      status = 'sent' 
                  WHERE campaign_id = :campaign_id";
    $database->query($update_sql, ['campaign_id' => $c['campaign_id']]);
    echo "Campaign " . $c['campaign_id'] . " marked as fully sent\n";
}

// Log summary
session_tracking('newsletter_batch_complete', [
    'sent' => $sent_count,
    'errors' => $error_count,
    'timestamp' => date('Y-m-d H:i:s')
], 'mk-newsletter-sender');

echo date('Y-m-d H:i:s') . " - Newsletter sender finished\n";

// Close the pre tag for HTML formatting
echo '</pre>';

/**
 * Generate CTA block with company offers
 */
function generateCTABlock($category, $mode, $user_id, $campaign_id, $user_data, $database, $qik) {
    // Build query based on mode
    if ($mode == 'inclusive') {
        // Show companies the user IS enrolled in
        $companies_sql = "SELECT c.* 
                         FROM bg_companies c
                         INNER JOIN bg_user_enrollments e ON c.company_id = e.company_id
                         WHERE e.user_id = :user_id
                         AND e.status = 'success'
                         AND c.status = 'finalized'
                         AND c.display_category = :category
                         ORDER BY RAND()
                         LIMIT 4";
    } else {
        // Show companies the user is NOT enrolled in (exclusive mode)
        $companies_sql = "SELECT c.* 
                         FROM bg_companies c
                         WHERE c.status = 'finalized'
                         AND c.display_category = :category
                         AND c.company_id NOT IN (
                             SELECT company_id FROM bg_user_enrollments 
                             WHERE user_id = :user_id AND status = 'success'
                         )
                         ORDER BY 
                             CASE WHEN c.city = :city THEN 0 ELSE 1 END,
                             RAND()
                         LIMIT 4";
    }
    
    $params = [
        'user_id' => $user_id,
        'category' => $category,
        'city' => $user_data['city'] ?? ''
    ];
    
    $companies = $database->getrows($companies_sql, $params);
    
    if (empty($companies)) {
        return '<div style="padding: 30px; text-align: center; background: #f8f9fa; margin: 20px 0; border-radius: 10px;">
                <h3 style="color: #495057;">More birthday rewards coming soon!</h3>
                <p style="color: #6c757d;">We are constantly adding new businesses to Birthday Gold.</p>
                </div>';
    }
    
    // Build CTA HTML
    $cta_html = '
    <div style="background: #f8f9fa; padding: 30px; margin: 30px 0; border-radius: 10px;">
        <h2 style="text-align: center; margin-bottom: 20px; color: #212529;">
            ' . ($mode == 'inclusive' ? 'Your Birthday Rewards' : 'More Birthday Rewards for You') . '
        </h2>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">';
    
    foreach ($companies as $company) {
        // Get company logo
        $logo_url = 'https://birthday.gold/images/companies/' . $company['company_id'] . '/logo.png';
        
        // Create tracking URL
        $track_url = 'https://birthday.gold/track/cta/' . 
                    $qik->encodeId($campaign_id) . '/' . 
                    $qik->encodeId($user_id) . '/' . 
                    $qik->encodeId($company['company_id']);
        
        $cta_html .= '
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <img src="' . $logo_url . '" 
                     style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 10px;" 
                     alt="' . htmlspecialchars($company['company_name']) . '">
                <h4 style="margin: 10px 0; font-size: 16px; color: #212529;">' . 
                    htmlspecialchars($company['company_name']) . '</h4>
                <p style="font-size: 14px; color: #6c757d; margin-bottom: 15px; min-height: 40px;">' . 
                    htmlspecialchars(substr($company['birthday_offer'], 0, 60)) . '...</p>
                <a href="' . $track_url . '" 
                   style="display: inline-block; padding: 8px 20px; background: #007bff; color: white; 
                          text-decoration: none; border-radius: 5px; font-size: 14px;">
                    ' . ($mode == 'inclusive' ? 'View Details' : 'Enroll Now') . '
                </a>
            </div>';
    }
    
    $cta_html .= '
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="https://birthday.gold/myaccount" 
               style="color: #007bff; text-decoration: underline; font-size: 14px;">
                View all your birthday rewards →
            </a>
        </div>
    </div>';
    
    return $cta_html;
}
?>