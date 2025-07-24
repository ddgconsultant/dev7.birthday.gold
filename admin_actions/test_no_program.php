<?php
// Test what happens with no birthday program or loyalty platform

$test_cases = [
    [
        'name' => 'B2B Software Company',
        'html' => '<h1>Enterprise Software Solutions</h1>
                   <p>We provide cloud-based solutions for businesses.</p>
                   <a href="/pricing">Pricing</a>
                   <a href="/contact">Contact Sales</a>',
        'expected_result' => 'no_program_found'
    ],
    [
        'name' => 'Local Plumber',
        'html' => '<h1>Joe\'s Plumbing Services</h1>
                   <p>24/7 Emergency plumbing services</p>
                   <p>Licensed and insured</p>
                   <a href="/services">Our Services</a>',
        'expected_result' => 'no_program_found'
    ],
    [
        'name' => 'Restaurant with Birthday Club',
        'html' => '<h1>Pizza Palace</h1>
                   <p>Join our Birthday Club for a free pizza!</p>
                   <a href="/rewards">Rewards Program</a>',
        'expected_result' => 'birthday_program_found'
    ]
];

echo "Testing No Program Detection Scenarios\n";
echo "=====================================\n\n";

foreach ($test_cases as $test) {
    echo "Company Type: {$test['name']}\n";
    
    $html = $test['html'];
    $html_lower = strtolower($html);
    
    // Check for birthday keywords
    $birthday_keywords = ['birthday', 'birth day', 'bday', 'anniversary'];
    $found_birthday = false;
    
    foreach ($birthday_keywords as $keyword) {
        if (strpos($html_lower, $keyword) !== false) {
            $found_birthday = true;
            break;
        }
    }
    
    // Check for loyalty platforms
    $loyalty_indicators = ['yotpo', 'smile.io', 'loyaltylion', 'stamped.io', 'swell'];
    $found_platform = false;
    
    foreach ($loyalty_indicators as $indicator) {
        if (stripos($html, $indicator) !== false) {
            $found_platform = true;
            break;
        }
    }
    
    // Check for rewards links
    $has_rewards_link = preg_match('/<a[^>]+href=["\'][^"\']*(?:rewards?|loyalty|club)[^"\']*["\'][^>]*>/i', $html);
    
    // Determine result
    if ($found_birthday) {
        $result = 'birthday_program_found';
        $data_collected = 'Birthday program detected';
    } elseif ($found_platform) {
        $result = 'loyalty_platform_found';
        $data_collected = 'Loyalty platform detected - likely has birthday rewards';
    } else {
        $result = 'no_program_found';
        $data_collected = 'No birthday program detected';
    }
    
    echo "  Expected: {$test['expected_result']}\n";
    echo "  Actual: $result\n";
    echo "  Data Collected: $data_collected\n";
    echo "  Has Rewards Link: " . ($has_rewards_link ? 'Yes' : 'No') . "\n";
    echo "  Status Would Be: " . ($result === 'no_program_found' ? 'attempted' : 'completed') . "\n";
    echo "  ---\n\n";
}