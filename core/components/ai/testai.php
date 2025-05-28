<?php
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
if (!$account->isadmin())
{
    header('location: /401'); exit;
}

$question="What is the privacy policy of this website?";

// Use Anthropic Claude
#$ai->setEngine('anthropic_goldie');
#$response = $ai->process($question);

/*
// Or specify engine in options
$response = $ai->process($question, [
    'engine' => 'anthropic',
    'temperature' => 0.9,
    'max_tokens' => 300
]);
*/

// Simple usage
#$response = $ai->process($question);
/*
// Advanced usage with custom options
$response = $ai->process([
    ['role' => 'system', 'content' => 'Custom system message'],
    ['role' => 'user', 'content' => 'Your question here']
], [
    'temperature' => 0.9,
    'max_tokens' => 300
]);
*/
$ai->setEngine('anthropic_goldie', 'computer-use');


$systemPrompt = "{computer: true} You are in Computer Use mode, which means you are able to fully emulate web interactions and analyze web forms. You can describe actions and fill out forms with precise step-by-step details.";

$userPrompt = "Visit https://birthday.gold/contact and analyze the contact form. The form includes a custom captcha where we have to select an icon. Walk through the exact steps of:
1. Identifying all form fields
2. Checking the captcha requirement
3. Filling out test data including:
   - Name: Claude Tester
   - Email: claude@anthropic.com
   - A professional message about birthday rewards
4. Selecting the requested captcha icon
5. Submitting the form. Report back with what happened

Please describe each step as you perform it.";

// Process the request

// For Anthropic computer use, format the specific request structure needed
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userPrompt]
];

$options = [
    'max_tokens' => 2000,
    'temperature' => 0.7,
    'tools' => [
        [
            'type' => 'computer_20241022',
            'name' => 'computer',
            'display_width_px' => 1024,
            'display_height_px' => 768,
            'display_number' => 1
        ]
    ]
];


// Process using the generic AI class
$response = $ai->process($messages, $options);
// Let's see what Claude suggests for the form submission
// Display the AI response and metrics
if ($response) {
  #  $normalizedResponse = $ai->normalizeResponse($response);
  $normalizedResponse = $ai->getNormalizedResponse($response);
    
    echo "<h3>AI Response:</h3>";
    echo "<pre>";
    print_r($normalizedResponse['content']);
    print_r($response);
    echo "</pre>";
    
    echo "<h3>Usage Metrics:</h3>";
    echo $ai->displayMetrics($response);
} else {
    echo "<h3>Error: No Response Received</h3>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
}