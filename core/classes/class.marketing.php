<?PHP

class Marketing
{
    protected $database;
    protected $qik;
    protected $mail;

    public function __construct($database, $qik, $mail)
    {
        $this->database = $database;
        $this->qik = $qik;
        $this->mail = $mail;
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get a sample user that fits the campaign recipient criteria
     * @param array $criteria Recipient criteria (JSON decoded)
     * @return array|false User data or false if no matching user found
     */
    public function getSampleUserForCampaign($criteria = [])
    {
        // Default to getting any active user if no criteria specified
        $where_conditions = ["u.status = 'active'"];
        $params = [];

        // Apply criteria filters
        if (!empty($criteria['age_range'])) {
            if (!empty($criteria['age_range']['min'])) {
                $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) >= :min_age";
                $params['min_age'] = $criteria['age_range']['min'];
            }
            if (!empty($criteria['age_range']['max'])) {
                $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) <= :max_age";
                $params['max_age'] = $criteria['age_range']['max'];
            }
        }

        if (!empty($criteria['city'])) {
            $where_conditions[] = "u.city = :city";
            $params['city'] = $criteria['city'];
        }

        if (!empty($criteria['state'])) {
            $where_conditions[] = "u.state = :state";
            $params['state'] = $criteria['state'];
        }

        if (!empty($criteria['birth_month'])) {
            $where_conditions[] = "u.birth_month = :birth_month";
            $params['birth_month'] = $criteria['birth_month'];
        }

        // Exclude unsubscribed users
        $where_conditions[] = "u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')";

        $sql = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.city, u.state, 
                       u.birth_date, u.birth_month, u.join_dt
                FROM bg_users u 
                WHERE " . implode(' AND ', $where_conditions) . "
                ORDER BY RAND() 
                LIMIT 1";

        try {
            $user = $this->database->getrow($sql, $params);
            return $user ? $user : false;
        } catch (Exception $e) {
            error_log("Marketing::getSampleUserForCampaign - Error: " . $e->getMessage());
            return false;
        }
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Replace placeholders in content with user data
     * @param string $content Content with placeholders
     * @param array $user_data User data for replacement
     * @return string Content with placeholders replaced
     */
    public function replacePlaceholders($content, $user_data)
    {
        $placeholders = [
            '{{FIRST_NAME}}' => $user_data['first_name'] ?? '',
            '{{LAST_NAME}}' => $user_data['last_name'] ?? '',
            '{{FULL_NAME}}' => trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')),
            '{{EMAIL}}' => $user_data['email'] ?? '',
            '{{CITY}}' => $user_data['city'] ?? '',
            '{{STATE}}' => $user_data['state'] ?? '',
            '{{BIRTH_MONTH}}' => $user_data['birth_month'] ?? '',
            '{{BIRTH_MONTH_NAME}}' => !empty($user_data['birth_month']) ? date('F', mktime(0, 0, 0, $user_data['birth_month'], 1)) : '',
            '{{CURRENT_YEAR}}' => date('Y'),
            '{{CURRENT_MONTH}}' => date('F'),
            '{{CURRENT_DATE}}' => date('F j, Y')
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get available business logos for CTA that match category but aren't already enrolled
     * @param int $user_id User ID to check enrollments for
     * @param string $category CTA category to match
     * @param int $limit Number of logos to return (default: 5)
     * @return array Array of business logo data
     */
    public function getAvailableBusinessLogosForCTA($user_id, $category, $limit = 5)
    {
        // Get user's current enrollments to exclude them
        $enrolled_sql = "SELECT DISTINCT company_id 
                        FROM bg_user_enrollments 
                        WHERE user_id = :user_id 
                        AND status IN ('active', 'pending')";
        
        $enrolled_companies = $this->database->getrows($enrolled_sql, ['user_id' => $user_id]);
        $enrolled_ids = array_column($enrolled_companies, 'company_id');

        // Build exclusion condition
        $exclusion_condition = '';
        $params = ['category' => $category, 'limit' => $limit];
        
        if (!empty($enrolled_ids)) {
            $placeholders = implode(',', array_fill(0, count($enrolled_ids), '?'));
            $exclusion_condition = " AND c.company_id NOT IN ($placeholders)";
            $params = array_merge($params, $enrolled_ids);
        }

        // Get available companies with logos in the specified category
        $sql = "SELECT c.company_id, c.company_display_name, c.company_category,
                       cl.logo_id, cl.logo_filename, cl.logo_url, cl.is_primary,
                       c.reward_description, c.reward_value
                FROM bg_companies c
                INNER JOIN bg_company_logos cl ON c.company_id = cl.company_id
                WHERE c.status = 'active' 
                AND c.company_category = :category
                AND cl.status = 'active'
                AND c.accepts_new_members = 1
                $exclusion_condition
                ORDER BY c.priority DESC, cl.is_primary DESC, RAND()
                LIMIT :limit";

        try {
            return $this->database->getrows($sql, $params);
        } catch (Exception $e) {
            error_log("Marketing::getAvailableBusinessLogosForCTA - Error: " . $e->getMessage());
            return [];
        }
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Generate CTA HTML section with business logos
     * @param array $logos Array of business logo data
     * @param array $user_data User data for personalization
     * @return string Generated CTA HTML
     */
    public function generateCTASection($logos, $user_data)
    {
        if (empty($logos)) {
            return '<div class="cta-section"><p>No new businesses available for enrollment at this time.</p></div>';
        }

        $cta_html = '<div class="cta-section">';
        $cta_html .= '<h3 style="color: #2F3133; font-size: 18px; margin: 20px 0 15px 0;">Available Birthday Rewards for You:</h3>';
        $cta_html .= '<div class="business-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">';

        foreach ($logos as $business) {
            $logo_url = !empty($business['logo_url']) ? $business['logo_url'] : '/public/assets/img/logos/' . $business['logo_filename'];
            $reward_text = !empty($business['reward_description']) ? $business['reward_description'] : 'Special Birthday Offer';
            
            $cta_html .= '<div class="business-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; background: #fff;">';
            $cta_html .= '<img src="' . htmlspecialchars($logo_url) . '" alt="' . htmlspecialchars($business['company_display_name']) . '" style="max-width: 80px; max-height: 60px; margin-bottom: 10px;">';
            $cta_html .= '<div class="business-name" style="font-weight: bold; font-size: 14px; margin: 5px 0;">' . htmlspecialchars($business['company_display_name']) . '</div>';
            $cta_html .= '<div class="reward-info" style="font-size: 12px; color: #666; margin: 5px 0;">' . htmlspecialchars($reward_text) . '</div>';
            $cta_html .= '<a href="https://birthday.gold/myaccount/enrollment-picker.php?highlight=' . $business['company_id'] . '" style="display: inline-block; background: goldenrod; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin-top: 5px;">Enroll Now</a>';
            $cta_html .= '</div>';
        }

        $cta_html .= '</div>';
        $cta_html .= '<p style="font-size: 12px; color: #666; text-align: center; margin: 15px 0;">Don\'t miss out on these exclusive birthday rewards!</p>';
        $cta_html .= '</div>';

        return $cta_html;
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Generate complete preview of campaign for a sample user
     * @param int $campaign_id Campaign ID
     * @param array $override_criteria Optional criteria override
     * @return array Result with preview data or error
     */
    public function generateCampaignPreview($campaign_id, $override_criteria = null)
    {
        // Get campaign details
        $campaign_sql = "SELECT * FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
        $campaign = $this->database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);
        
        if (!$campaign) {
            return ['success' => false, 'error' => 'Campaign not found'];
        }

        // Parse recipient criteria
        $criteria = [];
        if (!empty($campaign['recipient_criteria'])) {
            $criteria = json_decode($campaign['recipient_criteria'], true);
        }
        
        // Allow override of criteria for preview testing
        if ($override_criteria) {
            $criteria = array_merge($criteria, $override_criteria);
        }

        // Get sample user
        $sample_user = $this->getSampleUserForCampaign($criteria);
        if (!$sample_user) {
            return ['success' => false, 'error' => 'No users match the campaign criteria'];
        }

        // Replace placeholders in subject and body
        $personalized_subject = $this->replacePlaceholders($campaign['subject'], $sample_user);
        $personalized_body = $this->replacePlaceholders($campaign['body_html'], $sample_user);

        // Generate CTA section if category specified
        $cta_html = '';
        if (!empty($campaign['cta_category'])) {
            $available_logos = $this->getAvailableBusinessLogosForCTA($sample_user['user_id'], $campaign['cta_category']);
            $cta_html = $this->generateCTASection($available_logos, $sample_user);
        }

        // Replace CTA placeholder in body
        $personalized_body = str_replace('{{CTA_SECTION}}', $cta_html, $personalized_body);

        return [
            'success' => true,
            'campaign' => $campaign,
            'sample_user' => $sample_user,
            'personalized_subject' => $personalized_subject,
            'personalized_body' => $personalized_body,
            'cta_businesses' => $available_logos ?? [],
            'preview_html' => $this->wrapInEmailTemplate($personalized_body, $personalized_subject)
        ];
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Wrap content in email template for preview
     * @param string $body Email body content
     * @param string $subject Email subject
     * @return string Complete HTML email
     */
    private function wrapInEmailTemplate($body, $subject)
    {
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($subject) . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .email-header { background: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid goldenrod; }
                .email-body { padding: 30px 20px; }
                .email-footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="email-header">
                <h1 style="color: goldenrod; margin: 0;">Birthday.Gold</h1>
                <p style="margin: 5px 0 0 0; color: #666;">Your Birthday Celebration Platform</p>
            </div>
            <div class="email-body">
                ' . $body . '
            </div>
            <div class="email-footer">
                <p>&copy; ' . date('Y') . ' Birthday.Gold - Making birthdays special, one celebration at a time.</p>
                <p><a href="https://birthday.gold/unsubscribe.php">Unsubscribe</a> | <a href="https://birthday.gold/legalhub/privacy.php">Privacy Policy</a></p>
            </div>
        </body>
        </html>';

        return $template;
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Store notification in bg_user_notifications table
     * @param int $user_id User ID
     * @param int $campaign_id Campaign ID
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return int Notification ID
     */
    public function storeUserNotification($user_id, $campaign_id, $subject, $body)
    {
        $sql = "INSERT INTO bg_user_notifications 
                (user_id, type, title, message, status, create_dt, modify_dt, priority, category, email_campaign_id) 
                VALUES 
                (:user_id, 'newsletter', :title, :message, 'unread', NOW(), NOW(), 'normal', 'marketing', :campaign_id)";
        
        $params = [
            'user_id' => $user_id,
            'title' => $subject,
            'message' => $body,
            'campaign_id' => $campaign_id
        ];

        try {
            $this->database->query($sql, $params);
            return $this->database->lastInsertId();
        } catch (Exception $e) {
            error_log("Marketing::storeUserNotification - Error: " . $e->getMessage());
            return 0;
        }
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Queue campaign for batch sending
     * @param int $campaign_id Campaign ID
     * @param array $criteria Optional recipient criteria override
     * @return array Result with queue statistics
     */
    public function queueCampaignForSending($campaign_id, $criteria = null)
    {
        $campaign_sql = "SELECT * FROM bg_newsletter_campaigns WHERE campaign_id = :campaign_id";
        $campaign = $this->database->getrow($campaign_sql, ['campaign_id' => $campaign_id]);
        
        if (!$campaign) {
            return ['success' => false, 'error' => 'Campaign not found'];
        }

        // Parse criteria
        if (!$criteria && !empty($campaign['recipient_criteria'])) {
            $criteria = json_decode($campaign['recipient_criteria'], true);
        }

        // Get target users
        $users = $this->getTargetUsers($criteria);
        
        if (empty($users)) {
            return ['success' => false, 'error' => 'No users match the criteria'];
        }

        $queued_count = 0;
        $failed_count = 0;
        
        $send_dt = !empty($campaign['send_dt']) ? $campaign['send_dt'] : date('Y-m-d H:i:s');

        foreach ($users as $user) {
            try {
                // Check if already queued
                $exists_sql = "SELECT queue_id FROM bg_newsletter_queue 
                              WHERE campaign_id = :campaign_id AND user_id = :user_id";
                $exists = $this->database->getrow($exists_sql, [
                    'campaign_id' => $campaign_id,
                    'user_id' => $user['user_id']
                ]);

                if (!$exists) {
                    $queue_sql = "INSERT INTO bg_newsletter_queue 
                                 (campaign_id, user_id, scheduled_dt, status, created_dt) 
                                 VALUES 
                                 (:campaign_id, :user_id, :scheduled_dt, 'pending', NOW())";
                    
                    $this->database->query($queue_sql, [
                        'campaign_id' => $campaign_id,
                        'user_id' => $user['user_id'],
                        'scheduled_dt' => $send_dt
                    ]);
                    
                    $queued_count++;
                }
            } catch (Exception $e) {
                $failed_count++;
                error_log("Marketing::queueCampaignForSending - Error queuing for user " . $user['user_id'] . ": " . $e->getMessage());
            }
        }

        // Update campaign status
        $update_sql = "UPDATE bg_newsletter_campaigns 
                       SET status = 'queued', queued_dt = NOW() 
                       WHERE campaign_id = :campaign_id";
        $this->database->query($update_sql, ['campaign_id' => $campaign_id]);

        return [
            'success' => true,
            'total_users' => count($users),
            'queued_count' => $queued_count,
            'failed_count' => $failed_count,
            'campaign_id' => $campaign_id
        ];
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get target users based on criteria
     * @param array $criteria Recipient criteria
     * @return array Array of user data
     */
    private function getTargetUsers($criteria = [])
    {
        $where_conditions = ["u.status = 'active'"];
        $params = [];

        // Apply same criteria logic as getSampleUserForCampaign but return all matching users
        if (!empty($criteria['age_range'])) {
            if (!empty($criteria['age_range']['min'])) {
                $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) >= :min_age";
                $params['min_age'] = $criteria['age_range']['min'];
            }
            if (!empty($criteria['age_range']['max'])) {
                $where_conditions[] = "TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) <= :max_age";
                $params['max_age'] = $criteria['age_range']['max'];
            }
        }

        if (!empty($criteria['city'])) {
            $where_conditions[] = "u.city = :city";
            $params['city'] = $criteria['city'];
        }

        if (!empty($criteria['state'])) {
            $where_conditions[] = "u.state = :state";  
            $params['state'] = $criteria['state'];
        }

        if (!empty($criteria['birth_month'])) {
            $where_conditions[] = "u.birth_month = :birth_month";
            $params['birth_month'] = $criteria['birth_month'];
        }

        // Exclude unsubscribed users
        $where_conditions[] = "u.user_id NOT IN (SELECT user_id FROM bg_unsubscribes WHERE status = 'active')";

        $sql = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.city, u.state, 
                       u.birth_date, u.birth_month
                FROM bg_users u 
                WHERE " . implode(' AND ', $where_conditions) . "
                ORDER BY u.user_id";

        try {
            return $this->database->getrows($sql, $params);
        } catch (Exception $e) {
            error_log("Marketing::getTargetUsers - Error: " . $e->getMessage());
            return [];
        }
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get campaign statistics
     * @param int $campaign_id Campaign ID
     * @return array Campaign stats
     */
    public function getCampaignStats($campaign_id)
    {
        // Queue stats
        $queue_stats_sql = "SELECT 
                           COUNT(*) as total_queued,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                           SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                           SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
                           FROM bg_newsletter_queue 
                           WHERE campaign_id = :campaign_id";
        
        $queue_stats = $this->database->getrow($queue_stats_sql, ['campaign_id' => $campaign_id]);

        // Event stats
        $event_stats_sql = "SELECT 
                           event_type,
                           COUNT(*) as count
                           FROM bg_newsletter_events 
                           WHERE campaign_id = :campaign_id 
                           GROUP BY event_type";
        
        $event_stats = $this->database->getrows($event_stats_sql, ['campaign_id' => $campaign_id]);

        return [
            'queue_stats' => $queue_stats ?: ['total_queued' => 0, 'pending' => 0, 'processing' => 0, 'sent' => 0, 'errors' => 0],
            'event_stats' => $event_stats ?: []
        ];
    }
}