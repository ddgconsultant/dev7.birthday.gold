<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Simple control panel for shortener behavior
echo "<h1>URL Shortener Control Panel</h1>";

// Check if we're updating settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_shortener') {
        $enabled = $_POST['enabled'] === '1' ? 1 : 0;
        
        // Store in database or config
        $sql = "INSERT INTO bg_settings (setting_key, setting_value, modify_dt) VALUES ('shortener_enabled', ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, modify_dt = NOW()";
        $stmt = $database->prepare($sql);
        $stmt->execute([$enabled, $enabled]);
        
        echo "<p style='color: green;'>✓ Shortener " . ($enabled ? 'ENABLED' : 'DISABLED') . "</p>";
    }
    
    if ($_POST['action'] === 'clear_logs') {
        $sql = "DELETE FROM bg_errors WHERE type LIKE 'shortener_%'";
        $stmt = $database->prepare($sql);
        $stmt->execute();
        echo "<p style='color: green;'>✓ Shortener logs cleared</p>";
    }
}

// Get current status
$sql = "SELECT setting_value FROM bg_settings WHERE setting_key = 'shortener_enabled'";
$stmt = $database->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$shortenerEnabled = $result ? ($result['setting_value'] == '1') : true; // Default to enabled

// Get stats
$sql = "SELECT 
    type,
    COUNT(*) as count,
    MAX(create_dt) as last_occurrence
FROM bg_errors 
WHERE type LIKE 'shortener_%' 
GROUP BY type 
ORDER BY last_occurrence DESC";
$stmt = $database->prepare($sql);
$stmt->execute();
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Current Status</h2>
<p>Shortener is currently: <strong style="color: <?= $shortenerEnabled ? 'green' : 'red' ?>"><?= $shortenerEnabled ? 'ENABLED' : 'DISABLED' ?></strong></p>

<h2>Controls</h2>
<form method="POST" style="margin-bottom: 20px;">
    <input type="hidden" name="action" value="toggle_shortener">
    <input type="hidden" name="enabled" value="<?= $shortenerEnabled ? '0' : '1' ?>">
    <button type="submit" style="padding: 10px 20px; font-size: 16px;">
        <?= $shortenerEnabled ? 'Disable Shortener' : 'Enable Shortener' ?>
    </button>
</form>

<form method="POST" style="margin-bottom: 20px;">
    <input type="hidden" name="action" value="clear_logs">
    <button type="submit" style="padding: 10px 20px; font-size: 16px;" onclick="return confirm('Clear all shortener logs?')">
        Clear Shortener Logs
    </button>
</form>

<h2>Shortener Statistics</h2>
<?php if (empty($stats)): ?>
    <p>No shortener activity recorded.</p>
<?php else: ?>
    <table border="1" cellpadding="5" style="border-collapse: collapse;">
        <tr>
            <th>Event Type</th>
            <th>Count</th>
            <th>Last Occurrence</th>
        </tr>
        <?php foreach ($stats as $stat): ?>
            <tr>
                <td><?= htmlspecialchars($stat['type']) ?></td>
                <td><?= $stat['count'] ?></td>
                <td><?= htmlspecialchars($stat['last_occurrence']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Recent Activity (Last 10)</h2>
<?php
$sql = "SELECT * FROM bg_errors WHERE type LIKE 'shortener_%' ORDER BY create_dt DESC LIMIT 10";
$stmt = $database->prepare($sql);
$stmt->execute();
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recent)) {
    echo "<p>No recent activity.</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Type</th><th>Message</th><th>Time</th></tr>";
    foreach ($recent as $log) {
        $rowColor = '';
        if ($log['type'] == 'shortener_success') {
            $rowColor = 'style="background-color: #e8f5e9;"';
        } elseif (strpos($log['type'], 'fail') !== false || strpos($log['type'], 'error') !== false) {
            $rowColor = 'style="background-color: #ffebee;"';
        }
        
        echo "<tr $rowColor>";
        echo "<td>" . htmlspecialchars($log['type']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($log['message'], 0, 100)) . "...</td>";
        echo "<td>" . htmlspecialchars($log['create_dt']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>

<h2>Test Links</h2>
<ul>
    <li><a href="/myaccount/test-shortener-simple.php">Simple Shortener Test</a></li>
    <li><a href="/myaccount/test-shortener-email.php">Email Shortener Test</a></li>
    <li><a href="/myaccount/test-url-shortener.php">Original URL Shortener Test</a></li>
</ul>