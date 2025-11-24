<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin only access
if (!$account->isadmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$contact_message_id = $_GET['contact_event_id'] ?? $_GET['contact_message_id'] ?? 0;

if (empty($contact_message_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing contact_message_id']);
    exit;
}

// Get all replies for this contact message
$replies_sql = "SELECT
    r.id,
    r.contact_message_id,
    r.session_id,
    r.reply_to_email,
    r.reply_subject,
    r.reply_message,
    r.admin_user_id,
    r.admin_username,
    r.status,
    r.email_sent_dt,
    r.email_error,
    r.original_message_data,
    r.create_dt,
    r.update_dt
FROM bg_contact_replies r
WHERE r.contact_message_id = :contact_message_id
ORDER BY r.create_dt DESC";

try {
    $replies = $database->query($replies_sql, ['contact_message_id' => $contact_message_id])
        ->fetchAll(PDO::FETCH_ASSOC);

    // Parse original_message_data JSON for each reply
    foreach ($replies as &$reply) {
        if (!empty($reply['original_message_data'])) {
            $reply['original_message_data'] = json_decode($reply['original_message_data'], true);
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'replies' => $replies,
        'count' => count($replies)
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
