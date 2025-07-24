<?php
// Simple test without database dependency
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
    // Extract text content
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Search for birthday-related content more broadly
    echo "1. Searching for birthday/celebration keywords:\n";
    $keywords = [
        'birthday' => 'Direct birthday mention',
        'birth day' => 'Split birthday mention', 
        'celebrate' => 'Celebration reference',
        'special occasion' => 'Special occasion mention',
        'anniversary' => 'Anniversary mention',
        'annual' => 'Annual/yearly reference',
        'once a year' => 'Yearly frequency',
        'every year' => 'Annual recurrence'
    ];
    
    $text_lower = strtolower($text);
    $found_any = false;
    
    foreach ($keywords as $keyword => $description) {
        if (strpos($text_lower, $keyword) !== false) {
            echo "   ✓ Found '$keyword' ($description)\n";
            $found_any = true;
            
            // Get context
            $pos = strpos($text_lower, $keyword);
            $start = max(0, $pos - 150);
            $end = min(strlen($text), $pos + strlen($keyword) + 150);
            $context = substr($text, $start, $end - $start);
            echo "     Context: \"...$context...\"\n\n";
        }
    }
    
    if (!$found_any) {
        echo "   ✗ No birthday/celebration keywords found\n\n";
    }
    
    // Look for reward tiers and benefits
    echo "2. Analyzing reward program structure:\n";
    
    // Search for VIP tiers
    if (preg_match_all('/(?:VIP|tier|level)\s*(\d+|[A-Z][a-z]+)/i', $text, $matches)) {
        echo "   Found tier/level references:\n";
        foreach (array_unique($matches[0]) as $match) {
            echo "   - $match\n";
        }
    }
    
    // Look for point values and rewards
    echo "\n3. Point values and rewards:\n";
    if (preg_match_all('/(\d+)\s*points?\s*(?:for|=|equals?)\s*\$?(\d+)/i', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            echo "   - {$match[1]} points = \${$match[2]}\n";
        }
    }
    
    // Extract earning opportunities
    echo "\n4. Ways to earn points (first 10):\n";
    if (preg_match_all('/(?:earn|get|receive)\s+(\d+)\s+points?\s+(?:for|when|by)\s+([^.!?\n]{10,100})/i', $text, $matches, PREG_SET_ORDER)) {
        foreach (array_slice($matches, 0, 10) as $match) {
            echo "   - Earn {$match[1]} points for {$match[2]}\n";
        }
    }
    
    // Save samples for analysis
    file_put_contents('/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/temp/1up_text.txt', $text);
    echo "\n5. Text content saved to: /mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/temp/1up_text.txt\n";
    echo "   Length: " . strlen($text) . " characters\n";
    
    // Show a portion of the rewards content
    echo "\n6. Sample of rewards content (first 1000 chars):\n";
    echo "----------------------------------------\n";
    $rewards_pos = stripos($text, 'rewards program');
    if ($rewards_pos !== false) {
        echo substr($text, $rewards_pos, 1000) . "...\n";
    }
}