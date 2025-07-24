<?php
// Test what pages the crawler discovers

$test_html = '
<html>
<body>
    <nav>
        <a href="/about">About Us</a>
        <a href="/rewards">Rewards Program</a>
        <a href="/loyalty-club">Join Our Loyalty Club</a>
        <a href="/vip-members">VIP Members</a>
        <a href="/perks">Member Perks</a>
        <a href="/benefits">Customer Benefits</a>
        <a href="/contact">Contact</a>
        <a href="/blog">Blog</a>
        <a href="/store-locator">Find a Store</a>
        <a href="https://external.com/rewards">External Rewards</a>
    </nav>
    
    <div class="footer">
        <a href="/terms">Terms</a>
        <a href="/privacy">Privacy</a>
        <a href="/customer-program">Customer Program</a>
        <a href="/member-benefits">Member Benefits</a>
        <a href="/club-signup">Club Signup</a>
    </div>
</body>
</html>
';

// Extract program URLs using the same patterns as the processor
$program_patterns = [
    '/<a[^>]+href=["\']([^"\']*(?:rewards?|loyalty|club|member|perks?|benefits?|vip|program)[^"\']*)["\'][^>]*>/i',
    '/<a[^>]+(?:rewards?|loyalty|club|program)[^>]+href=["\']([^"\']+)["\'][^>]*>/i'
];

$company_url = 'https://example.com';
$parsed_base = parse_url($company_url);
$program_urls = [];

foreach ($program_patterns as $pattern) {
    if (preg_match_all($pattern, $test_html, $matches)) {
        foreach ($matches[1] as $url) {
            // Make URL absolute
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                if (substr($url, 0, 2) === '//') {
                    $url = $parsed_base['scheme'] . ':' . $url;
                } elseif (substr($url, 0, 1) === '/') {
                    $url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
                } else {
                    $url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . '/' . $url;
                }
            }
            
            // Skip external links
            $url_host = parse_url($url, PHP_URL_HOST);
            if ($url_host && $url_host === $parsed_base['host'] && !in_array($url, $program_urls)) {
                $program_urls[] = $url;
            } elseif (!$url_host && !in_array($url, $program_urls)) {
                // Handle relative URLs without host
                $program_urls[] = $url;
            }
        }
    }
}

echo "Page Discovery Test Results\n";
echo "===========================\n\n";

echo "Found " . count($program_urls) . " program-related URLs:\n\n";
foreach ($program_urls as $i => $url) {
    $marker = ($i < 3) ? " ✓ WOULD BE CRAWLED" : " ✗ WOULD BE SKIPPED (over limit)";
    echo ($i + 1) . ". $url$marker\n";
}

echo "\n\nCurrent Behavior:\n";
echo "- Crawls the main page (homepage)\n";
echo "- Finds all links matching rewards/loyalty/club/member/perks/benefits/vip/program\n";
echo "- Only crawls the FIRST 3 matching URLs (array_slice(\$program_urls, 0, 3))\n";
echo "- Skips any URLs beyond the 3rd one\n";

echo "\n\nPotential Issues:\n";
echo "1. Important pages might be missed if there are many program-related links\n";
echo "2. The order of links in HTML determines which get crawled\n";
echo "3. Footer links might never be reached if nav has 3+ matches\n";

echo "\n\nRecommendation:\n";
echo "Increase limit from 3 to 5-7 pages to ensure comprehensive coverage\n";