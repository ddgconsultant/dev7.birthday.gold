<?php

// Check if the last notification of this category was sent within the last 30 days
$category = 'zero_enrollments_after_one_week';
$notification_interval='2y';
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

// users with zero enrollments after one week
$subject = 'Your Birthday Rewards Are Waiting! Don’t Miss Out!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
         . "It's been more than a week since you joined Birthday.Gold, and we're thrilled to have you as part of our community!\n\n"
         . "We noticed that you haven't enrolled in any brand reward programs yet, and we don't want you to miss out.\n\n"
         . "There are fantastic birthday rewards waiting just for you! 🎉\n\n"
         . "Log in to your account and explore hundreds of ".$website['biznames']." offering exclusive rewards.\n\n"
         . "Enroll in your favorite programs now and make your upcoming birthday celebration extra special!\n\n"
         . "Best regards,\n"
         . "Your friends at Birthday.Gold 🎂";

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
