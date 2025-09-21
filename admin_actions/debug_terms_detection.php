<?php
// Debug terms detection for 1UP Nutrition
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$url = 'https://1upnutrition.com';

// Fetch the webpage
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
echo "Debug Terms Detection for: $url\n";
echo "HTTP Code: $httpCode\n";
echo "===========================================\n\n";

if ($httpCode === 200 && !empty($html)) {
    $terms_variations = [
        'Terms and Conditions', 'Terms & Conditions', 'Terms of Service', 
        'Terms of Use', 'User Agreement', 'Terms', 'ToS', 'T&C',
        'Legal Terms', 'Service Terms', 'Website Terms'
    ];
    
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    libxml_clear_errors();
    
    $found_links = [];
    
    foreach ($dom->getElementsByTagName('a') as $anchor) {
        $link_text = trim($anchor->nodeValue);
        $link_href = $anchor->getAttribute('href');
        
        if (empty($link_href)) continue;
        
        // Check if link text matches any terms variation
        foreach ($terms_variations as $term) {
            if (stripos($link_text, $term) !== false || 
                stripos($link_href, str_replace(' ', '-', strtolower($term))) !== false) {
                
                $found_links[] = [
                    'text' => $link_text,
                    'href' => $link_href,
                    'matched_term' => $term
                ];
                break;
            }
        }
    }
    
    echo "Found " . count($found_links) . " potential terms links:\n\n";
    foreach ($found_links as $i => $link) {
        echo ($i + 1) . ". Link Text: \"" . $link['text'] . "\"\n";
        echo "   Link Href: " . $link['href'] . "\n";
        echo "   Matched Term: " . $link['matched_term'] . "\n\n";
    }
    
    // Also check footer area specifically
    echo "\nSearching footer area specifically...\n";
    $xpath = new DOMXPath($dom);
    $footer_links = $xpath->query("//footer//a | //*[contains(@class, 'footer')]//a | //*[contains(@id, 'footer')]//a");
    
    echo "Found " . $footer_links->length . " links in footer areas\n";
    
    // Show first 10 footer links
    $count = 0;
    foreach ($footer_links as $link) {
        if ($count++ >= 10) break;
        $text = trim($link->nodeValue);
        $href = $link->getAttribute('href');
        if (!empty($text) && !empty($href)) {
            echo "  - \"$text\" => $href\n";
        }
    }
}