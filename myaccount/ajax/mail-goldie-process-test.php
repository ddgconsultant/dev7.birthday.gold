<?php
// Minimal test version of mail-goldie-process.php
// This will help isolate the SSE issue

// Set a flag to prevent site-controller from setting headers
define('SSE_MODE', true);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Increase execution limits
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '256M');

// Try to load site controller first
try {
    $addClasses = ['mail', 'ai'];
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
} catch (Exception $e) {
    header('Content-Type: text/event-stream');
    echo "data: " . json_encode(['type' => 'error', 'message' => 'Failed to load site controller: ' . $e->getMessage()]) . "\n\n";
    exit;
}

// Now set up SSE after site controller is loaded
if (ob_get_level()) ob_end_clean();

// Set up for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Force output
ob_implicit_flush(true);
flush();

// Helper function to send SSE message
function sendEvent($data) {
    echo "data: " . json_encode($data) . "\n\n";
    @ob_flush();
    flush();
}

// Send initial heartbeat
sendEvent([
    'type' => 'heartbeat',
    'message' => 'Connection established',
    'timestamp' => time()
]);

// Wait a moment
usleep(100000); // 0.1 second

sendEvent([
    'type' => 'progress',
    'message' => 'Site controller loaded',
    'percent' => 10
]);

// Check user
$uid = $current_user_data['user_id'] ?? 0;
if (!$uid) {
    sendEvent(['type' => 'error', 'message' => 'User not logged in']);
    exit;
}

sendEvent([
    'type' => 'progress',
    'message' => 'User authenticated',
    'detail' => 'User ID: ' . $uid,
    'percent' => 20
]);

// Test database connection
try {
    $test = $database->query("SELECT 1");
    sendEvent([
        'type' => 'progress',
        'message' => 'Database connected',
        'percent' => 30
    ]);
} catch (Exception $e) {
    sendEvent([
        'type' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}

// Test mail class
if (!isset($mail) || !method_exists($mail, 'getMessagesForAI')) {
    sendEvent([
        'type' => 'error',
        'message' => 'Mail class not available'
    ]);
    exit;
}

sendEvent([
    'type' => 'progress',
    'message' => 'Mail class loaded',
    'percent' => 40
]);

// Try to get messages
try {
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-7 days'));
    
    sendEvent([
        'type' => 'progress',
        'message' => 'Fetching messages...',
        'detail' => 'Date range: ' . $start_date . ' to ' . $end_date,
        'percent' => 50
    ]);
    
    $result = $mail->getMessagesForAI($uid, $start_date, $end_date);
    $messages = $result['messages'] ?? [];
    
    sendEvent([
        'type' => 'progress',
        'message' => 'Messages retrieved',
        'detail' => 'Found ' . count($messages) . ' messages',
        'percent' => 60
    ]);
} catch (Exception $e) {
    sendEvent([
        'type' => 'error',
        'message' => 'Failed to get messages: ' . $e->getMessage()
    ]);
    exit;
}

// Test summary table
try {
    $sql = "SELECT COUNT(*) as cnt FROM bg_user_message_summaries WHERE user_id = :user_id";
    $stmt = $database->query($sql, ['user_id' => $uid]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendEvent([
        'type' => 'progress',
        'message' => 'Summary table accessible',
        'detail' => 'Existing summaries: ' . ($result['cnt'] ?? 0),
        'percent' => 70
    ]);
} catch (Exception $e) {
    sendEvent([
        'type' => 'progress',
        'message' => 'Summary table not found',
        'detail' => 'Will create summaries fresh',
        'percent' => 70
    ]);
}

// Send a few more events to test streaming
for ($i = 80; $i <= 100; $i += 10) {
    sendEvent([
        'type' => 'progress',
        'message' => 'Testing stream...',
        'detail' => 'Progress: ' . $i . '%',
        'percent' => $i
    ]);
    usleep(500000); // 0.5 second
}

// Final complete event
sendEvent([
    'type' => 'complete',
    'message' => 'Test completed successfully',
    'stats' => [
        'user_id' => $uid,
        'messages_found' => count($messages ?? []),
        'memory_usage' => round(memory_get_usage() / 1048576, 2) . ' MB',
        'peak_memory' => round(memory_get_peak_usage() / 1048576, 2) . ' MB'
    ]
]);

exit;
?>