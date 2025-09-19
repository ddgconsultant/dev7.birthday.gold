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
     * Log marketing activity for calendar tracking
     * @param string $activity_type Type of activity (platform_created, campaign_created, campaign_launched, etc.)
     * @param string $title Activity title
     * @param string $description Activity description
     * @param int $related_id ID of related object (platform_id, campaign_id, etc.)
     * @param string $related_type Type of related object (platform, campaign, etc.)
     * @param string $scheduled_date Date for calendar display (defaults to now)
     * @param array $metadata Additional data about the activity
     * @return int|false Activity ID or false on failure
     */
    public function logActivity($activity_type, $title, $description = '', $related_id = 0, $related_type = '', $scheduled_date = null, $metadata = [])
    {
        global $current_user_data;
        
        if (!$scheduled_date) {
            $scheduled_date = date('Y-m-d H:i:s');
        }
        
        $activity_data = [
            'activity_type' => $activity_type,
            'related_id' => $related_id,
            'related_type' => $related_type,
            'metadata' => $metadata,
            'created_by' => $current_user_data['user_id']
        ];
        
        $insert_sql = "INSERT INTO bg_content 
            (name, category, type, display_name, description, tags, publish_dt, status, create_dt) 
            VALUES 
            (:name, 'marketing', 'activity_log', :title, :description, :tags, :scheduled_date, 'active', NOW())";
        
        $name = 'activity_' . $activity_type . '_' . time() . '_' . $related_id;
        
        try {
            $this->database->query($insert_sql, [
                'name' => $name,
                'title' => $title,
                'description' => $description,
                'tags' => json_encode($activity_data),
                'scheduled_date' => $scheduled_date
            ]);
            
            return $this->database->lastInsertId();
        } catch (Exception $e) {
            error_log("Marketing::logActivity - Error: " . $e->getMessage());
            return false;
        }
    }
    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Create a new marketing platform
     * @param array $platform_config Platform configuration
     * @return int|false Platform ID or false on failure
     */
    public function createPlatform($platform_config)
    {
        $insert_sql = "INSERT INTO bg_content 
            (name, category, type, display_name, description, tags, `rank`, status, create_dt) 
            VALUES 
            (:name, 'marketing', 'platform_link', :display_name, :description, :tags, :rank, 'active', NOW())";
        
        $name = 'platform_' . time() . '_' . substr(md5($platform_config['display_name']), 0, 8);
        
        try {
            $this->database->query($insert_sql, [
                'name' => $name,
                'display_name' => $platform_config['display_name'],
                'description' => $platform_config['description'],
                'tags' => json_encode($platform_config['tags']),
                'rank' => $platform_config['rank']
            ]);
            
            $platform_id = $this->database->lastInsertId();
            
            // Log the platform creation activity
            $this->logActivity(
                'platform_created',
                'Platform Created: ' . $platform_config['display_name'],
                'New marketing platform added: ' . $platform_config['description'],
                $platform_id,
                'platform',
                null,
                ['platform_type' => $platform_config['platform_type'] ?? 'unknown']
            );
            
            return $platform_id;
        } catch (Exception $e) {
            error_log("Marketing::createPlatform - Error: " . $e->getMessage());
            return false;
        }
    }
    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Create a new marketing campaign
     * @param int $platform_id Platform ID this campaign belongs to
     * @param array $campaign_config Campaign configuration
     * @return int|false Campaign ID or false on failure
     */
    public function createCampaign($platform_id, $campaign_config)
    {
        global $current_user_data;
        
        $campaign_data = array_merge($campaign_config, [
            'platform_id' => $platform_id,
            'created_by' => $current_user_data['user_id'],
            'external_id' => null,
            'metrics' => []
        ]);
        
        $insert_sql = "INSERT INTO bg_content 
            (name, category, type, display_name, description, tags, status, create_dt) 
            VALUES 
            (:name, 'marketing', 'campaign', :display_name, :description, :tags, :status, NOW())";
        
        $name = 'campaign_' . $platform_id . '_' . time();
        
        try {
            $this->database->query($insert_sql, [
                'name' => $name,
                'display_name' => $campaign_config['display_name'],
                'description' => $campaign_config['description'],
                'tags' => json_encode($campaign_data),
                'status' => $campaign_config['status']
            ]);
            
            $campaign_id = $this->database->lastInsertId();
            
            // Log the campaign creation activity
            $this->logActivity(
                'campaign_created',
                'Campaign Created: ' . $campaign_config['display_name'],
                'New campaign created for platform',
                $campaign_id,
                'campaign',
                null,
                [
                    'platform_id' => $platform_id,
                    'campaign_type' => $campaign_config['type'] ?? 'unknown',
                    'budget' => $campaign_config['budget'] ?? 0,
                    'status' => $campaign_config['status']
                ]
            );
            
            // If campaign has a start date, log future launch activity
            if (!empty($campaign_config['start_date']) && $campaign_config['status'] == 'active') {
                $this->logActivity(
                    'campaign_launched',
                    'Campaign Launch: ' . $campaign_config['display_name'],
                    'Campaign scheduled to go live',
                    $campaign_id,
                    'campaign',
                    $campaign_config['start_date'] . ' 09:00:00',
                    [
                        'platform_id' => $platform_id,
                        'budget' => $campaign_config['budget'] ?? 0
                    ]
                );
            }
            
            return $campaign_id;
        } catch (Exception $e) {
            error_log("Marketing::createCampaign - Error: " . $e->getMessage());
            return false;
        }
    }
    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get marketing activities for calendar display
     * @param string $start_date Start date for range (Y-m-d)
     * @param string $end_date End date for range (Y-m-d)
     * @param array $activity_types Filter by activity types
     * @return array Activities for calendar
     */
    public function getActivitiesForCalendar($start_date, $end_date, $activity_types = [])
    {
        $where_conditions = [
            "category = 'marketing'",
            "type = 'activity_log'",
            "publish_dt BETWEEN :start_date AND :end_date"
        ];
        $params = [
            'start_date' => $start_date . ' 00:00:00',
            'end_date' => $end_date . ' 23:59:59'
        ];
        
        if (!empty($activity_types)) {
            $placeholders = str_repeat('?,', count($activity_types) - 1) . '?';
            $where_conditions[] = "JSON_EXTRACT(tags, '$.activity_type') IN ($placeholders)";
            $params = array_merge($params, $activity_types);
        }
        
        $sql = "SELECT *, publish_dt as activity_date FROM bg_content 
                WHERE " . implode(' AND ', $where_conditions) . "
                ORDER BY publish_dt ASC";
        
        try {
            $activities = $this->database->getrows($sql, $params);
            
            // Enhance with metadata
            foreach ($activities as &$activity) {
                $activity['metadata'] = json_decode($activity['tags'], true) ?: [];
                $activity['activity_type'] = $activity['metadata']['activity_type'] ?? 'unknown';
            }
            
            return $activities;
        } catch (Exception $e) {
            error_log("Marketing::getActivitiesForCalendar - Error: " . $e->getMessage());
            return [];
        }
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
                INNER JOIN bg_companies_logos cl ON c.company_id = cl.company_id
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
        // Get campaign details from mk_campaigns
        $campaign_sql = "SELECT campaign_id, campaign_name as title, email_subject as subject, 
                        campaign_content as body_html, cta_category, cta_mode, 
                        recipient_criteria, gen_specific_messaging
                        FROM mk_campaigns 
                        WHERE campaign_id = :campaign_id AND campaign_type = 'newsletter'";
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
                <p><a href="https://birthday.gold/unsubscribe">Unsubscribe</a> | <a href="https://birthday.gold/privacy">Privacy Policy</a></p>
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
        $campaign_sql = "SELECT campaign_id, campaign_name as title, email_subject as subject,
                        campaign_content as body_html, recipient_criteria, start_date as send_dt,
                        newsletter_status as status
                        FROM mk_campaigns 
                        WHERE campaign_id = :campaign_id AND campaign_type = 'newsletter'";
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

        // Update campaign status in mk_campaigns
        $update_sql = "UPDATE mk_campaigns 
                       SET newsletter_status = 'queued', queued_dt = NOW() 
                       WHERE campaign_id = :campaign_id AND campaign_type = 'newsletter'";
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

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Calculate recipient count based on token criteria (with full parentheses and NOT support)
     * Uses RPN (Reverse Polish Notation) for complex boolean expressions
     * @param array $tokens Array of tokens defining recipient criteria
     * @return int Count of matching recipients
     */
    public function getRecipientCount($tokens = [])
    {
        if (empty($tokens)) {
            return 0;
        }

        // Base WHERE for active users with valid email
        $baseWhere = "u.status = 'active' AND u.email IS NOT NULL AND u.email != ''";

        // Short-circuit for ALL
        foreach ($tokens as $t) {
            if (($t['type'] ?? '') === 'all') {
                $sql = "SELECT COUNT(DISTINCT u.user_id) AS cnt FROM bg_users u WHERE $baseWhere";
                $stmt = $this->database->query($sql);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return (int)($row['cnt'] ?? 0);
            }
        }

        // Convert infix tokens to RPN using shunting-yard
        $rpn = $this->toRPN($tokens);
        if ($rpn === null) {
            // Invalid expression (unbalanced parentheses, etc.)
            return 0;
        }

        // Evaluate RPN into a single expression tree (sql snippet + params + join flags)
        $expr = $this->evaluateRPN($rpn);
        if ($expr === null || empty($expr['sql'])) {
            return 0;
        }
        // FROM clause based on flags
        $from = 'bg_users u';
        if (!empty($expr['needs_enroll'])) {
            $from .= ' LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = "success"';
        }
        if (!empty($expr['needs_business'])) {
            // Requires enroll join; ensure it's present
            if (strpos($from, ' bg_user_enrollments ') === false) {
                $from .= ' LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = "success"';
            }
            $from .= ' LEFT JOIN bg_businesses b ON e.business_id = b.business_id';
        }
        $finalWhere = $baseWhere . ' AND (' . $expr['sql'] . ')';
        $sql = 'SELECT COUNT(DISTINCT u.user_id) AS cnt FROM ' . $from . ' WHERE ' . $finalWhere;

        try {
            $stmt = $this->database->query($sql, $expr['params'] ?? []);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['cnt'] ?? 0);
        } catch (Throwable $e) {
            error_log('Marketing::getRecipientCount error: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($expr['params'] ?? []));
            return 0;
        }
    }

    /**
     * Get actual recipient data based on token criteria
     * @param array $tokens Array of tokens defining recipient criteria
     * @param int $limit Number of recipients to return
     * @return array Array of user data
     */
    public function getRecipients($tokens = [], $limit = 1)
    {
        if (empty($tokens)) {
            return [];
        }

        // Base WHERE for active users with valid email
        $baseWhere = "u.status = 'active' AND u.email IS NOT NULL AND u.email != ''";

        // Short-circuit for ALL
        foreach ($tokens as $t) {
            if (($t['type'] ?? '') === 'all') {
                $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, 
                        u.city, u.state, u.birthdate,
                        GROUP_CONCAT(DISTINCT e.company_id) as enrolled_company_ids
                        FROM bg_users u 
                        LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.enrollment_status = 'A'
                        WHERE $baseWhere 
                        GROUP BY u.user_id
                        LIMIT " . intval($limit);
                $stmt = $this->database->query($sql);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Convert infix tokens to RPN using shunting-yard
        $rpn = $this->toRPN($tokens);
        if ($rpn === null) {
            // Invalid expression (unbalanced parentheses, etc.)
            return [];
        }

        // Evaluate RPN into a single expression tree (sql snippet + params + join flags)
        $expr = $this->evaluateRPN($rpn);
        if ($expr === null || empty($expr['sql'])) {
            return [];
        }
        
        // FROM clause based on flags
        $from = 'bg_users u';
        if (!empty($expr['needs_enroll'])) {
            $from .= ' LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = "success"';
        }
        if (!empty($expr['needs_business'])) {
            // Requires enroll join; ensure it's present
            if (strpos($from, ' bg_user_enrollments ') === false) {
                $from .= ' LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = "success"';
            }
            $from .= ' LEFT JOIN bg_businesses b ON e.business_id = b.business_id';
        }
        
        $finalWhere = $baseWhere . ' AND (' . $expr['sql'] . ')';
        
        // Get params without limit (LIMIT needs special handling)
        $params = $expr['params'] ?? [];
        
        // Always join enrollments to get company IDs
        if (strpos($from, 'bg_user_enrollments') === false) {
            $from .= ' LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = "success"';
        }
        
        // Use subquery to get distinct users first, then join for company IDs
        $sql = 'SELECT u.user_id, u.first_name, u.last_name, u.email, 
                u.city, u.state, u.birthdate,
                GROUP_CONCAT(DISTINCT e.company_id) as enrolled_company_ids
                FROM (
                    SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email, 
                    u.city, u.state, u.birthdate
                    FROM ' . $from . ' 
                    WHERE ' . $finalWhere . ' 
                    LIMIT ' . intval($limit) . '
                ) u
                LEFT JOIN bg_user_enrollments e ON u.user_id = e.user_id AND e.status = "success"
                GROUP BY u.user_id';

        try {
            // Debug logging
            error_log('Marketing::getRecipients SQL: ' . $sql);
            error_log('Marketing::getRecipients Params: ' . json_encode($params));
            
            $stmt = $this->database->query($sql, $params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('Marketing::getRecipients Results count: ' . count($results));
            if (!empty($results)) {
                error_log('Marketing::getRecipients First result: ' . json_encode($results[0]));
            }
            
            return $results;
        } catch (Throwable $e) {
            error_log('Marketing::getRecipients error: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($params));
            return [];
        }
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /** @var int Used for generating unique parameter names */
    private $paramCounter = 0;

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Convert infix tokens to Reverse Polish Notation (supports AND/OR/NOT and parentheses)
     * @param array $tokens
     * @return array<int, array>|null
     */
    private function toRPN(array $tokens)
    {
        $output = [];
        $ops = [];

        $prec = [
            'NOT' => 3,
            'AND' => 2,
            'OR'  => 1,
        ];

        $rightAssoc = [
            'NOT' => true, // unary right-associative
            'AND' => false,
            'OR'  => false,
        ];

        foreach ($tokens as $tok) {
            $type = $tok['type'] ?? '';

            if ($type === 'operator') {
                $op = strtoupper(trim($tok['value'] ?? ''));
                if (!isset($prec[$op])) {
                    return null; // unknown operator
                }
                while (!empty($ops)) {
                    $top = end($ops);
                    if (($top['type'] ?? '') !== 'operator') {
                        break;
                    }
                    $topOp = strtoupper($top['value']);
                    if (!isset($prec[$topOp])) {
                        break;
                    }
                    $cmp = $prec[$topOp] - $prec[$op];
                    if ($cmp > 0 || ($cmp === 0 && !$rightAssoc[$op])) {
                        $output[] = array_pop($ops);
                    } else {
                        break;
                    }
                }
                $ops[] = ['type' => 'operator', 'value' => $op];
                continue;
            }

            if ($type === 'parenthesis') {
                $val = $tok['value'] ?? '';
                if ($val === '(') {
                    $ops[] = ['type' => 'parenthesis', 'value' => '('];
                } elseif ($val === ')') {
                    $found = false;
                    while (!empty($ops)) {
                        $top = array_pop($ops);
                        if (($top['type'] ?? '') === 'parenthesis' && ($top['value'] ?? '') === '(') {
                            $found = true;
                            break;
                        }
                        $output[] = $top;
                    }
                    if (!$found) {
                        return null; // mismatched parenthesis
                    }
                } else {
                    return null; // invalid parenthesis token
                }
                continue;
            }

            if ($type === 'all') {
                // Should have been short-circuited earlier; ignore here
                continue;
            }

            // Criteria token -> push directly to output
            $output[] = $tok;
        }

        while (!empty($ops)) {
            $top = array_pop($ops);
            if (($top['type'] ?? '') === 'parenthesis') {
                return null; // mismatched
            }
            $output[] = $top;
        }

        return $output;
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Evaluate RPN into a single SQL expression bundle
     * @param array $rpn
     * @return array|null [sql, params, needs_enroll, needs_business]
     */
    private function evaluateRPN(array $rpn)
    {
        $stack = [];

        foreach ($rpn as $tok) {
            $type = $tok['type'] ?? '';

            if ($type === 'operator') {
                $op = strtoupper($tok['value'] ?? '');
                if ($op === 'NOT') {
                    $a = array_pop($stack);
                    if (!$a) { return null; }
                    $stack[] = [
                        'sql' => '(NOT ' . $a['sql'] . ')',
                        'params' => $a['params'] ?? [],
                        'needs_enroll' => !empty($a['needs_enroll']),
                        'needs_business' => !empty($a['needs_business']),
                    ];
                } else {
                    $b = array_pop($stack); // right
                    $a = array_pop($stack); // left
                    if (!$a || !$b) { return null; }
                    $bundle = $this->combine($a, $b, $op);
                    $stack[] = $bundle;
                }
                continue;
            }

            // Criteria -> build leaf expression
            $leaf = $this->tokenToExpr($tok);
            if ($leaf === null) { return null; }
            $stack[] = $leaf;
        }

        if (count($stack) !== 1) { return null; }
        return $stack[0];
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Combine two expression bundles with an operator
     */
    private function combine(array $a, array $b, string $op): array
    {
        $sql = '(' . $a['sql'] . ' ' . $op . ' ' . $b['sql'] . ')';
        $params = array_merge($a['params'] ?? [], $b['params'] ?? []);
        return [
            'sql' => $sql,
            'params' => $params,
            'needs_enroll' => (!empty($a['needs_enroll']) || !empty($b['needs_enroll'])),
            'needs_business' => (!empty($a['needs_business']) || !empty($b['needs_business'])),
        ];
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Map a single criteria token to an expression bundle
     * @param array $token
     * @return array|null
     */
    private function tokenToExpr(array $token)
    {
        $type = $token['type'] ?? '';
        $val  = $token['value'] ?? null;

        $cond = '';
        $params = [];
        $needsEnroll = false;
        $needsBiz = false;

        switch ($type) {
            case 'account_type':
                // Database uses 'type' field with values 'test' and 'real'
                if ($val === 'real') {
                    $cond = "(u.type = 'real' OR u.type IS NULL OR u.type = '')";
                } elseif ($val === 'test') {
                    $cond = "u.type = 'test'";
                } elseif ($val === 'staff') {
                    // Check account_type field for staff
                    $cond = "u.account_type = 'staff'";
                } elseif ($val === 'demo') {
                    $cond = "u.account_type = 'demo'";
                }
                break;

            case 'gender':
                // Database stores 'male' and 'female' as full words
                if ($val === 'not_specified') {
                    $cond = "(u.gender IS NULL OR u.gender = '' OR u.gender = 'U')";
                } elseif ($val === 'prefer_not') {
                    $cond = "u.gender = 'U'";
                } else {
                    $k = $this->newParam('gender');
                    $cond = "u.gender = :$k";
                    // Keep the full value (male/female) instead of just first letter
                    $params[$k] = (string)$val;
                }
                break;

            case 'birthday_month':
                $k = $this->newParam('month');
                $cond = "MONTH(u.birthdate) = :$k";
                $params[$k] = (int)$val;
                break;

            case 'state':
                $k = $this->newParam('state');
                $cond = "u.state = :$k";
                $params[$k] = (string)$val;
                break;

            case 'age_range':
                $value = str_replace('+', '', (string)$val);
                $parts = explode('-', $value);
                if (strpos((string)$val, '+') !== false) {
                    $min = (int)($parts[0] ?? 0);
                    $cond = 'TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) >= ' . $min;
                } elseif (count($parts) === 2) {
                    $min = (int)$parts[0];
                    $max = (int)$parts[1];
                    $cond = 'TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) BETWEEN ' . $min . ' AND ' . $max;
                }
                break;

            case 'plan':
                if ($val === 'free') {
                    $cond = "(NOT EXISTS (SELECT 1 FROM bg_user_attributes ua 
                                  WHERE ua.user_id = u.user_id 
                                  AND ua.type = 'subscription' 
                                  AND ua.name = 'plan_type' 
                                  AND ua.value != 'free' 
                                  AND ua.status = 'A'))";
                } else {
                    $k = $this->newParam('plan');
                    $cond = "EXISTS (SELECT 1 FROM bg_user_attributes ua 
                                  WHERE ua.user_id = u.user_id 
                                  AND ua.type = 'subscription' 
                                  AND ua.name = 'plan_type' 
                                  AND ua.value = :$k 
                                  AND ua.status = 'A')";
                    $params[$k] = (string)$val;
                }
                break;

            case 'profile_completeness':
                if ((string)$val === '100') {
                    $cond = '(' .
                        'u.first_name IS NOT NULL AND u.first_name != \'\' AND ' .
                        'u.last_name  IS NOT NULL AND u.last_name  != \'\' AND ' .
                        'u.birthdate  IS NOT NULL AND ' .
                        'u.gender     IS NOT NULL AND u.gender     != \'\' AND ' .
                        'u.zip_code   IS NOT NULL AND u.zip_code   != \'\' AND ' .
                        'u.phone_number IS NOT NULL AND u.phone_number != \'\'' .
                    ')';
                } else {
                    $ranges = explode('-', (string)$val);
                    if (count($ranges) === 2) {
                        $min = (int)$ranges[0];
                        $max = (int)$ranges[1];
                        if ($min === 0 && $max === 25) {
                            $cond = "(u.first_name IS NULL OR u.first_name = '')";
                        } elseif ($min === 26 && $max === 50) {
                            $cond = "(u.first_name IS NOT NULL AND u.first_name != '' AND (u.last_name IS NULL OR u.last_name = ''))";
                        } elseif ($min === 51 && $max === 75) {
                            $cond = "(u.first_name IS NOT NULL AND u.first_name != '' AND u.last_name IS NOT NULL AND u.last_name != '' AND u.birthdate IS NOT NULL)";
                        } elseif ($min === 76 && $max === 99) {
                            $cond = "(u.first_name IS NOT NULL AND u.first_name != '' AND u.last_name IS NOT NULL AND u.last_name != '' AND u.birthdate IS NOT NULL AND u.gender IS NOT NULL AND u.gender != '')";
                        }
                    }
                }
                break;

            case 'enrollment_count':
                $needsEnroll = true;
                if ((string)$val === '0') {
                    $cond = '(e.enrollment_id IS NULL)';
                } elseif (strpos((string)$val, '+') !== false) {
                    $min = (int)str_replace('+', '', (string)$val);
                    $cond = '(SELECT COUNT(*) FROM bg_user_enrollments e2 WHERE e2.user_id = u.user_id AND e2.status = "success") >= ' . $min;
                } else {
                    $ranges = explode('-', (string)$val);
                    if (count($ranges) === 2) {
                        $min = (int)$ranges[0];
                        $max = (int)$ranges[1];
                        $cond = '(SELECT COUNT(*) FROM bg_user_enrollments e2 WHERE e2.user_id = u.user_id AND e2.status = "success") BETWEEN ' . $min . ' AND ' . $max;
                    }
                }
                break;

            case 'business_category':
                $needsEnroll = true;
                $needsBiz = true;
                $k = $this->newParam('category');
                $cond = "b.business_category = :$k";
                $params[$k] = (string)$val;
                break;

            default:
                return null; // unknown criteria
        }

        if ($cond === '') {
            return null;
        }

        return [
            'sql' => '(' . $cond . ')',
            'params' => $params,
            'needs_enroll' => $needsEnroll,
            'needs_business' => $needsBiz,
        ];
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Generate a unique parameter key
     */
    private function newParam(string $prefix): string
    {
        $k = $prefix . '_' . $this->paramCounter;
        $this->paramCounter++;
        return $k;
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get companies with logos for newsletter CTA block
     * @param string $category Display category to filter by
     * @param string $mode 'inclusive' or 'exclusive'
     * @param array $userEnrollments Array of enrolled company IDs
     * @param int $limit Number of companies to return
     * @return array Companies with logo information
     */
    public function getCompaniesForCTA($category, $mode = 'inclusive', $userEnrollments = [], $limit = 4, $user_id = null)
    {
        error_log("getCompaniesForCTA called - Category: $category, Mode: $mode, Enrollments: " . implode(',', $userEnrollments));
        
        $params = ['category' => $category];
        $excludeClause = '';
        
        // Use user_id as seed for consistent randomization if provided
        $orderBy = $user_id ? "ORDER BY RAND(" . intval($user_id) . ")" : "ORDER BY RAND()";
        
        if ($mode === 'inclusive' && !empty($userEnrollments)) {
            // Inclusive: Only show user's enrolled companies
            $placeholders = array_map(function($i) { return ':company_' . $i; }, array_keys($userEnrollments));
            foreach ($userEnrollments as $i => $companyId) {
                $params['company_' . $i] = $companyId;
            }
            $includeClause = " AND c.company_id IN (" . implode(',', $placeholders) . ")";
            
            // Using the proper query from enrollment-picker.php
            $sql = "SELECT c.company_id, c.company_name, c.description as offer_text, 
                           MAX(a.description) as company_logo
                    FROM bg_companies c
                    LEFT JOIN bg_company_attributes a ON c.company_id = a.company_id 
                        AND a.category = 'company_logos' AND a.`grouping` = 'primary_logo'
                    WHERE c.status = 'finalized' 
                    AND c.display_category = :category
                    $includeClause
                    GROUP BY c.company_id, c.company_name, c.description
                    $orderBy
                    LIMIT " . intval($limit);
        } else {
            // Exclusive mode or no enrollments: Show other companies
            if ($mode === 'exclusive' && !empty($userEnrollments)) {
                $excludePlaceholders = array_map(function($i) { return ':exclude_' . $i; }, array_keys($userEnrollments));
                foreach ($userEnrollments as $i => $companyId) {
                    $params['exclude_' . $i] = $companyId;
                }
                $excludeClause = " AND c.company_id NOT IN (" . implode(',', $excludePlaceholders) . ")";
            }
            
            // Debug: check what categories exist
            $cat_sql = "SELECT DISTINCT display_category, COUNT(*) as count FROM bg_companies WHERE status = 'finalized' AND display_category IS NOT NULL GROUP BY display_category";
            $categories_exist = $this->database->getrows($cat_sql);
            error_log("Available categories: " . json_encode($categories_exist));
            
            $sql = "SELECT c.company_id, c.company_name, c.description as offer_text, 
                           MAX(a.description) as company_logo
                    FROM bg_companies c
                    LEFT JOIN bg_company_attributes a ON c.company_id = a.company_id 
                        AND a.category = 'company_logos' AND a.`grouping` = 'primary_logo'
                    WHERE c.status = 'finalized' 
                    AND c.display_category = :category
                    $excludeClause
                    GROUP BY c.company_id, c.company_name, c.description
                    $orderBy
                    LIMIT " . intval($limit);
        }
        
        try {
            error_log("SQL Query: " . $sql);
            error_log("SQL Params: " . json_encode($params));
            
            $companies = $this->database->getrows($sql, $params);
            
            error_log("Companies found: " . count($companies));
            if (!empty($companies)) {
                error_log("First company: " . json_encode($companies[0]));
            }
            
            // If no companies found, DON'T fall back to random companies
            // This prevents wrong categories from appearing (e.g., Target in Food category)
            if (empty($companies)) {
                error_log("No companies found for category '$category'");
                
                // Option 1: Return empty array (no CTA block shown)
                // return [];
                
                // Option 2: Try to find companies with offers/deals regardless of category
                // but ONLY if they have actual birthday offers
                $fallback_sql = "SELECT c.company_id, c.company_name, c.description as offer_text, 
                                       a.description as company_logo
                        FROM bg_companies c
                        LEFT JOIN bg_company_attributes a ON c.company_id = a.company_id 
                            AND a.category = 'company_logos' AND a.`grouping` = 'primary_logo'
                        WHERE c.status = 'finalized'
                        AND c.description IS NOT NULL 
                        AND c.description != ''
                        AND (c.description LIKE '%birthday%' OR c.description LIKE '%free%' OR c.description LIKE '%discount%')
                        ORDER BY RAND()
                        LIMIT " . intval($limit);
                
                $companies = $this->database->getrows($fallback_sql);
                if (!empty($companies)) {
                    error_log("Fallback: Found " . count($companies) . " companies with birthday offers");
                } else {
                    error_log("No fallback companies with offers found - CTA block will be empty");
                }
            }
            
            // Process logo URLs using the same format as discover.php
            foreach ($companies as &$company) {
                if (!empty($company['company_logo'])) {
                    // Format: //cdn.birthday.gold/public/images/company_images/{company_id}/{logo_filename}
                    $company['logo'] = '//cdn.birthday.gold/public/images/company_images/' . $company['company_id'] . '/' . $company['company_logo'];
                } else {
                    $company['logo'] = null; // Will show placeholder
                }
            }
            
            return $companies;
        } catch (Exception $e) {
            error_log("Marketing::getCompaniesForCTA - Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate CTA Block HTML for newsletters
     * Centralized method to create consistent CTA blocks across all newsletter components
     * 
     * @param string $category - The CTA category (e.g., 'Retail', 'Food', etc.)
     * @param string $mode - 'inclusive' or 'exclusive' mode
     * @param int $user_id - The recipient user ID
     * @param int $campaign_id - The campaign ID for tracking
     * @param array $user_data - User data array with first_name, last_name, etc.
     * @param string $cta_button_text - Custom CTA button text (optional)
     * @return string HTML for the CTA block
     */
    public function generateCTABlockHTML($category, $mode = 'inclusive', $user_id = 0, $campaign_id = 0, $user_data = [], $cta_button_text = 'Claim Reward →')
    {
        try {
            // Get user's enrollments for inclusive/exclusive mode
            $enrollments_sql = "SELECT company_id FROM bg_user_enrollments 
                               WHERE user_id = :user_id AND status = 'success'";
            $enrollments = $this->database->getrows($enrollments_sql, ['user_id' => $user_id]);
            $user_enrollments = array_column($enrollments, 'company_id');
            
            // Get companies using the existing method - pass user_id for consistent randomization
            $companies = $this->getCompaniesForCTA($category, $mode, $user_enrollments, 4, $user_id);
            
            if (empty($companies)) {
                // Return placeholder if no companies found
                return '<div style="border: 2px dashed #ccc; padding: 20px; margin: 20px 0; text-align: center;">' .
                       '<strong>Birthday Rewards</strong><br>No offers available in this category</div>';
            }
            
            // Build the CTA block HTML (2x2 grid)
            $html = '<div style="background: #f8f9fa; padding: 30px; margin: 20px 0; border-radius: 8px;">';
            $html .= '<h3 style="color: #333; margin-bottom: 20px; text-align: center;">🎁 Your Birthday Rewards Await!</h3>';
            $html .= '<table style="width: 100%; border-collapse: separate; border-spacing: 20px;">';
            
            // Create 2x2 grid
            for ($i = 0; $i < count($companies); $i += 2) {
                $html .= '<tr>';
                
                // First column
                if (isset($companies[$i])) {
                    $company = $companies[$i];
                    $html .= '<td style="width: 50%; background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top;">';
                    
                    // Company logo or placeholder
                    $logo_displayed = false;
                    if (!empty($company['logo'])) {
                        // Check if it's already base64 or needs processing
                        if (strpos($company['logo'], 'data:image') === 0) {
                            // Already base64
                            $logo_src = $company['logo'];
                            $logo_displayed = true;
                        } else {
                            // Try to convert to base64
                            if (strpos($company['logo'], '//') === 0 || strpos($company['logo'], 'http') === 0) {
                                $logo_src = $this->convertImageToBase64($company['logo'], 96, 96);
                            } else {
                                // Assume it's a relative path
                                $logo_src = $this->convertImageToBase64('https:' . $company['logo'], 96, 96);
                            }
                            
                            // Check if conversion was successful
                            if (!empty($logo_src)) {
                                $logo_displayed = true;
                            }
                        }
                        
                        if ($logo_displayed) {
                            $html .= '<img src="' . $logo_src . '" style="max-width: 96px; max-height: 96px; margin-bottom: 10px; border-radius: 4px;" alt="' . htmlspecialchars($company['company_name']) . '">';
                        }
                    }
                    
                    // Use attractive placeholder if no logo
                    if (!$logo_displayed) {
                        // Create initials from company name
                        $initials = '';
                        $name_parts = explode(' ', $company['company_name']);
                        foreach ($name_parts as $part) {
                            if (!empty($part) && strlen($initials) < 2) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                        }
                        if (empty($initials)) {
                            $initials = strtoupper(substr($company['company_name'], 0, 2));
                        }
                        
                        // Generate a consistent color based on company name
                        $color_hash = substr(md5($company['company_name']), 0, 6);
                        $bg_color = '#' . $color_hash;
                        
                        $html .= '<div style="width: 96px; height: 96px; background: ' . $bg_color . '; border-radius: 8px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold; font-family: Arial, sans-serif;">' . htmlspecialchars($initials) . '</div>';
                    }
                    
                    // Company name
                    $html .= '<h4 style="color: #333; font-size: 16px; margin: 10px 0;">' . htmlspecialchars($company['company_name']) . '</h4>';
                    
                    // Offer text
                    if (!empty($company['offer_text'])) {
                        $html .= '<p style="color: #666; font-size: 14px; margin: 10px 0; min-height: 40px;">' . htmlspecialchars($company['offer_text']) . '</p>';
                    }
                    
                    // Tracking URL
                    $track_url = 'https://m.bd.gold/track/cta/' . 
                                $this->qik->encodeId($campaign_id) . '/' . 
                                $this->qik->encodeId($user_id) . '/' . 
                                $this->qik->encodeId($company['company_id']);
                    
                    $html .= '<a href="' . $track_url . '" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold;">' . htmlspecialchars($cta_button_text) . '</a>';
                    $html .= '</td>';
                } else {
                    $html .= '<td style="width: 50%;"></td>';
                }
                
                // Second column
                if (isset($companies[$i + 1])) {
                    $company = $companies[$i + 1];
                    $html .= '<td style="width: 50%; background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top;">';
                    
                    // Company logo or placeholder
                    $logo_displayed = false;
                    if (!empty($company['logo'])) {
                        // Check if it's already base64 or needs processing
                        if (strpos($company['logo'], 'data:image') === 0) {
                            // Already base64
                            $logo_src = $company['logo'];
                            $logo_displayed = true;
                        } else {
                            // Try to convert to base64
                            if (strpos($company['logo'], '//') === 0 || strpos($company['logo'], 'http') === 0) {
                                $logo_src = $this->convertImageToBase64($company['logo'], 96, 96);
                            } else {
                                // Assume it's a relative path
                                $logo_src = $this->convertImageToBase64('https:' . $company['logo'], 96, 96);
                            }
                            
                            // Check if conversion was successful
                            if (!empty($logo_src)) {
                                $logo_displayed = true;
                            }
                        }
                        
                        if ($logo_displayed) {
                            $html .= '<img src="' . $logo_src . '" style="max-width: 96px; max-height: 96px; margin-bottom: 10px; border-radius: 4px;" alt="' . htmlspecialchars($company['company_name']) . '">';
                        }
                    }
                    
                    // Use attractive placeholder if no logo
                    if (!$logo_displayed) {
                        // Create initials from company name
                        $initials = '';
                        $name_parts = explode(' ', $company['company_name']);
                        foreach ($name_parts as $part) {
                            if (!empty($part) && strlen($initials) < 2) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                        }
                        if (empty($initials)) {
                            $initials = strtoupper(substr($company['company_name'], 0, 2));
                        }
                        
                        // Generate a consistent color based on company name
                        $color_hash = substr(md5($company['company_name']), 0, 6);
                        $bg_color = '#' . $color_hash;
                        
                        $html .= '<div style="width: 96px; height: 96px; background: ' . $bg_color . '; border-radius: 8px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold; font-family: Arial, sans-serif;">' . htmlspecialchars($initials) . '</div>';
                    }
                    
                    // Company name
                    $html .= '<h4 style="color: #333; font-size: 16px; margin: 10px 0;">' . htmlspecialchars($company['company_name']) . '</h4>';
                    
                    // Offer text
                    if (!empty($company['offer_text'])) {
                        $html .= '<p style="color: #666; font-size: 14px; margin: 10px 0; min-height: 40px;">' . htmlspecialchars($company['offer_text']) . '</p>';
                    }
                    
                    // Tracking URL
                    $track_url = 'https://m.bd.gold/track/cta/' . 
                                $this->qik->encodeId($campaign_id) . '/' . 
                                $this->qik->encodeId($user_id) . '/' . 
                                $this->qik->encodeId($company['company_id']);
                    
                    $html .= '<a href="' . $track_url . '" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold;">' . htmlspecialchars($cta_button_text) . '</a>';
                    $html .= '</td>';
                } else {
                    $html .= '<td style="width: 50%;"></td>';
                }
                
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            $html .= '<div style="text-align: center; margin-top: 20px;">';
            $html .= '<a href="https://birthday.gold/myaccount" style="color: #007bff; text-decoration: underline; font-size: 14px;">View all your birthday rewards →</a>';
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
            
        } catch (Exception $e) {
            error_log("Marketing::generateCTABlockHTML - Error: " . $e->getMessage());
            // Return placeholder on error
            return '<div style="border: 2px dashed #ccc; padding: 20px; margin: 20px 0; text-align: center;">' .
                   '<strong>Birthday Rewards</strong><br>Unable to load offers at this time</div>';
        }
    }
    
    /**
     * Convert image URL to base64 data URI with resizing
     * Enhanced with better error handling and multiple fallback strategies
     * 
     * @param string $image_url - The image URL to convert
     * @param int $width - Target width
     * @param int $height - Target height
     * @return string Base64 data URI, fallback URL, or empty string
     */
    private function convertImageToBase64($image_url, $width = 96, $height = 96)
    {
        try {
            error_log("convertImageToBase64: Processing logo URL: $image_url");
            
            // Clean up the URL
            $original_url = $image_url;
            if (strpos($image_url, '//') === 0) {
                $image_url = 'https:' . $image_url;
            }
            
            // Try multiple methods to fetch the image
            $image_data = false;
            
            // Method 1: Direct file_get_contents with context
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (compatible; Birthday Gold Newsletter)',
                    'follow_location' => true,
                    'max_redirects' => 3
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $image_data = @file_get_contents($image_url, false, $context);
            
            // Method 2: Try CURL if file_get_contents failed
            if (!$image_data) {
                error_log("convertImageToBase64: file_get_contents failed, trying CURL for: $image_url");
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $image_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Birthday Gold Newsletter)');
                $image_data = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code !== 200) {
                    error_log("convertImageToBase64: CURL failed with HTTP $http_code for: $image_url");
                    $image_data = false;
                }
            }
            
            // Method 3: Try local file path if CDN URL
            if (!$image_data && strpos($image_url, 'cdn.birthday.gold') !== false) {
                // Extract path from CDN URL and try local file
                $path_match = preg_match('/cdn\.birthday\.gold(.+)$/', $image_url, $matches);
                if ($path_match && isset($matches[1])) {
                    $local_path = $_SERVER['DOCUMENT_ROOT'] . '/../cdn.birthday.gold' . $matches[1];
                    error_log("convertImageToBase64: Trying local path: $local_path");
                    if (file_exists($local_path)) {
                        $image_data = @file_get_contents($local_path);
                    }
                }
            }
            
            if (!$image_data) {
                error_log("convertImageToBase64: All fetch methods failed for: $image_url");
                return ''; // Return empty string to trigger placeholder
            }
            
            // Detect image type from data
            $image_info = @getimagesizefromstring($image_data);
            if (!$image_info) {
                error_log("convertImageToBase64: Invalid image data from: $image_url");
                return '';
            }
            
            // Create image resource based on type
            $source_image = false;
            switch ($image_info['mime']) {
                case 'image/jpeg':
                    $source_image = @imagecreatefromstring($image_data);
                    break;
                case 'image/png':
                    $source_image = @imagecreatefromstring($image_data);
                    break;
                case 'image/gif':
                    $source_image = @imagecreatefromstring($image_data);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $source_image = @imagecreatefromstring($image_data);
                    }
                    break;
                default:
                    error_log("convertImageToBase64: Unsupported image type {$image_info['mime']} for: $image_url");
                    return '';
            }
            
            if (!$source_image) {
                error_log("convertImageToBase64: Failed to create image resource from: $image_url");
                return '';
            }
            
            // Get original dimensions
            $orig_width = imagesx($source_image);
            $orig_height = imagesy($source_image);
            
            // Skip resizing if already smaller than target
            if ($orig_width <= $width && $orig_height <= $height) {
                $new_width = $orig_width;
                $new_height = $orig_height;
            } else {
                // Calculate new dimensions maintaining aspect ratio
                $ratio = min($width / $orig_width, $height / $orig_height);
                $new_width = round($orig_width * $ratio);
                $new_height = round($orig_height * $ratio);
            }
            
            // Create resized image with white background (better for email)
            $resized_image = imagecreatetruecolor($new_width, $new_height);
            
            // Fill with white background first
            $white = imagecolorallocate($resized_image, 255, 255, 255);
            imagefill($resized_image, 0, 0, $white);
            
            // Then enable alpha blending for transparency support
            imagealphablending($resized_image, true);
            imagesavealpha($resized_image, true);
            
            // Resize the image
            imagecopyresampled($resized_image, $source_image, 0, 0, 0, 0, 
                              $new_width, $new_height, $orig_width, $orig_height);
            
            // Capture output as JPEG for smaller size (PNG can be large)
            ob_start();
            imagejpeg($resized_image, null, 85); // 85% quality for good balance
            $jpeg_data = ob_get_clean();
            
            // Also try PNG if JPEG is too large
            if (strlen($jpeg_data) > 50000) {
                ob_start();
                imagepng($resized_image, 9); // Maximum compression
                $png_data = ob_get_clean();
                
                if (strlen($png_data) < strlen($jpeg_data)) {
                    $final_data = $png_data;
                    $mime_type = 'image/png';
                } else {
                    $final_data = $jpeg_data;
                    $mime_type = 'image/jpeg';
                }
            } else {
                $final_data = $jpeg_data;
                $mime_type = 'image/jpeg';
            }
            
            // Clean up
            imagedestroy($source_image);
            imagedestroy($resized_image);
            
            // Convert to base64
            $base64 = 'data:' . $mime_type . ';base64,' . base64_encode($final_data);
            
            // Check if base64 is too large (>75KB for email compatibility)
            if (strlen($base64) > 75000) {
                error_log("convertImageToBase64: Base64 too large (" . strlen($base64) . " bytes), returning empty for: $image_url");
                return ''; // Return empty to use placeholder
            }
            
            error_log("convertImageToBase64: Success! Generated " . strlen($base64) . " byte base64 for: $image_url");
            return $base64;
            
        } catch (Exception $e) {
            error_log("convertImageToBase64: Exception - " . $e->getMessage() . " for URL: $image_url");
            return ''; // Return empty string to trigger placeholder
        }
    }
}