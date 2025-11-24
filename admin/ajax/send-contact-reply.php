<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only access
if (!$account->isadmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data
$contact_event_id = $_POST['contact_event_id'] ?? 0;
$reply_message = trim($_POST['reply_message'] ?? '');
$sent_via = $_POST['sent_via'] ?? 'internal';

if (empty($contact_event_id) || empty($reply_message)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Get the original contact message
$contact_sql = "SELECT
    id,
    user_id,
    tracking_data
FROM bg_sessiontracking
WHERE id = :id";
$contact = $database->getrow($contact_sql, ['id' => $contact_event_id]);

if (!$contact) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Contact message not found']);
    exit;
}

// Parse tracking data to get email and name
$tracking_data = (is_string($contact['tracking_data'] ?? null) && $contact['tracking_data'] !== '')
    ? json_decode($contact['tracking_data'], true)
    : [];
$tracking_data = is_array($tracking_data) ? $tracking_data : [];

$recipient_email = $tracking_data['email'] ?? null;
$recipient_name = $tracking_data['name'] ?? 'there';
$recipient_user_id = $contact['user_id'] ?? null;

// Prepare metadata
$metadata = [
    'original_subject' => $tracking_data['subject'] ?? '',
    'original_message_preview' => $tracking_data['message_preview'] ?? '',
    'sent_from' => 'admin_panel',
    'sent_by_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
];

// Insert reply record
$insert_sql = "INSERT INTO bg_contact_replies (
    contact_event_id,
    admin_user_id,
    admin_username,
    reply_message,
    recipient_email,
    recipient_user_id,
    status,
    sent_via,
    metadata
) VALUES (
    :contact_event_id,
    :admin_user_id,
    :admin_username,
    :reply_message,
    :recipient_email,
    :recipient_user_id,
    :status,
    :sent_via,
    :metadata
)";

$insert_params = [
    'contact_event_id' => $contact_event_id,
    'admin_user_id' => $account->id(),
    'admin_username' => $account->username(),
    'reply_message' => $reply_message,
    'recipient_email' => $recipient_email,
    'recipient_user_id' => $recipient_user_id,
    'status' => 'draft',
    'sent_via' => $sent_via,
    'metadata' => json_encode($metadata)
];

try {
    $database->query($insert_sql, $insert_params);
    $reply_id = $database->lastInsertId();

    // Now actually send the reply based on sent_via
    $email_sent = false;
    $notification_sent = false;
    $notification_id = null;

    // Send via email
    if ($sent_via === 'email' || $sent_via === 'both') {
        if (!empty($recipient_email)) {
            try {
                $email_subject = "Re: " . ($tracking_data['subject'] ?? 'Your Contact Message');
                $email_body = "Hi " . htmlspecialchars($recipient_name) . ",\n\n";
                $email_body .= $reply_message . "\n\n";
                $email_body .= "---\n";
                $email_body .= "This is a reply to your contact message submitted to Birthday.Gold.\n";
                $email_body .= "If you have any questions, please feel free to reply to this email.\n\n";
                $email_body .= "Best regards,\n";
                $email_body .= "Birthday.Gold Team";

                // Use the mail class
                $mail_result = $mail->send([
                    'to' => $recipient_email,
                    'subject' => $email_subject,
                    'message' => $email_body,
                    'from_name' => 'Birthday.Gold Support',
                    'from_email' => 'support@birthday.gold'
                ]);

                $email_sent = ($mail_result === true);

                // Update email status
                $database->query(
                    "UPDATE bg_contact_replies SET email_status = :status WHERE reply_id = :id",
                    ['status' => $email_sent ? 'sent' : 'failed', 'id' => $reply_id]
                );
            } catch (Exception $e) {
                $database->query(
                    "UPDATE bg_contact_replies SET email_status = :status WHERE reply_id = :id",
                    ['status' => 'error: ' . $e->getMessage(), 'id' => $reply_id]
                );
            }
        }
    }

    // Send via notification (only if user is logged in / has user_id)
    if (($sent_via === 'notification' || $sent_via === 'both') && !empty($recipient_user_id)) {
        try {
            $notif_sql = "INSERT INTO bg_user_notifications (
                user_id,
                type,
                title,
                message,
                status,
                create_dt,
                modify_dt,
                alert_class,
                priority,
                category,
                sent_to
            ) VALUES (
                :user_id,
                'contact_reply',
                :title,
                :message,
                'active',
                NOW(),
                NOW(),
                'info',
                'normal',
                'support',
                'display'
            )";

            $database->query($notif_sql, [
                'user_id' => $recipient_user_id,
                'title' => 'Reply to Your Contact Message',
                'message' => $reply_message
            ]);

            $notification_id = $database->lastInsertId();
            $notification_sent = true;

            // Update reply record with notification_id
            $database->query(
                "UPDATE bg_contact_replies SET notification_id = :notif_id WHERE reply_id = :id",
                ['notif_id' => $notification_id, 'id' => $reply_id]
            );
        } catch (Exception $e) {
            // Notification failed, but continue
        }
    }

    // Update reply status
    $final_status = 'sent';
    if ($sent_via === 'email' && !$email_sent) {
        $final_status = 'failed';
    } elseif ($sent_via === 'notification' && !$notification_sent) {
        $final_status = 'failed';
    } elseif ($sent_via === 'both' && !$email_sent && !$notification_sent) {
        $final_status = 'failed';
    }

    $database->query(
        "UPDATE bg_contact_replies SET status = :status, sent_dt = NOW() WHERE reply_id = :id",
        ['status' => $final_status, 'id' => $reply_id]
    );

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reply_id' => $reply_id,
        'email_sent' => $email_sent,
        'notification_sent' => $notification_sent,
        'notification_id' => $notification_id,
        'status' => $final_status,
        'message' => 'Reply saved successfully!'
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
