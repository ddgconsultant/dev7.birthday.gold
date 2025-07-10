<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Test tour SMS functionality
echo "<h1>Tour SMS Shortener Test</h1>";

// Create a sample navigation URL like the one used in tour.php
$testNavigationUrl = "https://www.google.com/maps/dir/?api=1&travelmode=driving&waypoints=10106%20Atlanta%20St%2C%20Parker%2C%20CO%2080134%7C18901%20Mainstreet%20%23%20E%2C%20Parker%2C%20CO%2080134%7C15725%20E%20Briarwood%20Cir%2C%20Aurora%2C%20CO%2080016&destination=5050%20Factory%20Shops%20Blvd%20Space%20%23605%2C%20Castle%20Rock%2C%20CO%2080108";

echo "<h2>1. Test URL Shortening</h2>";
echo "<p><strong>Original URL:</strong> " . htmlspecialchars(substr($testNavigationUrl, 0, 100)) . "...</p>";
echo "<p>Length: " . strlen($testNavigationUrl) . " characters</p>";

// Test with custom tour code
$tourDate = date('Y-m-d');
$customCode = 'tour_nav_' . $tourDate;

echo "<p><strong>Custom code:</strong> " . $customCode . "</p>";

// Try shortening
$shortCodeData = $app->getshortcode($testNavigationUrl, $customCode);

echo "<pre>";
print_r($shortCodeData);
echo "</pre>";

if ($shortCodeData && isset($shortCodeData['shorturl'])) {
    $shortUrl = $shortCodeData['shorturl'];
    echo "<p style='color: green;'><strong>✓ Short URL:</strong> <a href='" . $shortUrl . "' target='_blank'>" . $shortUrl . "</a></p>";
    echo "<p>Length: " . strlen($shortUrl) . " characters (saved " . (strlen($testNavigationUrl) - strlen($shortUrl)) . " characters)</p>";
    
    // Show what the SMS would look like
    echo "<h2>2. SMS Message Preview</h2>";
    $message = "Your Birthday Tour navigation link for " . date('M j, Y', strtotime($tourDate)) . ":\n" . $shortUrl . "\n\nTap to open in Google Maps";
    
    echo "<div style='border: 2px solid #ccc; padding: 15px; background: #f5f5f5; font-family: monospace; white-space: pre-wrap; max-width: 400px;'>";
    echo htmlspecialchars($message);
    echo "</div>";
    
    echo "<p><strong>Message length:</strong> " . strlen($message) . " characters</p>";
    
    // Test multiple URLs to ensure each gets a unique short code
    echo "<h2>3. Multiple URL Test</h2>";
    echo "<p>Testing that different URLs get different short codes...</p>";
    
    $testUrls = [
        "https://www.google.com/maps/dir/Location1/Location2",
        "https://www.google.com/maps/dir/Location3/Location4",
        "https://www.google.com/maps/dir/Location5/Location6"
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Original URL</th><th>Short URL</th><th>Code</th></tr>";
    
    foreach ($testUrls as $idx => $url) {
        $code = 'tour_test_' . ($idx + 1);
        $result = $app->getshortcode($url, $code);
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars(substr($url, 0, 40)) . "...</td>";
        echo "<td>" . ($result['shorturl'] ?? 'FAILED') . "</td>";
        echo "<td>" . ($result['shortcode'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} else {
    echo "<p style='color: red;'><strong>✗ Shortening failed!</strong></p>";
}

// Show recent shortener logs
echo "<h2>4. Recent Tour Shortener Activity</h2>";
$sql = "SELECT * FROM bg_errors 
        WHERE type LIKE 'shortener_%' 
        AND (message LIKE '%tour%' OR request_url LIKE '%tour%')
        ORDER BY create_dt DESC 
        LIMIT 10";
$stmt = $database->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($logs)) {
    echo "<p>No tour-related shortener logs found.</p>";
} else {
    echo "<table border='1' cellpadding='5' style='font-size: 12px;'>";
    echo "<tr><th>Type</th><th>Message</th><th>Time</th></tr>";
    foreach ($logs as $log) {
        $rowColor = ($log['type'] == 'shortener_success') ? 'style="background-color: #e8f5e9;"' : 'style="background-color: #ffebee;"';
        echo "<tr $rowColor>";
        echo "<td>" . htmlspecialchars($log['type']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($log['message'], 0, 80)) . "...</td>";
        echo "<td>" . htmlspecialchars($log['create_dt']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>5. Test Links</h2>";
echo "<ul>";
echo "<li><a href='/myaccount/tour.php?debug=1'>Tour Page (Debug Mode)</a> - Use debug mode to see shortener in action</li>";
echo "<li><a href='/myaccount/shortener-control.php'>Shortener Control Panel</a></li>";
echo "</ul>";
?>