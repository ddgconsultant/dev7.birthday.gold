<?php
// Configuration for Rocket.Chat API and Goldie
define('ROCKETCHAT_URL', 'https://chat.birthdaygold.cloud/api/v1/chat.postMessage');
define('ROCKETCHAT_TOKEN', 'YOUR_ROCKET_CHAT_TOKEN');  // Replace with your bot's token
define('ROCKETCHAT_USER_ID', 'YOUR_BOT_USER_ID');      // Replace with your bot's user ID

// Function to send message to Goldie and retrieve response
function sendMessageToGoldie($message) {
    $url = 'https://chatgpt.com/g/g-IWdsCnqQR-goldie';
    $data = json_encode(['message' => $message]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data,
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);

    return $response['response'] ?? 'Sorry, Goldie is unavailable right now.';
}

// Function to post response back to Rocket.Chat
function postToRocketChat($channel, $responseText) {
    $data = [
        'channel' => $channel,
        'text' => $responseText
    ];
    $options = [
        'http' => [
            'header'  => [
                "Content-Type: application/json",
                "X-Auth-Token: " . ROCKETCHAT_TOKEN,
                "X-User-Id: " . ROCKETCHAT_USER_ID
            ],
            'method'  => 'POST',
            'content' => json_encode($data),
        ]
    ];
    $context  = stream_context_create($options);
    file_get_contents(ROCKETCHAT_URL, false, $context);
}

// Main script to handle Rocket.Chat webhook request
$requestPayload = json_decode(file_get_contents('php://input'), true);

if (isset($requestPayload['text']) && isset($requestPayload['channel_id'])) {
    $incomingMessage = $requestPayload['text'];
    $channel = $requestPayload['channel_id'];

    // Send incoming message to Goldie
    $responseFromGoldie = sendMessageToGoldie($incomingMessage);

    // Post response back to Rocket.Chat
    postToRocketChat($channel, $responseFromGoldie);
}

// Return success response
header("Content-Type: application/json");
echo json_encode(['status' => 'success']);
?>
