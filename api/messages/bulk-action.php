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

// Use the framework's formposted check for CSRF
if (!$app->formposted('token')) {
    // For API requests, check the token manually since formposted expects POST data
    if (!isset($input['_token']) || $input['_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Validate input
if (!isset($input['action']) || !isset($input['messageIds']) || !is_array($input['messageIds'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$messageIds = $input['messageIds'];
$server = $input['server'] ?? null;

// Decode message IDs
$decodedIds = [];
foreach ($messageIds as $encodedId) {
    try {
        $decodedId = $qik->decodeId($encodedId);
        if ($decodedId) {
            $decodedIds[] = $decodedId;
        }
    } catch (Exception $e) {
        // Skip invalid IDs
    }
}

if (empty($decodedIds)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No valid message IDs provided']);
    exit;
}

// Get current user ID
$userId = $current_user_data['user_id'];

// Process action
$success = false;
$processedCount = 0;

switch ($action) {
    case 'mark-read':
        foreach ($decodedIds as $messageId) {
            $result = $mail->markMessageRead($messageId, $userId, $server);
            if ($result) {
                $processedCount++;
            }
        }
        $success = $processedCount > 0;
        break;
        
    case 'mark-unread':
        foreach ($decodedIds as $messageId) {
            $result = $mail->markMessageUnread($messageId, $userId, $server);
            if ($result) {
                $processedCount++;
            }
        }
        $success = $processedCount > 0;
        break;
        
    case 'delete':
        foreach ($decodedIds as $messageId) {
            $result = $mail->deleteMessage($messageId, $userId, $server);
            if ($result) {
                $processedCount++;
            }
        }
        $success = $processedCount > 0;
        break;
        
    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// Return response
echo json_encode([
    'success' => $success,
    'processedCount' => $processedCount,
    'totalCount' => count($decodedIds),
    'message' => $success ? 'Action completed successfully' : 'Failed to process messages'
]);
exit;