<?php
/**
 * Handle Contact Message Reply Submission
 * Sends reply to contact form submissions and stores in database
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

// Admin only access
if (!$account->isadmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$contact_message_id = $_POST['contact_message_id'] ?? null;
$session_id = $_POST['session_id'] ?? null;
$reply_to_email = $_POST['reply_to_email'] ?? null;
$reply_subject = $_POST['reply_subject'] ?? null;
$reply_message = $_POST['reply_message'] ?? null;

// Validate required fields
if (!$contact_message_id || !$reply_to_email || !$reply_subject || !$reply_message) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Validate email
if (!filter_var($reply_to_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

try {
    // Get original contact message data for context
    $original_sql = "SELECT tracking_data FROM bg_sessiontracking WHERE id = :id";
    $original = $database->getrow($original_sql, ['id' => $contact_message_id]);

    $original_data = null;
    if ($original && !empty($original['tracking_data'])) {
        $original_data = json_decode($original['tracking_data'], true);
    }

    // Insert reply into database
    $insert_sql = "
    INSERT INTO bg_contact_replies (
        contact_message_id,
        session_id,
        reply_to_email,
        reply_subject,
        reply_message,
        admin_user_id,
        admin_username,
        status,
        original_message_data
    ) VALUES (
        :contact_message_id,
        :session_id,
        :reply_to_email,
        :reply_subject,
        :reply_message,
        :admin_user_id,
        :admin_username,
        'draft',
        :original_message_data
    )
    ";

    $database->query($insert_sql, [
        'contact_message_id' => $contact_message_id,
        'session_id' => $session_id,
        'reply_to_email' => $reply_to_email,
        'reply_subject' => $reply_subject,
        'reply_message' => $reply_message,
        'admin_user_id' => $_SESSION['user_id'],
        'admin_username' => $_SESSION['username'] ?? 'Admin',
        'original_message_data' => $original_data ? json_encode($original_data) : null
    ]);

    $reply_id = $database->pdo->lastInsertId();

    // Prepare email
    $mail = new Mail($system, $sitesettings);

    // Build email body with context
    $email_body = $reply_message;

    // Add original message context if available
    if ($original_data) {
        $email_body .= "\n\n" . str_repeat('-', 50) . "\n";
        $email_body .= "Your original message:\n\n";
        if (!empty($original_data['subject'])) {
            $email_body .= "Subject: " . $original_data['subject'] . "\n";
        }
        if (!empty($original_data['message_preview'])) {
            $email_body .= "\n" . $original_data['message_preview'];
        }
    }

    // Send email
    $mail_result = $mail->send(
        $reply_to_email,
        $reply_subject,
        $email_body,
        'support@birthday.gold',
        'Birthday.Gold Support'
    );

    if ($mail_result['success']) {
        // Update reply status to 'sent'
        $update_sql = "
        UPDATE bg_contact_replies
        SET status = 'sent', email_sent_dt = NOW()
        WHERE id = :id
        ";
        $database->query($update_sql, ['id' => $reply_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Reply sent successfully',
            'reply_id' => $reply_id
        ]);
    } else {
        // Update reply status to 'failed' with error
        $update_sql = "
        UPDATE bg_contact_replies
        SET status = 'failed', email_error = :error
        WHERE id = :id
        ";
        $database->query($update_sql, [
            'id' => $reply_id,
            'error' => $mail_result['error'] ?? 'Unknown error'
        ]);

        echo json_encode([
            'success' => false,
            'error' => 'Failed to send email: ' . ($mail_result['error'] ?? 'Unknown error'),
            'reply_id' => $reply_id
        ]);
    }

} catch (Exception $e) {
    error_log('Contact reply error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
