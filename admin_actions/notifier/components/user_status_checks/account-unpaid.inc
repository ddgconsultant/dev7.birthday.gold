<?php

// Check if the last notification of this category was sent within the last 11 days
$category = 'checkout_incomplete_after_validation';
$notification_interval = '18d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);

// Only proceed if no notification was sent within the defined interval for this category
if ($lastnotification_category_sent) {
    // A notification has already been sent within the last 11 days for this category, skip this one
    return;
}

// Check if the user has validated their account but hasn't completed the checkout process
    
    // Calculate how long it's been since the user signed up
    $timesince = $app->getTimeSinceDate($notify_user_data['create_dt']);
    
    if ($timesince['days'] < 6) {
        // Skip if the user account was created in the last 6 days
        return;
    }

    // Define the email subject, message, and category for checkout step reminder
    $subject = 'Complete Your Birthday.Gold Signup and Checkout!';
    $message = "Hi {$notify_user_data['first_name']},\n\n"
             . "We noticed that you've already validated your Birthday.Gold account, but you haven't completed the checkout process yet.\n\n"
             . "Don't miss out on the birthday rewards awaiting you! Finish your checkout today and start enjoying exclusive birthday perks from our partner ".$website['biznames'].".\n\n"
             . "It only takes a minute, and you'll be all set to collect birthday rewards from hundreds of ".$website['biznames'].".\n\n"
             . "Complete Your Signup Now!\n\n"
             . "If you need any help or have questions, feel free to reach out to us.\n\n"
             . "Cheers,\nThe Birthday.Gold Team";
    $message = str_replace("\n", '<br>', $message);


    
    $link = $website_full_url.'/checkout?u='.$qik->encodeId($notify_user_data['user_id']);
    $buttonlink = $mail->emailbutton('Complete Your Birthday.Gold Signup', $link);

    // Include the template and build the full message
    include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_accountstart.inc');
    $fullmessage = str_replace($search, $replace, $output);
    unset($buttonlink);

    // Log the notification for records
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
        'end_dt'      => $notification_interval // End date set based on notification interval
    ]);
