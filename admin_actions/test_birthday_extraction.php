<?php
// Test birthday program extraction
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Test HTML with birthday program
$test_html = '
<html>
<body>
<h2>Join Our Rewards Program</h2>
<p>Sign up for our rewards club and get a FREE dessert on your birthday!</p>
<p>Birthday Club members receive a special birthday month offer - enjoy 20% off your entire meal during your birthday month.</p>
<h3>How it works:</h3>
<ul>
<li>Join our email list</li>
<li>Must be 18 or older</li>
<li>Receive your birthday reward via email</li>
<li>Valid during your birthday week</li>
</ul>
<p>Download our app to manage your rewards and never miss a birthday treat!</p>
</body>
</html>
';

// Process the HTML
$html_lower = strtolower($test_html);
$birthday_data = [
    'has_program' => false,
    'program_type' => null,
    'requirements' => [],
    'rewards' => [],
    'signup_method' => null,
    'age_restrictions' => []
];

// Check for birthday keywords
$birthday_keywords = ['birthday', 'birth day', 'bday'];
foreach ($birthday_keywords as $keyword) {
    if (strpos($html_lower, $keyword) !== false) {
        $birthday_data['has_program'] = true;
        break;
    }
}

// Extract rewards
$reward_patterns = [
    '/(?:free|complimentary)\s+(?:birthday)?\s*([a-zA-Z\s]+?)(?:on|during|for)\s+your\s+birthday/i',
    '/birthday\s+(?:gift|reward|treat|offer)[\s:]+([^.!?]+)/i',
    '/(\d+%?)\s*(?:off|discount).*?birthday/i'
];

foreach ($reward_patterns as $pattern) {
    if (preg_match_all($pattern, $test_html, $matches)) {
        foreach ($matches[1] as $reward) {
            $birthday_data['rewards'][] = trim(strip_tags($reward));
        }
    }
}

// Determine timing
if (preg_match('/birthday\s+week/i', $test_html)) {
    $birthday_data['program_type'] = 'week';
} elseif (preg_match('/birthday\s+month/i', $test_html)) {
    $birthday_data['program_type'] = 'month';
}

// Age restrictions
if (preg_match('/(?:must be|age)\s+(\d+)(?:\+|\s+(?:or|and)\s+(?:older|above))/i', $test_html, $age_match)) {
    $birthday_data['age_restrictions']['minimum'] = intval($age_match[1]);
}

// Signup method
if (preg_match('/(?:join|sign\s*up|register|enroll).*?(?:email|list)/i', $test_html, $match)) {
    $birthday_data['signup_method'] = trim($match[0]);
}

header('Content-Type: application/json');
echo json_encode([
    'test_result' => 'Birthday program extraction test',
    'found_program' => $birthday_data['has_program'],
    'extracted_data' => $birthday_data
], JSON_PRETTY_PRINT);