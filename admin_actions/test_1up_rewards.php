<?php
// Test 1UP Nutrition rewards page specifically
include('/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/core/site-controller.php');

$url = 'https://1upnutrition.com/pages/1-up-nutrition-rewards-program';

// Fetch the page
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: text/plain');
echo "Testing 1UP Nutrition Rewards Page\n";
echo "URL: $url\n";
echo "HTTP Code: $httpCode\n";
echo "=====================================\n\n";

if ($httpCode === 200 && !empty($html)) {
    // Search for birthday keywords
    $birthday_keywords = [
        'birthday', 'birth day', 'bday', 'b-day',
        'anniversary', 'special day', 'special occasion',
        'celebrate', 'annual'
    ];
    
    echo "1. Searching for birthday keywords:\n";
    $found_birthday = false;
    $html_lower = strtolower($html);
    
    foreach ($birthday_keywords as $keyword) {
        if (strpos($html_lower, $keyword) !== false) {
            echo "   ✓ Found: '$keyword'\n";
            $found_birthday = true;
            
            // Extract context around the keyword
            $pos = strpos($html_lower, $keyword);
            $start = max(0, $pos - 100);
            $end = min(strlen($html), $pos + strlen($keyword) + 100);
            $context = substr($html, $start, $end - $start);
            $context = strip_tags($context);
            $context = preg_replace('/\s+/', ' ', trim($context));
            echo "   Context: \"...$context...\"\n\n";
        }
    }
    
    if (!$found_birthday) {
        echo "   ✗ No direct birthday keywords found\n\n";
    }
    
    // Look for rewards program structure
    echo "2. Analyzing rewards program structure:\n";
    
    // Look for point earning opportunities
    if (preg_match_all('/earn\s+(\d+)\s+points?/i', $html, $matches)) {
        echo "   Found point earning opportunities:\n";
        foreach (array_unique($matches[0]) as $match) {
            echo "   - $match\n";
        }
        echo "\n";
    }
    
    // Look for tiers or levels
    if (preg_match_all('/(?:tier|level|status)\s*(?:\d+|[a-z]+)/i', $html, $matches)) {
        echo "   Found tier/level mentions:\n";
        foreach (array_unique($matches[0]) as $match) {
            echo "   - $match\n";
        }
        echo "\n";
    }
    
    // Look for ways to earn points
    echo "3. Ways to earn points:\n";
    $earn_patterns = [
        '/(?:earn|get|receive)\s+(?:\d+\s+)?points?\s+(?:for|when|by)\s+([^.!?<]+)/i',
        '/([^.!?<]+)\s+(?:earns?|gets?)\s+you\s+(?:\d+\s+)?points?/i'
    ];
    
    foreach ($earn_patterns as $pattern) {
        if (preg_match_all($pattern, strip_tags($html), $matches)) {
            foreach ($matches[1] as $way) {
                $way = trim($way);
                if (strlen($way) > 10 && strlen($way) < 200) {
                    echo "   - $way\n";
                }
            }
        }
    }
    
    // Look for benefits/rewards
    echo "\n4. Member benefits:\n";
    $benefit_patterns = [
        '/(?:members?|vip)\s+(?:get|receive|enjoy)\s+([^.!?<]+)/i',
        '/(?:exclusive|special)\s+([^.!?<]+)\s+for\s+(?:members?|vip)/i',
        '/redeem\s+(?:your\s+)?points?\s+for\s+([^.!?<]+)/i'
    ];
    
    foreach ($benefit_patterns as $pattern) {
        if (preg_match_all($pattern, strip_tags($html), $matches)) {
            foreach ($matches[1] as $benefit) {
                $benefit = trim($benefit);
                if (strlen($benefit) > 10 && strlen($benefit) < 200) {
                    echo "   - $benefit\n";
                }
            }
        }
    }
    
    // Save a sample of the HTML for manual inspection
    $sample_file = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/temp/1up_rewards_sample.html';
    file_put_contents($sample_file, $html);
    echo "\n5. Full HTML saved to: $sample_file\n";
    
    // Extract text content for analysis
    $text_content = strip_tags($html);
    $text_content = preg_replace('/\s+/', ' ', $text_content);
    $text_file = '/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/temp/1up_rewards_text.txt';
    file_put_contents($text_file, $text_content);
    echo "6. Text content saved to: $text_file\n";
}