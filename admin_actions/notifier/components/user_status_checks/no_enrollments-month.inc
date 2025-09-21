<?php

// Check if the last notification of this category was sent within the last 30 days
$category = 'zero_enrollments_after_one_month';
$notification_interval='30d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);


// Only proceed if no notification was sent within the day and none of this category in the last 30 days
if ($lastnotification_category_sent || $lastnotification_sent) {
    // A notification has already been sent today or within 30 days for this category, skip this one
    return;
}

$businessoutput = $account->getbusinesslist($notify_user_data, 'any', 0);
if ($businessoutput['counts']['total'] > 0) {
    // User has enrolled in at least one brand, skip this notification
    return;
}



// users with zero enrollments after one month
$subject = 'Don\'t Miss Out on Amazing Rewards!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
         . "It's been more than a month since you joined Birthday.Gold, and we're excited to have you on board!\n\n"
         . "However, we noticed that you haven't enrolled in any brand reward programs yet.\n\n"
         . "Don't miss out on the incredible birthday rewards waiting for you! 🎉\n\n"
         . "Log in to your account and start exploring hundreds of ".$website['biznames']." offering exclusive rewards.\n\n"
         . "Come back and select some reward programs to be enrolled in and make your next birthday celebration unforgettable!\n\n"
         . "Cheers,\nThe Birthday.Gold Team";
$message = str_replace("\n", '<br>', $message);


include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_nameduser.inc');
$fullmessage = str_replace($search, $replace, $output);
unset($buttonlink);
// Log the first notification
log_notification([
    'notify_user_data' => $notify_user_data,
    'user_id'     => $notify_user_data['user_id'],
    'type'        => 'email_notification', // This can be customized
    'title'       => $subject,
    'message'     => $fullmessage,
    'status'      => 'notsent', 
    'category'    => $category,
    'alert_class' => '',  
    'sent_to'     => $to_email,
    'priority'    => 'normal',      // Priority can be set here
    'start_dt'    => 'NOW()',       // Start date set to now
    'end_dt'      => $notification_interval           // End date, 30 days later
]);
