<?PHP
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
echo '<html>';
// Initialize counters based on expected notification categories

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
        $qik->logmessage( "<pre style='color:#ddd'>Ignoring test account not sending email - [" . $notification_data['title'] . '] for: ' . $notification_data['user_id'].'</pre>', 1  );
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

    $qik->logmessage( "Notification logged successfully! - [" . $notification_data['title'] . '] for: ' . $notification_data['user_id'] );
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
       $qik->logmessage( "Email sent successfully! - " . $notification_data['title'] . ' for '.(implode('/',$details['to'])).' / ' . $notification_data['user_id'] );
        // update the status = sent
        $query = "UPDATE bg_user_notifications SET `status` = 'sent', modify_dt=now() WHERE notification_id = :notification_id";
        $stmt = $database->prepare($query);
        $stmt->execute(['notification_id' => $insertednotificationId]);
        $counters['emails_sent']++;
    } else {
        $qik->logmessage( "Email sending failed! - " . $notification_data['title'] . ' for ' . $notification_data['user_id']  );
    }
}

}

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
function retry_unsent_notifications() {
    global $database, $mail, $qik, $counters, $config;
    
    $qik->logmessage('<h3>Starting retry of unsent notifications...</h3>', 1);
    
    // Query for notifications with "notsent" status
    $query = "SELECT n.*, u.first_name, u.last_name, u.email 
              FROM bg_user_notifications n
              JOIN bg_users u ON n.user_id = u.user_id
              WHERE n.status = 'notsent' 
              AND n.create_dt > DATE_SUB(NOW(), INTERVAL 7 DAY)
              ORDER BY n.create_dt ASC";
              
    $stmt = $database->prepare($query);
    $stmt->execute();
    $unsent_notifications = $stmt->fetchAll();
    
    $counters['retry_notifications_found'] = count($unsent_notifications);
    $qik->logmessage("Found {$counters['retry_notifications_found']} unsent notifications to retry", 1);
    
    $retry_success = 0;
    $retry_failure = 0;
    
    foreach ($unsent_notifications as $notification) {
        // Skip test accounts if configured to do so
        if ($config['ignoretestaccounts'] && strpos($notification['email'], '@bdtest.xyz') !== false) {
            $qik->logmessage("<pre style='color:#ddd'>Ignoring test account not sending email - [" . 
                $notification['title'] . '] for: ' . $notification['user_id'].'</pre>', 1);
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
            // Format the 'to' details for addAddress
            $details = [
                'to' => [$recipient_email, $notification['first_name'] . ' ' . $notification['last_name']],
                'subject' => $notification['title'],
                'body' => $notification['message'],
                'notificationid' => $notification['notification_id']
            ];
            
            // Log detailed info about what we're trying to send
            $qik->logmessage("RETRY ATTEMPT: Sending email with the following details:", 1);
            $qik->logmessage("- To: " . $recipient_email . " (" . $notification['first_name'] . ' ' . $notification['last_name'] . ")", 1);
            $qik->logmessage("- Subject: " . $notification['title'], 1);
            $qik->logmessage("- Notification ID: " . $notification['notification_id'], 1);
            
            // Check if recipient email is valid
            if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
                $qik->logmessage("RETRY ERROR: Invalid email address format: " . $recipient_email, 1);
                $retry_failure++;
                continue;
            }
            
            $result = $mail->sendmail($details);
            
            // Detailed logging of the result
            $qik->logmessage("RETRY RESULT: " . print_r($result, true), 1);
            
            // Check if the email was sent successfully
            if ($result['mail_sent'] === true) {
                $qik->logmessage("RETRY: Email sent successfully! - " . 
                    $notification['title'] . ' for ' . (implode('/', $details['to'])) . 
                    ' / ' . $notification['user_id']);
                
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
                
                $qik->logmessage("RETRY: Email sending failed! - " . 
                    $notification['title'] . ' for ' . $notification['user_id'] . 
                    " - Error: " . $error_message, 1);
                
                // Just update the modification date to show we tried
                $update_query = "UPDATE bg_user_notifications SET 
                                 modify_dt = NOW()
                                 WHERE notification_id = :notification_id";
                                 
                $update_stmt = $database->prepare($update_query);
                $update_stmt->execute(['notification_id' => $notification['notification_id']]);
                
                $retry_failure++;
            }
        }
    }
    
    $counters['retry_success'] = $retry_success;
    $counters['retry_failure'] = $retry_failure;
    
    $qik->logmessage("<h3>Completed retry of unsent notifications. Success: $retry_success, Failure: $retry_failure</h3>", 1);
    
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
$qik->logmessage( '<H1>start - ' . date('Y-m-d H:i:s') . '<h1><br>',1);
$process_shards = true;  // false will process all users, true will process only users with user_id % 10 == current_hour
$counters['process_shards'] = $process_shards ? 'Y' : 'N';

$today = new DateTime();

if ($config['testmail']) {
    $website_full_url = $website['fullurl'];
} else {
    $website_full_url = 'https://birthday.gold/';
}

if ($process_shards !== false) {
    $currentHour = date('g') % 10;  // Get the last digit of the 12-hour clock (1-12)
    $counters['hour_userid_segment'] = $currentHour; 

    #  $currentHour=0;
    $qik->logmessage(  'Current Hour: ' . $currentHour );
    $query = "SELECT * FROM bg_users WHERE `status` !='deleted' AND MOD(user_id, 10) = :currentHour";
    $stmt = $database->prepare($query);
    $stmt->execute(['currentHour' => $currentHour]);
} else {
    $query = "SELECT * FROM bg_users WHERE `status` !='deleted'";
    $stmt = $database->prepare($query);
    $stmt->execute();
}


$notify_users = $stmt->fetchAll();
$qik->logmessage( 'Users to notify: ' . count($notify_users) );
$counters['total_users'] = count($notify_users); 
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

$qik->logmessage('<h2>Starting retry process for failed notifications</h2>', 1);
$retry_results = retry_unsent_notifications();
$counters['retry_results'] = $retry_results;


$qik->logmessage( '<H1>done - ' . date('Y-m-d H:i:s') . '<h1><br>',1);
session_tracking('notification_process_counts', $counters );
session_tracking('notification_process_log', $qik->logmessage('!FINALIZE!') );
