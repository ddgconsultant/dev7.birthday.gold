<?php
// Test the Anthropic API directly

$apiKey = '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';
$model = 'claude-3-sonnet-20240229';
$message = "What is today's date? Please respond briefly.";

$url = 'https://api.anthropic.com/v1/messages';

$data = [
    'model' => $model,
    'messages' => [
        ['role' => 'user', 'content' => $message]
    ],
    'max_tokens' => 1000,
    'system' => 'You are a helpful AI assistant. Please provide clear and concise responses.'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . $apiKey,
    'anthropic-version: 2023-06-01'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "CURL Error: " . $curlError . "\n";
echo "Response: " . $response . "\n";

if ($httpCode == 200) {
    $result = json_decode($response, true);
    echo "\nSuccess! Claude says: " . $result['content'][0]['text'] . "\n";
} else {
    echo "\nError occurred.\n";
}
?>