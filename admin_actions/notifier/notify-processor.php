<?php
/**
 * Notification Processor
 *
 * Processes and sends various user notifications including:
 * - Birthday reminders
 * - Profile completion prompts
 * - Enrollment status updates
 * - Account status notifications
 *
 * Also retries failed notification sends
 */

$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$config = [
    'testmail' => false,
    'actualsend' => true,
    'ignoretestaccounts' => true,
    'testemailaccount' => 'richard@ddg.mx'
];

$counters = [
    'process_datetime' => date('Y-m-d H:i:s'),
    'config_testmail' => $config['testmail'],
    'config_actualsend' => $config['actualsend'],
    'config_ignoretestaccounts' => $config['ignoretestaccounts'],

    // Add other counters
    'email_notifications' => 0,
    'profile_incomplete' => 0,
    'no_enrollments_week' => 0,
    'no_enrollments_month' => 0,
    'birthday_notifications' => 0,
    'total_notifications_sent' => 0,
    'emails_sent' => 0
];



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function log_notification($notification_data)
{
    global $database, $mail, $qik, $lastnotification_sent, $buttonlink,  $details,  $counters, $config;
    unset($details);
    unset($notify_user_data);
    // Set start_dt to NOW
    $start_dt = date('Y-m-d H:i:s');

    // Calculate end_dt based on the format like "5d" (5 days later)
    if (isset($notification_data['end_dt'])) {
        // Assuming the format is something like "5d" (for days)
        $interval = substr($notification_data['end_dt'], 0, -1);  // Extract the number (e.g., "5")
        $unit = substr($notification_data['end_dt'], -1);         // Extract the unit (e.g., "d" for days)

        switch ($unit) {
            case 'd':
                $end_dt = date('Y-m-d H:i:s', strtotime("+$interval days"));
                break;
            case 'h':
                $end_dt = date('Y-m-d H:i:s', strtotime("+$interval hours"));
                break;
            case 'm':
                $end_dt = date('Y-m-d H:i:s', strtotime("+$interval minutes"));
                break;
            default:
                $end_dt = null;  // If format is invalid, set to null
        }
    } else {
        $end_dt = null;
    }


    if ($config['ignoretestaccounts'] && strpos($notification_data['sent_to'], '@bdtest.xyz') !== false) {
        $qik->logmessage("<p style='color: #999;'>⏭️ Skipped test account: " . $notification_data['user_id'] . " - " . $notification_data['title'] . "</p>\n", 1);
        return;
    }

    // Prepare the query to insert the notification
    $query = "INSERT INTO bg_user_notifications (user_id, `type`, title, message, `status`, create_dt, modify_dt, sent_to, alert_class, priority, category, sent_dt, start_dt, end_dt
    ) VALUES (
:user_id, :type, :title, :message, :status, NOW(), NOW(),  :sent_to, :alert_class, :priority, :category, NOW(), :start_dt, :end_dt)";

    $stmt = $database->prepare($query);

    // Bind parameters to the query with defaults
    $stmt->execute([
        'user_id'     => $notification_data['user_id'],
        'type'        => $notification_data['type'] ?? 'email_notification',
        'title'       => $notification_data['title'] ?? '',
        'message'     => $notification_data['message'] ?? '',
        'status'      => $notification_data['status'] ?? 'notsent',
        'alert_class' => $notification_data['alert_class'] ?? null,
        'priority'    => $notification_data['priority'] ?? null,
        'category'    => $notification_data['category'] ?? null,
        'sent_to'    => $notification_data['sent_to'] ?? null,
        'start_dt'    => $start_dt,
        'end_dt'      => $end_dt
    ]);
    $insertednotificationId = $database->lastInsertId();


    // Log category to track counters
    if (!empty($notification_data['category'])) {
        $category = strtolower($notification_data['category']);
        if (isset($counters[$category])) {
            $counters[$category]++;
        } else {
            $counters[$category] = 1;  // Initialize counter if not set
        }
    }
    $counters['total_notifications_sent']++;

    $qik->logmessage("<p style='color: green;'>✅ Notification queued: " . htmlspecialchars($notification_data['title']) . " (User: " . $notification_data['user_id'] . ")</p>\n");
    $lastnotification_sent=1;
   
if ($config['testmail']) {
    if (strpos($notification_data['sent_to'], '@bdtest.xyz') === false) {        $notification_data['sent_to'] = $config['testemailaccount'];    }
}

if ($config['actualsend']) {
    // Format the 'to' details for addAddress
    $notify_user_data = $notification_data['notify_user_data'];
    $details['to'] = [$notification_data['sent_to'], $notify_user_data['first_name'] . ' ' . $notify_user_data['last_name']];
    $details['subject'] = $notification_data['title'];
    $details['body'] = $notification_data['message'];
$details['notificationid'] = $insertednotificationId;

    $result = $mail->sendmail($details);

    // Check if the 'mail_sent' field in $result is true
    if ($result['mail_sent'] === true) {
        $qik->logmessage("<p style='color: green;'>✉️ Email sent: " . htmlspecialchars($notification_data['title']) . " to " . htmlspecialchars($details['to'][0]) . " (User: " . $notification_data['user_id'] . ")</p>\n");
        // update the status = sent
        $query = "UPDATE bg_user_notifications SET `status` = 'sent', modify_dt=now() WHERE notification_id = :notification_id";
        $stmt = $database->prepare($query);
        $stmt->execute(['notification_id' => $insertednotificationId]);
        $counters['emails_sent']++;
    } else {
        $qik->logmessage("<p style='color: red;'>❌ Email failed: " . htmlspecialchars($notification_data['title']) . " (User: " . $notification_data['user_id'] . ")</p>\n");
    }
}

}

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function retry_unsent_notifications() {
    global $database, $mail, $qik, $counters, $config;

    $qik->logmessage("\n<h3>🔄 Retry Process: Unsent Notifications</h3>\n", 1);

    // Query for notifications with "notsent" status - including user_id=0 for system emails
    // Only process notifications where start_dt has passed (or is NULL)
    // Filter out notifications without valid email addresses
    $query = "SELECT n.*,
              CASE WHEN n.user_id = 0 THEN 'System' ELSE u.first_name END as first_name,
              CASE WHEN n.user_id = 0 THEN 'Email' ELSE u.last_name END as last_name,
              CASE WHEN n.user_id = 0 THEN n.sent_to ELSE u.email END as email
              FROM bg_user_notifications n
              LEFT JOIN bg_users u ON n.user_id = u.user_id
              WHERE n.status = 'notsent'
              AND (n.start_dt IS NULL OR n.start_dt <= NOW())
              AND (n.end_dt IS NULL OR n.end_dt >= NOW())
              AND n.create_dt > DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND (
                  (n.user_id = 0 AND n.sent_to IS NOT NULL AND n.sent_to != '')
                  OR (n.user_id != 0 AND u.email IS NOT NULL AND u.email != '')
              )
              ORDER BY n.priority DESC, n.create_dt ASC";
              
    $stmt = $database->prepare($query);
    $stmt->execute();
    $unsent_notifications = $stmt->fetchAll();

    $counters['retry_notifications_found'] = count($unsent_notifications);
    $qik->logmessage("<p>Found {$counters['retry_notifications_found']} unsent notifications to retry</p>\n", 1);
    
    $retry_success = 0;
    $retry_failure = 0;
    
    foreach ($unsent_notifications as $notification) {
        // Skip test accounts if configured to do so
        if ($config['ignoretestaccounts'] && strpos($notification['email'], '@bdtest.xyz') !== false) {
            $qik->logmessage("<p style='color: #999;'>⏭️ Skipped test account retry: " . $notification['user_id'] . " - " . htmlspecialchars($notification['title']) . "</p>\n", 1);
            continue;
        }
        
        // Test email redirection if configured
        $recipient_email = $notification['email'];
        if ($config['testmail']) {
            if (strpos($recipient_email, '@bdtest.xyz') === false) {
                $recipient_email = $config['testemailaccount'];
            }
        }
        
        if ($config['actualsend']) {
            // Check if this is a system email (user_id=0) with additional options
            if ($notification['user_id'] == 0 && !empty($notification['options'])) {
                $options = json_decode($notification['options'], true);

                // For system emails, try to get the actual email from options
                if (!empty($options['toemail']) && filter_var($options['toemail'], FILTER_VALIDATE_EMAIL)) {
                    $recipient_email = $options['toemail'];
                }

                // Use original email details if available
                if (!empty($options['original_to'])) {
                    $to_details = $options['original_to'];
                    // Ensure the first element is a valid email
                    if (!filter_var($to_details[0], FILTER_VALIDATE_EMAIL)) {
                        $to_details[0] = $recipient_email;
                    }
                } else {
                    $to_details = [$recipient_email, $notification['first_name'] . ' ' . $notification['last_name']];
                }

                // Use original from if available
                if (!empty($options['original_from'])) {
                    $from_details = $options['original_from'];
                }
            } else {
                $to_details = [$recipient_email, $notification['first_name'] . ' ' . $notification['last_name']];
            }

            // Format the 'to' details for addAddress
            $details = [
                'to' => $to_details,
                'subject' => $notification['title'],
                'body' => $notification['message'],
                'notificationid' => $notification['notification_id']
            ];

            // Add from if specified
            if (!empty($from_details)) {
                $details['from'] = $from_details;
            }
            
            // Log detailed info about what we're trying to send
            $qik->logmessage("<p style='color: #0066cc;'>🔄 Retry attempt: " . htmlspecialchars($notification['title']) . " to " . htmlspecialchars($recipient_email) . " (ID: " . $notification['notification_id'] . ")</p>\n", 1);
            
            // Check if recipient email is valid
            if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
                $error_detail = empty($recipient_email) ? "Email is empty/missing" : "Invalid email format";
                $qik->logmessage("<p style='color: red;'>❌ Retry error: Invalid email for notification ID " . $notification['notification_id'] . " - " . $error_detail . " (User: " . $notification['user_id'] . ")</p>\n", 1);
                $retry_failure++;
                continue;
            }
            
            $result = $mail->sendmail($details);

            // Check if the email was sent successfully
            if ($result['mail_sent'] === true) {
                $qik->logmessage("<p style='color: green;'>✅ Retry success: " . htmlspecialchars($notification['title']) . " sent to " . htmlspecialchars($details['to'][0]) . " (User: " . $notification['user_id'] . ")</p>\n");
                
                // Update the status to 'sent'
                $update_query = "UPDATE bg_user_notifications SET 
                                 `status` = 'sent', 
                                 modify_dt = NOW(),
                                 sent_dt = NOW()
                                 WHERE notification_id = :notification_id";
                                 
                $update_stmt = $database->prepare($update_query);
                $update_stmt->execute(['notification_id' => $notification['notification_id']]);
                
                $retry_success++;
                $counters['emails_sent']++;
            } else {
                // Extract error message if available
                $error_message = isset($result['error']) ? $result['error'] : 'No specific error returned';

                $qik->logmessage("<p style='color: red;'>❌ Retry failed: " . htmlspecialchars($notification['title']) . " (User: " . $notification['user_id'] . ") - Error: " . htmlspecialchars($error_message) . "</p>\n", 1);

                // Update the retry count and next retry time in options
                $options = !empty($notification['options']) ? json_decode($notification['options'], true) : [];
                $retry_count = isset($options['retry_count']) ? $options['retry_count'] + 1 : 1;
                $options['retry_count'] = $retry_count;
                $options['last_retry'] = date('Y-m-d H:i:s');

                // Calculate next retry time with exponential backoff
                // 5 mins, 15 mins, 1 hour, 4 hours, 12 hours, 24 hours, then stop
                $retry_delays = [5, 15, 60, 240, 720, 1440];
                $delay_index = min($retry_count - 1, count($retry_delays) - 1);
                $delay_minutes = $retry_delays[$delay_index];

                // Update with new retry time and increment count
                $update_query = "UPDATE bg_user_notifications SET
                                 modify_dt = NOW(),
                                 start_dt = DATE_ADD(NOW(), INTERVAL :delay MINUTE),
                                 options = :options
                                 WHERE notification_id = :notification_id";

                $update_stmt = $database->prepare($update_query);
                $update_stmt->execute([
                    'notification_id' => $notification['notification_id'],
                    'delay' => $delay_minutes,
                    'options' => json_encode($options)
                ]);

                $qik->logmessage("<p style='color: #666;'>⏰ Next retry scheduled in $delay_minutes minutes (attempt #$retry_count)</p>\n", 1);

                $retry_failure++;
            }
        }
    }

    $counters['retry_success'] = $retry_success;
    $counters['retry_failure'] = $retry_failure;

    $qik->logmessage("\n<h3>✅ Retry Process Complete</h3>\n", 1);
    $qik->logmessage("<p>Success: $retry_success | Failure: $retry_failure | Total: {$counters['retry_notifications_found']}</p>\n", 1);
    
    return [
        'success' => $retry_success,
        'failure' => $retry_failure,
        'total' => $counters['retry_notifications_found']
    ];
}



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function notification_last_sent($user_id, $type = 'email_notification', $category = '', $timeframe = '') {
    global $database;
    $query = "SELECT count(*) as cnt FROM bg_user_notifications WHERE user_id = :user_id AND `type` = :type ";
    #$query .= " AND `status` != 'read'";
    if ($category != '') $query .= " AND `category` = :category";
    if ($timeframe != '') {
        if (preg_match('/(\d+)([dwmy])/', $timeframe, $matches)) {
            $interval = $matches[1]; $unit = $matches[2];
            $unit = ($unit == 'd') ? 'DAY' : (($unit == 'w') ? 'WEEK' : (($unit == 'm') ? 'MONTH' : 'YEAR'));
            $query .= " AND date(create_dt) >= DATE_SUB(CURDATE(), INTERVAL :interval $unit)";
        }
    } else $query .= " AND date(create_dt) = CURDATE()";
    
    $stmt = $database->prepare($query);
    $params = ['user_id' => $user_id, 'type' => $type];
    if ($category != '') $params['category'] = $category;
    if ($timeframe != '') $params['interval'] = $matches[1];
    
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
}





//*************************************************************************************************************************
//*************************************************************************************************************************
$debug=true;

echo "<h1>📧 Notification Processor - " . date('Y-m-d H:i:s') . "</h1>\n";

$process_shards = true;  // false will process all users, true will process only users with user_id % 10 == current_hour
$counters['process_shards'] = $process_shards ? 'Y' : 'N';

$today = new DateTime();

if ($config['testmail']) {
    $website_full_url = $website['fullurl'];
} else {
    $website_full_url = 'https://birthday.gold'; // No trailing slash to avoid double slashes in URLs
}

if ($process_shards !== false) {
    $currentHour = date('g') % 10;  // Get the last digit of the 12-hour clock (1-12)
    $counters['hour_userid_segment'] = $currentHour;

    echo "<p>Processing shard segment: User IDs ending in <strong>$currentHour</strong></p>\n";
    $query = "SELECT * FROM bg_users WHERE `status` !='deleted' AND MOD(user_id, 10) = :currentHour";
    $stmt = $database->prepare($query);
    $stmt->execute(['currentHour' => $currentHour]);
} else {
    echo "<p>Processing all active users (shard processing disabled)</p>\n";
    $query = "SELECT * FROM bg_users WHERE `status` !='deleted'";
    $stmt = $database->prepare($query);
    $stmt->execute();
}

$notify_users = $stmt->fetchAll();
$counters['total_users'] = count($notify_users);

echo "<p>Found <strong>" . count($notify_users) . "</strong> users to process</p>\n";
echo "\n<h2>📬 Processing User Notifications</h2>\n"; 
$debug=true;
foreach ($notify_users as $notify_user_data) {

// setup general user related information for the notifcation process
    $to_email = $notify_user_data['email'];
    $till = $app->getTimeTilBirthday($notify_user_data['birthdate']);

    
$timesince=$app->getTimeSinceDate($notify_user_data['create_dt']);



    $lastnotification_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification');

    // loop through the possible notifications to send
    #echo 'Last notification sent: ' . print_r( $lastnotification_sent,1) . '<br>';

    if ($till['days'] == 0) include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/birthday-today-yea.inc');

    if ($notify_user_data['status'] == 'pending')   include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/account-pending.inc');
    if ($notify_user_data['status'] == 'validated')  include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/account-unpaid.inc');
    
    if ($notify_user_data['status'] == 'active')  {
   
    include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/profile_incomplete.inc');

    if ($timesince['days'] >=7 and $timesince['days'] < 13 )  include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/no_enrollments-week.inc');
    if ($timesince['days'] >=28 and $timesince['days'] < 59 )  include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/no_enrollments-month.inc');

    if ($till['months'] == 1 && $till['days'] >= 28) include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/birthday-nextmonth.inc');
    if ($till['weeks'] == 1 && $till['days']<=9) include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/birthday-nextweek.inc');
    if ($till['days'] == 1) include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/birthday-tomorrow.inc');
    
    include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/bad_profile_withpendingenrollments.inc');

    
    include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/enrollment-first-success.inc');


    include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/user_status_checks/enrollment-high-failurerate.inc');
}
}

$debug=true;

echo "\n<h2>🔄 Retry Process</h2>\n";
$retry_results = retry_unsent_notifications();
$counters['retry_results'] = $retry_results;

echo "\n<h2>📊 Process Summary</h2>\n";
echo "<ul>\n";
echo "<li><strong>Total Users Processed:</strong> {$counters['total_users']}</li>\n";
echo "<li><strong>Total Notifications Queued:</strong> {$counters['total_notifications_sent']}</li>\n";
echo "<li><strong>Emails Sent:</strong> {$counters['emails_sent']}</li>\n";
echo "<li><strong>Retry Notifications Found:</strong> " . ($counters['retry_notifications_found'] ?? 0) . "</li>\n";
echo "<li><strong>Retry Success:</strong> " . ($counters['retry_success'] ?? 0) . "</li>\n";
echo "<li><strong>Retry Failure:</strong> " . ($counters['retry_failure'] ?? 0) . "</li>\n";
echo "</ul>\n";

// Category breakdown
if ($counters['total_notifications_sent'] > 0) {
    echo "\n<h3>Notifications by Category</h3>\n";
    echo "<ul>\n";
    if (!empty($counters['email_notifications'])) echo "<li>Email Notifications: {$counters['email_notifications']}</li>\n";
    if (!empty($counters['profile_incomplete'])) echo "<li>Profile Incomplete: {$counters['profile_incomplete']}</li>\n";
    if (!empty($counters['no_enrollments_week'])) echo "<li>No Enrollments (Week): {$counters['no_enrollments_week']}</li>\n";
    if (!empty($counters['no_enrollments_month'])) echo "<li>No Enrollments (Month): {$counters['no_enrollments_month']}</li>\n";
    if (!empty($counters['birthday_notifications'])) echo "<li>Birthday Notifications: {$counters['birthday_notifications']}</li>\n";
    echo "</ul>\n";
}

echo "\n<p><strong>✅ Notification processing complete - " . date('Y-m-d H:i:s') . "</strong></p>\n";

session_tracking('notification_process_counts', $counters);
session_tracking('notification_process_log', $qik->logmessage('!FINALIZE!'));
