<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
$additionalstyles = '';
$display_footertype = '';

# Set your secret token here
$secret_token = 'your-secret-token'; 
#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the token from the header
    $received_token = $_SERVER['HTTP_X_ROCKETCHAT_LIVECHAT_TOKEN'] ?? '';
    if ($received_token !== $secret_token) {
        http_response_code(403);
        echo 'Forbidden: Invalid Secret Token';
        exit;
    }

    // Read the incoming POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo 'Invalid JSON';
        exit;
    }

    // Log incoming data for debugging
    error_log('Received Webhook Data: ' . print_r($data, true));

    // Process the webhook data
    $user_name = $data['user_name'] ?? 'Unknown';
    $text = $data['text'] ?? 'No message';

    // Craft a response message
    $response = [
        'text' => 'Hello, ' . $user_name . '! You said: ' . $text
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '<div class="container main-content">';
echo '<h2>Rocket.Chat Webhook Handler</h2>';
echo '<p>This page responds to incoming webhooks from Rocket.Chat.</p>';
echo '</div>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
