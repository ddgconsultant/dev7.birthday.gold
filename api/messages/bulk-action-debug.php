<?php
// Prevent any HTML output
$GLOBALS['nooutput'] = true;
error_reporting(0);
ob_start();

$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// CSRF is valid, continue processing
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
        // Debug the error
        echo json_encode([
            'debug' => 'Decode error',
            'encodedId' => $encodedId,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Check if mail methods exist
$methodsExist = [
    'markMessageRead' => method_exists($mail, 'markMessageRead'),
    'markMessageUnread' => method_exists($mail, 'markMessageUnread'),
    'deleteMessage' => method_exists($mail, 'deleteMessage')
];

// Try to call the method with error catching
$userId = $current_user_data['user_id'];
$messageId = $decodedIds[0];

// Enable error reporting temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if $mail object exists
if (!isset($mail)) {
    echo json_encode(['debug' => 'Mail object not set']);
    exit;
}

if (!is_object($mail)) {
    echo json_encode(['debug' => 'Mail is not an object', 'type' => gettype($mail)]);
    exit;
}

echo json_encode([
    'debug' => 'About to call method',
    'mail_class' => get_class($mail),
    'methods_exist' => $methodsExist,
    'action' => $action,
    'messageId' => $messageId,
    'userId' => $userId,
    'server' => $server
]);

// Actually try the call
if ($action === 'mark-unread' && method_exists($mail, 'markMessageUnread')) {
    $result = $mail->markMessageUnread($messageId, $userId, $server);
    echo json_encode(['success' => true, 'result' => $result]);
}
exit;