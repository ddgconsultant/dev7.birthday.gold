<?php
/**
 * AJAX endpoint to generate voice assistant linking codes
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');

header('Content-Type: application/json');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get user data
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Get platform
$platform = $_POST['platform'] ?? '';
if (!in_array($platform, ['google', 'alexa', 'siri'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid platform']);
    exit;
}

// Generate device ID
$deviceId = uniqid($platform . '_');

// Initialize Assistant class
$assistant = new Assistant($database, $app, $account, $session);

// Generate linking code
$result = $assistant->generateLinkingCode($platform, $deviceId);

if ($result) {
    echo json_encode([
        'success' => true,
        'code' => $result['code'],
        'expires_in' => 10,
        'platform' => $platform
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate code'
    ]);
}
?>