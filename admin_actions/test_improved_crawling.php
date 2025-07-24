<?php
// Test improved crawling with priority

$test_urls = [
    'https://example.com/rewards',
    'https://example.com/loyalty-club', 
    'https://example.com/vip-members',
    'https://example.com/perks',
    'https://example.com/birthday-club',  // Should be prioritized!
    'https://example.com/benefits',
    'https://example.com/customer-program',
    'https://example.com/member-benefits',
    'https://example.com/club-signup',
    'https://example.com/birthday-rewards', // Should be prioritized!
];

echo "Improved Crawling Test\n";
echo "=====================\n\n";

// Simulate the priority sorting
$priority_urls = [];
$other_urls = [];

foreach ($test_urls as $url) {
    if (preg_match('/birthday|bday|birth/i', $url)) {
        $priority_urls[] = $url;
    } else {
        $other_urls[] = $url;
    }
}

$sorted_urls = array_merge($priority_urls, $other_urls);

echo "Original URL order:\n";
foreach ($test_urls as $i => $url) {
    echo ($i + 1) . ". $url\n";
}

echo "\n\nAfter priority sorting:\n";
foreach ($sorted_urls as $i => $url) {
    $marker = ($i < 7) ? " ✓ WILL BE CRAWLED" : " ✗ WOULD BE SKIPPED";
    $is_priority = in_array($url, $priority_urls) ? " [PRIORITY]" : "";
    echo ($i + 1) . ". $url$marker$is_priority\n";
}

echo "\n\nImprovements made:\n";
echo "1. ✓ Increased crawl limit from 3 to 7 pages\n";
echo "2. ✓ Birthday-related URLs are prioritized and crawled first\n";
echo "3. ✓ Better chance of finding birthday-specific pages\n";
echo "4. ✓ Still respects resource limits (7 pages max)\n";