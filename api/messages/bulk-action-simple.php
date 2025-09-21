<?php
// Turn off all output buffering and error reporting initially
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers first
header('Content-Type: application/json');

// Now include site controller
$GLOBALS['nooutput'] = true;
$addClasses[] = 'mail';

try {
    include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Just echo something simple
    echo json_encode([
        'status' => 'ok',
        'user_id' => $current_user_data['user_id'] ?? 'none',
        'mail_class_exists' => class_exists('Mail'),
        'mail_object_exists' => isset($mail),
        'input_received' => !empty($input)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;