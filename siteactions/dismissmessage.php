<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if the request has an HTTP_REFERER header
if (!empty($_SERVER['HTTP_REFERER'])) {

    if (isset($_GET['midtag'])) {
        // Sanitize and decode the message ID
        $encryptedMessageId = $_GET['midtag'];
        list($type, $messageid) = explode(':', $encryptedMessageId, 2);
        $messageId = $qik->decodeId($messageid); // Implement your decryption logic

        try {
            // Determine which table to update based on message type
            switch ($type) {
                case 'attribute':
                    $stmt = $database->prepare('UPDATE bg_user_attributes SET status = "read", modify_dt = NOW() WHERE attribute_id = :id');
                    break;
                case 'notification':
                    $stmt = $database->prepare('UPDATE bg_user_notification SET status = "read", modify_dt = NOW() WHERE id = :id');
                    break;
                default:
                    http_response_code(400); // Bad request
                    echo json_encode(['status' => 'error', 'message' => 'Invalid message type.']);
                    exit;
            }

            // Execute the update query
            $stmt->bindParam(':id', $messageId, PDO::PARAM_INT);
            if ($stmt->execute()) {
                http_response_code(200); // OK
                echo json_encode(['status' => 'success', 'message' => 'Message marked as read.']);
            } else {
                http_response_code(500); // Internal server error
                echo json_encode(['status' => 'error', 'message' => 'Failed to update message status.']);
            }
        } catch (Exception $e) {
            http_response_code(500); // Internal server error
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400); // Bad request
        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    }
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed.']);
}
