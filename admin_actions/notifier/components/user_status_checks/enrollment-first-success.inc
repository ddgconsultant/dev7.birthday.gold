<?php

// Check if the last notification of this category was sent within the last 60 days
$category = 'first_enrollment_success';
$notification_interval = '60d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);

// Only proceed if no notification was sent today or within 60 days for this category
if ($lastnotification_category_sent || $lastnotification_sent) {
    // A notification has already been sent today or within 60 days for this category, skip this one
    return;
}

// Query to check for successful enrollments
/*$query = "SELECT COUNT(*) as enrollment_count, GROUP_CONCAT(company_name) as companies 
          FROM bg_user_companies 
          WHERE user_id = :user_id          
          AND status IN ('success', 'success-btn')
          AND registration_dt >= DATE_SUB(NOW(), INTERVAL 2 DAY)";
          */
$query="SELECT COUNT(*) as enrollment_count, 
       GROUP_CONCAT(c.company_name) as companies 
FROM bg_user_enrollments ue
JOIN bg_companies c ON c.company_id = ue.company_id
WHERE ue.user_id = :user_id          
AND ue.status IN ('success', 'success-btn')
AND ue.registration_dt >= DATE_SUB(NOW(), INTERVAL 2 DAY)";

$stmt = $database->prepare($query);
$stmt->execute(['user_id' => $notify_user_data['user_id']]);
$enrollment_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($enrollment_data['enrollment_count'] == 0) {
    // No successful enrollments yet, skip notification
    return;
}

// Format company names for message
$companies = explode(',', $enrollment_data['companies']);
$company_text = count($companies) > 1 
    ? implode(', ', array_slice($companies, 0, -1)) . ' and ' . end($companies)
    : $companies[0];

    $subject = $enrollment_data['enrollment_count'] > 1 
    ? '🎉 Woohoo! You\'re Now Part of ' . $enrollment_data['enrollment_count'] . ' Birthday Rewards Programs!' 
    : '🎉 Woohoo! You\'re Now Part of a Birthday Rewards Program!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
         . "GREAT NEWS! 🎈 You're now officially enrolled in the rewards program at $company_text!\n\n"
         . "This means you're all set to receive some amazing birthday perks. Make sure to mark your calendar "
         . "and get ready for some fantastic birthday celebrations!\n\n"
         . "Here's what's next:\n"
         . "• Keep an eye on your email as your birthday approaches\n"
         . "• Your rewards will typically arrive close to your special day\n"
         . "• Some brands might send you a surprise even earlier!\n\n"
         . "Want even more birthday rewards? There are hundreds of other ".$website['biznames']." waiting to celebrate with you!\n\n"
         . "Log in to your Birthday.Gold account to discover and enroll in more reward programs.\n\n"
         . "Happy collecting!\n"
         . "The Birthday.Gold Team";

$message = str_replace("\n", '<br>', $message);

include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_nameduser.inc');
$fullmessage = str_replace($search, $replace, $output);
unset($buttonlink);

// Log the notification
log_notification([
    'notify_user_data' => $notify_user_data,
    'user_id'     => $notify_user_data['user_id'],
    'type'        => 'email_notification',
    'title'       => $subject,
    'message'     => $fullmessage,
    'status'      => 'notsent',
    'category'    => $category,
    'alert_class' => 'success',
    'sent_to'     => $to_email,
    'priority'    => 'high',
    'start_dt'    => 'NOW()',
    'end_dt'      => $notification_interval
]);