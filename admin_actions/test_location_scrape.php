<?php
// Test location scraping on a known multi-location business
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$test_url = $_GET['url'] ?? 'https://www.starbucks.com';

// Fetch the webpage
$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: text/plain');
echo "Testing location extraction on: $test_url\n";
echo "HTTP Code: $httpCode\n";
echo "========================================\n\n";

if ($httpCode === 200 && !empty($html)) {
    // Look for location/store finder links
    echo "1. Searching for location/store finder links:\n";
    $location_patterns = [
        '/<a[^>]+href=["\']([^"\']*(?:locations?|stores?|find-?us|store-?locator|find-?a-?store)[^"\']*)["\'][^>]*>/i',
        '/<a[^>]+(?:locations?|stores?|find)[^>]+href=["\']([^"\']+)["\'][^>]*>/i'
    ];
    
    foreach ($location_patterns as $i => $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            echo "   Pattern $i found " . count($matches[1]) . " matches:\n";
            foreach (array_unique($matches[1]) as $url) {
                echo "   - $url\n";
            }
        }
    }
    
    // Look for JSON-LD structured data
    echo "\n2. Searching for JSON-LD structured data:\n";
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonld_matches)) {
        echo "   Found " . count($jsonld_matches[1]) . " JSON-LD blocks\n";
        foreach ($jsonld_matches[1] as $i => $jsonld) {
            try {
                $data = json_decode($jsonld, true);
                if ($data && isset($data['@type'])) {
                    echo "   Block $i: @type = " . $data['@type'] . "\n";
                    if (isset($data['address'])) {
                        echo "   - Has address field\n";
                    }
                    if (isset($data['location'])) {
                        echo "   - Has location field\n";
                    }
                }
            } catch (Exception $e) {
                echo "   Block $i: Invalid JSON\n";
            }
        }
    }
    
    // Look for addresses
    echo "\n3. Searching for US addresses:\n";
    $address_pattern = '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?),?\s*([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i';
    
    if (preg_match_all($address_pattern, $html, $matches, PREG_SET_ORDER)) {
        echo "   Found " . count($matches) . " potential addresses:\n";
        foreach (array_slice($matches, 0, 5) as $match) {
            echo "   - " . $match[0] . "\n";
        }
        if (count($matches) > 5) {
            echo "   ... and " . (count($matches) - 5) . " more\n";
        }
    } else {
        echo "   No US addresses found\n";
    }
}