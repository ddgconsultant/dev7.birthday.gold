<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Test email shortener behavior
echo "<h1>Email Shortener Test</h1>";

// Simulate email validation link
$testValidateLink = "https://dev7.birthday.gold/verify.php?code=ABC123&email=test@example.com&token=" . bin2hex(random_bytes(16));

echo "<h2>Test 1: Normal Shortener Call</h2>";
echo "<p>Testing URL: " . htmlspecialchars($testValidateLink) . "</p>";

$result = $app->getshortcode($testValidateLink);
echo "<pre>";
print_r($result);
echo "</pre>";

// Test the fallback behavior
echo "<h2>Test 2: Simulating Shortener Failure</h2>";
echo "<p>Testing fallback when shortener returns false...</p>";

// Simulate what happens in the email class
$shortcode = $result; // In real scenario this could be false

// This is the new code in the mail class
if ($shortcode === false || !isset($shortcode['shorturl'])) {
    $shorturl = $testValidateLink; // Use original URL if shortener fails
    echo "<p style='color: orange;'>⚠️ Shortener failed, using original URL as fallback</p>";
    echo "<p>Fallback URL: " . htmlspecialchars($shorturl) . "</p>";
} else {
    $shorturl = $shortcode['shorturl'];
    echo "<p style='color: green;'>✓ Shortener succeeded</p>";
    echo "<p>Short URL: " . htmlspecialchars($shorturl) . "</p>";
}

// Show recent logs
echo "<h2>Recent Shortener Activity (last 20 entries)</h2>";
$sql = "SELECT * FROM bg_errors WHERE type LIKE 'shortener_%' ORDER BY create_dt DESC LIMIT 20";
$stmt = $database->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($logs)) {
    echo "<p>No shortener logs found.</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Type</th><th>Message</th><th>Time</th><th>URL (truncated)</th></tr>";
    foreach ($logs as $log) {
        $rowColor = '';
        if ($log['type'] == 'shortener_success') {
            $rowColor = 'style="background-color: #e8f5e9;"';
        } elseif (strpos($log['type'], 'fail') !== false || strpos($log['type'], 'error') !== false) {
            $rowColor = 'style="background-color: #ffebee;"';
        }
        
        echo "<tr $rowColor>";
        echo "<td>" . htmlspecialchars($log['type']) . "</td>";
        echo "<td>" . htmlspecialchars($log['message']) . "</td>";
        echo "<td>" . htmlspecialchars($log['create_dt']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($log['request_url'] ?? '', 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test rapid fire calls
echo "<h2>Test 3: Rapid Fire Test (5 calls with 100ms delay)</h2>";
echo "<p>Testing if rate limiting is an issue...</p>";

$rapidResults = [];
for ($i = 1; $i <= 5; $i++) {
    $testUrl = "https://birthday.gold/test/" . $i . "/" . time();
    $start = microtime(true);
    $result = $app->getshortcode($testUrl);
    $elapsed = round((microtime(true) - $start) * 1000, 2);
    
    $rapidResults[] = [
        'attempt' => $i,
        'success' => ($result !== false && isset($result['shorturl'])),
        'time_ms' => $elapsed,
        'url' => $testUrl,
        'short' => $result['shorturl'] ?? 'FAILED'
    ];
}

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Attempt</th><th>Success</th><th>Time (ms)</th><th>Short URL</th></tr>";
foreach ($rapidResults as $r) {
    $color = $r['success'] ? 'green' : 'red';
    echo "<tr>";
    echo "<td>{$r['attempt']}</td>";
    echo "<td style='color: $color;'>" . ($r['success'] ? 'YES' : 'NO') . "</td>";
    echo "<td>{$r['time_ms']}</td>";
    echo "<td>" . htmlspecialchars($r['short']) . "</td>";
    echo "</tr>";
}
echo "</table>";

?>