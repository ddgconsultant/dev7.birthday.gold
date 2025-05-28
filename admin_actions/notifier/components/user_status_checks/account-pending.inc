<?php

// Check if the last notification of this category was sent within the last 30 days
$category = 'validation_incomplete_after_signup';
$notification_interval='11d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);


// Only proceed if no notification was sent within the day and none of this category in the last 30 days
if ($lastnotification_category_sent || $lastnotification_sent) {
    // A notification has already been sent today or within 30 days for this category, skip this one
    return;
}


$timesince=$app->getTimeSinceDate($notify_user_data['create_dt']);
if ($timesince['days'] < 3) {
    // the user has been active for less than 3 days or has completed their profile, skip this notification
    return;
}


// users who have not completed their profile after one week
// Define the email subject, message, and category for validation step reminder
$subject = 'Validate Your Account and Start Collecting Birthday Rewards!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
         . "We noticed that you haven't completed the validation step for your Birthday.Gold account.\n\n"
         . "Don't miss out on your birthday reward freebies! Validate your account today and start enrolling in exciting brand reward programs.\n\n"
         . "It only takes a minute, and you'll be one step closer to enjoying birthday perks from hundreds of ".$website['biznames'].".\n\n"
         . "Validate Your Account Now!\n\n"
         . "If you need any help or have questions, feel free to reach out to us.\n\n"
         . "Cheers,\nThe Birthday.Gold Team";
$message = str_replace("\n", '<br>', $message);

$validatedata['rawdata'] = $to_email;
$validatedata['user_id'] = $notify_user_data['user_id'];
$validatedata['sendcount'] = 0;
$validationcodes = $app->getvalidationcodes($validatedata);

$link = $website_full_url.'/validate-account?t='.$validationcodes['long'];

$buttonlink=$mail->emailbutton('Validate Birthday.Gold Account', $link);

include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_accountstart.inc');
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
    'end_dt'      => $notification_interval // End date set to 30 days later
]);
