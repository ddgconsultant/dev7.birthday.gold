<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Test URL shortening for navigation
$testUrl = "https://www.google.com/maps/dir/10106%20Atlanta%20St%2C%20Parker%2C%20CO%2080134/18901%20Mainstreet%20%23%20E%2C%20Parker%2C%20CO%2080134/15725%20E%20Briarwood%20Cir%2C%20Aurora%2C%20CO%2080016/5050%20Factory%20Shops%20Blvd%20Space%20%23605%2C%20Castle%20Rock%2C%20CO%2080108";

echo "<h1>URL Shortener Test</h1>";
echo "<h2>Testing bd.gold URL shortening service</h2>";

// Test 1: Basic shortening
echo "<h3>Test 1: Basic URL Shortening</h3>";
echo "<p><strong>Original URL:</strong> " . htmlspecialchars(substr($testUrl, 0, 100)) . "...</p>";
echo "<p>Length: " . strlen($testUrl) . " characters</p>";

$result1 = $app->getshortcode($testUrl);
echo "<pre>";
print_r($result1);
echo "</pre>";

if (isset($result1['shorturl'])) {
    echo "<p><strong>Shortened URL:</strong> <a href='" . $result1['shorturl'] . "' target='_blank'>" . $result1['shorturl'] . "</a></p>";
    echo "<p>Length: " . strlen($result1['shorturl']) . " characters</p>";
}

// Test 2: Custom code
echo "<hr>";
echo "<h3>Test 2: Custom Code</h3>";
$customCode = 'tour_' . date('Ymd_His');
echo "<p>Using custom code: " . $customCode . "</p>";

$result2 = $app->getshortcode($testUrl, $customCode);
echo "<pre>";
print_r($result2);
echo "</pre>";

if (isset($result2['shorturl'])) {
    echo "<p><strong>Shortened URL:</strong> <a href='" . $result2['shorturl'] . "' target='_blank'>" . $result2['shorturl'] . "</a></p>";
}

// Test 3: Navigation URL for different dates
echo "<hr>";
echo "<h3>Test 3: Tour Navigation URLs</h3>";

$tourDate = date('Y-m-d');
$shortCode = 'tour_nav_' . $tourDate;

echo "<p>Tour date: " . $tourDate . "</p>";
echo "<p>Short code: " . $shortCode . "</p>";

$result3 = $app->getshortcode($testUrl, $shortCode);

if (isset($result3['shorturl'])) {
    echo "<p><strong>Tour navigation link:</strong> <a href='" . $result3['shorturl'] . "' target='_blank'>" . $result3['shorturl'] . "</a></p>";
    
    // Simulate SMS message
    echo "<hr>";
    echo "<h3>Sample SMS Message:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 15px; background: #f5f5f5; font-family: monospace;'>";
    echo "Your Birthday Tour navigation link for " . date('M j, Y', strtotime($tourDate)) . ":<br>";
    echo $result3['shorturl'] . "<br><br>";
    echo "Tap to open in Maps";
    echo "</div>";
}

// Test API response time
echo "<hr>";
echo "<h3>Performance Test</h3>";
$start = microtime(true);
$perfTest = $app->getshortcode("https://www.google.com/maps/dir/test1/test2/test3");
$end = microtime(true);
$time = round(($end - $start) * 1000, 2);
echo "<p>API response time: " . $time . " ms</p>";

?>