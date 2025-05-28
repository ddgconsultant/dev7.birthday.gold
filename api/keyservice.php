<?PHP
$pagemode = 'core';
  include('api_coordinator.php');

// Get the API key from the request
$api_key = $_POST['api_key'] ?? '';

/*
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
*/



// Authenticate the API key
$auth_response = $api->authenticate_api_key($api_key);

if ($auth_response['success']) {
    // Define the path to the public key file
    #$public_key_file = $_SERVER['DOCUMENT_ROOT'] . "/../_CERTS_/SSH_KEYS/id_rsa_root.pub";
    $public_key_file= 'C:\Users\bg_ssh_admin\.ssh\id_rsa.pub';
    #$public_key_file= 'C:\Users\bg_ssh_admin\.ssh\id_rsa';   ### SAVE AS: /root/.ssh/id_rsa_bg_ssh_admin

    
    // Check if the public key file exists
    if (file_exists($public_key_file)) {
        header('Content-Type: text/plain');
        readfile($public_key_file);
    } else {
        http_response_code(404);
        echo "Public key file not found.";
    }
} else {
    // Invalid API key
    http_response_code(401);
    echo "Unauthorized";
   # echo "Attempt recorded with: ".$api_key;
}