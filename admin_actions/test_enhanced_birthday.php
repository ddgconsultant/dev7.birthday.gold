<?php
// Test enhanced birthday detection logic

$test_cases = [
    [
        'name' => '1UP Nutrition',
        'html' => '<div class="yotpo-widget-instance" data-yotpo-instance-id="84037"></div>
                   <script src="https://cdn-loyalty.yotpo.com/loader/YPmXkfB34GOsLeJT2ByMTw.js"></script>
                   <h1>Rewards Program</h1>',
        'expected' => 'Should detect Yotpo loyalty platform'
    ],
    [
        'name' => 'Smile.io Example',
        'html' => '<div class="smile-launcher" data-channel-key="channel_xyz"></div>
                   <script src="https://js.smile.io/v1/smile-shopify.js"></script>',
        'expected' => 'Should detect Smile.io platform'
    ],
    [
        'name' => 'Direct Birthday Mention',
        'html' => '<h2>Birthday Club</h2>
                   <p>Join our birthday club and get a free dessert on your special day!</p>',
        'expected' => 'Should detect direct birthday program'
    ],
    [
        'name' => 'No Program',
        'html' => '<h1>About Us</h1><p>We sell great products.</p>',
        'expected' => 'Should not detect any program'
    ]
];

foreach ($test_cases as $test) {
    echo "Testing: {$test['name']}\n";
    echo "Expected: {$test['expected']}\n";
    
    $html = $test['html'];
    $html_lower = strtolower($html);
    
    // Birthday keywords check
    $birthday_keywords = [
        'birthday', 'birth day', 'bday', 'b-day',
        'anniversary', 'special day', 'special occasion',
        'birthday club', 'birthday reward',
        'birthday offer', 'birthday freebie',
        'birthday gift', 'birthday treat',
        'birthday perk', 'birthday benefit',
        'celebrate', 'annual', 'yearly',
        'once a year', 'every year'
    ];
    
    $mentions_birthday = false;
    foreach ($birthday_keywords as $keyword) {
        if (strpos($html_lower, $keyword) !== false) {
            $mentions_birthday = true;
            echo "  ✓ Found birthday keyword: '$keyword'\n";
            break;
        }
    }
    
    // Loyalty platform check
    $loyalty_platforms = [
        'yotpo' => ['yotpo', 'data-yotpo', 'yotpo-widget'],
        'smile' => ['smile.io', 'smile-launcher', 'smile-ui'],
        'loyalty_lion' => ['loyaltylion', 'lion-loyalty'],
        'stamped' => ['stamped.io', 'stamped-loyalty'],
        'swell' => ['swell.store', 'swell-campaign'],
        'rewards_program' => ['rewards program', 'loyalty program', 'vip program']
    ];
    
    $detected_platform = null;
    $has_loyalty_program = false;
    
    foreach ($loyalty_platforms as $platform => $indicators) {
        foreach ($indicators as $indicator) {
            if (stripos($html, $indicator) !== false) {
                $detected_platform = $platform;
                $has_loyalty_program = true;
                echo "  ✓ Detected loyalty platform: $platform (indicator: '$indicator')\n";
                break 2;
            }
        }
    }
    
    // Final determination
    if ($mentions_birthday) {
        echo "  Result: Direct birthday program detected\n";
    } elseif ($has_loyalty_program) {
        echo "  Result: Loyalty platform detected - likely has birthday rewards\n";
    } else {
        echo "  Result: No birthday program detected\n";
    }
    
    echo "  ---\n\n";
}

// Test actual 1UP Nutrition content
echo "Testing actual 1UP Nutrition rewards page content:\n";
$file_content = file_get_contents('/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/temp/1up_rewards.html');
if ($file_content) {
    $found_yotpo = stripos($file_content, 'yotpo') !== false;
    $found_loyalty = stripos($file_content, 'cdn-loyalty.yotpo.com') !== false;
    $found_widget = stripos($file_content, 'data-yotpo-instance-id') !== false;
    
    echo "  Yotpo indicators found:\n";
    echo "    - 'yotpo' text: " . ($found_yotpo ? "YES" : "NO") . "\n";
    echo "    - Yotpo loyalty CDN: " . ($found_loyalty ? "YES" : "NO") . "\n";
    echo "    - Yotpo widget instance: " . ($found_widget ? "YES" : "NO") . "\n";
}