<?php

// Check if the last notification of this category was sent within the last 30 days
$category = 'high_enrollment_failure_rate';
$notification_interval = '30d';
$lastnotification_category_sent = notification_last_sent($notify_user_data['user_id'], 'email_notification', $category, $notification_interval);

// Only proceed if no notification was sent within the day and none of this category in the last 30 days
if ($lastnotification_category_sent || $lastnotification_sent) {
    // A notification has already been sent today or within 30 days for this category, skip this one
    return;
}

// Query to get enrollment statistics
$query = "SELECT 
            COUNT(*) as total_enrollments,
            SUM(CASE WHEN status IN ('failed', 'failed-btn') THEN 1 ELSE 0 END) as failed_enrollments
          FROM bg_user_enrollments
          WHERE user_id = :user_id";

$stmt = $database->prepare($query);
$stmt->execute(['user_id' => $notify_user_data['user_id']]);
$enrollment_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate failure rate if there are any enrollments
if ($enrollment_stats['total_enrollments'] > 0) {
    $failure_rate = ($enrollment_stats['failed_enrollments'] / $enrollment_stats['total_enrollments']) * 100;
    
    // Only proceed if failure rate is 40% or higher and there are at least 3 total enrollments
    if ($failure_rate >= 40 && $enrollment_stats['total_enrollments'] >= 3) {
        $subject = 'Important: We Notice Some Issues With Your Reward Program Enrollments';
        $message = "Hi {$notify_user_data['first_name']},\n\n"
                . "We've noticed that some of your recent reward program enrollment attempts haven't been successful. "
                . "Out of your {$enrollment_stats['total_enrollments']} enrollment attempts, {$enrollment_stats['failed_enrollments']} weren't able to complete successfully.\n\n"
                . "This often happens when:\n"
                . "• Your profile information doesn't match the requirements of certain businesses\n"
                . "• The information format isn't what the business expects\n"
                . "• Required fields might be missing or incomplete\n\n"
                . "Good news - this is usually easy to fix! Here's what you can do:\n"
                . "1. Review your enrollment history at {$website_full_url}myaccount/enrollment-history\n"
                . "2. Check your profile information to ensure it's complete and accurate\n"
                . "3. Look for any specific error messages in your failed enrollments\n\n"
                . "Want help? Just reply to this email and our support team will be happy to assist you!\n\n"
                . "Once your profile is updated, our system will automatically retry your enrollments again and then you can start collecting those birthday rewards.\n\n"
                . "Best regards,\n"
                . "The Birthday.Gold Team";

        $message = str_replace("\n", '<br>', $message);

        $buttonlink = $website_full_url . 'myaccount/enrollment-history';
        $buttontext = 'Review Enrollment History';

        include($_SERVER['DOCUMENT_ROOT'] . '/admin_actions/notifier/components/email-template_nameduser.inc');
        $fullmessage = str_replace($search, $replace, $output);

        // Log the notification
        log_notification([
            'notify_user_data' => $notify_user_data,
            'user_id'     => $notify_user_data['user_id'],
            'type'        => 'email_notification',
            'title'       => $subject,
            'message'     => $fullmessage,
            'status'      => 'notsent',
            'category'    => $category,
            'alert_class' => 'warning',
            'sent_to'     => $to_email,
            'priority'    => 'high',
            'start_dt'    => 'NOW()',
            'end_dt'      => $notification_interval
        ]);
    }
}