<?php

// Check if the last notification of this category was sent within the last 30 days
$category = 'incomplete_profile_after_one_week';
$notification_interval='45d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);


// Only proceed if no notification was sent within the day and none of this category in the last 30 days
if ($lastnotification_category_sent || $lastnotification_sent) {
    // A notification has already been sent today or within 30 days for this category, skip this one
    return;
}



$timesince=$app->getTimeSinceDate($notify_user_data['create_dt']);
$profilecompletion = $account->profilecompletionratio($notify_user_data);
if ($timesince['days'] < 3 || $profilecompletion['required_percentage'] >= 75) {
    // the user has been active for less than 3 days or has completed their profile, skip this notification
    return;
}



// users who have not completed their profile after one week
$subject = 'Complete Your Profile to Start Collecting Rewards!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
         . "We’re thrilled to have you on Birthday.Gold, but it looks like your enrollment profile is still incomplete.\n\n"
         . "To unlock all the amazing birthday rewards we have waiting for you, simply finish setting up your profile and start enrolling in your favorite brand reward programs.\n\n"
         . "It only takes a minute and you’ll be one step closer to enjoying birthday perks from hundreds of ".$website['biznames']."!\n\n"
         . "Complete Your Profile Now!\n\n"
         . "If you need any help, feel free to reach out to us.\n\n"
         . "Best,\nThe Birthday.Gold Team";
$message = str_replace("\n", '<br>', $message);

include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_nameduser.inc');
$fullmessage = str_replace($search, $replace, $output);
unset($buttonlink);
// Log the second notification
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
    'end_dt'      => $notification_interval          // End date, 30 days later
]);