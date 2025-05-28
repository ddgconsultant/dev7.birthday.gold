<?php
// Configuration
$CONFIG = [
    #'api_endpoint' => 'https://mylab.homiedns.com:8081/api/',
    'api_endpoint' => 'https://167.88.36.148:8081/api/',
    'api_key' => '273VW9FfT3g0xt_W8p1XlEmDxqFh@3Bk', // Replace with your API key
    'batch_size' => 25, // Number of emails to send per batch
    'email_domain' => 'bdtest.xyz',
    'from_address' => 'sender@bdtest.xyz'
];

// Array of possible subject lines for randomization
$SUBJECTS = [
    'Important Update About Your Account',
    'Your Monthly Newsletter',
    'Special Offer Inside',
    'Confirmation Required',
    'Updates to Our Service',
    'Thank You for Your Support',
    'Welcome to Our Community',
    'Your Account Status',
    'New Features Available',
    'Action Required'
];

// Array of possible message contents for randomization
$MESSAGES = [
    'We hope this email finds you well. Here is some important information...',
    'Thank you for being a valued member. We wanted to share...',
    'We are excited to announce some new updates to our service...',
    'This is a test message to verify our email delivery system...',
    'Please review the following information regarding your account...'
];

/**
 * Generates a random message with some variation
 */
function generateRandomMessage() {
    global $MESSAGES;
    $base_message = $MESSAGES[array_rand($MESSAGES)];
    return $base_message . "\n\nTimestamp: " . date('Y-m-d H:i:s') . "\nUnique ID: " . uniqid();
}

/**
 * Makes the API call to the email queue service
 */
function emailqueueApiCall($endpoint, $key, $messages = []) {
    $curl = curl_init();
    $request = [
        "key" => $key,
        "messages" => $messages
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL => $endpoint,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => ["q" => json_encode($request)],
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification
        CURLOPT_SSL_VERIFYHOST => false   // Disable SSL host verification
    ]);

    $result = curl_exec($curl);
    
    if (curl_errno($curl)) {
        echo "Curl error: " . curl_error($curl) . "\n";
    }
    
    curl_close($curl);
    return json_decode($result, true);
}

/**
 * Main execution
 */
function main() {
    global $CONFIG, $SUBJECTS;
    
    // Prepare batch of messages
    $messages = [];
    
    for ($i = 0; $i < $CONFIG['batch_size']; $i++) {
        $messages[] = [
            "from" => $CONFIG['from_address'],
            "to" => "emtest_{$i}@" . $CONFIG['email_domain'],
            "subject" => 'TESTEMAILQUEUE: '.$SUBJECTS[array_rand($SUBJECTS)],
            "content" => generateRandomMessage()
        ];
    }

    // Send the batch
    $result = emailqueueApiCall(
        $CONFIG['api_endpoint'],
        $CONFIG['api_key'],
        $messages
    );

    // Output results
    header('Content-Type: application/json');
    echo json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'batch_size' => $CONFIG['batch_size'],
        'result' => $result,
        'messages_sent' => count($messages)
    ], JSON_PRETTY_PRINT);
}

// Execute the script
main();