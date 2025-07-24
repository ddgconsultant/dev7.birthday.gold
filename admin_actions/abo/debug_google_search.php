<?php
// Debug Google Play search
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$company_name = $_GET['q'] ?? '1UP Nutrition';
$search_query = urlencode($company_name);
$search_url = "https://play.google.com/store/search?q={$search_query}&c=apps";

echo "<h3>Searching for: $company_name</h3>";
echo "<p>URL: <a href='$search_url' target='_blank'>$search_url</a></p>";

$ch = curl_init($search_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<p>Final URL: $final_url</p>";
echo "<p>HTML Length: " . strlen($html) . " bytes</p>";

// Find all app IDs
preg_match_all('/\/store\/apps\/details\?id=([a-zA-Z0-9._]+)/', $html, $matches);
$app_ids = array_unique($matches[1]);

echo "<h4>Found " . count($app_ids) . " app IDs:</h4>";
echo "<ol>";
foreach ($app_ids as $app_id) {
    $app_url = "https://play.google.com/store/apps/details?id=$app_id";
    echo "<li><a href='$app_url' target='_blank'>$app_id</a>";
    
    // Check if it contains "1up" or "oneup"
    if (stripos($app_id, '1up') !== false || stripos($app_id, 'oneup') !== false) {
        echo " <strong style='color: green;'>← MATCH!</strong>";
    }
    echo "</li>";
}
echo "</ol>";

// Show relevant HTML snippet
if (strpos($html, 'oneupnutrition') !== false || strpos($html, '1upnutrition') !== false) {
    echo "<h4>Found OneUp/1UP references in HTML!</h4>";
    
    // Extract snippet around the match
    $pos = stripos($html, 'oneupnutrition');
    if ($pos === false) $pos = stripos($html, '1upnutrition');
    
    $start = max(0, $pos - 200);
    $snippet = substr($html, $start, 400);
    echo "<pre>" . htmlspecialchars($snippet) . "</pre>";
}

// Try a more specific search
echo "<hr>";
echo "<h3>Alternative searches:</h3>";
$alternatives = [
    '1up fitness',
    'oneup nutrition',
    'oneup fitness',
    '1up nutrition fitness'
];

foreach ($alternatives as $alt) {
    $alt_url = "https://play.google.com/store/search?q=" . urlencode($alt) . "&c=apps";
    echo "<p><a href='$alt_url' target='_blank'>Try: $alt</a></p>";
}