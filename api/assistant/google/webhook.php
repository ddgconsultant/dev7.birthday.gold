<?php
/**
 * Google Assistant Webhook Handler
 * Processes requests from Google Assistant
 */

include_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');

// Set response headers
header('Content-Type: application/json');

// Initialize Assistant
$assistant = new Assistant($database, $app, $account, $session);

// Get request body (either from parent webhook or direct call)
if (!isset($body)) {
    $body = file_get_contents('php://input');
}

// Parse Google Assistant request
$request = json_decode($body, true);

// Extract user information from Google's request
$userId = null;
$accessToken = null;

// Check if user has linked their account
if (isset($request['user']['accessToken'])) {
    $accessToken = $request['user']['accessToken'];
    $userId = $assistant->authenticateToken($accessToken, 'google');
}

// Extract intent and parameters
$intent = $request['intent']['name'] ?? '';
$parameters = $request['intent']['params'] ?? [];

// Build response
$response = [
    'session' => [
        'id' => $request['session']['id'] ?? uniqid(),
        'params' => []
    ]
];

// Handle account linking
if (!$userId) {
    // User needs to link their account
    $linkingCode = $assistant->generateLinkingCode('google', $request['device']['id'] ?? null);
    
    $response['prompt'] = [
        'firstSimple' => [
            'speech' => "To access your Birthday Gold information, I need to link your account. " .
                       "Please go to birthday.gold/link and enter code " . 
                       $linkingCode['code'] . ". The code expires in 10 minutes."
        ]
    ];
} else {
    // Process the intent
    $speechResponse = $assistant->processIntent($intent, $parameters, $userId, 'google');
    
    $response['prompt'] = [
        'firstSimple' => [
            'speech' => $speechResponse
        ]
    ];
    
    // Add suggestions based on intent
    $response['suggestions'] = getSuggestionsForIntent($intent);
}

// Send response
echo json_encode($response);

/**
 * Get contextual suggestions based on the current intent
 */
function getSuggestionsForIntent($intent) {
    $suggestions = [];
    
    switch ($intent) {
        case 'GetEnrollmentCount':
            $suggestions = [
                ['title' => 'Show my rewards'],
                ['title' => 'Check allocations']
            ];
            break;
            
        case 'GetActiveRewards':
            $suggestions = [
                ['title' => 'More details'],
                ['title' => 'Check allocations']
            ];
            break;
            
        default:
            $suggestions = [
                ['title' => 'Check enrollments'],
                ['title' => 'Show rewards'],
                ['title' => 'Account status']
            ];
    }
    
    return $suggestions;
}
?>