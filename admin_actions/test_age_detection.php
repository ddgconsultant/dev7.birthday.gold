<?php
// Test age detection logic

// Test cases with different scenarios
$test_cases = [
    [
        'company' => 'Cannabis Dispensary',
        'category' => 'Cannabis & CBD',
        'text' => 'Must be 21 or older to enter. Valid ID required.',
        'expected' => 'Min: 21 (cannabis category)'
    ],
    [
        'company' => 'Chuck E. Cheese',
        'category' => 'Kids Entertainment',
        'text' => 'Birthday parties for children ages 4-12. Special teen packages available for ages 13-16.',
        'expected' => 'Min: 4, Max: 16 (kids category + text)'
    ],
    [
        'company' => 'Hertz Car Rental',
        'category' => 'Car Rental',
        'text' => 'Drivers must be at least 25 years old. Additional fees apply for drivers 21-24.',
        'expected' => 'Min: 25 (car rental category)'
    ],
    [
        'company' => 'Starbucks',
        'category' => 'Restaurant',
        'text' => 'Join Starbucks Rewards today. Open to all ages.',
        'expected' => 'Min: 13, Max: 250 (default)'
    ],
    [
        'company' => 'Wine Club',
        'category' => 'Wine & Spirits',
        'text' => 'Members must be of legal drinking age (21+).',
        'expected' => 'Min: 21 (alcohol category)'
    ]
];

echo "Age Detection Test\n";
echo "==================\n\n";

// Category rules (from the actual code)
$category_rules = [
    'cannabis' => ['min' => 21, 'max' => 250, 'reason' => 'Cannabis industry standard'],
    'marijuana' => ['min' => 21, 'max' => 250, 'reason' => 'Cannabis industry standard'],
    'dispensary' => ['min' => 21, 'max' => 250, 'reason' => 'Cannabis industry standard'],
    'alcohol' => ['min' => 21, 'max' => 250, 'reason' => 'Alcohol age requirement'],
    'bar' => ['min' => 21, 'max' => 250, 'reason' => 'Bar/nightclub age requirement'],
    'nightclub' => ['min' => 21, 'max' => 250, 'reason' => 'Nightclub age requirement'],
    'wine' => ['min' => 21, 'max' => 250, 'reason' => 'Alcohol age requirement'],
    'brewery' => ['min' => 21, 'max' => 250, 'reason' => 'Alcohol age requirement'],
    'car rental' => ['min' => 25, 'max' => 250, 'reason' => 'Car rental age requirement'],
    'vehicle rental' => ['min' => 25, 'max' => 250, 'reason' => 'Vehicle rental age requirement'],
    'kids' => ['min' => 4, 'max' => 16, 'reason' => 'Kids-focused business'],
    'children' => ['min' => 4, 'max' => 16, 'reason' => 'Children-focused business'],
    'toy' => ['min' => 4, 'max' => 16, 'reason' => 'Toy store target demographic'],
    'teen' => ['min' => 13, 'max' => 19, 'reason' => 'Teen-focused business'],
    'restaurant' => ['min' => 13, 'max' => 250, 'reason' => 'Standard restaurant policy'],
    'retail' => ['min' => 13, 'max' => 250, 'reason' => 'Standard retail policy'],
    'food' => ['min' => 13, 'max' => 250, 'reason' => 'Standard food service policy'],
];

// Age patterns
$age_patterns = [
    '/must\s+be\s+(\d+)\s*(?:\+|years?|or\s+older)/i' => 'minimum',
    '/age\s+(\d+)\s*(?:\+|and\s+(?:over|above|older))/i' => 'minimum',
    '/(\d+)\s*years?\s+(?:of\s+age|old)\s+or\s+older/i' => 'minimum',
    '/at\s+least\s+(\d+)\s*years?/i' => 'minimum',
    '/(\d+)\+\s*only/i' => 'minimum',
    '/ages?\s+(\d+)\s*-\s*(\d+)/i' => 'range',
    '/between\s+(?:ages?\s+)?(\d+)\s+and\s+(\d+)/i' => 'range',
];

foreach ($test_cases as $test) {
    echo "Company: {$test['company']}\n";
    echo "Category: {$test['category']}\n";
    echo "Text: \"{$test['text']}\"\n";
    echo "Expected: {$test['expected']}\n";
    
    // Start with defaults
    $min_age = 13;
    $max_age = 250;
    $source = 'default';
    
    // Check category
    $category_lower = strtolower($test['category']);
    foreach ($category_rules as $keyword => $rules) {
        if (strpos($category_lower, $keyword) !== false) {
            $min_age = $rules['min'];
            $max_age = $rules['max'];
            $source = "category ({$rules['reason']})";
            break;
        }
    }
    
    // Check text for age patterns
    $found_ages = [];
    foreach ($age_patterns as $pattern => $type) {
        if (preg_match($pattern, $test['text'], $matches)) {
            if ($type === 'minimum' && isset($matches[1])) {
                $age = intval($matches[1]);
                if ($age > $min_age) {
                    $min_age = $age;
                    $source = 'text pattern';
                }
            } elseif ($type === 'range' && isset($matches[1]) && isset($matches[2])) {
                $range_min = intval($matches[1]);
                $range_max = intval($matches[2]);
                if ($range_min >= 4 && $range_max <= 100) {
                    $min_age = $range_min;
                    $max_age = $range_max;
                    $source = 'text pattern (range)';
                }
            }
        }
    }
    
    echo "Result: Min: $min_age, Max: $max_age (Source: $source)\n";
    echo "---\n\n";
}

echo "\nLogic Summary:\n";
echo "1. Start with defaults: 13-250\n";
echo "2. Check category for specific rules (cannabis=21+, kids=4-16, etc.)\n";
echo "3. Parse text for age mentions (overrides if more restrictive)\n";
echo "4. Store results with source and confidence level\n";