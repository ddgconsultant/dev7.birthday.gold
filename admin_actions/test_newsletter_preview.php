<?php
/**
 * Test Newsletter Preview Functionality
 */

$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include('../core/site-controller.php');

// Simulate AJAX request
header('Content-Type: text/html');

echo "<h2>Testing Newsletter Preview Endpoint</h2>";
echo "<pre>";

// Test 1: No tokens (empty recipient criteria)
echo "Test 1: Preview with no recipient criteria\n";
echo "=========================================\n";

$_POST = [
    'tokens' => json_encode([]),
    'process' => 'single',
    'cta_category' => 'food',
    'cta_mode' => 'inclusive',
    'debug' => 'true'
];

// Capture output
ob_start();
include('../myaccount/marketing/ajax/newsletter-recipients-count.php');
$response1 = ob_get_clean();

$data1 = json_decode($response1, true);
echo "Response: " . ($data1['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Has user data: " . (isset($data1['user']) ? 'YES' : 'NO') . "\n";
if (isset($data1['user'])) {
    echo "User: " . $data1['user']['first_name'] . " " . $data1['user']['last_name'] . "\n";
    echo "Email: " . $data1['user']['email'] . "\n";
    echo "Age: " . $data1['user']['age'] . "\n";
    echo "Birthday Month: " . $data1['user']['birthday_month'] . "\n";
}
if (isset($data1['companies'])) {
    echo "Companies returned: " . count($data1['companies']) . "\n";
}
echo "\n";

// Test 2: With "all" token
echo "Test 2: Preview with 'all' recipients token\n";
echo "============================================\n";

$_POST = [
    'tokens' => json_encode([
        ['type' => 'all', 'label' => 'All Recipients', 'value' => 'all']
    ]),
    'process' => 'single',
    'cta_category' => 'food',
    'cta_mode' => 'inclusive',
    'debug' => 'true'
];

ob_start();
include('../myaccount/marketing/ajax/newsletter-recipients-count.php');
$response2 = ob_get_clean();

$data2 = json_decode($response2, true);
echo "Response: " . ($data2['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Has user data: " . (isset($data2['user']) ? 'YES' : 'NO') . "\n";
if (isset($data2['user'])) {
    echo "User: " . $data2['user']['first_name'] . " " . $data2['user']['last_name'] . "\n";
    echo "Matched criteria: " . ($data2['matched_criteria'] ? 'YES' : 'NO') . "\n";
}
echo "\n";

// Test 3: With specific criteria
echo "Test 3: Preview with specific criteria (September birthdays)\n";
echo "===========================================================\n";

$_POST = [
    'tokens' => json_encode([
        [
            'type' => 'criteria',
            'label' => 'Birthday in September',
            'field' => 'birthday_month',
            'operator' => 'equals',
            'value' => '9'
        ]
    ]),
    'process' => 'single',
    'cta_category' => 'food',
    'cta_mode' => 'inclusive',
    'debug' => 'true'
];

ob_start();
include('../myaccount/marketing/ajax/newsletter-recipients-count.php');
$response3 = ob_get_clean();

$data3 = json_decode($response3, true);
echo "Response: " . ($data3['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Has user data: " . (isset($data3['user']) ? 'YES' : 'NO') . "\n";
if (isset($data3['user'])) {
    echo "User: " . $data3['user']['first_name'] . " " . $data3['user']['last_name'] . "\n";
    echo "Birthday Month: " . $data3['user']['birthday_month'] . "\n";
    echo "Matched criteria: " . ($data3['matched_criteria'] ? 'YES' : 'NO') . "\n";
}

echo "\n";
echo "=== Summary ===\n";
echo "✓ All tests should return user data for preview\n";
echo "✓ Empty tokens should return a default user\n";
echo "✓ 'All' token should return the first active user\n";
echo "✓ Specific criteria should return a matching user or default\n";

echo "</pre>";

echo "<hr>";
echo "<p><a href='/myaccount/marketing/newsletter-edit.php' class='btn btn-primary'>Go to Newsletter Editor</a></p>";
?>