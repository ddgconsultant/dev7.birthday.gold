<?php
/**
 * Siri/iOS App Webhook Handler
 * Processes requests from the Birthday Gold iOS app's Siri integration
 */

include_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');

// Set response headers
header('Content-Type: application/json');

// Initialize Assistant
$assistant = new Assistant($database, $app, $account, $session);

// Parse iOS app request
$request = json_decode($body, true);

// Extract user information from app's request
$userId = null;
$accessToken = null;

// Check if user has authenticated in the app
if (isset($request['accessToken'])) {
    $accessToken = $request['accessToken'];
    $userId = $assistant->authenticateToken($accessToken, 'siri');
}

// Handle account linking
if (!$userId && isset($request['linkingCode'])) {
    $linkingCode = $request['linkingCode'];
    $deviceId = $request['deviceId'] ?? null;
    
    $result = $assistant->verifyLinkingCode($linkingCode, null, 'siri');
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'tokens' => $result['tokens'],
            'message' => 'Account linked successfully'
        ]);
        exit;
    }
}

// Extract intent and parameters
$intent = $request['intent'] ?? '';
$parameters = $request['parameters'] ?? [];

// Process intent
$response = $assistant->processIntent($intent, $parameters, $userId, 'siri');

// Format response for iOS app
$siriResponse = [
    'version' => '1.0',
    'response' => [
        'outputSpeech' => [
            'type' => 'PlainText',
            'text' => $response['speech']
        ],
        'displayText' => $response['displayText'] ?? $response['speech'],
        'shouldEndSession' => $response['shouldEndSession'] ?? true
    ]
];

// Add account linking card if needed
if (!$userId) {
    $siriResponse['response']['card'] = [
        'type' => 'LinkAccount',
        'message' => 'Please link your Birthday Gold account in the app'
    ];
}

echo json_encode($siriResponse);
?>