<?php

$webhook_url = 'https://your-n8n-instance-url/webhook/upload-to-backblaze';

// Random test data
$source_hostname = 'example.com';
$source_file_location = 'test_directory/test_file.txt';
$source_record_id = rand(1, 1000);
$target_file_path = 'uploaded_directory/uploaded_file.txt';

// Data to send to the webhook
$data = [
    'source_hostname' => $source_hostname,
    'source_file_location' => $source_file_location,
    'source_record_id' => $source_record_id,
    'target_file_path' => $target_file_path,
];

// Initialize cURL session
$ch = curl_init($webhook_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

// Execute cURL request
$response = curl_exec($ch);

// Check for cURL errors
if ($response === false) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    echo 'Response: ' . $response;
}

// Close cURL session
curl_close($ch);
?>
