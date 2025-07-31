<?php
/**
 * Voice Assistant Webhook Dispatcher
 * Routes requests to platform-specific handlers
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get request headers and body
$headers = getallheaders();
$body = file_get_contents('php://input');
$jsonBody = json_decode($body, true);

// Detect platform based on headers or request structure
$platform = '';

// Google Assistant detection
if (isset($headers['Google-Actions-API-Version']) || 
    (isset($jsonBody['handler']) && $jsonBody['handler']['name'] === 'birthdayGold')) {
    $platform = 'google';
}

// Amazon Alexa detection
elseif (isset($headers['SignatureCertChainUrl']) || 
        (isset($jsonBody['session']) && isset($jsonBody['session']['application']['applicationId']))) {
    $platform = 'alexa';
}

// Siri/Apple detection
elseif (isset($headers['X-Apple-Intent']) || 
        isset($jsonBody['siriIntent'])) {
    $platform = 'siri';
}

// Route to platform-specific handler
if ($platform && file_exists(__DIR__ . '/' . $platform . '/webhook.php')) {
    include __DIR__ . '/' . $platform . '/webhook.php';
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown platform']);
}
?>