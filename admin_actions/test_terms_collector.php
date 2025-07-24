<?php
// Test the terms and privacy collectors

// Test HTML with terms and privacy links
$test_html = '
<html>
<body>
    <div class="header">
        <a href="/about">About Us</a>
        <a href="/contact">Contact</a>
    </div>
    
    <div class="content">
        <h1>Welcome to our site</h1>
    </div>
    
    <div class="footer">
        <a href="/terms-of-service">Terms of Service</a>
        <a href="/privacy-policy">Privacy Policy</a>
        <a href="/cookie-policy">Cookie Policy</a>
        <a href="/legal/terms">Terms and Conditions</a>
        <a href="/legal/privacy">Privacy Statement</a>
    </div>
</body>
</html>
';

// Terms variations to test
$terms_variations = [
    'Terms and Conditions', 'Terms & Conditions', 'Terms of Service', 
    'Terms of Use', 'User Agreement', 'Terms', 'ToS', 'T&C',
    'Legal Terms', 'Service Terms', 'Website Terms'
];

// Privacy variations to test
$privacy_variations = [
    'Privacy Policy', 'privacy-policy', 'Privacy Statement', 
    'Privacy Notice', 'Privacy', 'Data Protection', 'Privacy Center',
    'Your Privacy', 'Privacy & Security', 'Privacy and Security'
];

echo "Policy Detection Test\n";
echo "====================\n\n";

// Parse the HTML
libxml_use_internal_errors(true);
$dom = new DOMDocument();
@$dom->loadHTML($test_html);
libxml_clear_errors();

$found_terms = [];
$found_privacy = [];

foreach ($dom->getElementsByTagName('a') as $anchor) {
    $link_text = trim($anchor->nodeValue);
    $link_href = $anchor->getAttribute('href');
    
    if (empty($link_href)) continue;
    
    // Check for terms
    foreach ($terms_variations as $term) {
        if (stripos($link_text, $term) !== false || 
            stripos($link_href, str_replace(' ', '-', strtolower($term))) !== false) {
            $found_terms[] = [
                'text' => $link_text,
                'href' => $link_href,
                'matched' => $term
            ];
            break;
        }
    }
    
    // Check for privacy
    foreach ($privacy_variations as $privacy) {
        if (stripos($link_text, $privacy) !== false || 
            stripos($link_href, str_replace(' ', '-', strtolower($privacy))) !== false) {
            $found_privacy[] = [
                'text' => $link_text,
                'href' => $link_href,
                'matched' => $privacy
            ];
            break;
        }
    }
}

echo "Terms Links Found:\n";
foreach ($found_terms as $term) {
    echo "  - Text: '{$term['text']}'\n";
    echo "    Href: '{$term['href']}'\n";
    echo "    Matched: '{$term['matched']}'\n\n";
}

echo "\nPrivacy Links Found:\n";
foreach ($found_privacy as $privacy) {
    echo "  - Text: '{$privacy['text']}'\n";
    echo "    Href: '{$privacy['href']}'\n";
    echo "    Matched: '{$privacy['matched']}'\n\n";
}

// Test content hashing
$sample_content = "This is a sample privacy policy. We collect data to improve our services. Last updated: 2025-01-01";
$hash1 = hash('sha256', $sample_content);
$hash2 = hash('sha256', $sample_content . " ");  // Added space
$hash3 = hash('sha256', $sample_content);

echo "\nContent Hashing Test:\n";
echo "Original hash: $hash1\n";
echo "Modified hash: $hash2\n";
echo "Same content: $hash3\n";
echo "Hashes match: " . ($hash1 === $hash3 ? "YES" : "NO") . "\n";
echo "Modified detected: " . ($hash1 !== $hash2 ? "YES" : "NO") . "\n";

// Database schema info
echo "\n\nDatabase Tables Used:\n";
echo "1. bg_company_attributes:\n";
echo "   - Stores policy URLs (type='url', grouping='policies')\n";
echo "   - name='terms' or name='privacy'\n\n";

echo "2. bg_company_policies (needs to be created):\n";
echo "   - Tracks policy versions and changes\n";
echo "   - Fields: policy_id, company_id, policy_type, policy_name, content_hash, version, status, last_verified\n\n";

echo "3. bg_company_policy_content (needs to be created):\n";
echo "   - Stores actual policy text content\n";
echo "   - Fields: company_id, policy_type, content, content_hash, create_dt\n";