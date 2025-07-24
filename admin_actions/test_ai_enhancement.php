<?php
// Test AI enhancement logic

// Sample collected data
$test_data = [
    'company_name' => '1UP Nutrition',
    'company_url' => 'https://1upnutrition.com',
    'birthday_program' => [
        'program_data' => [
            'has_program' => true,
            'program_type' => 'loyalty_platform',
            'requirements' => ['Join the rewards program', 'Provide birth date during signup'],
            'rewards' => ['Birthday reward (check program for details)'],
            'signup_method' => 'join yotpo rewards program'
        ]
    ],
    'rewards' => [
        [
            'reward_name' => 'Birthday Reward',
            'reward_description_short' => 'Birthday reward (check program for details)',
            'reward_type' => 'physical'
        ]
    ],
    'age_requirements' => [
        'minimum_age' => '18',
        'maximum_age' => '250'
    ],
    'locations' => [],
    'policies' => [
        ['policy_type' => 'terms', 'url' => 'https://1upnutrition.com/policies/terms-of-service'],
        ['policy_type' => 'privacy', 'url' => 'https://1upnutrition.com/policies/privacy-policy']
    ]
];

// Sample AI prompt that would be generated
$ai_prompt = "You are analyzing birthday reward program data for 1UP Nutrition. Please review the collected data and provide:

1. VALIDATION: Identify any inconsistencies or missing critical information
2. ENHANCEMENTS: Suggest improvements or clarifications
3. REWARD_DESCRIPTION: Write a clear, customer-friendly description of the birthday reward
4. SIGNUP_INSTRUCTIONS: Create step-by-step instructions for signing up
5. CONFIDENCE_SCORE: Rate data completeness from 0-100

Collected Data:
" . json_encode($test_data, JSON_PRETTY_PRINT) . "

Respond in JSON format with these keys: validation_issues, enhancements, reward_description, signup_instructions, confidence_score, recommendations";

// Expected AI response format
$sample_ai_response = [
    'validation_issues' => [
        'Birthday reward details are vague - specific reward not identified',
        'No physical locations found - appears to be online-only business'
    ],
    'enhancements' => [
        'Consider checking Yotpo rewards page directly for specific birthday benefit',
        'Add note that this is an e-commerce/online-only business'
    ],
    'reward_description' => 'Join 1UP Nutrition\'s rewards program powered by Yotpo and receive a special birthday surprise! While the exact reward varies, members typically receive exclusive discounts, free products, or bonus points during their birthday month. You must be 18 or older to join.',
    'signup_instructions' => [
        '1. Visit 1upnutrition.com',
        '2. Look for the rewards program widget (usually in bottom corner)',
        '3. Click "Join Now" or "Sign Up for Rewards"',
        '4. Enter your email address and create a password',
        '5. Fill in your birth date when prompted',
        '6. Confirm your email address',
        '7. Your birthday reward will be automatically sent during your birthday month'
    ],
    'confidence_score' => 75,
    'recommendations' => [
        'Manual verification of exact birthday reward would improve data quality',
        'Consider adding seasonal or time-sensitive reward variations'
    ]
];

header('Content-Type: text/plain');
echo "AI Enhancement Test\n";
echo "===================\n\n";

echo "Sample Data Being Analyzed:\n";
echo "- Company: " . $test_data['company_name'] . "\n";
echo "- Has Birthday Program: " . ($test_data['birthday_program']['program_data']['has_program'] ? 'Yes' : 'No') . "\n";
echo "- Program Type: " . $test_data['birthday_program']['program_data']['program_type'] . "\n";
echo "- Age Requirements: " . $test_data['age_requirements']['minimum_age'] . "-" . $test_data['age_requirements']['maximum_age'] . "\n";
echo "- Locations: " . count($test_data['locations']) . " found\n";
echo "- Policies: " . count($test_data['policies']) . " found\n\n";

echo "Expected AI Analysis:\n";
echo "====================\n\n";

echo "1. Validation Issues (" . count($sample_ai_response['validation_issues']) . " found):\n";
foreach ($sample_ai_response['validation_issues'] as $issue) {
    echo "   - $issue\n";
}

echo "\n2. Enhanced Reward Description:\n";
echo "   \"" . $sample_ai_response['reward_description'] . "\"\n";

echo "\n3. Signup Instructions:\n";
foreach ($sample_ai_response['signup_instructions'] as $i => $step) {
    echo "   $step\n";
}

echo "\n4. Data Confidence Score: " . $sample_ai_response['confidence_score'] . "%\n";

echo "\n5. Recommendations:\n";
foreach ($sample_ai_response['recommendations'] as $rec) {
    echo "   - $rec\n";
}

echo "\n\nHow AI Enhancement Improves Data:\n";
echo "=================================\n";
echo "✓ Transforms vague descriptions into customer-friendly language\n";
echo "✓ Creates actionable signup instructions\n";
echo "✓ Identifies data gaps and validation issues\n";
echo "✓ Provides confidence scoring for quality assurance\n";
echo "✓ Suggests improvements for future data collection\n";