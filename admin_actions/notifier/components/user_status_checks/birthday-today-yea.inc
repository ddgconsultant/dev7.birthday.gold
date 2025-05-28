<?php

$category = 'birthday_today';
$notification_interval='360d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);


// Only proceed if no notification was sent within the day and none of this category in the last 30 days
if ($lastnotification_category_sent || $lastnotification_sent) {
    // A notification has already been sent today or within 30 days for this category, skip this one
    return;
}



// Define the email subject, message, and category
$subject = 'Happy Birthday!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
         . "Happy Birthday! We hope you have a wonderful day filled with joy and rewards. Don't forget to check out your special birthday rewards from us.\n\n"
         . "Cheers,\nThe Birthday.Gold Team";
         $message=str_replace("\n",'<br>',$message);

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
    'alert_class' => '',  
    'sent_to'     => $to_email,
    'priority'    => 'normal',
    'start_dt'    => 'NOW()',  
    'end_dt'      => $notification_interval
]);
