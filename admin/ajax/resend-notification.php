<?php
/**
 * AJAX handler for resending notifications
 * Resends email notifications to users
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check admin access
if (!$account->isadmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!$app->formposted()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Get parameters
$notification_id = intval($_POST['notification_id'] ?? 0);

// Validate
if ($notification_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification ID']);
    exit;
}

try {
    // Get the notification details
    $sql = "SELECT * FROM bg_user_notifications WHERE notification_id = :notification_id";
    $stmt = $database->prepare($sql);
    $stmt->execute([':notification_id' => $notification_id]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notification) {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
        exit;
    }

    // Get user data
    $user_data = $account->getuserdata($notification['user_id'], 'user_id');
    if (!$user_data) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Check if notification can be resent (must be email or both)
    if (!in_array($notification['sent_to'], ['email', 'both'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Only email notifications can be resent']);
        exit;
    }

    // Prepare email data
    $email_data = [
        'to' => $user_data['email'],
        'to_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
        'subject' => $notification['title'],
        'message' => $notification['message'],
        'type' => $notification['type'] ?? 'notification'
    ];

    // Parse options if available
    $options = [];
    if (!empty($notification['options'])) {
        $options = json_decode($notification['options'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $options = [];
        }
    }

    // Try to send the email
    $mailsent = false;

    // Create a simple email body if the message is plain text
    $email_body = $notification['message'];

    // If there's HTML in options, use that
    if (isset($options['html_body'])) {
        $email_body = $options['html_body'];
    }

    // Send via mail class
    $mail_data = [
        'to' => $user_data['email'],
        'to_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
        'from' => $sitesettings['config']['MAIL_USERNAME'],
        'from_name' => 'Birthday Gold',
        'subject' => $notification['title'],
        'body' => $email_body,
        'ishtml' => true
    ];

    try {
        $result = $mail->sendmail($mail_data);

        if ($result) {
            $mailsent = true;

            // Update notification record
            $update_sql = "UPDATE bg_user_notifications
                          SET status = 'sent',
                              sent_dt = NOW(),
                              modify_dt = NOW()
                          WHERE notification_id = :notification_id";
            $update_stmt = $database->prepare($update_sql);
            $update_stmt->execute([':notification_id' => $notification_id]);

            // Log the resend
            session_tracking('admin_notification_resend_success', [
                'notification_id' => $notification_id,
                'user_id' => $notification['user_id'],
                'type' => $notification['type'],
                'admin_user_id' => $current_user_data['user_id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Notification resent successfully to ' . $user_data['email']
            ]);
        } else {
            throw new Exception('Mail sending failed');
        }
    } catch (Exception $e) {
        // Update notification to failed
        $update_sql = "UPDATE bg_user_notifications
                      SET status = 'failed',
                          modify_dt = NOW()
                      WHERE notification_id = :notification_id";
        $update_stmt = $database->prepare($update_sql);
        $update_stmt->execute([':notification_id' => $notification_id]);

        // Log the failure
        session_tracking('admin_notification_resend_failed', [
            'notification_id' => $notification_id,
            'user_id' => $notification['user_id'],
            'error' => $e->getMessage(),
            'admin_user_id' => $current_user_data['user_id']
        ]);

        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to send notification: ' . $e->getMessage()
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in resend-notification.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
