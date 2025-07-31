<?php
/**
 * Amazon Alexa Webhook Handler
 * Processes requests from Alexa Skills
 */

include_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');

// Set response headers
header('Content-Type: application/json');

// Initialize Assistant
$assistant = new Assistant($database, $app, $account, $session);

// Parse Alexa request
$request = json_decode($body, true);

// Extract user information
$userId = null;
$accessToken = null;

// Check if user has linked their account
if (isset($request['context']['System']['user']['accessToken'])) {
    $accessToken = $request['context']['System']['user']['accessToken'];
    $userId = $assistant->authenticateToken($accessToken, 'alexa');
}

// Extract intent
$requestType = $request['request']['type'];
$intent = '';
$parameters = [];

if ($requestType === 'IntentRequest') {
    $intent = $request['request']['intent']['name'];
    $parameters = $request['request']['intent']['slots'] ?? [];
}

// Build Alexa response
$response = [
    'version' => '1.0',
    'response' => []
];

// Handle different request types
switch ($requestType) {
    case 'LaunchRequest':
        // User said "Alexa, open Birthday Gold"
        if (!$userId) {
            $linkingCode = $assistant->generateLinkingCode('alexa', $request['context']['System']['device']['deviceId'] ?? null);
            $response['response'] = [
                'outputSpeech' => [
                    'type' => 'SSML',
                    'ssml' => '<speak>Welcome to Birthday Gold. To access your account, ' .
                             'please link your account using the Alexa app, or go to birthday dot gold slash link ' .
                             'and enter code <say-as interpret-as="spell-out">' . 
                             str_replace('-', ' ', $linkingCode['code']) . '</say-as></speak>'
                ],
                'card' => [
                    'type' => 'LinkAccount'
                ],
                'shouldEndSession' => true
            ];
        } else {
            $response['response'] = [
                'outputSpeech' => [
                    'type' => 'PlainText',
                    'text' => 'Welcome to Birthday Gold. You can ask about your enrollments, rewards, or account status.'
                ],
                'reprompt' => [
                    'outputSpeech' => [
                        'type' => 'PlainText',
                        'text' => 'What would you like to know?'
                    ]
                ],
                'shouldEndSession' => false
            ];
        }
        break;
        
    case 'IntentRequest':
        if (!$userId) {
            $response['response'] = [
                'outputSpeech' => [
                    'type' => 'PlainText',
                    'text' => 'Please link your Birthday Gold account first using the Alexa app.'
                ],
                'card' => [
                    'type' => 'LinkAccount'
                ],
                'shouldEndSession' => true
            ];
        } else {
            // Map Alexa intent names to our standard intent names
            $intentMap = [
                'GetEnrollmentCountIntent' => 'GetEnrollmentCount',
                'GetActiveRewardsIntent' => 'GetActiveRewards',
                'GetAllocationBalanceIntent' => 'GetAllocationBalance',
                'GetAccountStatusIntent' => 'GetAccountStatus'
            ];
            
            $standardIntent = $intentMap[$intent] ?? $intent;
            $speechResponse = $assistant->processIntent($standardIntent, $parameters, $userId, 'alexa');
            
            $response['response'] = [
                'outputSpeech' => [
                    'type' => 'PlainText',
                    'text' => $speechResponse
                ],
                'shouldEndSession' => true
            ];
        }
        break;
        
    case 'SessionEndedRequest':
        // Session ended, no response needed
        break;
        
    default:
        $response['response'] = [
            'outputSpeech' => [
                'type' => 'PlainText',
                'text' => 'Sorry, I didn\'t understand that request.'
            ],
            'shouldEndSession' => true
        ];
}

// Send response
echo json_encode($response);
?>