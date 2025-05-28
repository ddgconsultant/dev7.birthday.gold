<?php
// Directory and file to store the public key
$ssh_dir = "C:/Users/Administrator/.ssh";
$auth_keys_file = $ssh_dir . "/authorized_keys";

// Ensure the .ssh directory exists
if (!file_exists($ssh_dir)) {
    mkdir($ssh_dir, 0700, true);
}

// Get the public key from the POST request
$public_key = $_POST['pubkey'] ?? '';

if (empty($public_key)) {
    http_response_code(400);
    echo "Public key is required.";
    exit;
}

// Append the public key to the authorized_keys file
file_put_contents($auth_keys_file, $public_key . PHP_EOL, FILE_APPEND | LOCK_EX);

// Set the correct permissions
chmod($auth_keys_file, 0600);

echo "Public key has been added successfully.";