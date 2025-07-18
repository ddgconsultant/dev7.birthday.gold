<?php
// Kill all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// Set headers
header('Content-Type: application/json');

try {
    // Include site controller
    $GLOBALS['nooutput'] = true;
    $addClasses[] = 'mail';
    error_reporting(0);
    
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate CSRF
    if (!isset($input['_token']) || $input['_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }
    
    // Validate input
    if (!isset($input['action']) || !isset($input['messageIds']) || !is_array($input['messageIds'])) {
        throw new Exception('Invalid request parameters');
    }
    
    $action = $input['action'];
    $messageIds = $input['messageIds'];
    $server = $input['server'] ?? null;
    $userId = $current_user_data['user_id'];
    
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
        throw new Exception('No valid message IDs provided');
    }
    
    // Process action
    $processedCount = 0;
    
    foreach ($decodedIds as $messageId) {
        $result = false;
        
        switch ($action) {
            case 'mark-read':
                $result = $mail->markMessageRead($messageId, $userId, $server);
                break;
                
            case 'mark-unread':
                $result = $mail->markMessageUnread($messageId, $userId, $server);
                break;
                
            case 'delete':
                $result = $mail->deleteMessage($messageId, $userId, $server);
                break;
                
            default:
                throw new Exception('Invalid action: ' . $action);
        }
        
        if ($result) {
            $processedCount++;
        }
    }
    
    // Success response
    echo json_encode([
        'success' => $processedCount > 0,
        'processedCount' => $processedCount,
        'totalCount' => count($decodedIds),
        'message' => $processedCount > 0 ? 'Action completed successfully' : 'Failed to process messages'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;