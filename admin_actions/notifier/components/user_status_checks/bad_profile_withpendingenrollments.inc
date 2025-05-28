<?php

// Notification category and interval setup
$category = 'incomplete_profile_with_pending_enrollments';
$notification_interval = '15d';

// Check if a notification of this category was sent within the last notification interval (e.g., 45 days)
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);

// Only proceed if no notification was sent within the interval
if ($lastnotification_category_sent || $lastnotification_sent) {
// A notification has already been sent within the time frame, skip this one
return;
}

// Calculate the time since the user profile was created and their profile completion percentage
$timesince = $app->getTimeSinceDate($notify_user_data['create_dt']);
$profilecompletion = $account->profilecompletionratio($notify_user_data);


// Get enrollment count using the `getbusinesslist` function
$businessoutput = $account->getbusinesslist($notify_user_data, 'any', 0);
$enrollment_count = $businessoutput['counts']['total'];

// If the user has enrolled in at least one brand, skip this notification
if ($enrollment_count == 0) {
return;
}

// If the user has been active for less than 3 days or their profile is 75% or more complete, skip the notification
if ($timesince['days'] < 3 || count($profilecompletion['required_fields_notcompleted']) == 0) {
return;
}

// Construct the message based on enrollment count (one vs. many)
if ($enrollment_count === 1) {
    $message_body = "You’ve selected an amazing brand reward, but it looks like your profile isn’t complete yet.";
} else {
    $message_body = "You’ve selected some amazing brand rewards, but it looks like your profile isn’t complete yet.";
}

// Construct the full message
$subject = 'Complete Your Profile to Start Collecting Rewards!';
$message = "Hi {$notify_user_data['first_name']},\n\n"
            . "$message_body\n\n"
            . "To finish enrolling in your chosen reward programs and start collecting birthday perks, please complete your profile.\n\n"
            . "It only takes a few moments to provide the necessary information and unlock the full Birthday.Gold experience.\n\n"
            . "Complete Your Profile Now!\n\n"
            . "If you need any help, feel free to reach out to us.\n\n"
            . "Best,\nThe Birthday.Gold Team";

// Replace newlines with <br> for HTML email formatting
$message = str_replace("\n", '<br>', $message);

$link = $website_full_url.'/myaccount/profile';
$buttonlink = $mail->emailbutton('Complete Your Birthday.Gold Profile', $link);

// Include the email template and replace placeholders with the actual message
include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_nameduser.inc');
$fullmessage = str_replace($search, $replace, $output);
unset($buttonlink);


// Log the notification (but mark as 'notsent' for now)
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
