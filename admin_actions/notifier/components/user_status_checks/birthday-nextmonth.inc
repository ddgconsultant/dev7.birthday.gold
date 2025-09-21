<?php


$category = 'birthday_next_month';
$notification_interval='330d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);


// Only proceed if no notification was sent within the day and none of this category in the last 30 days
if ($lastnotification_category_sent || $lastnotification_sent) {
    // A notification has already been sent today or within 30 days for this category, skip this one
    return;
}



// Define the email subject, message, and category
$subject = 'Your Birthday is Next Month!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
         . "We just wanted to give you a heads-up that your birthday is coming up next month!\n\n"
         . "Don't forget to check to see if you have any upcoming birthday rewards.\n"
         . "You might have just enough time to get enrolled in some last minute rewards to get even more birthday freebies!\n\n"
         . "Cheers,\nThe Birthday.Gold Team";
         $message=str_replace("\n",'<br>',$message);


include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_nameduser.inc');
$fullmessage=str_replace($search,$replace,$output);
unset($buttonlink);
// Log the notification
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
    'end_dt'      => $notification_interval          // End date, 5 days later
]);
