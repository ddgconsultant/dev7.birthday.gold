<?php
// Turn off all output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers first
header('Content-Type: application/json');

// Now include site controller
$GLOBALS['nooutput'] = true;
$addClasses[] = 'mail';
error_reporting(0);

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!isset($input['_token']) || $input['_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate input
if (!isset($input['messageId'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Message ID required']);
    exit;
}

$encodedMessageId = $input['messageId'];
$server = $input['server'] ?? null;

// Decode message ID
try {
    $messageId = $qik->decodeId($encodedMessageId);
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit;
}

// Get current user ID
$userId = $current_user_data['user_id'];

// Delete the message
$result = $mail->deleteMessage($messageId, $userId, $server);

// Return response
echo json_encode([
    'success' => $result,
    'message' => $result ? 'Message deleted successfully' : 'Failed to delete message'
]);
exit;