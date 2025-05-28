<?php
$pagemode = 'core';
include('api_coordinator.php');

// Get the API key from the request
$api_key = $_POST['api_key'] ?? '';

// Function to call the /api/auth endpoint
function authenticate_api_key($api_key) {
    $auth_url = 'https://dev.birthday.gold/api/auth';
    $data = ['api_key' => $api_key];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($auth_url, false, $context);

    return json_decode($result, true);
}

// Authenticate the API key
$auth_response = authenticate_api_key($api_key);

if ($auth_response['success']) {
    // Check if the required parameters are present
    if (!isset($_POST['pubkey']) || !isset($_POST['api_key'])) {
        http_response_code(400);
        echo "Missing required parameters.";
        exit;
    }

    // Retrieve the posted data
    $pubkey = trim($_POST['pubkey']); // Ensure no extra spaces or new lines
    $pubkey = preg_replace('/\s+/', ' ', $pubkey); // Remove any extra spaces and new lines

    // Define the path to the authorized_keys file
    $authorized_keys_file = 'C:\\Users\\Administrator\\.ssh\\authorized_keys';

    // Check if the public key already exists in the authorized_keys file
    if (file_exists($authorized_keys_file)) {
        $existing_keys = file($authorized_keys_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($pubkey, $existing_keys)) {
            echo "Public key is already present.";
            exit;
        }
    }

    // Append the public key to the authorized_keys file
    $pubkey .= "\n"; // Ensure there is a newline at the end of the key
    file_put_contents($authorized_keys_file, $pubkey, FILE_APPEND);

    // Set permissions using icacls
    exec('icacls ' . escapeshellarg($authorized_keys_file) . ' /grant Administrator:F', $output, $return_var);

    if ($return_var !== 0) {
        http_response_code(500);
        echo "Failed to set permissions.";
        exit;
    }

    echo "Public key successfully added and permissions set.\n";
    echo "Please validate by executing: ssh administrator@dev.birthday.gold\n";
    echo "\n";
} else {
    http_response_code(403);
    echo "Invalid API key.";
    exit;
}
