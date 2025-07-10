<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Simple test to check if shortener is working
echo "<h1>Simple URL Shortener Test</h1>";
echo "<pre>";

// Test 1: Simple URL
echo "Test 1: Simple URL\n";
echo "==================\n";
$testUrl1 = "https://www.google.com";
echo "URL: $testUrl1\n";
$result1 = $app->getshortcode($testUrl1);
if ($result1 === false) {
    echo "FAILED: Shortener returned false\n";
} else {
    echo "SUCCESS: " . ($result1['shorturl'] ?? 'No shorturl in response') . "\n";
    print_r($result1);
}
echo "\n\n";

// Test 2: Complex URL (like the ones used in tour)
echo "Test 2: Complex Tour URL\n";
echo "========================\n";
$testUrl2 = "https://www.google.com/maps/dir/10106%20Atlanta%20St%2C%20Parker%2C%20CO%2080134/18901%20Mainstreet%20%23%20E%2C%20Parker%2C%20CO%2080134";
echo "URL: " . substr($testUrl2, 0, 80) . "...\n";
$result2 = $app->getshortcode($testUrl2);
if ($result2 === false) {
    echo "FAILED: Shortener returned false\n";
} else {
    echo "SUCCESS: " . ($result2['shorturl'] ?? 'No shorturl in response') . "\n";
    print_r($result2);
}
echo "\n\n";

// Test 3: With custom code
echo "Test 3: With Custom Code\n";
echo "========================\n";
$testUrl3 = "https://birthday.gold";
$customCode = "test_" . time();
echo "URL: $testUrl3\n";
echo "Custom code: $customCode\n";
$result3 = $app->getshortcode($testUrl3, $customCode);
if ($result3 === false) {
    echo "FAILED: Shortener returned false\n";
} else {
    echo "SUCCESS: " . ($result3['shorturl'] ?? 'No shorturl in response') . "\n";
    print_r($result3);
}

echo "</pre>";

// Check error logs
echo "<h2>Recent Shortener Logs from bg_errors table:</h2>";
$sql = "SELECT * FROM bg_errors WHERE type LIKE 'shortener_%' ORDER BY create_dt DESC LIMIT 10";
$stmt = $database->prepare($sql);
$stmt->execute();
$errors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($errors)) {
    echo "<p>No shortener logs found in bg_errors table.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Type</th><th>Message</th><th>URL</th><th>Time</th></tr>";
    foreach ($errors as $error) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($error['type']) . "</td>";
        echo "<td>" . htmlspecialchars($error['message']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($error['request_url'] ?? '', 0, 50)) . "...</td>";
        echo "<td>" . htmlspecialchars($error['create_dt']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>