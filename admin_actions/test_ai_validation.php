<?php
// Test AI validation logic

// Sample validation scenarios
$test_scenarios = [
    [
        'name' => 'Complete Birthday Program (1UP Nutrition)',
        'data' => [
            'has_program' => true,
            'program_type' => 'loyalty_platform',
            'minimum_age' => 18,
            'maximum_age' => 250,
            'confidence_score' => 75,
            'has_enhanced_description' => true,
            'has_signup_instructions' => true,
            'has_terms' => true,
            'has_privacy' => true
        ],
        'expected_score' => 85,
        'expected_status' => 'good'
    ],
    [
        'name' => 'Missing Birthday Program',
        'data' => [
            'has_program' => false,
            'minimum_age' => 13,
            'maximum_age' => 250,
            'confidence_score' => 0,
            'has_enhanced_description' => false,
            'has_signup_instructions' => false,
            'has_terms' => true,
            'has_privacy' => true
        ],
        'expected_score' => 40,
        'expected_status' => 'needs_review'
    ],
    [
        'name' => 'Perfect Data Collection',
        'data' => [
            'has_program' => true,
            'program_type' => 'direct',
            'minimum_age' => 13,
            'maximum_age' => 250,
            'confidence_score' => 95,
            'has_enhanced_description' => true,
            'has_signup_instructions' => true,
            'has_terms' => true,
            'has_privacy' => true,
            'has_specific_reward' => true
        ],
        'expected_score' => 100,
        'expected_status' => 'excellent'
    ],
    [
        'name' => 'Adult-Only Business (Bar)',
        'data' => [
            'has_program' => true,
            'program_type' => 'direct',
            'minimum_age' => 21,
            'maximum_age' => 250,
            'confidence_score' => 80,
            'has_enhanced_description' => true,
            'has_signup_instructions' => true,
            'has_terms' => false,
            'has_privacy' => false
        ],
        'expected_score' => 85,
        'expected_status' => 'good'
    ]
];

header('Content-Type: text/plain');
echo "AI Validation Test Scenarios\n";
echo "============================\n\n";

foreach ($test_scenarios as $scenario) {
    echo "Scenario: {$scenario['name']}\n";
    echo str_repeat('-', strlen($scenario['name']) + 10) . "\n";
    
    // Simulate validation logic
    $score = 100;
    $issues = [];
    $warnings = [];
    $successes = [];
    
    // Birthday program check
    if ($scenario['data']['has_program']) {
        $successes[] = 'Birthday program detected';
    } else {
        $issues[] = 'No birthday program detected';
        $score -= 50;
    }
    
    // Age requirements check
    $min_age = $scenario['data']['minimum_age'];
    if ($min_age >= 21) {
        $warnings[] = "High minimum age ($min_age) may exclude younger customers";
    } else {
        $successes[] = "Age requirements validated";
    }
    
    // Enhanced description check
    if (!$scenario['data']['has_enhanced_description']) {
        $warnings[] = 'No enhanced reward description';
        $score -= 5;
    } else {
        $successes[] = 'Enhanced reward description available';
    }
    
    // Signup instructions check
    if (!$scenario['data']['has_signup_instructions']) {
        $warnings[] = 'No signup instructions generated';
        $score -= 5;
    } else {
        $successes[] = 'Signup instructions available';
    }
    
    // Confidence score check
    $confidence = $scenario['data']['confidence_score'];
    if ($confidence >= 80) {
        $successes[] = "High confidence score: $confidence%";
    } elseif ($confidence >= 60) {
        $warnings[] = "Moderate confidence score: $confidence%";
    } else {
        $issues[] = "Low confidence score: $confidence%";
        $score -= 15;
    }
    
    // Policy compliance check
    if (!$scenario['data']['has_terms']) {
        $warnings[] = 'Terms of service not found';
        $score -= 5;
    }
    if (!$scenario['data']['has_privacy']) {
        $warnings[] = 'Privacy policy not found';
        $score -= 5;
    }
    if ($scenario['data']['has_terms'] && $scenario['data']['has_privacy']) {
        $successes[] = 'Terms and privacy policies found';
    }
    
    // Specific reward check for loyalty platforms
    if ($scenario['data']['program_type'] === 'loyalty_platform' && 
        empty($scenario['data']['has_specific_reward'])) {
        $warnings[] = 'Specific reward details not captured from loyalty platform';
        $score -= 10;
    }
    
    // Ensure score doesn't go below 0
    if ($score < 0) $score = 0;
    
    // Determine status
    if ($score >= 90) {
        $status = 'excellent';
    } elseif ($score >= 75) {
        $status = 'good';
    } elseif ($score >= 60) {
        $status = 'fair';
    } else {
        $status = 'needs_review';
    }
    
    // Display results
    echo "Validation Score: $score% (Expected: {$scenario['expected_score']}%)\n";
    echo "Status: $status (Expected: {$scenario['expected_status']})\n\n";
    
    if (count($successes) > 0) {
        echo "✓ Successes (" . count($successes) . "):\n";
        foreach ($successes as $success) {
            echo "  - $success\n";
        }
        echo "\n";
    }
    
    if (count($warnings) > 0) {
        echo "⚠ Warnings (" . count($warnings) . "):\n";
        foreach ($warnings as $warning) {
            echo "  - $warning\n";
        }
        echo "\n";
    }
    
    if (count($issues) > 0) {
        echo "✗ Issues (" . count($issues) . "):\n";
        foreach ($issues as $issue) {
            echo "  - $issue\n";
        }
        echo "\n";
    }
    
    echo "\n";
}

echo "Validation Rules Summary\n";
echo "========================\n";
echo "• Birthday program existence: -50 points if missing (critical)\n";
echo "• Enhanced description: -5 points if missing\n";
echo "• Signup instructions: -5 points if missing\n";
echo "• Low confidence (<60%): -15 points\n";
echo "• Missing policies: -5 points each\n";
echo "• Vague loyalty rewards: -10 points\n";
echo "\nStatus Thresholds:\n";
echo "• Excellent: 90-100%\n";
echo "• Good: 75-89%\n";
echo "• Fair: 60-74%\n";
echo "• Needs Review: <60%\n";